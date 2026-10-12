<?php
/**
 * Shapes the wp-admin experience for the people who maintain the website.
 *
 * Three jobs: register the two menu locations the public site uses, hide the
 * parts of WordPress a content editor has no business in, and give an
 * administrator one screen showing whether WordPress and the website are
 * actually talking to each other.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

class ID5_Admin {

	const OPTION_GROUP = 'id5_cms_connection';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_menus' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'fix_view_site_link' ), 100 );
		add_action( 'admin_menu', array( __CLASS__, 'hide_irrelevant_menus' ), 999 );
		add_action( 'current_screen', array( __CLASS__, 'close_irrelevant_screens' ) );
		add_filter( 'wp_untrash_post_status', array( __CLASS__, 'restore_as_it_was' ), 10, 3 );
		add_action( 'admin_bar_menu', array( __CLASS__, 'tidy_toolbar' ), 999 );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'tidy_dashboard' ), 999 );
		add_filter( 'gettext', array( __CLASS__, 'translate_leftovers' ), 10, 3 );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'dashboard_widget' ) );
		add_filter( 'admin_footer_text', array( __CLASS__, 'footer_text' ) );
		add_filter( 'post_link', array( __CLASS__, 'public_link' ), 10, 2 );
		add_action( 'admin_head', array( __CLASS__, 'hide_preview_button' ) );

		// English labels on navigation menu items.
		add_action( 'wp_nav_menu_item_custom_fields', array( __CLASS__, 'menu_item_fields' ), 10, 2 );
		add_action( 'wp_update_nav_menu_item', array( __CLASS__, 'save_menu_item_fields' ), 10, 2 );
	}

	/**
	 * The two menus the public site renders.
	 */
	public static function register_menus() {
		register_nav_menus(
			array(
				'id5_header' => 'เมนูด้านบน (Header menu)',
				'id5_footer' => 'เมนูด้านล่าง (Footer menu)',
			)
		);
	}

	// ─── Navigation menu item English labels ────────────────────────────────

	public static function menu_item_fields( $item_id, $item ) {
		$value = get_post_meta( $item_id, '_id5_label_en', true );
		?>
		<p class="field-id5-label-en description description-wide">
			<label for="id5-label-en-<?php echo esc_attr( $item_id ); ?>">
				ชื่อเมนูภาษาอังกฤษ (English label)<br>
				<input type="text" id="id5-label-en-<?php echo esc_attr( $item_id ); ?>"
					class="widefat"
					name="id5_menu_label_en[<?php echo esc_attr( $item_id ); ?>]"
					value="<?php echo esc_attr( $value ); ?>">
			</label>
		</p>
		<?php
	}

	public static function save_menu_item_fields( $menu_id, $item_id ) {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}

		if ( ! isset( $_POST['id5_menu_label_en'][ $item_id ] ) ) {
			return;
		}

		check_admin_referer( 'update-nav_menu', 'update-nav-menu-nonce' );

		$value = sanitize_text_field( wp_unslash( $_POST['id5_menu_label_en'][ $item_id ] ) );

		if ( '' === $value ) {
			delete_post_meta( $item_id, '_id5_label_en' );
		} else {
			update_post_meta( $item_id, '_id5_label_en', $value );
		}
	}

	// ─── Connection settings ────────────────────────────────────────────────

	public static function add_settings_page() {
		add_options_page(
			'การเชื่อมต่อเว็บไซต์ ID5',
			'การเชื่อมต่อเว็บไซต์ ID5',
			'manage_options',
			'id5-cms',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function register_settings() {
		register_setting( self::OPTION_GROUP, 'id5_public_site_url', array( 'sanitize_callback' => 'esc_url_raw', 'default' => '' ) );
		register_setting( self::OPTION_GROUP, 'id5_webhook_url', array( 'sanitize_callback' => 'esc_url_raw', 'default' => '' ) );
		register_setting( self::OPTION_GROUP, 'id5_webhook_secret', array( 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ) );
	}

	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้' ) );
		}

		$last_ok    = (int) get_option( 'id5_webhook_last_ok', 0 );
		$last_error = get_option( 'id5_webhook_last_error' );
		?>
		<div class="wrap">
			<h1>การเชื่อมต่อเว็บไซต์ ID5</h1>
			<p>หน้านี้สำหรับผู้ดูแลระบบเท่านั้น ผู้ดูแลเนื้อหาไม่จำเป็นต้องแก้ไขค่าเหล่านี้</p>

			<form method="post" action="options.php">
				<?php settings_fields( self::OPTION_GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="id5_public_site_url">URL ของเว็บไซต์สาธารณะ</label></th>
						<td>
							<input type="url" class="regular-text" id="id5_public_site_url"
								name="id5_public_site_url"
								value="<?php echo esc_attr( get_option( 'id5_public_site_url', '' ) ); ?>"
								placeholder="https://example.go.th">
							<p class="description">ใช้สำหรับปุ่ม "ดูเว็บไซต์" ด้านบนซ้าย</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="id5_webhook_url">ที่อยู่สำหรับแจ้งการเปลี่ยนแปลง</label></th>
						<td>
							<input type="url" class="regular-text" id="id5_webhook_url"
								name="id5_webhook_url"
								value="<?php echo esc_attr( get_option( 'id5_webhook_url', '' ) ); ?>"
								placeholder="https://example.go.th/cms/webhook">
							<p class="description">ต้องตรงกับค่า <code>CMS_WEBHOOK</code> ที่ตั้งไว้ฝั่งเว็บไซต์</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="id5_webhook_secret">รหัสลับสำหรับลงลายเซ็น</label></th>
						<td>
							<input type="password" class="regular-text" id="id5_webhook_secret"
								name="id5_webhook_secret" autocomplete="new-password"
								value="<?php echo esc_attr( get_option( 'id5_webhook_secret', '' ) ); ?>">
							<p class="description">ต้องตรงกับ <code>CMS_WEBHOOK_SECRET</code> ในไฟล์ <code>.env</code> ของเว็บไซต์</p>
						</td>
					</tr>
				</table>
				<?php submit_button( 'บันทึกการตั้งค่า' ); ?>
			</form>

			<h2>สถานะล่าสุด</h2>
			<table class="widefat striped" style="max-width:640px">
				<tbody>
					<tr>
						<td><strong>ส่งข้อมูลสำเร็จล่าสุด</strong></td>
						<td><?php echo $last_ok ? esc_html( wp_date( 'j M Y, H:i', $last_ok ) ) : 'ยังไม่เคยส่งสำเร็จ'; ?></td>
					</tr>
					<tr>
						<td><strong>ข้อผิดพลาดล่าสุด</strong></td>
						<td>
							<?php
							if ( is_array( $last_error ) ) {
								printf(
									'%s — %s (%s)',
									esc_html( wp_date( 'j M Y, H:i', (int) $last_error['at'] ) ),
									esc_html( $last_error['reason'] ),
									esc_html( $last_error['event'] )
								);
							} else {
								echo 'ไม่มี';
							}
							?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	// ─── Tidying ────────────────────────────────────────────────────────────

	/**
	 * Point "View site" at the real website rather than this editing install.
	 */
	public static function fix_view_site_link( $admin_bar ) {
		$site = get_option( 'id5_public_site_url' );

		if ( ! $site ) {
			return;
		}

		$node = $admin_bar->get_node( 'view-site' );

		if ( $node ) {
			// With an edit-mode token, so the editor lands on the real website
			// with the top bar and the pencils already showing.
			$node->href   = ID5_Site_Editor::public_url( '/' ) ?: $site;
			$node->meta   = array_merge( (array) $node->meta, array( 'target' => '_blank' ) );
			$node->title  = 'ดูเว็บไซต์';
			$admin_bar->add_node( (array) $node );
		}
	}

	/**
	 * Remove menus a content editor cannot use. Administrators keep everything.
	 */
	/**
	 * The screens hidden from the menu below still open if their address is
	 * typed in. None of them can do harm with an editor's permissions — the
	 * website does not use a WordPress theme, and the tools are empty — but a
	 * screen that has nothing to do with the website is only a way to get
	 * lost, so editors are sent back to the dashboard.
	 */
	public static function close_irrelevant_screens( $screen ) {
		if ( current_user_can( 'manage_options' ) || ! $screen ) {
			return;
		}

		$closed = array( 'themes', 'tools', 'import', 'export', 'site-health', 'edit-comments', 'edit-page', 'page', 'theme-editor', 'site-editor' );

		if ( in_array( $screen->id, $closed, true ) ) {
			wp_safe_redirect( admin_url() );
			exit;
		}
	}

	/**
	 * "Restore" from the trash puts an item back the way it was.
	 *
	 * WordPress on its own restores everything as a draft, so an editor who
	 * deleted a published item by mistake and restored it would find it still
	 * missing from the website, with nothing saying why. For the website's
	 * content a restored item returns with the status it had — published
	 * items are published again.
	 */
	public static function restore_as_it_was( $new_status, $post_id, $previous_status ) {
		$types = ID5_Schema::types();

		return isset( $types[ get_post_type( $post_id ) ] ) && $previous_status ? $previous_status : $new_status;
	}

	/**
	 * Take out of the top toolbar what has nothing to do with the website:
	 * comments (the site has none), the WordPress "about" menu and the command
	 * palette, which jumps to screens editors are not given.
	 */
	public static function tidy_toolbar( $admin_bar ) {
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		foreach ( array( 'comments', 'command-palette', 'wp-logo' ) as $node ) {
			$admin_bar->remove_node( $node );
		}
	}

	/**
	 * Leave the dashboard with the one panel that helps an editor. WordPress's
	 * own boxes talk about its version, theme and comments.
	 */
	public static function tidy_dashboard() {
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		foreach ( array( 'dashboard_right_now', 'dashboard_activity', 'dashboard_quick_press', 'dashboard_primary', 'dashboard_site_health' ) as $box ) {
			remove_meta_box( $box, 'dashboard', 'normal' );
			remove_meta_box( $box, 'dashboard', 'side' );
		}
	}

	/**
	 * The few interface words the Thai language pack leaves in English.
	 */
	public static function translate_leftovers( $translation, $text, $domain ) {
		if ( 'default' !== $domain || $translation !== $text ) {
			return $translation;
		}

		$thai = array(
			'Collapse Menu' => 'ย่อเมนู',
			'Expand Menu'   => 'ขยายเมนู',
		);

		return isset( $thai[ $text ] ) ? $thai[ $text ] : $translation;
	}

	public static function hide_irrelevant_menus() {
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		remove_menu_page( 'tools.php' );
		remove_menu_page( 'edit-comments.php' );
		// WordPress "Pages" are not part of the public website — its pages are
		// built into the site itself — so an editor creating one here would be
		// writing something no visitor can ever reach.
		remove_menu_page( 'edit.php?post_type=page' );
		remove_submenu_page( 'themes.php', 'themes.php' );
		remove_submenu_page( 'themes.php', 'theme-editor.php' );
		remove_submenu_page( 'themes.php', 'site-editor.php' );
	}

	/**
	 * A plain-language dashboard panel so an editor landing in wp-admin knows
	 * where things are without being trained twice.
	 */
	public static function dashboard_widget() {
		// WordPress's stock dashboard advertises things this install does not
		// use — block themes, the site editor, community events — and one of
		// its panels calls out to wordpress.org on every visit. Clearing them
		// leaves an editor with a first screen that is only about their site.
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );

		if ( ! current_user_can( 'manage_options' ) ) {
			remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );
		}

		// Registered directly (rather than through wp_add_dashboard_widget) so
		// it can be pinned to the top of the main column.
		add_meta_box(
			'id5_cms_help',
			'การจัดการเว็บไซต์ ID5',
			array( __CLASS__, 'render_dashboard_widget' ),
			'dashboard',
			'normal',
			'high'
		);
	}

	public static function render_dashboard_widget() {
		// Built from the schema, so a content type added later appears here
		// without anyone remembering to list it.
		$links = array();

		foreach ( ID5_Schema::types() as $post_type => $type ) {
			$path           = 'post' === $post_type ? 'edit.php' : 'edit.php?post_type=' . $post_type;
			$links[ $path ] = $type['label_th'];
		}

		$links['customize.php'] = 'ข้อความหน้าแรกและส่วนท้าย';
		$links['nav-menus.php'] = 'เมนูนำทาง';
		$links['upload.php']    = 'คลังรูปภาพและไฟล์';

		// The quickest route for most edits: see the site, click what is wrong.
		printf(
			'<p><a class="button button-primary button-hero" href="%s"><span class="dashicons dashicons-admin-customizer" style="margin:12px 6px 0 -4px"></span>%s</a></p><p>%s</p>',
			esc_url( ID5_Site_Editor::url() ),
			esc_html( 'แก้ไขเว็บไซต์ (ดูหน้าเว็บจริงแล้วคลิกเพื่อแก้ไข)' ),
			esc_html( 'เปิดหน้าเว็บจริงทางขวา แล้วกดปุ่มดินสอบนส่วนที่ต้องการแก้ไข' )
		);

		echo '<p>หรือเลือกประเภทเนื้อหาจากเมนูด้านซ้าย / รายการนี้</p><ul class="id5-dashboard-links">';

		foreach ( $links as $path => $label ) {
			printf(
				'<li><a href="%s">%s</a></li>',
				esc_url( admin_url( $path ) ),
				esc_html( $label )
			);
		}

		echo '</ul>';
		echo '<p><em>การเปลี่ยนแปลงจะปรากฏบนเว็บไซต์จริงภายในไม่กี่วินาทีหลังกด "เผยแพร่"</em></p>';
	}

	/**
	 * Point "View post" at the public website's news section.
	 *
	 * WordPress would otherwise open its own copy of the post, drawn with the
	 * default theme on the editing install — a page no visitor ever sees and
	 * which looks nothing like the real site. Sending the editor to the real
	 * news page answers the question they are actually asking: "is it up?"
	 */
	public static function public_link( $permalink, $post ) {
		$site = get_option( 'id5_public_site_url' );

		if ( $site && 'post' === $post->post_type ) {
			return trailingslashit( $site ) . 'news';
		}

		return $permalink;
	}

	/**
	 * Hide the Preview button on the content screens.
	 *
	 * A preview would be rendered by this install's default theme, not by the
	 * public website, so it would show an editor something that does not match
	 * what visitors will get. Saving as a draft and publishing when ready is the
	 * honest workflow here.
	 */
	public static function hide_preview_button() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && 'post' === $screen->base && ID5_Schema::type( $screen->post_type ) ) {
			echo '<style>#minor-publishing-actions #preview-action, #edit-slug-box { display: none !important; }</style>';
		}
	}

	public static function footer_text() {
		return 'ระบบจัดการเนื้อหาเว็บไซต์ ID5 @ Thailand 5.0';
	}
}
