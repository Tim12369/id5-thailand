<?php
/**
 * The one-time import endpoint used to move the existing website content into
 * WordPress.
 *
 * It lives here rather than being driven through `wp/v2` from the outside for
 * two reasons: the mapping from a legacy row to a post, its meta, its terms and
 * its term meta belongs with the schema that defines those things, and writing
 * underscore-prefixed meta through the generic endpoints is awkward and
 * version-dependent.
 *
 * Idempotency: each item carries `legacy_type` + `legacy_id`. The first import
 * creates a post and stamps those onto it; later imports find that post and
 * update it. Running the import twice therefore changes nothing.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

class ID5_Import {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			ID5_REST::NAMESPACE,
			'/import/terms',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( __CLASS__, 'can_import' ),
				'callback'            => array( __CLASS__, 'handle_terms' ),
			)
		);

		register_rest_route(
			ID5_REST::NAMESPACE,
			'/import',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'handle' ),
				'permission_callback' => array( __CLASS__, 'can_import' ),
			)
		);

		register_rest_route(
			ID5_REST::NAMESPACE,
			'/import/settings',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'handle_settings' ),
				'permission_callback' => array( __CLASS__, 'can_import_settings' ),
			)
		);
	}

	/**
	 * Create (or find) categories that exist on the website but that no
	 * imported item carries, so their names can be edited here too.
	 *
	 * Body: { "terms": { "<taxonomy>": [ { name, slug, fields } ] } }
	 * Answers with the WordPress ID of each, for the website to remember.
	 */
	public static function handle_terms( WP_REST_Request $request ) {
		$groups = $request->get_param( 'terms' );
		$schema = ID5_Schema::taxonomies();
		$out    = array();

		ID5_Webhook::$muted = true;

		foreach ( is_array( $groups ) ? $groups : array() as $taxonomy => $items ) {
			$taxonomy = sanitize_key( $taxonomy );

			if ( ! isset( $schema[ $taxonomy ] ) || ! taxonomy_exists( $taxonomy ) || ! is_array( $items ) ) {
				continue;
			}

			foreach ( $items as $item ) {
				$name = isset( $item['name'] ) ? sanitize_text_field( (string) $item['name'] ) : '';
				$slug = isset( $item['slug'] ) ? sanitize_title( (string) $item['slug'] ) : '';

				if ( '' === $name ) {
					continue;
				}

				$term = $slug ? get_term_by( 'slug', $slug, $taxonomy ) : false;
				$term = $term ? $term : get_term_by( 'name', $name, $taxonomy );

				if ( $term ) {
					$term_id = (int) $term->term_id;
				} else {
					$created = wp_insert_term( $name, $taxonomy, $slug ? array( 'slug' => $slug ) : array() );

					if ( is_wp_error( $created ) ) {
						continue;
					}

					$term_id = (int) $created['term_id'];

					if ( ! empty( $item['fields'] ) && ! empty( $schema[ $taxonomy ]['fields'] ) ) {
						foreach ( $schema[ $taxonomy ]['fields'] as $field ) {
							if ( array_key_exists( $field['key'], $item['fields'] ) ) {
								update_term_meta( $term_id, ID5_Fields::PREFIX . $field['key'], self::clean_scalar( $field, $item['fields'][ $field['key'] ] ) );
							}
						}
					}
				}

				$out[] = array(
					'taxonomy' => $taxonomy,
					'name'     => $name,
					'slug'     => isset( $item['slug'] ) ? (string) $item['slug'] : '',
					'term_id'  => $term_id,
				);
			}
		}

		ID5_Webhook::$muted = false;

		return rest_ensure_response( array( 'terms' => $out ) );
	}

	public static function can_import() {
		if ( current_user_can( 'edit_posts' ) && current_user_can( 'upload_files' ) ) {
			return true;
		}

		return new WP_Error( 'id5_forbidden', 'Not allowed.', array( 'status' => is_user_logged_in() ? 403 : 401 ) );
	}

	public static function can_import_settings() {
		if ( current_user_can( 'edit_theme_options' ) ) {
			return true;
		}

		return new WP_Error( 'id5_forbidden', 'Not allowed.', array( 'status' => is_user_logged_in() ? 403 : 401 ) );
	}

	/**
	 * Import one content item.
	 *
	 * Expected body:
	 *   post_type     string   one of the registered ID5 types
	 *   legacy_type   string   the Laravel table the row came from
	 *   legacy_id     int      that row's primary key
	 *   wp_post_id    int      optional; the post this row is already linked to
	 *   title         string
	 *   slug          string   optional; preserved because it is a public URL
	 *   content       string   optional post body
	 *   excerpt       string   optional
	 *   status        string   publish|draft|pending|private
	 *   date_gmt      string   optional ISO 8601
	 *   menu_order    int
	 *   featured_media int     attachment ID, already uploaded
	 *   fields        object   schema field key => value
	 *   terms         object   taxonomy => [ { name, slug?, fields?{} }, ... ]
	 */
	public static function handle( WP_REST_Request $request ) {
		ID5_Webhook::$muted = true;

		$body = $request->get_json_params();

		if ( ! is_array( $body ) ) {
			return new WP_Error( 'id5_bad_request', 'Expected a JSON object.', array( 'status' => 400 ) );
		}

		$post_type = isset( $body['post_type'] ) ? sanitize_key( $body['post_type'] ) : '';
		$type      = ID5_Schema::type( $post_type );

		if ( ! $type ) {
			return new WP_Error( 'id5_unknown_type', 'Unknown content type.', array( 'status' => 400 ) );
		}

		$legacy_type = isset( $body['legacy_type'] ) ? sanitize_text_field( $body['legacy_type'] ) : '';
		$legacy_id   = isset( $body['legacy_id'] ) ? (int) $body['legacy_id'] : 0;

		if ( '' === $legacy_type || $legacy_id <= 0 ) {
			return new WP_Error( 'id5_bad_request', 'legacy_type and legacy_id are required.', array( 'status' => 400 ) );
		}

		$existing = self::resolve_target(
			$post_type,
			$legacy_type,
			$legacy_id,
			isset( $body['wp_post_id'] ) ? (int) $body['wp_post_id'] : 0
		);

		$postarr = array(
			'post_type'    => $post_type,
			'post_title'   => isset( $body['title'] ) ? wp_strip_all_tags( (string) $body['title'] ) : '',
			'post_content' => isset( $body['content'] ) ? wp_kses_post( (string) $body['content'] ) : '',
			'post_excerpt' => isset( $body['excerpt'] ) ? sanitize_textarea_field( (string) $body['excerpt'] ) : '',
			'post_status'  => self::status( isset( $body['status'] ) ? $body['status'] : 'draft' ),
			'menu_order'   => isset( $body['menu_order'] ) ? (int) $body['menu_order'] : 0,
		);

		if ( ! empty( $body['slug'] ) ) {
			$postarr['post_name'] = sanitize_title( (string) $body['slug'] );
		}

		if ( ! empty( $body['date_gmt'] ) ) {
			$timestamp = strtotime( (string) $body['date_gmt'] );

			if ( $timestamp ) {
				$postarr['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $timestamp );
				$postarr['post_date']     = get_date_from_gmt( $postarr['post_date_gmt'] );
			}
		}

		if ( $existing ) {
			$postarr['ID'] = $existing;
			$post_id       = wp_update_post( wp_slash( $postarr ), true );
		} else {
			$post_id = wp_insert_post( wp_slash( $postarr ), true );
		}

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_id5_legacy_type', $legacy_type );
		update_post_meta( $post_id, '_id5_legacy_id', $legacy_id );

		if ( isset( $body['featured_media'] ) && (int) $body['featured_media'] > 0 ) {
			set_post_thumbnail( $post_id, (int) $body['featured_media'] );
		}

		self::apply_fields( $post_id, $post_type, isset( $body['fields'] ) ? (array) $body['fields'] : array() );
		self::apply_terms( $post_id, $post_type, isset( $body['terms'] ) ? (array) $body['terms'] : array() );

		return rest_ensure_response(
			array(
				'ok'          => true,
				'wp_post_id'  => (int) $post_id,
				'created'     => ! $existing,
				'post_type'   => $post_type,
				'legacy_id'   => $legacy_id,
				'edit_link'   => get_edit_post_link( $post_id, 'raw' ),
			)
		);
	}

	/**
	 * Import the site-wide Customizer values.
	 */
	public static function handle_settings( WP_REST_Request $request ) {
		$body = $request->get_json_params();

		if ( ! is_array( $body ) || empty( $body['settings'] ) ) {
			return new WP_Error( 'id5_bad_request', 'Expected { settings: {...} }.', array( 'status' => 400 ) );
		}

		$map     = ID5_Customizer::settings();
		$by_key  = array();
		$applied = array();

		foreach ( $map as $slug => $meta ) {
			$by_key[ $meta[0] ] = array( $slug, $meta[1] );
		}

		foreach ( (array) $body['settings'] as $laravel_key => $value ) {
			if ( ! isset( $by_key[ $laravel_key ] ) ) {
				continue;
			}

			list( $slug, $type ) = $by_key[ $laravel_key ];

			if ( 'image' === $type ) {
				// The caller uploads the file first and sends the attachment ID.
				update_option( $slug, (int) $value );
			} elseif ( 'textarea' === $type ) {
				update_option( $slug, sanitize_textarea_field( (string) $value ) );
			} else {
				update_option( $slug, sanitize_text_field( (string) $value ) );
			}

			$applied[] = $laravel_key;
		}

		return rest_ensure_response( array( 'ok' => true, 'applied' => $applied ) );
	}

	// ─── Helpers ────────────────────────────────────────────────────────────

	/**
	 * Decide which post this item should write to.
	 *
	 * `wp_post_id` wins when the caller supplies it: a row that is already
	 * linked to a post must update *that* post. Without this, content that
	 * originated in WordPress, was synced down to the website and then
	 * re-exported would not match any legacy stamp, and a second copy of it
	 * would be created on every export.
	 *
	 * Otherwise fall back to the legacy stamp, which is how the first export
	 * of a pre-WordPress row finds the post it created last time.
	 */
	private static function resolve_target( $post_type, $legacy_type, $legacy_id, $wp_post_id ) {
		if ( $wp_post_id > 0 ) {
			$post = get_post( $wp_post_id );

			if ( $post && $post->post_type === $post_type ) {
				return (int) $post->ID;
			}
		}

		return self::find_existing( $post_type, $legacy_type, $legacy_id );
	}

	/**
	 * The post a previous import created for this legacy row, if any.
	 */
	private static function find_existing( $post_type, $legacy_type, $legacy_id ) {
		$found = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => 'any',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				'meta_query'       => array(
					'relation' => 'AND',
					array( 'key' => '_id5_legacy_type', 'value' => $legacy_type ),
					array( 'key' => '_id5_legacy_id', 'value' => $legacy_id, 'type' => 'NUMERIC' ),
				),
			)
		);

		return $found ? (int) $found[0] : 0;
	}

	private static function status( $status ) {
		$allowed = array( 'publish', 'draft', 'pending', 'private' );

		return in_array( $status, $allowed, true ) ? $status : 'draft';
	}

	/**
	 * Write the schema fields, reusing the same sanitising rules the editor
	 * screens use so an imported value and a typed value are indistinguishable.
	 */
	private static function apply_fields( $post_id, $post_type, $fields ) {
		foreach ( ID5_Schema::fields_for( $post_type ) as $key => $field ) {
			if ( ! array_key_exists( $key, $fields ) ) {
				continue;
			}

			$value = $fields[ $key ];

			if ( 'repeater' === $field['type'] ) {
				$rows = self::clean_rows( $field, is_array( $value ) ? $value : array() );

				if ( $rows ) {
					update_post_meta( $post_id, ID5_Fields::PREFIX . $key, wp_slash( wp_json_encode( $rows, JSON_UNESCAPED_UNICODE ) ) );
				} else {
					delete_post_meta( $post_id, ID5_Fields::PREFIX . $key );
				}

				continue;
			}

			$clean = self::clean_scalar( $field, $value );

			if ( '' === $clean ) {
				delete_post_meta( $post_id, ID5_Fields::PREFIX . $key );
			} else {
				update_post_meta( $post_id, ID5_Fields::PREFIX . $key, wp_slash( $clean ) );
			}
		}
	}

	private static function clean_rows( $field, $rows ) {
		$out = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$clean = array();

			foreach ( $field['fields'] as $sub ) {
				$sub_type = 'wysiwyg' === $sub['type'] ? 'textarea' : $sub['type'];
				$value    = isset( $row[ $sub['key'] ] ) ? $row[ $sub['key'] ] : '';

				$clean[ $sub['key'] ] = self::clean_scalar( array( 'type' => $sub_type ) + $sub, $value );
			}

			$out[] = $clean;
		}

		return $out;
	}

	private static function clean_scalar( $field, $value ) {
		if ( is_bool( $value ) ) {
			$value = $value ? '1' : '0';
		}

		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		if ( null === $value ) {
			return '';
		}

		$value = (string) $value;

		switch ( $field['type'] ) {
			case 'wysiwyg':
				return wp_kses_post( $value );
			case 'textarea':
				return sanitize_textarea_field( $value );
			case 'checkbox':
				return in_array( strtolower( $value ), array( '1', 'true', 'yes' ), true ) ? '1' : '0';
			case 'number':
				return '' === trim( $value ) ? '' : (string) (int) $value;
			case 'url':
				return esc_url_raw( trim( $value ) );
			case 'email':
				return sanitize_email( $value );
			case 'date':
				$value = substr( trim( $value ), 0, 10 );
				return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
			case 'image':
			case 'file':
				return $value ? (string) absint( $value ) : '';
			case 'select':
				$options = isset( $field['options'] ) ? $field['options'] : array();
				return array_key_exists( $value, $options ) ? $value : '';
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Attach taxonomy terms, creating any that do not exist and carrying their
	 * own fields (section colour, ordering, visibility) across with them.
	 */
	private static function apply_terms( $post_id, $post_type, $terms ) {
		$schema = ID5_Schema::taxonomies();

		foreach ( $terms as $taxonomy => $items ) {
			$taxonomy = sanitize_key( $taxonomy );

			if ( ! taxonomy_exists( $taxonomy ) || ! is_array( $items ) ) {
				continue;
			}

			$term_ids = array();

			foreach ( $items as $item ) {
				$name = is_array( $item ) ? ( isset( $item['name'] ) ? $item['name'] : '' ) : (string) $item;
				$name = sanitize_text_field( (string) $name );

				if ( '' === $name ) {
					continue;
				}

				$term = get_term_by( 'name', $name, $taxonomy );

				if ( ! $term ) {
					$args = array();

					if ( is_array( $item ) && ! empty( $item['slug'] ) ) {
						$args['slug'] = sanitize_title( $item['slug'] );
					}

					$created = wp_insert_term( $name, $taxonomy, $args );

					if ( is_wp_error( $created ) ) {
						continue;
					}

					$term_id = (int) $created['term_id'];
				} else {
					$term_id = (int) $term->term_id;
				}

				$term_ids[] = $term_id;

				// Term-level fields, e.g. an org chart section's colour.
				if ( is_array( $item ) && ! empty( $item['fields'] ) && ! empty( $schema[ $taxonomy ]['fields'] ) ) {
					foreach ( $schema[ $taxonomy ]['fields'] as $field ) {
						if ( ! array_key_exists( $field['key'], $item['fields'] ) ) {
							continue;
						}

						update_term_meta(
							$term_id,
							ID5_Fields::PREFIX . $field['key'],
							self::clean_scalar( $field, $item['fields'][ $field['key'] ] )
						);
					}
				}
			}

			wp_set_object_terms( $post_id, $term_ids, $taxonomy, false );
		}
	}
}
