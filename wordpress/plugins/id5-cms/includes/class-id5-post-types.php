<?php
/**
 * Registers the post types and taxonomies described by ID5_Schema.
 *
 * Everything is registered as `public => false` with `show_ui => true`: this
 * WordPress install is an editing surface, not a website. Visitors read the
 * Laravel site, so there is no reason for WordPress to serve single-post pages,
 * archives or feeds, and not serving them avoids duplicate public URLs.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

class ID5_Post_Types {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 5 );
		add_filter( 'enter_title_here', array( __CLASS__, 'title_placeholder' ), 10, 2 );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'use_classic_editor' ), 10, 2 );
		add_filter( 'post_type_labels_post', array( __CLASS__, 'news_labels' ) );
		add_filter( 'page_attributes_dropdown_pages_args', array( __CLASS__, 'hide_parent_dropdown' ), 10, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_front_end' ) );
	}

	/**
	 * Register every taxonomy, then every post type.
	 */
	public static function register() {
		foreach ( ID5_Schema::taxonomies() as $name => $tax ) {
			// WordPress registers its own taxonomies; ours only adds fields.
			if ( ! empty( $tax['native'] ) ) {
				continue;
			}

			register_taxonomy(
				$name,
				$tax['post_types'],
				array(
					'labels'            => array(
						'name'          => $tax['label_th'],
						'singular_name' => $tax['singular'],
						'menu_name'     => $tax['label_th'],
						'all_items'     => 'ทั้งหมด',
						'edit_item'     => 'แก้ไข' . $tax['singular'],
						'add_new_item'  => 'เพิ่ม' . $tax['singular'],
						'search_items'  => 'ค้นหา',
					),
					'public'            => false,
					'show_ui'           => true,
					'show_in_menu'      => true,
					'show_in_rest'      => true,
					'show_admin_column' => true,
					'hierarchical'      => ! empty( $tax['hierarchical'] ),
					'rewrite'           => false,
				)
			);
		}

		foreach ( ID5_Schema::types() as $name => $type ) {
			if ( ! empty( $type['native'] ) ) {
				continue;
			}

			register_post_type(
				$name,
				array(
					'labels'              => self::labels( $type ),
					'public'              => false,
					'publicly_queryable'  => false,
					'exclude_from_search' => true,
					'show_ui'             => true,
					'show_in_menu'        => true,
					// Pages built in WordPress can be put in the navigation menus.
					'show_in_nav_menus'   => ! empty( $type['nav_menus'] ),
					'show_in_rest'        => true,
					'rest_base'           => $name,
					'menu_icon'           => isset( $type['menu_icon'] ) ? $type['menu_icon'] : 'dashicons-admin-post',
					'menu_position'       => isset( $type['menu_position'] ) ? $type['menu_position'] : 25,
					'supports'            => isset( $type['supports'] ) ? $type['supports'] : array( 'title', 'editor' ),
					'taxonomies'          => isset( $type['taxonomies'] ) ? $type['taxonomies'] : array(),
					'has_archive'         => false,
					'rewrite'             => false,
					'capability_type'     => 'post',
					'capabilities'        => self::capabilities( $type ),
					'map_meta_cap'        => true,
					'delete_with_user'    => false,
				)
			);
		}

		self::register_meta();
	}

	/**
	 * Capability overrides for one post type.
	 *
	 * Some content types are a fixed set the public website's code depends on
	 * existing exactly as they are: the six homepage cards, the Smart Guide
	 * topics and the assessment results. Editors may change their wording and
	 * images freely, but an extra one would silently overwrite a real one (they
	 * are matched by key) and a deleted one would leave a hole on a live page.
	 *
	 * So for those types, creating and deleting require `manage_options`, which
	 * only an administrator holds. Because this is declared on the post type
	 * itself, WordPress enforces it everywhere at once: the "Add new" button and
	 * menu entry disappear, the bulk and row "Trash" actions disappear, and the
	 * create screen refuses a direct visit.
	 */
	private static function capabilities( $type ) {
		if ( empty( $type['fixed_set'] ) ) {
			return array();
		}

		return array(
			'create_posts'           => 'manage_options',
			'delete_posts'           => 'manage_options',
			'delete_published_posts' => 'manage_options',
			'delete_others_posts'    => 'manage_options',
			'delete_private_posts'   => 'manage_options',
		);
	}

	/**
	 * Register every scalar field as post meta so the REST API and revisions see
	 * it. Repeaters are stored as one JSON string under the same key.
	 */
	private static function register_meta() {
		foreach ( ID5_Schema::types() as $post_type => $type ) {
			foreach ( ID5_Schema::fields_for( $post_type ) as $key => $field ) {
				register_post_meta(
					$post_type,
					'_id5_' . $key,
					array(
						'type'          => 'string',
						'single'        => true,
						'show_in_rest'  => false,
						'auth_callback' => function () {
							return current_user_can( 'edit_posts' );
						},
					)
				);
			}

			// Bookkeeping meta written by the Laravel migration command.
			foreach ( array( '_id5_legacy_id', '_id5_legacy_type' ) as $key ) {
				register_post_meta(
					$post_type,
					$key,
					array(
						'type'          => 'string',
						'single'        => true,
						'show_in_rest'  => false,
						'auth_callback' => function () {
							return current_user_can( 'edit_posts' );
						},
					)
				);
			}
		}
	}

	/**
	 * Build a complete Thai label set so no English WordPress string leaks into
	 * a screen an administrator uses every day.
	 */
	private static function labels( $type ) {
		$plural   = $type['label_th'];
		$singular = $type['singular'];

		return array(
			'name'                  => $plural,
			'singular_name'         => $singular,
			'menu_name'             => $plural,
			'all_items'             => $plural . 'ทั้งหมด',
			'add_new'               => 'เพิ่มใหม่',
			'add_new_item'          => 'เพิ่ม' . $singular . 'ใหม่',
			'edit_item'             => 'แก้ไข' . $singular,
			'new_item'              => $singular . 'ใหม่',
			'view_item'             => 'ดู' . $singular,
			'search_items'          => 'ค้นหา' . $plural,
			'not_found'             => 'ยังไม่มี' . $plural,
			'not_found_in_trash'    => 'ไม่มี' . $plural . 'ในถังขยะ',
			'featured_image'        => 'รูปภาพ',
			'set_featured_image'    => 'เลือกรูปภาพ',
			'remove_featured_image' => 'ลบรูปภาพ',
			'use_featured_image'    => 'ใช้เป็นรูปภาพ',
			'attributes'            => 'ลำดับการแสดงผล',
			'item_updated'          => $singular . 'ได้รับการบันทึกแล้ว',
			'item_published'        => $singular . 'เผยแพร่แล้ว',
		);
	}

	/**
	 * Use the classic editor for every content type the website reads.
	 *
	 * Two reasons, both about the public site rather than taste. The block
	 * editor produces layout markup (columns, buttons, galleries) that only
	 * looks right with WordPress's own block styles, which the public website
	 * does not load — so an editor could build something that looks fine here
	 * and broken there. The classic editor produces plain headings, paragraphs,
	 * lists, links and images, which is exactly what the website's article
	 * styles are written for. It also keeps the extra field boxes on one
	 * ordinary form with one Publish button, rather than saving them through a
	 * separate background request.
	 */
	public static function use_classic_editor( $use_block_editor, $post_type ) {
		$type = ID5_Schema::type( $post_type );

		if ( ! $type ) {
			return $use_block_editor;
		}

		// Except the free-form types, which are built from blocks on purpose
		// (see ID5_Blocks for what those blocks may be).
		return ! empty( $type['block_editor'] );
	}

	/**
	 * WordPress's built-in "Posts" are this site's news, so call them that.
	 */
	public static function news_labels( $labels ) {
		$labels->name               = 'ข่าวสาร';
		$labels->singular_name      = 'ข่าว';
		$labels->menu_name          = 'ข่าวสาร';
		$labels->name_admin_bar     = 'ข่าว';
		$labels->all_items          = 'ข่าวสารทั้งหมด';
		$labels->add_new            = 'เพิ่มข่าวใหม่';
		$labels->add_new_item       = 'เพิ่มข่าวใหม่';
		$labels->edit_item          = 'แก้ไขข่าว';
		$labels->new_item           = 'ข่าวใหม่';
		$labels->view_item          = 'ดูข่าว';
		$labels->search_items       = 'ค้นหาข่าวสาร';
		$labels->not_found          = 'ยังไม่มีข่าวสาร';
		$labels->not_found_in_trash = 'ไม่มีข่าวสารในถังขยะ';

		return $labels;
	}

	/**
	 * Replace "Add title" with a label that says what the title actually is on
	 * this screen, e.g. "คำถาม (ภาษาไทย)" on the FAQ editor.
	 */
	public static function title_placeholder( $text, $post ) {
		$type = ID5_Schema::type( $post->post_type );

		return ( $type && ! empty( $type['title_label'] ) ) ? $type['title_label'] : $text;
	}

	/**
	 * None of our types are hierarchical, so the "Parent" dropdown in Page
	 * Attributes is noise. Returning a query that matches nothing removes it
	 * while keeping the Order field, which is the part editors need.
	 */
	public static function hide_parent_dropdown( $args, $post ) {
		if ( ID5_Schema::type( $post->post_type ) ) {
			$args['post_type'] = '__id5_none__';
		}

		return $args;
	}

	/**
	 * This install has no public website of its own. Send anyone who lands on
	 * its front end to the real site instead of showing a bare default theme.
	 */
	public static function redirect_front_end() {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( is_customize_preview() || is_user_logged_in() ) {
			return;
		}

		$site = get_option( 'id5_public_site_url' );

		if ( ! $site ) {
			return;
		}

		// wp_safe_redirect() only allows the current host unless the target is
		// explicitly allowed, so add the configured public site for this call.
		add_filter(
			'allowed_redirect_hosts',
			function ( $hosts ) use ( $site ) {
				$host = wp_parse_url( $site, PHP_URL_HOST );

				if ( $host ) {
					$hosts[] = $host;
				}

				return $hosts;
			}
		);

		wp_safe_redirect( $site, 302 );
		exit;
	}
}
