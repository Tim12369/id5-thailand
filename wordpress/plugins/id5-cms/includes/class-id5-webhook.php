<?php
/**
 * Tells the public website that something changed.
 *
 * The notification is deliberately small — "this post type changed, here is the
 * post ID" — and carries no content. The website then pulls the canonical
 * payload from the REST API above, so a replayed or forged notification can at
 * worst cause a redundant refresh, never a content injection.
 *
 * Requests are signed with HMAC-SHA256 over `timestamp . "\n" . body`, using a
 * shared secret both sides hold in their own configuration.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

class ID5_Webhook {

	const RETRY_HOOK = 'id5_cms_webhook_retry';

	/**
	 * Set while the one-time content import is writing. The website sent that
	 * content itself and reconciles with a pull when the import ends, so
	 * notifying it of every imported item would only queue hundreds of
	 * redundant refreshes — and delay a real editor's change behind them.
	 */
	public static $muted = false;

	public static function init() {
		add_action( 'save_post', array( __CLASS__, 'on_save_post' ), 20, 3 );
		add_action( 'deleted_post', array( __CLASS__, 'on_deleted_post' ), 10, 2 );
		add_action( 'trashed_post', array( __CLASS__, 'on_trashed_post' ) );
		add_action( 'untrashed_post', array( __CLASS__, 'on_trashed_post' ) );

		add_action( 'customize_save_after', array( __CLASS__, 'on_settings_changed' ) );
		add_action( 'wp_update_nav_menu', array( __CLASS__, 'on_menu_changed' ) );
		add_action( 'wp_delete_nav_menu', array( __CLASS__, 'on_menu_changed' ) );

		add_action( 'created_term', array( __CLASS__, 'on_term_changed' ), 10, 3 );
		add_action( 'edited_term', array( __CLASS__, 'on_term_changed' ), 10, 3 );
		add_action( 'delete_term', array( __CLASS__, 'on_term_changed' ), 10, 3 );

		add_action( self::RETRY_HOOK, array( __CLASS__, 'deliver' ), 10, 2 );
	}

	// ─── Triggers ───────────────────────────────────────────────────────────

	public static function on_save_post( $post_id, $post, $update ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || 'auto-draft' === $post->post_status ) {
			return;
		}

		if ( ! ID5_Schema::type( $post->post_type ) ) {
			return;
		}

		self::notify(
			array(
				'event'     => $update ? 'post.updated' : 'post.created',
				'post_type' => $post->post_type,
				'post_id'   => (int) $post_id,
			)
		);
	}

	public static function on_deleted_post( $post_id, $post = null ) {
		if ( ! $post || ! ID5_Schema::type( $post->post_type ) ) {
			return;
		}

		self::notify(
			array(
				'event'     => 'post.deleted',
				'post_type' => $post->post_type,
				'post_id'   => (int) $post_id,
			)
		);
	}

	public static function on_trashed_post( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || ! ID5_Schema::type( $post->post_type ) ) {
			return;
		}

		self::notify(
			array(
				'event'     => 'post.updated',
				'post_type' => $post->post_type,
				'post_id'   => (int) $post_id,
			)
		);
	}

	public static function on_settings_changed() {
		self::notify( array( 'event' => 'settings.updated' ) );
	}

	public static function on_menu_changed() {
		self::notify( array( 'event' => 'menus.updated' ) );
	}

	public static function on_term_changed( $term_id, $tt_id, $taxonomy ) {
		$known = array_merge( array_keys( ID5_Schema::taxonomies() ), array( 'category', 'post_tag' ) );

		if ( ! in_array( $taxonomy, $known, true ) ) {
			return;
		}

		self::notify( array( 'event' => 'terms.updated', 'taxonomy' => $taxonomy ) );
	}

	// ─── Delivery ───────────────────────────────────────────────────────────

	/**
	 * Queue a notification. Repeated notifications inside one request collapse
	 * into one delivery per event+target, so saving a post with ten meta
	 * updates still sends a single call.
	 */
	public static function notify( array $payload ) {
		static $sent = array();

		if ( self::$muted ) {
			return;
		}

		$key = wp_json_encode( $payload );

		if ( isset( $sent[ $key ] ) ) {
			return;
		}

		$sent[ $key ] = true;

		// Deliver after the response is sent so editors never wait on the
		// website's network round trip.
		add_action(
			'shutdown',
			function () use ( $payload ) {
				ID5_Webhook::deliver( $payload, 0 );
			}
		);
	}

	/**
	 * Send one notification, scheduling a retry if the website did not accept it.
	 *
	 * @param array $payload Event description.
	 * @param int   $attempt Zero-based attempt counter.
	 */
	public static function deliver( $payload, $attempt = 0 ) {
		$url    = get_option( 'id5_webhook_url' );
		$secret = get_option( 'id5_webhook_secret' );

		if ( ! $url || ! $secret ) {
			return;
		}

		$payload['site']       = home_url();
		$payload['sent_at']    = gmdate( 'c' );
		$payload['plugin_ver'] = ID5_CMS_VERSION;

		$body      = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE );
		$timestamp = (string) time();
		$signature = hash_hmac( 'sha256', $timestamp . "\n" . $body, $secret );

		$response = wp_remote_post(
			$url,
			array(
				'timeout'  => 10,
				'blocking' => true,
				'headers'  => array(
					'Content-Type'    => 'application/json; charset=utf-8',
					'X-ID5-Timestamp' => $timestamp,
					'X-ID5-Signature' => $signature,
					'User-Agent'      => 'ID5-CMS/' . ID5_CMS_VERSION,
				),
				'body'     => $body,
			)
		);

		$code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

		if ( $code >= 200 && $code < 300 ) {
			update_option( 'id5_webhook_last_ok', time(), false );
			return;
		}

		$reason = is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . $code;

		update_option(
			'id5_webhook_last_error',
			array( 'at' => time(), 'reason' => $reason, 'event' => $payload['event'] ),
			false
		);

		// Three spaced retries, then give up: the scheduled reconcile on the
		// website side will pick the change up regardless.
		if ( $attempt < 3 ) {
			wp_schedule_single_event(
				time() + ( 60 * pow( 3, $attempt ) ),
				self::RETRY_HOOK,
				array( $payload, $attempt + 1 )
			);
		}
	}
}
