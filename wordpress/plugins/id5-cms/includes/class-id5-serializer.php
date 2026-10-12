<?php
/**
 * Turns WordPress objects into the stable payloads Laravel consumes.
 *
 * This is the contract between the two systems. Keeping it in one class means
 * WordPress internals (meta key names, attachment IDs, term objects) never leak
 * into the Laravel codebase, and either side can change independently as long
 * as these shapes hold.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

class ID5_Serializer {

	/**
	 * Map a WordPress post status onto the Laravel `articles.status` enum.
	 */
	private static function status( $post_status ) {
		switch ( $post_status ) {
			case 'publish':
				return 'published';
			case 'pending':
				return 'review';
			case 'trash':
			case 'private':
				return 'archived';
			case 'future':
			case 'draft':
			case 'auto-draft':
			default:
				return 'draft';
		}
	}

	/**
	 * Serialize one post into its canonical payload.
	 *
	 * @param WP_Post|int $post
	 * @return array|null
	 */
	public static function post( $post ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return null;
		}

		$type = ID5_Schema::type( $post->post_type );

		if ( ! $type ) {
			return null;
		}

		$payload = array(
			'wp_post_id'  => (int) $post->ID,
			'post_type'   => $post->post_type,
			'table'       => $type['laravel']['table'],
			'title'       => $post->post_title,
			'slug'        => $post->post_name,
			'excerpt'     => $post->post_excerpt,
			'content'     => self::render_content( $post ),
			'status'      => self::status( $post->post_status ),
			'menu_order'  => (int) $post->menu_order,
			'published_at'=> self::gmt( $post->post_date_gmt, $post->post_date ),
			'created_at'  => self::gmt( $post->post_date_gmt, $post->post_date ),
			'updated_at'  => self::gmt( $post->post_modified_gmt, $post->post_modified ),
			'image'       => self::attachment_url( get_post_thumbnail_id( $post->ID ) ),
			// Where this install keeps its uploads, so the website can recognise
			// the pictures an editor placed inside the text and keep its own copy.
			'uploads_url' => self::uploads_url(),
			'legacy_id'   => (int) get_post_meta( $post->ID, '_id5_legacy_id', true ),
			'fields'      => array(),
			'terms'       => array(),
		);

		if ( ! empty( $type['laravel']['article_type'] ) ) {
			$payload['article_type'] = $type['laravel']['article_type'];
		}

		foreach ( ID5_Schema::fields_for( $post->post_type ) as $key => $field ) {
			if ( 'repeater' === $field['type'] ) {
				$payload['fields'][ $key ] = ID5_Fields::get_rows( $post->ID, $key );
				continue;
			}

			$value = ID5_Fields::get( $post->ID, $field );

			if ( in_array( $field['type'], array( 'image', 'file' ), true ) ) {
				$payload['fields'][ $key ] = $value ? self::attachment( (int) $value ) : null;
				continue;
			}

			if ( 'checkbox' === $field['type'] ) {
				$payload['fields'][ $key ] = '1' === (string) $value;
				continue;
			}

			if ( 'wysiwyg' === $field['type'] ) {
				$payload['fields'][ $key ] = self::render_html( (string) $value );
				continue;
			}

			$payload['fields'][ $key ] = ( '' === $value ) ? null : $value;
		}

		foreach ( (array) get_object_taxonomies( $post->post_type ) as $taxonomy ) {
			$terms = get_the_terms( $post->ID, $taxonomy );

			if ( ! $terms || is_wp_error( $terms ) ) {
				continue;
			}

			$payload['terms'][ $taxonomy ] = array_map( array( __CLASS__, 'term' ), $terms );
		}

		return $payload;
	}

	/**
	 * Serialize one term, including its ID5 term-meta fields.
	 */
	public static function term( $term ) {
		$fields     = array();
		$taxonomies = ID5_Schema::taxonomies();

		if ( ! empty( $taxonomies[ $term->taxonomy ]['fields'] ) ) {
			foreach ( $taxonomies[ $term->taxonomy ]['fields'] as $field ) {
				$value = get_term_meta( $term->term_id, ID5_Fields::PREFIX . $field['key'], true );

				if ( '' === $value && isset( $field['default'] ) ) {
					$value = $field['default'];
				}

				$fields[ $field['key'] ] = 'checkbox' === $field['type'] ? ( '1' === (string) $value ) : $value;
			}
		}

		return array(
			'term_id'     => (int) $term->term_id,
			'taxonomy'    => $term->taxonomy,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => $term->description,
			'parent'      => (int) $term->parent,
			'fields'      => $fields,
		);
	}

	/**
	 * Serialize one navigation menu into an ordered, flat list.
	 *
	 * Laravel stores menu items with a parent reference, so the hierarchy is
	 * carried by `wp_parent_id` rather than by nesting.
	 *
	 * @param string $location 'header' or 'footer'.
	 */
	public static function menu( $location ) {
		$locations = get_nav_menu_locations();

		if ( empty( $locations[ 'id5_' . $location ] ) ) {
			return array();
		}

		$items = wp_get_nav_menu_items( $locations[ 'id5_' . $location ] );

		if ( ! $items ) {
			return array();
		}

		$out = array();

		foreach ( $items as $item ) {
			$out[] = array(
				'wp_item_id'   => (int) $item->ID,
				'wp_parent_id' => (int) $item->menu_item_parent,
				'label_th'     => $item->title,
				'label_en'     => (string) get_post_meta( $item->ID, '_id5_label_en', true ),
				'url'          => $item->url,
				'sort_order'   => (int) $item->menu_order,
			);
		}

		return $out;
	}

	/**
	 * The full site payload: every content type, the menus and the Customizer.
	 *
	 * @param array $only Restrict to these post types; empty means all.
	 */
	public static function everything( $only = array() ) {
		$types = $only ? array_intersect( ID5_Schema::all_type_names(), $only ) : ID5_Schema::all_type_names();

		$content = array();

		foreach ( $types as $post_type ) {
			$content[ $post_type ] = self::collection( $post_type );
		}

		$payload = array(
			'generated_at' => gmdate( 'c' ),
			'content'      => $content,
		);

		if ( ! $only ) {
			$payload['settings'] = ID5_Customizer::payload();
			$payload['menus']    = array(
				'header' => self::menu( 'header' ),
				'footer' => self::menu( 'footer' ),
			);
			$payload['terms']    = self::all_terms();
		}

		return $payload;
	}

	/**
	 * Every post of one type — drafts and trashed items included — so the
	 * website can mirror the exact state of each one. A trashed item is reported
	 * as archived, which hides it; leaving it out would make it indistinguishable
	 * from one that was never synced, and it would stay visible.
	 */
	public static function collection( $post_type ) {
		$posts = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
				'numberposts'      => -1,
				'orderby'          => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				'suppress_filters' => false,
			)
		);

		$out = array();

		foreach ( $posts as $post ) {
			$serialized = self::post( $post );

			if ( $serialized ) {
				$out[] = $serialized;
			}
		}

		return $out;
	}

	/**
	 * Every term of every ID5 taxonomy, so Laravel can rebuild its grouping
	 * tables (categories, org chart sections, ITA sections).
	 */
	public static function all_terms() {
		$out = array();

		foreach ( array_keys( ID5_Schema::taxonomies() ) as $taxonomy ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);

			if ( is_wp_error( $terms ) ) {
				continue;
			}

			$out[ $taxonomy ] = array_map( array( __CLASS__, 'term' ), $terms );
		}

		// Native post tags back the news section. (Native categories are already
		// covered above: the schema lists them so they can carry extra fields.)
		$tags = get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) );

		$out['post_tag'] = is_wp_error( $tags ) ? array() : array_map( array( __CLASS__, 'term' ), $tags );

		return $out;
	}

	// ─── Helpers ────────────────────────────────────────────────────────────

	/**
	 * Run the post body through WordPress's own content filters (shortcodes,
	 * blocks, paragraph wrapping) so Laravel receives finished HTML.
	 */
	private static function render_content( $post ) {
		$content = $post->post_content;

		if ( '' === trim( $content ) ) {
			return '';
		}

		return self::render_html( $content );
	}

	private static function render_html( $html ) {
		if ( '' === trim( $html ) ) {
			return '';
		}

		if ( function_exists( 'do_blocks' ) ) {
			$html = do_blocks( $html );
		}

		// Deliberately not wptexturize() or convert_smilies(). Those rewrite
		// the text itself — a straight apostrophe becomes a curly-quote entity,
		// "4x6" becomes "4×6", ":)" becomes an image — which is fine for a blog
		// theme and wrong here: this is official wording, form codes and
		// measurements, and the website must show exactly what was typed.
		$html = wpautop( $html );
		$html = do_shortcode( $html );

		return trim( $html );
	}

	/**
	 * Attachment details, including a URL Laravel can download and mirror.
	 */
	private static function attachment( $id ) {
		$url = wp_get_attachment_url( $id );

		if ( ! $url ) {
			return null;
		}

		$path = get_attached_file( $id );

		return array(
			'id'        => (int) $id,
			'url'       => $url,
			'filename'  => basename( (string) $path ),
			'mime_type' => (string) get_post_mime_type( $id ),
			'size'      => ( $path && file_exists( $path ) ) ? (int) filesize( $path ) : null,
			'alt'       => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
		);
	}

	private static function uploads_url() {
		$uploads = wp_get_upload_dir();

		return empty( $uploads['baseurl'] ) ? null : (string) $uploads['baseurl'];
	}

	private static function attachment_url( $id ) {
		return $id ? self::attachment( (int) $id ) : null;
	}

	/**
	 * Prefer the GMT timestamp; fall back to local when WordPress left it zeroed
	 * (which it does for drafts that have never been published).
	 */
	private static function gmt( $gmt, $local ) {
		if ( $gmt && '0000-00-00 00:00:00' !== $gmt ) {
			return gmdate( 'c', strtotime( $gmt . ' UTC' ) );
		}

		if ( $local && '0000-00-00 00:00:00' !== $local ) {
			return gmdate( 'c', (int) get_gmt_from_date( $local, 'U' ) );
		}

		return null;
	}
}
