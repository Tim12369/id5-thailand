<?php
/**
 * The `id5/v1` read API the public website pulls from.
 *
 * These endpoints exist instead of raw `wp/v2` for three reasons: the public
 * site can fetch a whole page's content in one request rather than N, the
 * payload shape is a deliberate contract rather than WordPress's internals, and
 * nothing about users, raw meta keys or plugin state is ever exposed.
 *
 * Every route requires an authenticated user with `edit_posts` (in practice an
 * Application Password held by the Laravel server), because the payloads
 * include drafts and scheduled content.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

class ID5_REST {

	const NAMESPACE = 'id5/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		$auth = array( __CLASS__, 'can_read' );

		register_rest_route(
			self::NAMESPACE,
			'/health',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'health' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/site',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'site' ),
				'permission_callback' => $auth,
				'args'                => array(
					'types' => array(
						'description'       => 'Comma-separated post types to restrict the payload to.',
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/collection/(?P<type>[a-z0-9_]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'collection' ),
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/post/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'single' ),
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'settings' ),
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/menus',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'menus' ),
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/terms',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'terms' ),
				'permission_callback' => $auth,
			)
		);
	}

	/**
	 * Only an authenticated editor may read: payloads contain unpublished work.
	 */
	public static function can_read() {
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return new WP_Error(
			'id5_forbidden',
			'Authentication required.',
			array( 'status' => is_user_logged_in() ? 403 : 401 )
		);
	}

	/**
	 * Unauthenticated liveness probe. Deliberately reveals nothing but the
	 * plugin version and whether the content model is registered.
	 */
	public static function health() {
		return rest_ensure_response(
			array(
				'ok'      => true,
				'plugin'  => 'id5-cms',
				'version' => ID5_CMS_VERSION,
				'types'   => count( ID5_Schema::all_type_names() ),
			)
		);
	}

	public static function site( WP_REST_Request $request ) {
		$types = array();

		if ( $request->get_param( 'types' ) ) {
			$types = array_filter( array_map( 'trim', explode( ',', $request->get_param( 'types' ) ) ) );
		}

		return rest_ensure_response( ID5_Serializer::everything( $types ) );
	}

	public static function collection( WP_REST_Request $request ) {
		$type = $request->get_param( 'type' );

		if ( ! ID5_Schema::type( $type ) ) {
			return new WP_Error( 'id5_unknown_type', 'Unknown content type.', array( 'status' => 404 ) );
		}

		return rest_ensure_response(
			array(
				'generated_at' => gmdate( 'c' ),
				'post_type'    => $type,
				'items'        => ID5_Serializer::collection( $type ),
			)
		);
	}

	public static function single( WP_REST_Request $request ) {
		$payload = ID5_Serializer::post( (int) $request->get_param( 'id' ) );

		if ( ! $payload ) {
			return new WP_Error( 'id5_not_found', 'Content not found.', array( 'status' => 404 ) );
		}

		return rest_ensure_response( $payload );
	}

	public static function settings() {
		return rest_ensure_response(
			array(
				'generated_at' => gmdate( 'c' ),
				'settings'     => ID5_Customizer::payload(),
			)
		);
	}

	public static function menus() {
		return rest_ensure_response(
			array(
				'generated_at' => gmdate( 'c' ),
				'menus'        => array(
					'header' => ID5_Serializer::menu( 'header' ),
					'footer' => ID5_Serializer::menu( 'footer' ),
				),
			)
		);
	}

	public static function terms() {
		return rest_ensure_response(
			array(
				'generated_at' => gmdate( 'c' ),
				'terms'        => ID5_Serializer::all_terms(),
			)
		);
	}
}
