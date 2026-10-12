<?php
/**
 * Site-wide text and images, edited in the Customizer.
 *
 * These are the values the public site shows on every page (site name, hotline,
 * footer) plus the homepage hero. The Customizer is the natural WordPress home
 * for them: an administrator who knows WordPress already looks here first.
 *
 * Each setting maps to one row in Laravel's `settings` table.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

class ID5_Customizer {

	/**
	 * setting slug => [ Laravel settings.key, type ]
	 *
	 * `type` is 'text', 'textarea' or 'image' (stored as an attachment URL).
	 */
	public static function settings() {
		return array(
			'id5_site_name'        => array( 'general.site_name', 'text' ),
			'id5_emergency_phone'  => array( 'general.emergency_phone', 'text' ),
			'id5_hero_title_th'    => array( 'home.hero_title_th', 'text' ),
			'id5_hero_title_en'    => array( 'home.hero_title_en', 'text' ),
			'id5_hero_subtitle_th' => array( 'home.hero_subtitle_th', 'textarea' ),
			'id5_hero_subtitle_en' => array( 'home.hero_subtitle_en', 'textarea' ),
			'id5_hero_cta_th'      => array( 'home.hero_cta_th', 'text' ),
			'id5_hero_cta_en'      => array( 'home.hero_cta_en', 'text' ),
			'id5_hero_image'       => array( 'site.hero_image', 'image' ),
			'id5_footer_copyright' => array( 'site.footer_copyright', 'text' ),
			'id5_footer_address'   => array( 'site.footer_address', 'textarea' ),
		);
	}

	public static function init() {
		add_action( 'customize_register', array( __CLASS__, 'register' ) );
	}

	public static function register( $wp_customize ) {
		$wp_customize->add_panel(
			'id5_website',
			array(
				'title'       => 'ข้อมูลเว็บไซต์ (ID5)',
				'description' => 'ข้อความและรูปภาพที่แสดงบนเว็บไซต์สาธารณะ',
				'priority'    => 10,
			)
		);

		// ── Site identity ───────────────────────────────────────────────────
		$wp_customize->add_section(
			'id5_identity',
			array(
				'title'       => 'ชื่อเว็บไซต์และสายด่วน',
				'panel'       => 'id5_website',
				'description' => 'แสดงบนส่วนหัวและส่วนท้ายของทุกหน้า',
			)
		);

		self::text( $wp_customize, 'id5_site_name', 'id5_identity', 'ชื่อเว็บไซต์', 'ID5 @ Thailand 5.0' );
		self::text( $wp_customize, 'id5_emergency_phone', 'id5_identity', 'เบอร์สายด่วนฉุกเฉิน', '1178' );

		// ── Homepage hero ───────────────────────────────────────────────────
		$wp_customize->add_section(
			'id5_hero',
			array(
				'title'       => 'หน้าแรก — แบนเนอร์ด้านบน',
				'panel'       => 'id5_website',
				'description' => 'เว้นช่องใดว่างไว้ เว็บไซต์จะใช้ข้อความเริ่มต้นของระบบแทน',
			)
		);

		self::text( $wp_customize, 'id5_hero_title_th', 'id5_hero', 'หัวข้อใหญ่ (ภาษาไทย)' );
		self::text( $wp_customize, 'id5_hero_title_en', 'id5_hero', 'หัวข้อใหญ่ (English)' );
		self::textarea( $wp_customize, 'id5_hero_subtitle_th', 'id5_hero', 'คำอธิบายใต้หัวข้อ (ภาษาไทย)' );
		self::textarea( $wp_customize, 'id5_hero_subtitle_en', 'id5_hero', 'คำอธิบายใต้หัวข้อ (English)' );
		self::text( $wp_customize, 'id5_hero_cta_th', 'id5_hero', 'ข้อความบนปุ่ม (ภาษาไทย)' );
		self::text( $wp_customize, 'id5_hero_cta_en', 'id5_hero', 'ข้อความบนปุ่ม (English)' );

		$wp_customize->add_setting(
			'id5_hero_image',
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'absint',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Media_Control(
				$wp_customize,
				'id5_hero_image',
				array(
					'label'       => 'รูปพื้นหลังแบนเนอร์',
					'description' => 'แนะนำขนาดอย่างน้อย 1920 x 1080 พิกเซล',
					'section'     => 'id5_hero',
					'mime_type'   => 'image',
				)
			)
		);

		// ── Footer ──────────────────────────────────────────────────────────
		$wp_customize->add_section(
			'id5_footer',
			array(
				'title'       => 'ส่วนท้ายเว็บไซต์',
				'panel'       => 'id5_website',
				'description' => 'แสดงที่ด้านล่างของทุกหน้า',
			)
		);

		self::text( $wp_customize, 'id5_footer_copyright', 'id5_footer', 'ข้อความลิขสิทธิ์' );
		self::textarea( $wp_customize, 'id5_footer_address', 'id5_footer', 'ที่อยู่หน่วยงาน' );

		// ── Connection ──────────────────────────────────────────────────────
		$wp_customize->add_section(
			'id5_connection',
			array(
				'title'       => 'ที่อยู่เว็บไซต์สาธารณะ',
				'panel'       => 'id5_website',
				'description' => 'ใช้สำหรับปุ่ม "ดูเว็บไซต์" และการส่งข้อมูลไปยังเว็บไซต์จริง เปลี่ยนเฉพาะเมื่อย้ายเซิร์ฟเวอร์',
			)
		);

		$wp_customize->add_setting(
			'id5_public_site_url',
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'sanitize_callback' => 'esc_url_raw',
			)
		);

		$wp_customize->add_control(
			'id5_public_site_url',
			array(
				'label'   => 'URL ของเว็บไซต์สาธารณะ',
				'section' => 'id5_connection',
				'type'    => 'url',
			)
		);
	}

	private static function text( $wp_customize, $slug, $section, $label, $default = '' ) {
		$wp_customize->add_setting(
			$slug,
			array(
				'default'           => $default,
				'type'              => 'option',
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		$wp_customize->add_control( $slug, array( 'label' => $label, 'section' => $section, 'type' => 'text' ) );
	}

	private static function textarea( $wp_customize, $slug, $section, $label, $default = '' ) {
		$wp_customize->add_setting(
			$slug,
			array(
				'default'           => $default,
				'type'              => 'option',
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'sanitize_textarea_field',
			)
		);

		$wp_customize->add_control( $slug, array( 'label' => $label, 'section' => $section, 'type' => 'textarea' ) );
	}

	/**
	 * The current values, keyed by their Laravel settings key, ready to sync.
	 */
	public static function payload() {
		$out = array();

		foreach ( self::settings() as $slug => $meta ) {
			list( $laravel_key, $type ) = $meta;

			$value = get_option( $slug, '' );

			if ( 'image' === $type ) {
				$value = $value ? (string) wp_get_attachment_url( (int) $value ) : '';
			}

			$out[ $laravel_key ] = (string) $value;
		}

		return $out;
	}
}
