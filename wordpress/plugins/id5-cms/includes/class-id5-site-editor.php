<?php
/**
 * The site editor: the public website on the right, an editing panel on the
 * left — the "click the site to edit it" experience of the WordPress Customizer.
 *
 * The public website is a separate application, so WordPress's own Customizer
 * cannot preview it. This screen does the same job by other means:
 *
 *  - The website is shown in a frame, opened with a short-lived signed token
 *    that switches it into "edit mode". In that mode the website draws a pencil
 *    on everything editable and reports what is on the page.
 *  - Clicking a pencil (or a row in the panel) slides the ordinary WordPress
 *    edit screen for that item over the preview. Saving there publishes as
 *    usual, and the preview reloads to show the result.
 *  - Site-wide text (site name, hero, footer) is edited directly in the panel.
 *
 * Nothing here is a second way of storing content. Every change goes through
 * the same post screens and options as the rest of the plugin, so the
 * validation, the roles and the sync all apply unchanged.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

class ID5_Site_Editor {

	const PAGE       = 'id5-site-editor';
	const NONCE      = 'id5_site_editor';
	const PARAM      = 'id5_edit';
	const TOKEN_LIFE = 43200; // 12 hours: one working day without re-opening.

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 90 );
		add_action( 'admin_head', array( __CLASS__, 'framed_styles' ) );
		add_action( 'wp_ajax_id5_save_site_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'wp_ajax_id5_item_info', array( __CLASS__, 'item_info' ) );
		add_action( 'wp_ajax_id5_item_delete', array( __CLASS__, 'item_delete' ) );
		add_filter( 'map_meta_cap', array( __CLASS__, 'protect_sections_in_use' ), 10, 4 );
		add_action( 'admin_notices', array( __CLASS__, 'explain_section_deletion' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
	}

	// ─── Tokens and links ───────────────────────────────────────────────────

	/**
	 * A token that switches the public website into edit mode for this user.
	 *
	 * Format: base64url( JSON { n: name, e: expiry } ) . "." . HMAC-SHA256.
	 * Signed with the secret both systems already share. The signed text is
	 * prefixed so this signature can never be mistaken for a webhook's.
	 *
	 * The token grants nothing but the drawing of pencils on the website; every
	 * edit still requires being logged in here.
	 */
	public static function token() {
		$secret = get_option( 'id5_webhook_secret' );

		if ( ! $secret || ! is_user_logged_in() ) {
			return '';
		}

		$user    = wp_get_current_user();
		$claims  = array( 'n' => $user->display_name, 'e' => time() + self::TOKEN_LIFE );
		$payload = rtrim( strtr( base64_encode( wp_json_encode( $claims ) ), '+/', '-_' ), '=' );

		return $payload . '.' . hash_hmac( 'sha256', 'id5-edit-mode|' . $payload, $secret );
	}

	/**
	 * A page of the public website, opened in edit mode.
	 *
	 * @param string $path Site-relative path, e.g. "/news".
	 */
	public static function public_url( $path = '/' ) {
		$site = untrailingslashit( (string) get_option( 'id5_public_site_url' ) );

		if ( ! $site ) {
			return '';
		}

		return add_query_arg( self::PARAM, rawurlencode( self::token() ), $site . self::clean_path( $path ) );
	}

	/**
	 * Accept only a path on the public website — never a full URL — so the
	 * `path` parameter cannot be used to frame or redirect to another site.
	 */
	private static function clean_path( $path ) {
		$path = (string) $path;

		if ( '' === $path || '/' !== $path[0] || 0 === strpos( $path, '//' ) || false !== strpos( $path, '\\' ) ) {
			return '/';
		}

		return $path;
	}

	public static function url( $path = '' ) {
		$url = admin_url( 'admin.php?page=' . self::PAGE );

		return $path ? add_query_arg( 'path', rawurlencode( $path ), $url ) : $url;
	}

	// ─── Menu and admin bar ─────────────────────────────────────────────────

	public static function add_page() {
		add_menu_page(
			'แก้ไขเว็บไซต์',
			'แก้ไขเว็บไซต์',
			'edit_posts',
			self::PAGE,
			array( __CLASS__, 'render' ),
			'dashicons-admin-customizer',
			3
		);
	}

	public static function admin_bar( $admin_bar ) {
		if ( ! current_user_can( 'edit_posts' ) || ! get_option( 'id5_public_site_url' ) ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'    => 'id5-site-editor',
				'title' => '<span class="ab-icon dashicons dashicons-admin-customizer" style="top:2px"></span>แก้ไขเว็บไซต์',
				'href'  => self::url(),
			)
		);
	}

	// ─── Screens shown inside the editor's overlay ──────────────────────────

	/**
	 * Whether this admin page is being loaded inside a frame — which, in this
	 * install, means inside the site editor's overlay.
	 */
	private static function is_framed() {
		return isset( $_SERVER['HTTP_SEC_FETCH_DEST'] ) && 'iframe' === $_SERVER['HTTP_SEC_FETCH_DEST'];
	}

	public static function body_class( $classes ) {
		return self::is_framed() ? $classes . ' id5-framed' : $classes;
	}

	/**
	 * Inside the overlay an edit screen should be just the form: the sidebar
	 * and toolbar are already provided by the editor around it.
	 */
	public static function framed_styles() {
		if ( ! self::is_framed() ) {
			return;
		}

		echo '<style id="id5-framed-css">'
			. '#adminmenumain,#wpadminbar,#wpfooter,#screen-meta-links{display:none!important}'
			. '#wpcontent,#wpfooter{margin-left:0!important}'
			. 'html.wp-toolbar{padding-top:0!important}'
			. '#wpbody-content{padding-bottom:24px}'
			. '</style>';

		// Tell the site editor around this page when a save starts and when the
		// next page has arrived. Printed inline in <head> so it is listening
		// from the page's first moment: an editor on a slow connection can
		// press Update before the page has finished loading, and that save
		// must still be noticed so the preview is refreshed afterwards.
		echo '<script id="id5-framed-js">(function(){'
			. 'if(window.parent===window){return;}'
			. 'var tell=function(t){try{window.parent.postMessage({source:"id5-cms-form",type:t},window.location.origin);}catch(e){}};'
			. 'document.addEventListener("submit",function(){tell("busy");},true);'
			. 'window.addEventListener("beforeunload",function(){tell("busy");});'
			. 'document.addEventListener("DOMContentLoaded",function(){tell("ready");});'
			. '})();</script>';
	}

	// ─── The editor screen ──────────────────────────────────────────────────

	/**
	 * The site-wide settings shown in the panel, grouped as in the Customizer.
	 */
	private static function setting_groups() {
		return array(
			'identity' => array(
				'title'  => 'ชื่อเว็บไซต์และสายด่วน',
				'help'   => 'เว้นว่างไว้ได้ เว็บไซต์จะใช้ค่าเริ่มต้นที่แสดงเป็นตัวอักษรสีจางในช่อง',
				'fields' => array(
					'id5_site_name'       => 'ชื่อเว็บไซต์',
					'id5_emergency_phone' => 'เบอร์สายด่วนฉุกเฉิน',
				),
			),
			'hero'     => array(
				'title'  => 'แบนเนอร์หน้าแรก',
				'help'   => 'เว้นช่องใดว่างไว้ เว็บไซต์จะใช้ข้อความเริ่มต้นของระบบแทน',
				'fields' => array(
					'id5_hero_title_th'    => 'หัวข้อใหญ่ (ภาษาไทย)',
					'id5_hero_title_en'    => 'หัวข้อใหญ่ (English)',
					'id5_hero_subtitle_th' => 'คำอธิบายใต้หัวข้อ (ภาษาไทย)',
					'id5_hero_subtitle_en' => 'คำอธิบายใต้หัวข้อ (English)',
					'id5_hero_cta_th'      => 'ข้อความบนปุ่ม (ภาษาไทย)',
					'id5_hero_cta_en'      => 'ข้อความบนปุ่ม (English)',
					'id5_hero_image'       => 'รูปพื้นหลังแบนเนอร์',
				),
			),
			'footer'   => array(
				'title'  => 'ส่วนท้ายเว็บไซต์',
				'fields' => array(
					'id5_footer_copyright' => 'ข้อความลิขสิทธิ์',
					'id5_footer_address'   => 'ที่อยู่หน่วยงาน',
				),
			),
		);
	}

	/**
	 * What the website shows when a setting is left empty, offered as the
	 * field's placeholder so an editor can see what is currently displayed.
	 */
	private static function placeholders() {
		return array(
			'id5_site_name'       => 'ID5 @ Thailand 5.0',
			'id5_emergency_phone' => '1178',
		);
	}

	public static function enqueue( $hook ) {
		if ( 'toplevel_page_' . self::PAGE !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'id5-site-editor', ID5_CMS_URL . 'assets/site-editor.css', array( 'dashicons' ), ID5_CMS_VERSION );
		wp_enqueue_script( 'id5-site-editor', ID5_CMS_URL . 'assets/site-editor.js', array(), ID5_CMS_VERSION, true );
		wp_enqueue_script( 'id5-site-editor-texts', ID5_CMS_URL . 'assets/site-editor-texts.js', array( 'id5-site-editor' ), ID5_CMS_VERSION, true );

		$site  = untrailingslashit( (string) get_option( 'id5_public_site_url' ) );
		$parts = wp_parse_url( $site );
		$types = array();

		foreach ( ID5_Schema::types() as $post_type => $type ) {
			$object  = get_post_type_object( $post_type );
			$types[] = array(
				'type'      => $post_type,
				'label'     => $type['label_th'],
				'listUrl'   => admin_url( 'post' === $post_type ? 'edit.php' : 'edit.php?post_type=' . $post_type ),
				'newUrl'    => admin_url( 'post' === $post_type ? 'post-new.php' : 'post-new.php?post_type=' . $post_type ),
				'canCreate' => $object && current_user_can( $object->cap->create_posts ),
			);
		}

		wp_localize_script(
			'id5-site-editor',
			'ID5SiteEditor',
			array(
				'site'        => $site,
				'siteOrigin'  => $parts ? $parts['scheme'] . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' ) : '',
				'token'       => self::token(),
				'param'       => self::PARAM,
				'path'        => self::clean_path( isset( $_GET['path'] ) ? wp_unslash( $_GET['path'] ) : '/' ), // phpcs:ignore WordPress.Security.NonceVerification
				'open'        => isset( $_GET['open'] ) ? sanitize_text_field( wp_unslash( $_GET['open'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
				'adminUrl'    => admin_url(),
				'termUrl'     => admin_url( 'admin-post.php?action=id5_open_term&term=' ),
				'termsUrl'    => admin_url( 'edit-tags.php?taxonomy=' ),
				'selfUrl'     => self::url(),
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( self::NONCE ),
				'types'       => $types,
				'canSettings' => current_user_can( 'edit_theme_options' ),
			)
		);
	}

	public static function render() {
		if ( ! get_option( 'id5_public_site_url' ) || ! get_option( 'id5_webhook_secret' ) ) {
			echo '<div class="wrap"><h1>แก้ไขเว็บไซต์</h1><div class="notice notice-warning"><p>ยังไม่ได้ตั้งค่าการเชื่อมต่อกับเว็บไซต์ กรุณาแจ้งผู้ดูแลระบบให้ตั้งค่าที่ <strong>ตั้งค่า → การเชื่อมต่อเว็บไซต์ ID5</strong></p></div></div>';
			return;
		}

		$settings   = ID5_Customizer::settings();
		$can_change = current_user_can( 'edit_theme_options' );
		?>
		<div id="id5-se" class="id5-se">
			<aside class="id5-se__panel" aria-label="แผงแก้ไขเว็บไซต์">
				<header class="id5-se__head">
					<a class="id5-se__close" id="id5-se-close" href="#" title="ปิดและกลับไปที่เว็บไซต์"><span class="dashicons dashicons-no-alt"></span></a>
					<span class="id5-se__state" id="id5-se-state">เผยแพร่แล้ว</span>
				</header>

				<div class="id5-se__intro">
					<span class="id5-se__intro-label">กำลังแก้ไขเว็บไซต์</span>
					<strong class="id5-se__intro-site"><?php echo esc_html( get_option( 'id5_site_name' ) ?: 'ID5 @ Thailand 5.0' ); ?></strong>
					<span class="id5-se__intro-page" id="id5-se-page">กำลังโหลดหน้าเว็บ…</span>
				</div>

				<div class="id5-se__scroll">
					<section class="id5-se__group">
						<h2 class="id5-se__group-title">ส่วนต่าง ๆ ในหน้านี้</h2>
						<p class="id5-se__hint">คลิกรายการด้านล่าง หรือชี้เมาส์ที่หน้าเว็บด้านขวาแล้วกดปุ่มดินสอ</p>
						<div id="id5-se-items" class="id5-se__items"><p class="id5-se__empty">กำลังโหลด…</p></div>
					</section>

					<?php if ( $can_change ) : ?>
					<section class="id5-se__group">
						<h2 class="id5-se__group-title" id="id5-se-texts-title">ข้อความในหน้านี้</h2>
						<p class="id5-se__hint">เมนู หัวข้อ ปุ่ม และข้อความอื่น ๆ ของหน้าเว็บ ชี้เมาส์ที่ข้อความบนหน้าเว็บแล้วกดดินสอ เลือกจากรายการ หรือค้นหาจากทั้งเว็บไซต์</p>
						<input type="search" id="id5-se-text-search" class="id5-se__search" placeholder="ค้นหาข้อความทั้งเว็บไซต์…" aria-label="ค้นหาข้อความทั้งเว็บไซต์">
						<div id="id5-se-texts" class="id5-se__texts"><p class="id5-se__empty">กำลังโหลด…</p></div>
					</section>

					<section class="id5-se__group">
						<h2 class="id5-se__group-title">ข้อมูลเว็บไซต์</h2>
						<?php foreach ( self::setting_groups() as $group_id => $group ) : ?>
						<details class="id5-se__section" data-setting-group="<?php echo esc_attr( $group_id ); ?>">
							<summary><?php echo esc_html( $group['title'] ); ?><span class="dashicons dashicons-arrow-right-alt2"></span></summary>
							<form class="id5-se__form" data-settings-form>
								<?php if ( ! empty( $group['help'] ) ) : ?>
									<p class="id5-se__hint"><?php echo esc_html( $group['help'] ); ?></p>
								<?php endif; ?>

								<?php
								foreach ( $group['fields'] as $slug => $label ) :
									$type  = $settings[ $slug ][1];
									$value = get_option( $slug, '' );
									?>
									<label class="id5-se__field">
										<span><?php echo esc_html( $label ); ?></span>
										<?php if ( 'image' === $type ) : ?>
											<?php $image = $value ? wp_get_attachment_image_url( (int) $value, 'medium' ) : ''; ?>
											<span class="id5-se__image" data-image-field>
												<input type="hidden" name="<?php echo esc_attr( $slug ); ?>" value="<?php echo esc_attr( (int) $value ); ?>">
												<img src="<?php echo esc_url( $image ); ?>" alt="" <?php echo $image ? '' : 'hidden'; ?>>
												<span class="id5-se__image-actions">
													<button type="button" class="button" data-image-choose>เลือกรูปภาพ</button>
													<button type="button" class="button-link id5-se__danger" data-image-clear <?php echo $image ? '' : 'hidden'; ?>>ลบรูป</button>
												</span>
											</span>
										<?php elseif ( 'textarea' === $type ) : ?>
											<textarea name="<?php echo esc_attr( $slug ); ?>" rows="3"><?php echo esc_textarea( $value ); ?></textarea>
										<?php else : ?>
											<?php $placeholders = self::placeholders(); ?>
											<input type="text" name="<?php echo esc_attr( $slug ); ?>" value="<?php echo esc_attr( $value ); ?>"
												placeholder="<?php echo esc_attr( isset( $placeholders[ $slug ] ) ? $placeholders[ $slug ] : '' ); ?>">
										<?php endif; ?>
									</label>
								<?php endforeach; ?>

								<p class="id5-se__actions">
									<button type="submit" class="button button-primary">เผยแพร่</button>
									<span class="id5-se__saved" hidden>บันทึกแล้ว ✓</span>
								</p>
							</form>
						</details>
						<?php endforeach; ?>
					</section>
					<?php endif; ?>

					<section class="id5-se__group">
						<h2 class="id5-se__group-title">เนื้อหาทั้งหมด</h2>
						<ul class="id5-se__links" id="id5-se-types"></ul>
						<ul class="id5-se__links">
							<?php if ( $can_change ) : ?>
								<li><a href="#" data-open-url="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>" data-open-title="เมนูนำทาง"><span class="dashicons dashicons-menu"></span>เมนูนำทาง</a></li>
							<?php endif; ?>
							<li><a href="#" data-open-url="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>" data-open-title="คลังรูปภาพและไฟล์"><span class="dashicons dashicons-admin-media"></span>คลังรูปภาพและไฟล์</a></li>
						</ul>
					</section>
				</div>

				<footer class="id5-se__foot">
					<a class="id5-se__dash" href="<?php echo esc_url( admin_url() ); ?>" title="ไปที่แผงควบคุม WordPress"><span class="dashicons dashicons-wordpress"></span>แผงควบคุม</a>
					<span class="id5-se__devices" role="group" aria-label="ขนาดหน้าจอตัวอย่าง">
						<button type="button" class="is-active" data-device="desktop" title="คอมพิวเตอร์"><span class="dashicons dashicons-desktop"></span></button>
						<button type="button" data-device="tablet" title="แท็บเล็ต"><span class="dashicons dashicons-tablet"></span></button>
						<button type="button" data-device="mobile" title="โทรศัพท์มือถือ"><span class="dashicons dashicons-smartphone"></span></button>
					</span>
				</footer>
			</aside>

			<main class="id5-se__stage" data-device="desktop">
				<iframe id="id5-se-preview" class="id5-se__preview" title="ตัวอย่างเว็บไซต์"></iframe>

				<div class="id5-se__overlay" id="id5-se-overlay" hidden>
					<div class="id5-se__overlay-bar">
						<button type="button" class="button" id="id5-se-back"><span class="dashicons dashicons-arrow-left-alt2"></span>กลับไปดูเว็บไซต์</button>
						<strong id="id5-se-overlay-title"></strong>
						<span class="id5-se__overlay-space"></span>
						<span class="id5-se__delete-note" id="id5-se-delete-note" hidden></span>
						<button type="button" class="button id5-se__delete" id="id5-se-delete" hidden><span class="dashicons dashicons-trash"></span><span id="id5-se-delete-label">ลบรายการนี้</span></button>
					</div>
					<iframe id="id5-se-form" class="id5-se__form-frame" title="แบบฟอร์มแก้ไข"></iframe>
				</div>
			</main>
		</div>
		<?php
	}

	// ─── Deleting the item that is open ─────────────────────────────────────

	/**
	 * Sections that items must belong to (an ITA topic needs its section, a
	 * person needs their unit): taxonomy => label of what lives in it.
	 */
	private static function required_taxonomies() {
		$out = array();

		foreach ( ID5_Schema::types() as $type ) {
			foreach ( isset( $type['required_taxonomies'] ) ? $type['required_taxonomies'] : array() as $taxonomy ) {
				$out[ $taxonomy ] = $type['label_th'];
			}
		}

		return $out;
	}

	/**
	 * Why a term may not be deleted right now, or '' when it may.
	 *
	 * A section that still has published items in it stays: deleting it would
	 * take every one of them off the website in one click, and they would be
	 * left in WordPress belonging nowhere.
	 */
	private static function term_in_use( $term ) {
		$required = self::required_taxonomies();

		if ( ! $term || is_wp_error( $term ) || ! isset( $required[ $term->taxonomy ] ) || (int) $term->count < 1 ) {
			return '';
		}

		return sprintf( 'ยังมี%s %d รายการอยู่ในหมวดนี้ กรุณาลบหรือย้ายรายการเหล่านั้นไปหมวดอื่นก่อน', $required[ $term->taxonomy ], (int) $term->count );
	}

	/**
	 * Applies the rule above everywhere WordPress itself offers "Delete" for a
	 * term — the list screen, the edit screen, bulk actions.
	 */
	public static function protect_sections_in_use( $caps, $cap, $user_id, $args ) {
		if ( 'delete_term' !== $cap || empty( $args[0] ) ) {
			return $caps;
		}

		return '' !== self::term_in_use( get_term( (int) $args[0] ) ) ? array( 'do_not_allow' ) : $caps;
	}

	public static function explain_section_deletion() {
		$screen   = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$required = self::required_taxonomies();

		if ( ! $screen || 'edit-tags' !== $screen->base || ! isset( $required[ $screen->taxonomy ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-info"><p><strong>การลบหมวด:</strong> ลบได้เฉพาะหมวดที่ไม่มี%1$sเผยแพร่อยู่ หากไม่เห็นปุ่ม “ลบ” ใต้ชื่อหมวด ให้ลบหรือย้าย%1$sในหมวดนั้นก่อน เมื่อลบแล้วหมวดจะหายจากเว็บไซต์ทันที</p></div>',
			esc_html( $required[ $screen->taxonomy ] )
		);
	}

	/**
	 * Resolve "post:12" / "term:34" to what it is and whether this user may
	 * delete it.
	 */
	private static function item( $target ) {
		if ( ! preg_match( '/^(post|term):(\d+)$/', (string) $target, $m ) ) {
			return null;
		}

		$id = (int) $m[2];

		if ( 'post' === $m[1] ) {
			$post  = get_post( $id );
			$types = ID5_Schema::types();

			if ( ! $post || ! isset( $types[ $post->post_type ] ) || 'trash' === $post->post_status ) {
				return null;
			}

			return array(
				'kind'      => 'post',
				'id'        => $id,
				'name'      => get_the_title( $post ),
				'type'      => $types[ $post->post_type ]['label_th'],
				'deletable' => current_user_can( 'delete_post', $id ),
				'reason'    => current_user_can( 'delete_post', $id ) ? '' : 'รายการชุดนี้มีจำนวนคงที่ จึงแก้ไขได้แต่ลบไม่ได้',
				'permanent' => false,
			);
		}

		$term = get_term( $id );

		if ( ! $term || is_wp_error( $term ) ) {
			return null;
		}

		$in_use = self::term_in_use( $term );

		return array(
			'kind'      => 'term',
			'id'        => $id,
			'name'      => $term->name,
			'type'      => 'หมวดหมู่',
			'deletable' => current_user_can( 'delete_term', $id ),
			'reason'    => $in_use ? $in_use : ( current_user_can( 'delete_term', $id ) ? '' : 'คุณไม่มีสิทธิ์ลบหมวดหมู่นี้' ),
			'permanent' => true,
		);
	}

	public static function item_info() {
		check_ajax_referer( self::NONCE, 'nonce' );

		$item = self::item( isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '' );

		if ( ! $item ) {
			wp_send_json_error( array( 'message' => 'ไม่พบรายการนี้' ), 404 );
		}

		wp_send_json_success( $item );
	}

	/**
	 * Delete the open item. A post goes to the trash (it can be restored for
	 * 30 days); a category is removed for good. Either way WordPress's own
	 * hooks notify the website, exactly as if its own Delete link was used.
	 */
	public static function item_delete() {
		check_ajax_referer( self::NONCE, 'nonce' );

		$item = self::item( isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '' );

		if ( ! $item ) {
			wp_send_json_error( array( 'message' => 'ไม่พบรายการนี้ อาจถูกลบไปแล้ว' ), 404 );
		}

		if ( ! $item['deletable'] ) {
			wp_send_json_error( array( 'message' => $item['reason'] ? $item['reason'] : 'คุณไม่มีสิทธิ์ลบรายการนี้' ), 403 );
		}

		if ( 'post' === $item['kind'] ) {
			$done = (bool) wp_trash_post( $item['id'] );
		} else {
			$term = get_term( $item['id'] );
			$done = true === wp_delete_term( $item['id'], $term->taxonomy );
		}

		if ( ! $done ) {
			wp_send_json_error( array( 'message' => 'ลบไม่สำเร็จ กรุณาลองใหม่อีกครั้ง' ), 500 );
		}

		wp_send_json_success( array( 'deleted' => $item['name'], 'permanent' => $item['permanent'] ) );
	}

	// ─── Saving site-wide settings from the panel ───────────────────────────

	public static function save_settings() {
		check_ajax_referer( self::NONCE, 'nonce' );

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_send_json_error( array( 'message' => 'คุณไม่มีสิทธิ์แก้ไขการตั้งค่านี้' ), 403 );
		}

		$saved = array();

		// Only the settings the Customizer itself exposes, each cleaned the same
		// way the Customizer cleans it.
		foreach ( ID5_Customizer::settings() as $slug => $meta ) {
			if ( ! isset( $_POST[ $slug ] ) ) {
				continue;
			}

			$raw = wp_unslash( $_POST[ $slug ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( 'image' === $meta[1] ) {
				$value = absint( $raw );
			} elseif ( 'textarea' === $meta[1] ) {
				$value = sanitize_textarea_field( $raw );
			} else {
				$value = sanitize_text_field( $raw );
			}

			update_option( $slug, $value );
			$saved[] = $slug;
		}

		if ( $saved ) {
			ID5_Webhook::notify( array( 'event' => 'settings.updated' ) );
		}

		wp_send_json_success( array( 'saved' => $saved ) );
	}
}
