<?php
/**
 * The page builder inside the site editor.
 *
 * Content sections ("ส่วนเนื้อหาเพิ่มเติม") and pages ("หน้าเว็บเพิ่มเติม")
 * are made of WordPress blocks. The site editor lets an editor build them
 * straight on the website preview — add and remove rows and columns, drop in
 * text, pictures, PDF documents and ready-made components, drag them around,
 * and style them from a side panel — the way a page builder works.
 *
 * The blocks themselves are handled in the browser by WordPress's own block
 * library (parse, create, serialize), so what is saved is exactly what the
 * block editor would save, and an item built here opens normally in the block
 * editor too. This class only moves that content in and out:
 *
 *  - id5_builder_load    the saved blocks of the sections on the page
 *  - id5_builder_save    store new blocks (WordPress's usual filtering applies)
 *  - id5_builder_create  a new, empty section on a page
 *  - id5_builder_move    move a section up or down among its neighbours
 *
 * Every request needs the site editor's nonce and the right to edit that item;
 * saving goes through wp_update_post(), so revisions, kses and the sync to the
 * website all apply as they do for any other edit.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

class ID5_Site_Builder {

	const BACKGROUNDS = array( 'none', 'white', 'light', 'brand' );

	public static function init() {
		add_action( 'wp_ajax_id5_builder_load', array( __CLASS__, 'load' ) );
		add_action( 'wp_ajax_id5_builder_save', array( __CLASS__, 'save' ) );
		add_action( 'wp_ajax_id5_builder_create', array( __CLASS__, 'create' ) );
		add_action( 'wp_ajax_id5_builder_move', array( __CLASS__, 'move' ) );
	}

	/** The scripts the builder needs, on the site editor screen only. */
	public static function enqueue() {
		// The website's icon font, for the icon picker. Browsers load a font
		// from another address only if that server allows it, so it is used
		// only when WordPress and the website share one (as they do online).
		$site  = untrailingslashit( (string) get_option( 'id5_public_site_url' ) );
		$fonts = $site && self::origin( $site ) === self::origin( admin_url() );
		if ( $fonts ) {
			wp_enqueue_style( 'id5-site-icons', $site . '/vendor/tabler-icons/tabler-icons.min.css', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		}

		wp_enqueue_style( 'id5-site-builder', ID5_CMS_URL . 'assets/site-builder.css', array( 'id5-site-editor' ), ID5_CMS_VERSION );
		wp_enqueue_script(
			'id5-site-builder',
			ID5_CMS_URL . 'assets/site-builder.js',
			array( 'id5-site-editor', 'wp-blocks', 'wp-block-library', 'wp-hooks', 'wp-rich-text', 'wp-dom-ready' ),
			ID5_CMS_VERSION,
			true
		);

		wp_localize_script(
			'id5-site-builder',
			'ID5Builder',
			array(
				'blocks'  => ID5_Blocks::BLOCKS,
				'palette' => ID5_Blocks::palette(),
				'sizes'   => ID5_Blocks::font_sizes(),
				'icons'   => $fonts,
			)
		);
	}

	private static function origin( $url ) {
		$p = wp_parse_url( $url );

		return $p ? strtolower( ( isset( $p['scheme'] ) ? $p['scheme'] : '' ) . '://' . ( isset( $p['host'] ) ? $p['host'] : '' ) . ( isset( $p['port'] ) ? ':' . $p['port'] : '' ) ) : '';
	}

	// ─── Requests ───────────────────────────────────────────────────────────

	/** One block-built item this user may edit, or null. */
	private static function item( $id ) {
		$post = get_post( absint( $id ) );

		if ( ! $post || ! ID5_Blocks::is_block_type( $post->post_type ) || 'trash' === $post->post_status ) {
			return null;
		}

		return current_user_can( 'edit_post', $post->ID ) ? $post : null;
	}

	private static function describe( WP_Post $post ) {
		return array(
			'id'         => $post->ID,
			'type'       => $post->post_type,
			'title'      => get_the_title( $post ),
			'status'     => $post->post_status,
			'content'    => $post->post_content,
			'background' => (string) get_post_meta( $post->ID, ID5_Fields::PREFIX . 'background', true ) ?: 'none',
			'modified'   => $post->post_modified_gmt,
			'canDelete'  => current_user_can( 'delete_post', $post->ID ),
		);
	}

	public static function load() {
		check_ajax_referer( ID5_Site_Editor::NONCE, 'nonce' );

		$ids   = isset( $_POST['ids'] ) ? array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['ids'] ) ) ) ) ) : array();
		$items = array();

		foreach ( array_slice( array_unique( $ids ), 0, 50 ) as $id ) {
			$post = self::item( $id );

			if ( $post ) {
				$items[] = self::describe( $post );
			}
		}

		wp_send_json_success( array( 'items' => $items ) );
	}

	public static function save() {
		check_ajax_referer( ID5_Site_Editor::NONCE, 'nonce' );

		$post = self::item( isset( $_POST['id'] ) ? wp_unslash( $_POST['id'] ) : 0 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! $post ) {
			wp_send_json_error( array( 'message' => 'ไม่พบส่วนนี้ หรือคุณไม่มีสิทธิ์แก้ไข' ), 403 );
		}

		// Somebody else saved this item after it was opened here: say so
		// rather than silently replacing their work.
		$since = isset( $_POST['since'] ) ? sanitize_text_field( wp_unslash( $_POST['since'] ) ) : '';

		if ( $since && $since !== $post->post_modified_gmt && empty( $_POST['force'] ) ) {
			wp_send_json_error( array( 'conflict' => true, 'message' => 'มีผู้อื่นแก้ไข "' . get_the_title( $post ) . '" หลังจากที่คุณเปิดหน้านี้' ), 409 );
		}

		// Raw block markup: WordPress filters it on the way in (kses for users
		// without unfiltered_html), exactly as it does for the block editor.
		$content = isset( $_POST['content'] ) ? (string) wp_unslash( $_POST['content'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( isset( $_POST['background'] ) ) {
			$background = sanitize_key( wp_unslash( $_POST['background'] ) );

			if ( in_array( $background, self::BACKGROUNDS, true ) ) {
				update_post_meta( $post->ID, ID5_Fields::PREFIX . 'background', $background );
			}
		}

		$result = wp_update_post(
			wp_slash(
				array(
					'ID'           => $post->ID,
					'post_content' => $content,
				)
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => 'บันทึกไม่สำเร็จ: ' . $result->get_error_message() ), 500 );
		}

		// A change of background alone leaves the content as it was, and then
		// WordPress has nothing to update — tell the website anyway.
		ID5_Webhook::notify( array( 'event' => 'post.updated', 'post_type' => $post->post_type, 'post_id' => $post->ID ) );

		wp_send_json_success( self::describe( get_post( $post->ID ) ) );
	}

	public static function create() {
		check_ajax_referer( ID5_Site_Editor::NONCE, 'nonce' );

		$type = get_post_type_object( 'id5_section' );

		if ( ! $type || ! current_user_can( $type->cap->create_posts ) ) {
			wp_send_json_error( array( 'message' => 'คุณไม่มีสิทธิ์เพิ่มส่วนเนื้อหา' ), 403 );
		}

		$page     = isset( $_POST['page'] ) ? sanitize_text_field( wp_unslash( $_POST['page'] ) ) : '/';
		$position = isset( $_POST['position'] ) ? sanitize_key( wp_unslash( $_POST['position'] ) ) : 'bottom';
		$pages    = ID5_Schema::section_pages();

		if ( ! isset( $pages[ $page ] ) ) {
			wp_send_json_error( array( 'message' => 'หน้านี้ยังเพิ่มส่วนเนื้อหาไม่ได้' ), 400 );
		}

		if ( ! isset( ID5_Schema::section_positions()[ $position ] ) ) {
			$position = 'bottom';
		}

		$content = isset( $_POST['content'] ) ? (string) wp_unslash( $_POST['content'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$status  = current_user_can( $type->cap->publish_posts ) ? 'publish' : 'pending';

		$id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => 'id5_section',
					'post_status'  => $status,
					'post_title'   => 'ส่วนเนื้อหา ' . $pages[ $page ] . ' ' . wp_date( 'j M Y H:i' ),
					'post_content' => $content,
					'menu_order'   => self::last_order( $page, $position ) + 1,
					'meta_input'   => array(
						ID5_Fields::PREFIX . 'page'       => $page,
						ID5_Fields::PREFIX . 'position'   => $position,
						ID5_Fields::PREFIX . 'language'   => 'both',
						ID5_Fields::PREFIX . 'background' => 'none',
					),
				)
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			wp_send_json_error( array( 'message' => 'เพิ่มส่วนเนื้อหาไม่สำเร็จ: ' . $id->get_error_message() ), 500 );
		}

		// The fields were written with the post, before save_post ran; make
		// sure the website is told about the finished item.
		ID5_Webhook::notify( array( 'event' => 'post.updated', 'post_type' => 'id5_section', 'post_id' => $id ) );

		wp_send_json_success( array_merge( self::describe( get_post( $id ) ), array( 'pending' => 'pending' === $status ) ) );
	}

	public static function move() {
		check_ajax_referer( ID5_Site_Editor::NONCE, 'nonce' );

		$post = self::item( isset( $_POST['id'] ) ? wp_unslash( $_POST['id'] ) : 0 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$step = isset( $_POST['step'] ) && 'up' === $_POST['step'] ? -1 : 1;

		if ( ! $post || 'id5_section' !== $post->post_type ) {
			wp_send_json_error( array( 'message' => 'ไม่พบส่วนนี้ หรือคุณไม่มีสิทธิ์แก้ไข' ), 403 );
		}

		$siblings = self::siblings( get_post_meta( $post->ID, ID5_Fields::PREFIX . 'page', true ), get_post_meta( $post->ID, ID5_Fields::PREFIX . 'position', true ) );
		$at       = array_search( $post->ID, $siblings, true );
		$to       = false === $at ? false : $at + $step;

		if ( false === $to || $to < 0 || $to >= count( $siblings ) ) {
			wp_send_json_success( array( 'moved' => false ) );
		}

		$siblings[ $at ] = $siblings[ $to ];
		$siblings[ $to ] = $post->ID;

		// Number the whole run again, so equal numbers can never tie.
		foreach ( $siblings as $order => $id ) {
			if ( (int) get_post_field( 'menu_order', $id ) !== $order && current_user_can( 'edit_post', $id ) ) {
				wp_update_post( array( 'ID' => $id, 'menu_order' => $order ) );
			}
		}

		wp_send_json_success( array( 'moved' => true ) );
	}

	// ─── Helpers ────────────────────────────────────────────────────────────

	/** The sections sharing one page and position, in the website's order. */
	private static function siblings( $page, $position ) {
		$posts = get_posts(
			array(
				'post_type'      => 'id5_section',
				'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
				'posts_per_page' => 200,
				'orderby'        => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array( 'key' => ID5_Fields::PREFIX . 'page', 'value' => (string) $page ),
				),
			)
		);

		$home = '/' === $page;

		// Away from the homepage every section is shown at the end, whatever
		// position it was given, so they are all one run.
		return array_values(
			array_filter(
				array_map( 'intval', $posts ),
				function ( $id ) use ( $home, $position ) {
					return ! $home || (string) get_post_meta( $id, ID5_Fields::PREFIX . 'position', true ) === (string) $position;
				}
			)
		);
	}

	private static function last_order( $page, $position ) {
		$last = -1;

		foreach ( self::siblings( $page, $position ) as $id ) {
			$last = max( $last, (int) get_post_field( 'menu_order', $id ) );
		}

		return $last;
	}
}
