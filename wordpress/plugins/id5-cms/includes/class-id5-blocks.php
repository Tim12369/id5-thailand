<?php
/**
 * The block editor, for the two free-form content types.
 *
 * "ส่วนเนื้อหาเพิ่มเติม" (id5_section) and "หน้าเว็บเพิ่มเติม" (id5_page) are
 * composed by editors themselves, the way a page builder works: rows of
 * columns — columns added and removed at will — holding text, headings,
 * pictures, PDF documents and buttons. WordPress's own block editor does
 * exactly this, so it is used as it is, with its choices narrowed to what the
 * public website knows how to show:
 *
 *  - only the blocks listed in self::BLOCKS;
 *  - only the website's own colours and text sizes, so nothing an editor
 *    picks can come out unstyled or off-brand on the website;
 *  - no free spacing, borders or fonts.
 *
 * Every other content type keeps the classic editor and its fixed design.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

class ID5_Blocks {

	/** The blocks an editor can use, and the website can show. */
	const BLOCKS = array(
		'core/columns',
		'core/column',
		'core/group',
		'core/paragraph',
		'core/heading',
		'core/list',
		'core/list-item',
		'core/image',
		'core/file',
		'core/buttons',
		'core/button',
		'core/separator',
		'core/spacer',
		'core/quote',
		'core/table',
	);

	public static function init() {
		add_filter( 'allowed_block_types_all', array( __CLASS__, 'allowed' ), 10, 2 );
		add_filter( 'wp_theme_json_data_theme', array( __CLASS__, 'choices' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'assets' ) );
		add_filter( 'post_type_link', array( __CLASS__, 'public_address' ), 10, 2 );
	}

	public static function is_block_type( $post_type ) {
		$type = ID5_Schema::type( (string) $post_type );

		return $type && ! empty( $type['block_editor'] );
	}

	public static function allowed( $allowed, $context ) {
		if ( isset( $context->post ) && $context->post && self::is_block_type( $context->post->post_type ) ) {
			return self::BLOCKS;
		}

		return $allowed;
	}

	/**
	 * The colours and text sizes offered in the editor are the website's own;
	 * the public stylesheet has a rule for each (resources/css/app.css,
	 * "Blocks built in WordPress").
	 */
	public static function choices( $theme_json ) {
		return $theme_json->update_with(
			array(
				'version'  => 3,
				'settings' => array(
					'appearanceTools' => false,
					'color'           => array(
						'custom'           => false,
						'customGradient'   => false,
						'customDuotone'    => false,
						'defaultPalette'   => false,
						'defaultGradients' => false,
						'defaultDuotone'   => false,
						'gradients'        => array(),
						'duotone'          => array(),
						'link'             => false,
						'palette'          => array(
							array( 'slug' => 'id5-blue', 'name' => 'น้ำเงิน', 'color' => '#1c3a9e' ),
							array( 'slug' => 'id5-navy', 'name' => 'น้ำเงินเข้ม', 'color' => '#0f1f4d' ),
							array( 'slug' => 'id5-gold', 'name' => 'ทอง', 'color' => '#d9a437' ),
							array( 'slug' => 'id5-red', 'name' => 'แดง', 'color' => '#dc2626' ),
							array( 'slug' => 'id5-green', 'name' => 'เขียว', 'color' => '#059669' ),
							array( 'slug' => 'id5-slate', 'name' => 'เทาเข้ม', 'color' => '#334155' ),
							array( 'slug' => 'id5-mist', 'name' => 'ฟ้าอ่อน', 'color' => '#eef4ff' ),
							array( 'slug' => 'id5-white', 'name' => 'ขาว', 'color' => '#ffffff' ),
						),
					),
					'typography'      => array(
						'customFontSize' => false,
						'fontFamilies'   => array(),
						'fontStyle'      => false,
						'fontWeight'     => false,
						'letterSpacing'  => false,
						'lineHeight'     => false,
						'textDecoration' => false,
						'textTransform'  => false,
						'dropCap'        => false,
						'writingMode'    => false,
						'fluid'          => false,
						'fontSizes'      => array(
							array( 'slug' => 'id5-small', 'name' => 'เล็ก', 'size' => '0.875rem' ),
							array( 'slug' => 'id5-normal', 'name' => 'ปกติ', 'size' => '1rem' ),
							array( 'slug' => 'id5-large', 'name' => 'ใหญ่', 'size' => '1.25rem' ),
							array( 'slug' => 'id5-huge', 'name' => 'ใหญ่มาก', 'size' => '1.75rem' ),
						),
					),
					'spacing'         => array(
						'padding'           => false,
						'margin'            => false,
						'blockGap'          => false,
						'customSpacingSize' => false,
						'units'             => array( 'px', '%' ),
					),
					'border'          => array(
						'color'  => false,
						'radius' => false,
						'style'  => false,
						'width'  => false,
					),
					'shadow'          => array(
						'defaultPresets' => false,
						'presets'        => array(),
					),
				),
			)
		);
	}

	/**
	 * In the editor: the instructions above the canvas, and — inside the site
	 * editor's overlay — telling it when a save starts and ends, as the
	 * classic forms do by loading a new page.
	 */
	public static function assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! self::is_block_type( $screen->post_type ) ) {
			return;
		}

		$type = ID5_Schema::type( $screen->post_type );

		wp_enqueue_script( 'id5-block-editor', ID5_CMS_URL . 'assets/block-editor.js', array( 'wp-data', 'wp-dom-ready', 'wp-notices', 'wp-editor', 'wp-edit-post' ), ID5_CMS_VERSION, true );
		wp_localize_script(
			'id5-block-editor',
			'ID5BlockEditor',
			array(
				'notice' => isset( $type['editor_notice'] ) ? $type['editor_notice'] : '',
			)
		);
	}

	/**
	 * Where a page built in WordPress lives on the public website: its slug when
	 * that is a plain web address, otherwise its number. This is also the
	 * address a navigation menu links to.
	 */
	public static function public_address( $link, $post ) {
		if ( 'id5_page' !== $post->post_type ) {
			return $link;
		}

		$site = rtrim( (string) get_option( 'id5_public_site_url' ), '/' );

		if ( '' === $site ) {
			return $link;
		}

		$key = preg_match( '/^[a-z0-9][a-z0-9-]*$/', (string) $post->post_name ) ? $post->post_name : (string) $post->ID;

		return $site . '/page/' . $key;
	}
}
