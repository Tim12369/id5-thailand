<?php
/**
 * The single declarative description of every content type this site has.
 *
 * Post types, taxonomies, editor fields, REST output and the Laravel sync all
 * read from here, so a content change is made in one place rather than four.
 *
 * Field types understood by ID5_Fields and ID5_Serializer:
 *   text, textarea, wysiwyg, number, checkbox, select, url, email, date,
 *   image (attachment ID), file (attachment ID), repeater (rows of fields)
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

class ID5_Schema {

	/** Cached schema so the arrays are built once per request. */
	private static $types = null;

	/** Cached taxonomy schema. */
	private static $taxonomies = null;

	/**
	 * Personas used by the Smart Guide. Mirrors App\Models\SmartGuide::PERSONAS.
	 */
	public static function personas() {
		return array(
			'tourist'      => 'นักท่องเที่ยว (Tourist)',
			'student'      => 'นักเรียน/นักศึกษา (Student)',
			'worker'       => 'คนทำงาน (Worker)',
			'investor'     => 'นักลงทุน (Investor)',
			'retiree'      => 'ผู้เกษียณอายุ (Retiree)',
			'family'       => 'ครอบครัว (Family)',
			'thai_citizen' => 'คนไทย (Thai citizen)',
			'hotel'        => 'โรงแรม/ที่พัก (Hotel)',
			'employer'     => 'นายจ้าง (Employer)',
			'general'      => 'ทั่วไป (General)',
			'officer'      => 'เจ้าหน้าที่ (Officer)',
		);
	}

	/**
	 * Smart Guide topics. Mirrors App\Models\SmartGuide::TOPICS.
	 */
	public static function topics() {
		return array(
			'visa'           => 'วีซ่า (Visa)',
			'tm30'           => 'แจ้งที่พัก ตม.30 (TM30)',
			'90day'          => 'รายงานตัว 90 วัน (90-day report)',
			'extension'      => 'ขยายเวลาพำนัก (Extension)',
			'overstay'       => 'อยู่เกินกำหนด (Overstay)',
			'entry'          => 'การเดินทางเข้า (Entry)',
			'exit'           => 'การเดินทางออก (Exit)',
			'work_permit'    => 'ใบอนุญาตทำงาน (Work permit)',
			'investor_visa'  => 'วีซ่านักลงทุน (Investor visa)',
			'marriage_visa'  => 'วีซ่าสมรส (Marriage visa)',
		);
	}

	/**
	 * The six SW1H dimensions. Mirrors App\Models\SmartGuide::DIMENSIONS.
	 */
	public static function dimensions() {
		return array(
			'who'   => 'ใคร (Who)',
			'what'  => 'อะไร (What)',
			'why'   => 'ทำไม (Why)',
			'where' => 'ที่ไหน (Where)',
			'when'  => 'เมื่อไร (When)',
			'how'   => 'อย่างไร (How)',
		);
	}

	/**
	 * Assessment status keys. Mirrors config/assessment/statuses.php in Laravel.
	 */
	public static function assessment_statuses() {
		return array(
			'thai_citizen'       => 'คนไทย (Thai citizen)',
			'tourist'            => 'นักท่องเที่ยว (Tourist)',
			'visa_exemption'     => 'ผ.30 / ยกเว้นวีซ่า (Visa exemption)',
			'visa_on_arrival'    => 'วีซ่า ณ ด่าน (Visa on arrival)',
			'student'            => 'นักเรียน/นักศึกษา (Student)',
			'worker'             => 'คนทำงาน (Worker)',
			'business'           => 'ธุรกิจ (Business)',
			'investor'           => 'นักลงทุน (Investor)',
			'family'             => 'ครอบครัว (Family)',
			'retirement'         => 'เกษียณอายุ (Retirement)',
			'permanent_resident' => 'ผู้มีถิ่นที่อยู่ถาวร (Permanent resident)',
			'stateless'          => 'บุคคลไร้รัฐ (Stateless)',
			'refugee_protection' => 'ผู้ลี้ภัย (Refugee protection)',
			'representative'     => 'ผู้แทน (Representative)',
			'employer'           => 'นายจ้าง (Employer)',
			'landlord'           => 'เจ้าของที่พัก (Landlord)',
			'embassy_consular'   => 'สถานทูต/กงสุล (Embassy or consular)',
			'emergency'          => 'เหตุฉุกเฉิน (Emergency)',
		);
	}

	/**
	 * The six fixed cards in the homepage "Explore ID5 Services" grid. These keys
	 * map to real public routes, so they are chosen from a list and never typed.
	 */
	public static function division_card_keys() {
		return array(
			'who_are_you'    => 'คุณคือใคร? (Who are you?)',
			'where_are_you'  => 'คุณอยู่ที่ไหน? (Where are you?)',
			'technology'     => 'ฝ่ายเทคโนโลยีตรวจคนเข้าเมือง (Technology)',
			'administration' => 'ฝ่ายอำนวยการ (Administration)',
			'foreigner'      => 'ฝ่ายบริการคนต่างด้าว (Foreigner services)',
			'investigation'  => 'ฝ่ายสืบสวนสอบสวน (Investigation)',
		);
	}

	/**
	 * The six Who / What / Why / Where / When / How editors for one language.
	 *
	 * `compact` asks for the small toolbar (bold, italic, lists, link) with no
	 * media button: this text is short formatted guidance, not an article.
	 *
	 * @param string $lang 'th' or 'en'.
	 */
	private static function dimension_fields( $lang ) {
		$fields = array();

		foreach ( self::dimensions() as $key => $label ) {
			$fields[] = array(
				'key'     => $key . '_' . $lang,
				'label'   => $label,
				'type'    => 'wysiwyg',
				'compact' => true,
			);
		}

		return $fields;
	}

	/**
	 * Reusable "English version" box for the four article-shaped content types.
	 *
	 * @param string $what Thai word naming the thing, e.g. 'ข่าว'.
	 */
	private static function english_box( $what ) {
		return array(
			'id'     => 'english',
			'title'  => 'ภาษาอังกฤษ / English version',
			'help'   => 'กรอกเฉพาะเมื่อมีฉบับภาษาอังกฤษ หากเว้นว่าง เว็บไซต์จะแสดงฉบับภาษาไทยแทน',
			'fields' => array(
				array( 'key' => 'title_en', 'label' => 'หัวข้อ (English title)', 'type' => 'text' ),
				array( 'key' => 'excerpt_en', 'label' => 'สรุปย่อ (English summary)', 'type' => 'textarea' ),
				array( 'key' => 'body_en', 'label' => 'เนื้อหา (English body)', 'type' => 'wysiwyg' ),
			),
		);
	}

	/**
	 * Reusable search-engine box. Replaces an SEO plugin with two plain fields.
	 */
	private static function seo_box() {
		return array(
			'id'     => 'seo',
			'title'  => 'การแสดงผลบนเครื่องมือค้นหา / Search engine listing',
			'help'   => 'เว้นว่างไว้ได้ ระบบจะใช้หัวข้อและสรุปย่อของเนื้อหานี้แทน',
			'fields' => array(
				array( 'key' => 'seo_title', 'label' => 'หัวข้อที่แสดงใน Google', 'type' => 'text', 'help' => 'ไม่บังคับ — เว้นว่างไว้ ระบบจะใช้หัวข้อของเรื่องนี้' ),
				array( 'key' => 'seo_description', 'label' => 'คำอธิบายที่แสดงใน Google', 'type' => 'textarea', 'maxlength' => 500, 'help' => 'ไม่บังคับ — ข้อความสั้น ๆ 1–2 ประโยคที่จะแสดงใต้หัวข้อในผลการค้นหา' ),
			),
		);
	}

	/**
	 * Reusable publishing-options box shared by the article-shaped types.
	 *
	 * @param bool $with_alert_level Whether to include the alert severity field.
	 */
	private static function publishing_box( $with_alert_level = false ) {
		$fields = array(
			array(
				'key'   => 'is_pinned',
				'label' => 'ปักหมุดไว้ด้านบน',
				'type'  => 'checkbox',
				'help'  => 'เนื้อหาที่ปักหมุดจะแสดงก่อนเนื้อหาอื่นเสมอ',
			),
			array(
				'key'   => 'expired_at',
				'label' => 'ซ่อนอัตโนมัติเมื่อพ้นวันที่',
				'type'  => 'date',
				'help'  => 'เว้นว่างไว้หากต้องการให้แสดงตลอดไป',
			),
		);

		if ( $with_alert_level ) {
			array_unshift(
				$fields,
				array(
					'key'     => 'alert_level',
					'label'   => 'ระดับความรุนแรง',
					'type'    => 'select',
					'default' => 'info',
					'options' => array(
						'info'    => 'ข้อมูลทั่วไป (สีน้ำเงิน)',
						'warning' => 'ควรระวัง (สีเหลือง)',
						'danger'  => 'อันตราย (สีแดง)',
					),
					'help'    => 'กำหนดสีของแถบแจ้งเตือนบนเว็บไซต์',
				)
			);
		}

		return array(
			'id'     => 'publishing',
			'title'  => 'ตัวเลือกการเผยแพร่ / Publishing options',
			'fields' => $fields,
		);
	}

	/**
	 * Every taxonomy this plugin registers.
	 */
	public static function taxonomies() {
		if ( null !== self::$taxonomies ) {
			return self::$taxonomies;
		}

		self::$taxonomies = array(
			// WordPress's own post categories, used by news. Marked `native` so the
			// plugin adds its fields to them without trying to register them again.
			'category'             => array(
				'native'      => true,
				'label_th'    => 'หมวดหมู่ข่าว',
				'label_en'    => 'News categories',
				'singular'    => 'หมวดหมู่ข่าว',
				'post_types'  => array( 'post' ),
				'hierarchical'=> true,
				'fields'      => array(
					array( 'key' => 'name_en', 'label' => 'ชื่อภาษาอังกฤษ (English name)', 'type' => 'text' ),
					array( 'key' => 'sort_order', 'label' => 'ลำดับการแสดงผล', 'type' => 'number', 'default' => 0, 'help' => 'ตัวเลขน้อยแสดงก่อน เช่น 1 อยู่ก่อน 2' ),
				),
			),
			'id5_content_category' => array(
				'label_th'    => 'หมวดหมู่',
				'label_en'    => 'Categories',
				'singular'    => 'หมวดหมู่',
				'post_types'  => array( 'id5_announcement', 'id5_alert', 'id5_knowledge', 'id5_faq' ),
				'hierarchical'=> true,
				'fields'      => array(
					array( 'key' => 'name_en', 'label' => 'ชื่อภาษาอังกฤษ (English name)', 'type' => 'text' ),
					array( 'key' => 'sort_order', 'label' => 'ลำดับการแสดงผล', 'type' => 'number', 'default' => 0, 'help' => 'ตัวเลขน้อยแสดงก่อน เช่น 1 อยู่ก่อน 2' ),
				),
			),
			'id5_form_category'    => array(
				'label_th'    => 'ประเภทแบบฟอร์ม',
				'label_en'    => 'Form categories',
				'singular'    => 'ประเภทแบบฟอร์ม',
				'post_types'  => array( 'id5_form' ),
				'hierarchical'=> true,
				'fields'      => array(),
			),
			'id5_org_section'      => array(
				'label_th'    => 'หน่วยงานในแผนผัง',
				'label_en'    => 'Organisation sections',
				'singular'    => 'หน่วยงาน',
				'post_types'  => array( 'id5_person' ),
				'hierarchical'=> true,
				'fields'      => array(
					array( 'key' => 'name_en', 'label' => 'ชื่อภาษาอังกฤษ (English name)', 'type' => 'text' ),
					array(
						'key'     => 'theme',
						'label'   => 'โทนสีของแผนผัง',
						'type'    => 'select',
						'default' => 'navy',
						// Must stay in step with App\Models\OrgChartSection::THEMES.
						'options' => array(
							'navy'      => 'น้ำเงินเข้ม (Navy)',
							'navy_gold' => 'น้ำเงิน-ทอง (Navy and gold)',
							'red_gold'  => 'แดง-ทอง (Red and gold)',
						),
					),
					array( 'key' => 'sort_order', 'label' => 'ลำดับการแสดงผล', 'type' => 'number', 'default' => 0, 'help' => 'ตัวเลขน้อยแสดงก่อน เช่น 1 อยู่ก่อน 2' ),
					array( 'key' => 'show_on_about_page', 'label' => 'แสดงในหน้า "เกี่ยวกับเรา"', 'type' => 'checkbox', 'default' => 1 ),
				),
			),
			'id5_ita_section'      => array(
				'label_th'    => 'หมวด ITA',
				'label_en'    => 'ITA sections',
				'singular'    => 'หมวด ITA',
				'post_types'  => array( 'id5_ita_card' ),
				'hierarchical'=> true,
				'fields'      => array(
					array( 'key' => 'section_number', 'label' => 'เลขหมวด (เช่น O1)', 'type' => 'text', 'help' => 'ตัวเลขในกรอบสีเหลืองหน้าชื่อหมวด เว้นว่างไว้ได้ ระบบจะใส่เลขถัดไปให้อัตโนมัติ' ),
					array( 'key' => 'title_en', 'label' => 'ชื่อภาษาอังกฤษ (English title)', 'type' => 'text' ),
					array( 'key' => 'description_th', 'label' => 'คำอธิบาย (ไทย)', 'type' => 'textarea' ),
					array( 'key' => 'description_en', 'label' => 'คำอธิบาย (English)', 'type' => 'textarea' ),
					array( 'key' => 'sort_order', 'label' => 'ลำดับการแสดงผล', 'type' => 'number', 'default' => 0, 'help' => 'ตัวเลขน้อยแสดงก่อน เช่น 1 อยู่ก่อน 2' ),
					array( 'key' => 'is_visible', 'label' => 'แสดงบนเว็บไซต์', 'type' => 'checkbox', 'default' => 1 ),
				),
			),
			'id5_region'           => array(
				'label_th'    => 'ภาค',
				'label_en'    => 'Regions',
				'singular'    => 'ภาค',
				'post_types'  => array( 'id5_office' ),
				'hierarchical'=> true,
				'fields'      => array(),
			),
			'id5_province'         => array(
				'label_th'    => 'จังหวัด',
				'label_en'    => 'Provinces',
				'singular'    => 'จังหวัด',
				'post_types'  => array( 'id5_office' ),
				'hierarchical'=> true,
				'fields'      => array(),
			),
		);

		return self::$taxonomies;
	}

	/**
	 * Every post type this plugin manages, including the native `post` type.
	 */
	public static function types() {
		if ( null !== self::$types ) {
			return self::$types;
		}

		$personas   = self::personas();
		$dimensions = self::dimensions();

		self::$types = array(

			// ── News ────────────────────────────────────────────────────────
			// Uses WordPress's own post type so the most frequent task stays
			// exactly where an administrator already expects to find it.
			'post' => array(
				'native'        => true,
				'label_th'      => 'ข่าวสาร',
				'label_en'      => 'News',
				'singular'      => 'ข่าว',
				'title_label'   => 'หัวข้อข่าว (ภาษาไทย)',
				'laravel'       => array( 'table' => 'articles', 'article_type' => 'news' ),
				'boxes'         => array(
					self::english_box( 'ข่าว' ),
					self::publishing_box(),
					self::seo_box(),
				),
			),

			// ── Announcements ───────────────────────────────────────────────
			'id5_announcement' => array(
				'label_th'      => 'ประกาศ',
				'label_en'      => 'Announcements',
				'singular'      => 'ประกาศ',
				'menu_icon'     => 'dashicons-megaphone',
				'menu_position' => 21,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author' ),
				'taxonomies'    => array( 'id5_content_category' ),
				'title_label'   => 'หัวข้อประกาศ (ภาษาไทย)',
				'laravel'       => array( 'table' => 'articles', 'article_type' => 'announcement' ),
				'boxes'         => array(
					self::english_box( 'ประกาศ' ),
					self::publishing_box(),
					self::seo_box(),
				),
			),

			// ── Alerts ──────────────────────────────────────────────────────
			'id5_alert' => array(
				'label_th'      => 'แจ้งเตือน',
				'label_en'      => 'Alerts',
				'singular'      => 'การแจ้งเตือน',
				'menu_icon'     => 'dashicons-warning',
				'menu_position' => 22,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author' ),
				'taxonomies'    => array( 'id5_content_category' ),
				'title_label'   => 'หัวข้อการแจ้งเตือน (ภาษาไทย)',
				'laravel'       => array( 'table' => 'articles', 'article_type' => 'alert' ),
				'boxes'         => array(
					self::english_box( 'การแจ้งเตือน' ),
					self::publishing_box( true ),
					self::seo_box(),
				),
			),

			// ── Knowledge base ──────────────────────────────────────────────
			'id5_knowledge' => array(
				'label_th'      => 'คลังความรู้',
				'label_en'      => 'Knowledge Base',
				'singular'      => 'บทความความรู้',
				'menu_icon'     => 'dashicons-book-alt',
				'menu_position' => 23,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author' ),
				'taxonomies'    => array( 'id5_content_category' ),
				'title_label'   => 'หัวข้อบทความ (ภาษาไทย)',
				'laravel'       => array( 'table' => 'articles', 'article_type' => 'kb_public' ),
				'boxes'         => array(
					self::english_box( 'บทความ' ),
					self::publishing_box(),
					self::seo_box(),
				),
			),

			// ── FAQ ─────────────────────────────────────────────────────────
			'id5_faq' => array(
				'label_th'      => 'คำถามที่พบบ่อย',
				'label_en'      => 'FAQ',
				'singular'      => 'คำถาม',
				'menu_icon'     => 'dashicons-editor-help',
				'menu_position' => 24,
				'supports'      => array( 'title', 'editor', 'page-attributes', 'revisions' ),
				'taxonomies'    => array( 'id5_content_category' ),
				'title_label'   => 'คำถาม (ภาษาไทย)',
				'editor_notice' => 'ช่องเนื้อหาด้านล่างคือ "คำตอบ" ภาษาไทย',
				'order_label'   => 'ลำดับการแสดงผล',
				'laravel'       => array( 'table' => 'faqs' ),
				'boxes'         => array(
					array(
						'id'     => 'english',
						'title'  => 'ภาษาอังกฤษ / English version',
						'fields' => array(
							array( 'key' => 'question_en', 'label' => 'คำถาม (English question)', 'type' => 'text' ),
							array( 'key' => 'answer_en', 'label' => 'คำตอบ (English answer)', 'type' => 'wysiwyg' ),
						),
					),
				),
			),

			// ── Downloadable forms ──────────────────────────────────────────
			'id5_form' => array(
				'label_th'      => 'แบบฟอร์มดาวน์โหลด',
				'label_en'      => 'Forms',
				'singular'      => 'แบบฟอร์ม',
				'menu_icon'     => 'dashicons-media-document',
				'menu_position' => 25,
				'supports'      => array( 'title', 'page-attributes', 'revisions' ),
				'taxonomies'    => array( 'id5_form_category' ),
				'title_label'   => 'ชื่อแบบฟอร์ม (ภาษาไทย)',
				'order_label'   => 'ลำดับการแสดงผล',
				'laravel'       => array( 'table' => 'public_forms' ),
				'boxes'         => array(
					array(
						'id'     => 'form',
						'title'  => 'รายละเอียดแบบฟอร์ม / Form details',
						'fields' => array(
							array( 'key' => 'code', 'label' => 'รหัสแบบฟอร์ม (เช่น TM30)', 'type' => 'text', 'required' => true, 'unique' => true, 'help' => 'รหัสที่พิมพ์อยู่บนแบบฟอร์ม ห้ามซ้ำกับแบบฟอร์มอื่น' ),
							array( 'key' => 'name_en', 'label' => 'ชื่อภาษาอังกฤษ (English name)', 'type' => 'text' ),
							array( 'key' => 'description_th', 'label' => 'คำอธิบาย (ไทย)', 'type' => 'textarea' ),
							array( 'key' => 'description_en', 'label' => 'คำอธิบาย (English)', 'type' => 'textarea' ),
							array( 'key' => 'version', 'label' => 'เวอร์ชัน', 'type' => 'text', 'default' => '1.0', 'help' => 'เปลี่ยนเลขเมื่ออัปโหลดแบบฟอร์มฉบับปรับปรุง เช่น 1.0 เป็น 1.1' ),
							array( 'key' => 'file', 'label' => 'ไฟล์ PDF', 'type' => 'file', 'mime' => 'application/pdf', 'help' => 'อัปโหลดไฟล์แบบฟอร์มที่ต้องการให้ประชาชนดาวน์โหลด (เฉพาะไฟล์ PDF เท่านั้น)' ),
							array( 'key' => 'show_on_about_page', 'label' => 'แสดงในหน้า "เกี่ยวกับเรา" ด้วย', 'type' => 'checkbox' ),
							array( 'key' => 'is_active', 'label' => 'เปิดให้ดาวน์โหลด', 'type' => 'checkbox', 'default' => 1 ),
						),
					),
				),
			),

			// ── Immigration offices ─────────────────────────────────────────
			'id5_office' => array(
				'label_th'      => 'สำนักงานตรวจคนเข้าเมือง',
				'label_en'      => 'Offices',
				'singular'      => 'สำนักงาน',
				'menu_icon'     => 'dashicons-building',
				'menu_position' => 26,
				'supports'      => array( 'title', 'thumbnail', 'page-attributes', 'revisions' ),
				'taxonomies'    => array( 'id5_region', 'id5_province' ),
				'title_label'   => 'ชื่อสำนักงาน (ภาษาไทย)',
				'order_label'   => 'ลำดับการแสดงผล',
				'laravel'       => array( 'table' => 'stations' ),
				'boxes'         => array(
					array(
						'id'     => 'office',
						'title'  => 'ข้อมูลสำนักงาน / Office details',
						'fields' => array(
							array( 'key' => 'code', 'label' => 'รหัสสำนักงาน', 'type' => 'text', 'required' => true, 'unique' => true, 'help' => 'ตัวอักษรภาษาอังกฤษและตัวเลข เช่น CM01 ห้ามซ้ำกับสำนักงานอื่น' ),
							array( 'key' => 'name_en', 'label' => 'ชื่อภาษาอังกฤษ (English name)', 'type' => 'text' ),
							array( 'key' => 'address_th', 'label' => 'ที่อยู่ (ไทย)', 'type' => 'textarea' ),
							array( 'key' => 'address_en', 'label' => 'ที่อยู่ (English)', 'type' => 'textarea' ),
							array( 'key' => 'phone', 'label' => 'โทรศัพท์', 'type' => 'text' ),
							array( 'key' => 'email', 'label' => 'อีเมล', 'type' => 'email' ),
							array( 'key' => 'parent_organization', 'label' => 'สังกัด', 'type' => 'text' ),
							array( 'key' => 'is_active', 'label' => 'แสดงบนเว็บไซต์', 'type' => 'checkbox', 'default' => 1 ),
						),
					),
					array(
						'id'     => 'office_links',
						'title'  => 'ช่องทางออนไลน์ / Online channels',
						'fields' => array(
							array( 'key' => 'website', 'label' => 'เว็บไซต์', 'type' => 'url' ),
							array( 'key' => 'facebook_url', 'label' => 'Facebook', 'type' => 'url' ),
							array( 'key' => 'line_url', 'label' => 'LINE', 'type' => 'url' ),
							array( 'key' => 'google_maps_url', 'label' => 'ลิงก์ Google Maps', 'type' => 'url', 'help' => 'เปิด Google Maps ค้นหาสำนักงาน กด “แชร์” แล้วคัดลอกลิงก์มาวาง' ),
							array( 'key' => 'online_services_available', 'label' => 'มีบริการออนไลน์', 'type' => 'checkbox' ),
						),
					),
					array(
						'id'     => 'office_map',
						'title'  => 'ตำแหน่งบนแผนที่ / Map location',
						'help'   => 'เว้นว่างไว้ได้ หากเว้นว่าง ตำแหน่งเดิมบนเว็บไซต์จะไม่ถูกเปลี่ยน',
						'fields' => array(
							array( 'key' => 'lat', 'label' => 'ละติจูด (Latitude)', 'type' => 'text', 'placeholder' => '18.7883', 'help' => 'ใช้ปักหมุดบนแผนที่: ใน Google Maps คลิกขวาที่ตำแหน่งสำนักงาน ตัวเลขชุดแรกคือละติจูด' ),
							array( 'key' => 'lng', 'label' => 'ลองจิจูด (Longitude)', 'type' => 'text', 'placeholder' => '98.9853', 'help' => 'ตัวเลขชุดที่สองจาก Google Maps' ),
							array( 'key' => 'last_verified_at', 'label' => 'ตรวจสอบข้อมูลล่าสุดเมื่อ', 'type' => 'date', 'help' => 'วันที่ท่านตรวจสอบว่าข้อมูลสำนักงานนี้ยังถูกต้อง จะแสดงให้ผู้เข้าชมเห็น' ),
						),
					),
					array(
						'id'     => 'office_hours',
						'title'  => 'เวลาทำการ / Opening hours',
						'help'   => 'เว้นว่างวันใดไว้ หมายถึงปิดทำการในวันนั้น',
						'fields' => array(
							array( 'key' => 'hours_mon', 'label' => 'จันทร์', 'type' => 'text', 'placeholder' => '08:30-16:30', 'help' => 'พิมพ์เวลาเปิด–ปิดในรูปแบบ 08:30-16:30 วันใดปิดทำการให้เว้นว่างไว้' ),
							array( 'key' => 'hours_tue', 'label' => 'อังคาร', 'type' => 'text', 'placeholder' => '08:30-16:30' ),
							array( 'key' => 'hours_wed', 'label' => 'พุธ', 'type' => 'text', 'placeholder' => '08:30-16:30' ),
							array( 'key' => 'hours_thu', 'label' => 'พฤหัสบดี', 'type' => 'text', 'placeholder' => '08:30-16:30' ),
							array( 'key' => 'hours_fri', 'label' => 'ศุกร์', 'type' => 'text', 'placeholder' => '08:30-16:30' ),
							array( 'key' => 'hours_sat', 'label' => 'เสาร์', 'type' => 'text', 'placeholder' => 'เว้นว่าง = ปิดทำการ' ),
							array( 'key' => 'hours_sun', 'label' => 'อาทิตย์', 'type' => 'text', 'placeholder' => 'เว้นว่าง = ปิดทำการ' ),
						),
					),
				),
			),

			// ── Organisation chart people ───────────────────────────────────
			'id5_person' => array(
				'label_th'      => 'บุคลากร',
				'label_en'      => 'People',
				'singular'      => 'บุคลากร',
				'menu_icon'     => 'dashicons-groups',
				'menu_position' => 27,
				'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
				'taxonomies'    => array( 'id5_org_section' ),
				'required_taxonomies' => array( 'id5_org_section' ),
				'title_label'   => 'ชื่อ-นามสกุล (ภาษาไทย)',
				'order_label'   => 'ลำดับในแถว',
				'laravel'       => array( 'table' => 'org_chart_members' ),
				'boxes'         => array(
					array(
						'id'     => 'person',
						'title'  => 'ข้อมูลบุคลากร / Person details',
						'fields' => array(
							array( 'key' => 'rank_th', 'label' => 'ยศ', 'type' => 'text' ),
							array( 'key' => 'position_th', 'label' => 'ตำแหน่ง', 'type' => 'text' ),
							array( 'key' => 'name_en', 'label' => 'ชื่อภาษาอังกฤษ (English name)', 'type' => 'text' ),
						),
					),
					array(
						'id'     => 'person_tier',
						'title'  => 'ตำแหน่งในแผนผัง / Position in the chart',
						'help'   => 'เลือกแถวที่ต้องการให้บุคลากรท่านนี้อยู่ วิธีที่ง่ายที่สุดคือเปิด “แก้ไขเว็บไซต์” ไปที่หน้าแผนผัง แล้วกดช่อง “+ เพิ่มบุคลากรในแถวนี้” ท้ายแถวที่ต้องการ ระบบจะกรอกส่วนนี้ให้เอง',
						'fields' => array(
							array( 'key' => 'tier_label_th', 'label' => 'ชื่อแถว (ไทย)', 'type' => 'text', 'required' => true, 'placeholder' => 'คลิกเพื่อเลือกแถวที่มีอยู่ หรือพิมพ์ชื่อแถวใหม่', 'suggest' => 'tiers', 'help' => 'คลิกในช่องแล้วเลือกจากรายการแถวที่มีอยู่ บุคลากรที่ใช้ชื่อแถวเดียวกันจะอยู่แถวเดียวกัน หากพิมพ์ชื่อใหม่จะเป็นการสร้างแถวใหม่' ),
							array( 'key' => 'tier_label_en', 'label' => 'ชื่อแถว (English)', 'type' => 'text' ),
							array( 'key' => 'tier_order', 'label' => 'ลำดับของแถว', 'type' => 'number', 'default' => 0, 'help' => 'แถวที่มีตัวเลขน้อยกว่าจะอยู่ด้านบน' ),
						),
					),
				),
			),

			// ── ITA items ───────────────────────────────────────────────────
			'id5_ita_card' => array(
				'label_th'      => 'หัวข้อ ITA',
				'label_en'      => 'ITA Items',
				'singular'      => 'หัวข้อ ITA',
				'menu_icon'     => 'dashicons-clipboard',
				'menu_position' => 28,
				// No rich editor: the public ITA page prints this text escaped, so
				// any formatting typed here would show up as literal tags. Plain
				// text boxes make the screen match what the page can display.
				'supports'      => array( 'title', 'page-attributes', 'revisions' ),
				'taxonomies'    => array( 'id5_ita_section' ),
				'required_taxonomies' => array( 'id5_ita_section' ),
				'title_label'   => 'ชื่อหัวข้อ (ภาษาไทย)',
				'order_label'   => 'ลำดับการแสดงผล',
				'laravel'       => array( 'table' => 'ita_cards' ),
				'boxes'         => array(
					array(
						'id'     => 'ita',
						'title'  => 'รายละเอียดหัวข้อ / Item details',
						'fields' => array(
							array( 'key' => 'card_number', 'label' => 'เลขหัวข้อ (เช่น O1)', 'type' => 'text', 'help' => 'ตัวเลขในกรอบสีหน้าชื่อหัวข้อ เว้นว่างไว้ได้ ระบบจะใส่เลขถัดไปของหมวดนั้นให้อัตโนมัติ' ),
							array( 'key' => 'title_en', 'label' => 'ชื่อภาษาอังกฤษ (English title)', 'type' => 'text' ),
							array( 'key' => 'content_th', 'label' => 'เนื้อหา (ไทย)', 'type' => 'textarea', 'help' => 'ข้อความธรรมดา ไม่รองรับตัวหนา ลิงก์ หรือรายการ' ),
							array( 'key' => 'content_en', 'label' => 'เนื้อหา (English)', 'type' => 'textarea' ),
							array( 'key' => 'icon_image', 'label' => 'รูปไอคอน (อัปโหลดรูปของท่านเอง)', 'type' => 'image', 'help' => 'ไม่บังคับ — ถ้าไม่อัปโหลด เว็บไซต์จะเลือกไอคอนที่เข้ากับชื่อหัวข้อให้อัตโนมัติ แนะนำรูปสี่เหลี่ยมจัตุรัส พื้นหลังโปร่งใส (ไฟล์ PNG)' ),
							array( 'key' => 'icon', 'label' => 'ชื่อไอคอน (ไม่บังคับ)', 'type' => 'text', 'placeholder' => 'เว้นว่างไว้ = เลือกให้อัตโนมัติ', 'help' => 'ปกติเว้นว่างไว้ได้ เว็บไซต์จะเลือกไอคอนจากชื่อหัวข้อ เช่น “งบประมาณ” ได้รูปเหรียญ หากต้องการกำหนดเอง ให้พิมพ์ชื่อไอคอนจาก tabler.io/icons เช่น file-text (ใช้เมื่อไม่ได้อัปโหลดรูป)' ),
							array( 'key' => 'color', 'label' => 'สีของกรอบเลขหัวข้อ', 'type' => 'color', 'default' => '#4F46E5', 'help' => 'คลิกที่ช่องสีเพื่อเลือกสี' ),
							array( 'key' => 'is_visible', 'label' => 'แสดงบนเว็บไซต์', 'type' => 'checkbox', 'default' => 1 ),
						),
					),
				),
			),

			// ── Homepage cards ──────────────────────────────────────────────
			'id5_division_card' => array(
				'label_th'       => 'การ์ดหน้าแรก',
				'label_en'       => 'Homepage Cards',
				'singular'       => 'การ์ดหน้าแรก',
				'menu_icon'      => 'dashicons-grid-view',
				'menu_position'  => 29,
				'supports'       => array( 'title', 'thumbnail', 'page-attributes' ),
				'title_label'    => 'หัวข้อบนการ์ด (ภาษาไทย)',
				'order_label'    => 'ลำดับการแสดงผล',
				// Fixed set: the six cards map to real routes on the public site.
				'fixed_set'      => true,
				'laravel'        => array( 'table' => 'home_division_cards' ),
				'boxes'          => array(
					array(
						'id'     => 'card',
						'title'  => 'เนื้อหาบนการ์ด / Card content',
						'help'   => 'รูปภาพของการ์ดใช้ "ภาพประจำเรื่อง" (Featured image) ทางด้านขวา',
						'fields' => array(
							array(
								'key'      => 'card_key',
								'label'    => 'การ์ดนี้คือ',
								'type'     => 'select',
								'options'  => array(), // filled below
								'required' => true,
								'unique'   => true,
								'help'     => 'กำหนดว่าการ์ดนี้ลิงก์ไปหน้าใด เปลี่ยนค่านี้เฉพาะเมื่อจำเป็นจริง ๆ',
							),
							array( 'key' => 'title_en', 'label' => 'หัวข้อภาษาอังกฤษ (English title)', 'type' => 'text' ),
							array( 'key' => 'desc_th', 'label' => 'คำอธิบายสั้น (ไทย)', 'type' => 'text' ),
							array( 'key' => 'desc_en', 'label' => 'คำอธิบายสั้น (English)', 'type' => 'text' ),
						),
					),
				),
			),

			// ── Smart Guide topics ──────────────────────────────────────────
			'id5_guide_topic' => array(
				'label_th'      => 'หัวข้อคู่มือ',
				'label_en'      => 'Guide Topics',
				'singular'      => 'หัวข้อคู่มือ',
				'menu_icon'     => 'dashicons-list-view',
				'menu_position' => 30,
				'supports'      => array( 'title', 'page-attributes' ),
				'title_label'   => 'ชื่อหัวข้อคู่มือ (ภาษาไทย)',
				'order_label'   => 'ลำดับการแสดงผล',
				'fixed_set'     => true,
				'laravel'       => array( 'table' => 'guide_topics' ),
				'boxes'         => array(
					array(
						'id'     => 'topic',
						'title'  => 'ตั้งค่าหัวข้อ / Topic settings',
						'fields' => array(
							array( 'key' => 'topic_key', 'label' => 'หัวข้อนี้คือ', 'type' => 'select', 'options' => array(), 'required' => true, 'unique' => true ),
							array( 'key' => 'icon', 'label' => 'ชื่อไอคอน', 'type' => 'text', 'help' => 'ชื่อไอคอนจาก tabler.io/icons เช่น passport หรือ file-text หากพิมพ์ชื่อที่ไม่มีอยู่จริง เว็บไซต์จะใช้ไอคอนเดิมต่อไป' ),
							array(
								'key'     => 'color',
								'label'   => 'โทนสี',
								'type'    => 'select',
								'options' => array(
									'blue'   => 'น้ำเงิน',
									'cyan'   => 'ฟ้า',
									'teal'   => 'เขียวน้ำทะเล',
									'green'  => 'เขียว',
									'indigo' => 'คราม',
									'purple' => 'ม่วง',
									'pink'   => 'ชมพู',
									'red'    => 'แดง',
									'orange' => 'ส้ม',
									'amber'  => 'เหลืองอำพัน',
									'slate'  => 'เทา',
								),
								'help'    => 'สีของไอคอนและพื้นหลังของหัวข้อนี้บนเว็บไซต์',
							),
							array( 'key' => 'deadline_days', 'label' => 'กำหนดเวลา (จำนวนวัน)', 'type' => 'number', 'help' => 'เว้นว่างหากไม่มีกำหนด' ),
							array( 'key' => 'is_urgent', 'label' => 'ทำเครื่องหมายว่าเร่งด่วน', 'type' => 'checkbox' ),
							array( 'key' => 'search_keywords', 'label' => 'คำค้นหาที่เกี่ยวข้อง', 'type' => 'textarea', 'help' => 'คั่นแต่ละคำด้วยเครื่องหมายจุลภาค' ),
							array( 'key' => 'is_active', 'label' => 'แสดงบนเว็บไซต์', 'type' => 'checkbox', 'default' => 1 ),
						),
					),
					array(
						'id'     => 'documents',
						'title'  => 'เอกสารที่ต้องเตรียม / Documents needed',
						'fields' => array(
							array(
								'key'        => 'documents',
								'label'      => '',
								'type'       => 'repeater',
								'item_label' => 'เอกสาร',
								'fields'     => array(
									array( 'key' => 'name_th', 'label' => 'ชื่อเอกสาร (ไทย)', 'type' => 'text' ),
									array( 'key' => 'name_en', 'label' => 'ชื่อเอกสาร (English)', 'type' => 'text' ),
									array( 'key' => 'form_code', 'label' => 'รหัสแบบฟอร์มที่เกี่ยวข้อง', 'type' => 'text', 'help' => 'เช่น TM30 เว้นว่างได้' ),
									array( 'key' => 'is_required', 'label' => 'จำเป็นต้องมี', 'type' => 'checkbox', 'default' => 1 ),
								),
							),
						),
					),
					array(
						'id'     => 'steps',
						'title'  => 'ขั้นตอนการดำเนินการ / Steps',
						'fields' => array(
							array(
								'key'        => 'steps',
								'label'      => '',
								'type'       => 'repeater',
								'item_label' => 'ขั้นตอน',
								'fields'     => array(
									array( 'key' => 'title_th', 'label' => 'ชื่อขั้นตอน (ไทย)', 'type' => 'text' ),
									array( 'key' => 'title_en', 'label' => 'ชื่อขั้นตอน (English)', 'type' => 'text' ),
									array( 'key' => 'description_th', 'label' => 'รายละเอียด (ไทย)', 'type' => 'textarea' ),
									array( 'key' => 'description_en', 'label' => 'รายละเอียด (English)', 'type' => 'textarea' ),
								),
							),
						),
					),
					array(
						'id'     => 'locations',
						'title'  => 'สถานที่ดำเนินการ / Where to go',
						'fields' => array(
							array(
								'key'        => 'locations',
								'label'      => '',
								'type'       => 'repeater',
								'item_label' => 'สถานที่',
								'fields'     => array(
									array(
										'key'     => 'type',
										'label'   => 'ประเภท',
										'type'    => 'select',
										'default' => 'office',
										'options' => array(
											'office'  => 'สำนักงาน',
											'online'  => 'ออนไลน์',
											'embassy' => 'สถานทูต/กงสุล',
											'other'   => 'อื่น ๆ',
										),
									),
									array( 'key' => 'label_th', 'label' => 'ชื่อสถานที่ (ไทย)', 'type' => 'text' ),
									array( 'key' => 'label_en', 'label' => 'ชื่อสถานที่ (English)', 'type' => 'text' ),
									array( 'key' => 'url', 'label' => 'ลิงก์', 'type' => 'url' ),
									array( 'key' => 'office_code', 'label' => 'รหัสสำนักงานที่เกี่ยวข้อง', 'type' => 'text', 'help' => 'เว้นว่างได้' ),
								),
							),
						),
					),
				),
			),

			// ── Smart Guide audience guidance ────────────────────────────────
			// One entry per "audience + topic" pair, holding the six Who / What /
			// Why / Where / When / How answers. These are formatted text (bold,
			// bullet lists), so each gets a real editor rather than a plain box.
			'id5_guide_content' => array(
				'label_th'      => 'เนื้อหาคู่มือ',
				'label_en'      => 'Guide Content',
				'singular'      => 'เนื้อหาคู่มือ',
				'menu_icon'     => 'dashicons-welcome-learn-more',
				'menu_position' => 31,
				'supports'      => array( 'title', 'revisions' ),
				'title_label'   => 'ชื่อเรียก เช่น นักท่องเที่ยว — วีซ่า',
				'laravel'       => array( 'table' => 'smart_guides' ),
				// One entry per audience + topic; a second would fight the first.
				'unique_together' => array( 'persona', 'topic_key' ),
				'boxes'         => array(
					array(
						'id'     => 'guide_target',
						'title'  => 'เนื้อหานี้สำหรับใคร / Who this guidance is for',
						'help'   => 'หนึ่งรายการต่อหนึ่ง "กลุ่มผู้ใช้ + หัวข้อ" เท่านั้น หากมีอยู่แล้วให้แก้ไขรายการเดิม อย่าสร้างซ้ำ',
						'fields' => array(
							array( 'key' => 'persona', 'label' => 'กลุ่มผู้ใช้', 'type' => 'select', 'options' => $personas, 'required' => true ),
							array( 'key' => 'topic_key', 'label' => 'หัวข้อคู่มือ', 'type' => 'select', 'options' => array(), 'required' => true ),
						),
					),
					array(
						'id'     => 'guide_th',
						'title'  => 'คำอธิบาย (ภาษาไทย)',
						'help'   => 'เว้นช่องใดว่างไว้ หัวข้อนั้นจะไม่แสดงบนเว็บไซต์',
						'fields' => self::dimension_fields( 'th' ),
					),
					array(
						'id'     => 'guide_en',
						'title'  => 'คำอธิบาย (English)',
						'help'   => 'เว้นว่างได้ เว็บไซต์จะแสดงฉบับภาษาไทยแทน',
						'fields' => self::dimension_fields( 'en' ),
					),
				),
			),

			// ── Assessment results ──────────────────────────────────────────
			// ── Free-form content, built from blocks ──────────────────────────
			// The two types an editor composes themselves: rows of columns holding
			// text, headings, pictures, PDF documents and buttons, in WordPress's
			// own block editor. Everything else on the website keeps its fixed
			// design; these add to it without being able to break it.
			'id5_section' => array(
				'label_th'      => 'ส่วนเนื้อหาเพิ่มเติม',
				'label_en'      => 'Content sections',
				'singular'      => 'ส่วนเนื้อหา',
				'menu_icon'     => 'dashicons-layout',
				'menu_position' => 20,
				'supports'      => array( 'title', 'editor', 'page-attributes', 'revisions' ),
				'block_editor'  => true,
				'title_label'   => 'ชื่อส่วนนี้ (ใช้เรียกในระบบเท่านั้น ไม่แสดงบนเว็บไซต์)',
				'editor_notice' => 'สร้างเนื้อหาด้วยบล็อก: กดปุ่ม + เพื่อเพิ่ม "คอลัมน์" (แถวที่แบ่งเป็นช่อง) "ข้อความ" "หัวข้อ" "รูปภาพ" "ไฟล์" (PDF) หรือ "ปุ่ม" แล้วเลือกตำแหน่งที่จะแสดงบนเว็บไซต์ในกล่องด้านล่าง',
				'order_label'   => 'ลำดับ (เมื่อมีหลายส่วนในตำแหน่งเดียวกัน ตัวเลขน้อยอยู่บน)',
				'laravel'       => array( 'table' => 'page_sections', 'kind' => 'section' ),
				'boxes'         => array(
					array(
						'id'     => 'placement',
						'title'  => 'แสดงที่ไหนบนเว็บไซต์',
						'fields' => array(
							array( 'key' => 'page', 'label' => 'หน้า', 'type' => 'select', 'options' => self::section_pages(), 'default' => '/', 'help' => 'หน้าของเว็บไซต์ที่จะแสดงส่วนนี้' ),
							array( 'key' => 'position', 'label' => 'ตำแหน่งในหน้า', 'type' => 'select', 'options' => self::section_positions(), 'default' => 'bottom', 'help' => 'ตำแหน่งระหว่างส่วนต่าง ๆ ใช้ได้กับหน้าแรก หน้าอื่นจะแสดงที่ท้ายหน้า' ),
							array( 'key' => 'language', 'label' => 'แสดงเมื่อผู้เข้าชมเลือกภาษา', 'type' => 'select', 'options' => array( 'both' => 'ทั้งภาษาไทยและอังกฤษ', 'th' => 'ภาษาไทยเท่านั้น', 'en' => 'ภาษาอังกฤษเท่านั้น' ), 'default' => 'both', 'help' => 'ต้องการเนื้อหาสองภาษา ให้สร้างสองส่วน: ส่วนหนึ่ง "ภาษาไทยเท่านั้น" อีกส่วน "ภาษาอังกฤษเท่านั้น"' ),
							array( 'key' => 'background', 'label' => 'พื้นหลัง', 'type' => 'select', 'options' => array( 'none' => 'ไม่มี (สีพื้นของหน้า)', 'white' => 'สีขาว', 'light' => 'สีฟ้าอ่อน', 'brand' => 'สีน้ำเงิน (ตัวอักษรสีขาว)' ), 'default' => 'none' ),
						),
					),
				),
			),

			'id5_page' => array(
				'label_th'      => 'หน้าเว็บเพิ่มเติม',
				'label_en'      => 'Pages',
				'singular'      => 'หน้าเว็บ',
				'menu_icon'     => 'dashicons-media-document',
				'menu_position' => 19,
				'supports'      => array( 'title', 'editor', 'excerpt', 'revisions' ),
				'block_editor'  => true,
				'nav_menus'     => true,
				'title_label'   => 'ชื่อหน้า (ภาษาไทย) — แสดงเป็นหัวข้อใหญ่ของหน้า',
				'editor_notice' => 'หน้าใหม่ของเว็บไซต์ สร้างด้วยบล็อกแบบเดียวกับ "ส่วนเนื้อหาเพิ่มเติม" เมื่อเผยแพร่แล้ว เพิ่มลงในเมนูได้ที่ เมนูนำทาง → หน้าเว็บเพิ่มเติม',
				'laravel'       => array( 'table' => 'page_sections', 'kind' => 'page' ),
				'boxes'         => array(
					array(
						'id'     => 'english',
						'title'  => 'ภาษาอังกฤษ / English version',
						'fields' => array(
							array( 'key' => 'title_en', 'label' => 'ชื่อหน้า (English title)', 'type' => 'text', 'help' => 'เนื้อหาของหน้าแสดงเหมือนกันทั้งสองภาษา เว้นว่างไว้ได้ ระบบจะใช้ชื่อภาษาไทย' ),
						),
					),
				),
			),

			'id5_assessment' => array(
				'label_th'      => 'ผลการประเมิน',
				'label_en'      => 'Assessment Results',
				'singular'      => 'ผลการประเมิน',
				'menu_icon'     => 'dashicons-yes-alt',
				'menu_position' => 32,
				'supports'      => array( 'title', 'page-attributes' ),
				'title_label'   => 'ชื่อผลการประเมิน (ภาษาไทย)',
				'order_label'   => 'ลำดับการแสดงผล',
				'fixed_set'     => true,
				'laravel'       => array( 'table' => 'assessment_recommendations' ),
				'boxes'         => array(
					array(
						'id'     => 'assessment',
						'title'  => 'ตั้งค่าผลการประเมิน / Result settings',
						'fields' => array(
							array( 'key' => 'status_key', 'label' => 'ผลนี้ใช้กับสถานะ', 'type' => 'select', 'options' => array(), 'required' => true, 'unique' => true ),
							array(
								'key'     => 'risk_level',
								'label'   => 'ระดับความสำคัญ',
								'type'    => 'select',
								'default' => 'info',
								'options' => array(
									'info'   => 'ข้อมูลทั่วไป (สีน้ำเงิน)',
									'low'    => 'ความเสี่ยงต่ำ (สีเขียว)',
									'medium' => 'ความเสี่ยงปานกลาง (สีเหลือง)',
									'high'   => 'ความเสี่ยงสูง (สีแดง)',
								),
							),
							array( 'key' => 'suggested_persona', 'label' => 'แนะนำคู่มือสำหรับกลุ่ม', 'type' => 'select', 'options' => array( '' => 'ใช้ค่าเริ่มต้น' ) + $personas ),
							array( 'key' => 'suggested_topics', 'label' => 'แนะนำหัวข้อคู่มือ', 'type' => 'text', 'help' => 'คั่นแต่ละหัวข้อด้วยจุลภาค เช่น visa, tm30 เว้นว่างเพื่อใช้ค่าเริ่มต้น' ),
							array( 'key' => 'is_active', 'label' => 'ใช้งานผลนี้', 'type' => 'checkbox', 'default' => 1 ),
						),
					),
					array(
						'id'     => 'assessment_th',
						'title'  => 'เนื้อหาผลการประเมิน (ภาษาไทย)',
						'fields' => array(
							array( 'key' => 'legal_basis_th', 'label' => 'ฐานทางกฎหมาย', 'type' => 'textarea' ),
							array( 'key' => 'validity_note_th', 'label' => 'ระยะเวลาที่อนุญาต', 'type' => 'textarea' ),
							array( 'key' => 'allowed_activities_th', 'label' => 'สิ่งที่ทำได้', 'type' => 'textarea' ),
							array( 'key' => 'restricted_activities_th', 'label' => 'สิ่งที่ทำไม่ได้', 'type' => 'textarea' ),
							array( 'key' => 'deadlines_th', 'label' => 'กำหนดเวลาที่ต้องดำเนินการ', 'type' => 'textarea' ),
							array( 'key' => 'responsible_agency_th', 'label' => 'หน่วยงานที่รับผิดชอบ', 'type' => 'textarea' ),
							array( 'key' => 'important_notices_th', 'label' => 'ข้อควรระวัง', 'type' => 'textarea' ),
						),
					),
					array(
						'id'     => 'assessment_en',
						'title'  => 'เนื้อหาผลการประเมิน (English)',
						'fields' => array(
							array( 'key' => 'legal_basis_en', 'label' => 'Legal basis', 'type' => 'textarea' ),
							array( 'key' => 'validity_note_en', 'label' => 'Period of stay', 'type' => 'textarea' ),
							array( 'key' => 'allowed_activities_en', 'label' => 'What you may do', 'type' => 'textarea' ),
							array( 'key' => 'restricted_activities_en', 'label' => 'What you may not do', 'type' => 'textarea' ),
							array( 'key' => 'deadlines_en', 'label' => 'Deadlines', 'type' => 'textarea' ),
							array( 'key' => 'responsible_agency_en', 'label' => 'Responsible agency', 'type' => 'textarea' ),
							array( 'key' => 'important_notices_en', 'label' => 'Important notices', 'type' => 'textarea' ),
						),
					),
				),
			),
		);

		// Fill the option lists that depend on the helper tables above.
		self::set_option_list( 'id5_division_card', 'card', 'card_key', self::division_card_keys() );
		self::set_option_list( 'id5_guide_topic', 'topic', 'topic_key', self::topics() );
		self::set_option_list( 'id5_guide_content', 'guide_target', 'topic_key', self::topics() );
		self::set_option_list( 'id5_assessment', 'assessment', 'status_key', self::assessment_statuses() );

		return self::$types;
	}

	/**
	 * Inject an options list into one already-declared select field.
	 */
	private static function set_option_list( $type, $box_id, $field_key, $options ) {
		foreach ( self::$types[ $type ]['boxes'] as $bi => $box ) {
			if ( $box['id'] !== $box_id ) {
				continue;
			}
			foreach ( $box['fields'] as $fi => $field ) {
				if ( $field['key'] === $field_key ) {
					self::$types[ $type ]['boxes'][ $bi ]['fields'][ $fi ]['options'] = $options;
				}
			}
		}
	}

	/**
	 * The pages of the website a content section can be placed on: address =>
	 * name. Pages made in WordPress ("หน้าเว็บเพิ่มเติม") are not listed: they
	 * are built from blocks already.
	 */
	public static function section_pages() {
		return array(
			'/'                               => 'หน้าแรก',
			'/about'                          => 'เกี่ยวกับเรา (แผนผังองค์กร)',
			'/administrative-affairs-division' => 'ฝ่ายอำนวยการ',
			'/foreigner-services-division'    => 'ฝ่ายบริการคนต่างด้าว',
			'/investigation-division'         => 'ฝ่ายสืบสวนสอบสวน',
			'/technology-division'            => 'ฝ่ายเทคโนโลยีตรวจคนเข้าเมือง',
			'/ita'                            => 'ITA',
			'/news'                           => 'ข่าวสาร',
			'/announcements'                  => 'ประกาศ',
			'/alerts'                         => 'การแจ้งเตือน',
			'/knowledge'                      => 'คลังความรู้',
			'/faq'                            => 'คำถามที่พบบ่อย',
			'/services'                       => 'บริการ',
			'/services/visa'                  => 'บริการ: วีซ่า',
			'/services/tm30'                  => 'บริการ: ตม.30',
			'/services/90-day'                => 'บริการ: รายงานตัว 90 วัน',
			'/services/extension'            => 'บริการ: ขออยู่ต่อ',
			'/services/overstay'              => 'บริการ: อยู่เกินกำหนด',
			'/smart-guide'                    => 'คู่มือการใช้บริการ',
			'/downloads'                      => 'ดาวน์โหลดแบบฟอร์ม',
			'/offices'                        => 'สำนักงาน',
			'/contact'                        => 'ติดต่อเรา',
			'/emergency'                      => 'เหตุฉุกเฉิน',
			'/open-data'                      => 'ข้อมูลเปิด',
			'/assessment'                     => 'ประเมินสถานะเบื้องต้น',
		);
	}

	/** Where on a page a section can sit: key => name. */
	public static function section_positions() {
		return array(
			'bottom'         => 'ท้ายหน้า (ก่อนส่วนท้ายของเว็บไซต์)',
			'after_hero'     => 'หน้าแรก: ใต้แบนเนอร์ใหญ่',
			'after_services' => 'หน้าแรก: ใต้การ์ดบริการ 6 ใบ',
			'after_news'     => 'หน้าแรก: ใต้ข่าวสาร ประกาศ และแจ้งเตือน',
		);
	}

	/**
	 * One post type's definition, or null when it is not ours.
	 */
	public static function type( $post_type ) {
		$types = self::types();

		return isset( $types[ $post_type ] ) ? $types[ $post_type ] : null;
	}

	/**
	 * Flat list of every field in a post type, repeaters included (as one entry).
	 */
	public static function fields_for( $post_type ) {
		$type = self::type( $post_type );

		if ( ! $type ) {
			return array();
		}

		$fields = array();

		foreach ( $type['boxes'] as $box ) {
			foreach ( $box['fields'] as $field ) {
				$fields[ $field['key'] ] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Post types this plugin manages, excluding the native `post`.
	 */
	public static function custom_type_names() {
		return array_keys( array_filter( self::types(), function ( $type ) {
			return empty( $type['native'] );
		} ) );
	}

	/**
	 * Every post type the sync covers, native `post` included.
	 */
	public static function all_type_names() {
		return array_keys( self::types() );
	}
}
