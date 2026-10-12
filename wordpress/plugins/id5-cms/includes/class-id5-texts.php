<?php
/**
 * The fixed wording of the public website: menu labels, headings, buttons, the
 * service guides, the assessment questions.
 *
 * The website ships with built-in wording in Thai and English. What is stored
 * here is only what an editor has changed — one row per line and language.
 * Clearing a line, or setting it back to the built-in wording, removes the row
 * and the website shows its own text again, so nothing can be left blank by
 * accident.
 *
 * The list of lines that exist (and their built-in wording) belongs to the
 * website, not to WordPress; it is fetched from the website, signed with the
 * same shared secret as the change notifications, and remembered for a few
 * minutes.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

class ID5_Texts {

	const DB_VERSION = '1';
	const LOCALES    = array( 'th', 'en' );
	const CACHE      = 'id5_texts_catalogue';

	/** Inline markup allowed in the few lines the website prints as HTML. */
	const INLINE_TAGS = array(
		'strong' => array( 'class' => true ),
		'b'      => array( 'class' => true ),
		'em'     => array( 'class' => true ),
		'i'      => array( 'class' => true ),
		'u'      => array( 'class' => true ),
		'sup'    => array(),
		'sub'    => array(),
		'br'     => array(),
		'span'   => array( 'class' => true ),
		'a'      => array( 'href' => true, 'class' => true, 'target' => true, 'rel' => true ),
	);

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_install' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'wp_ajax_id5_texts_get', array( __CLASS__, 'ajax_get' ) );
		add_action( 'wp_ajax_id5_texts_save', array( __CLASS__, 'ajax_save' ) );
		add_action( 'admin_post_id5_open_term', array( __CLASS__, 'open_term' ) );
	}

	// ─── Storage ────────────────────────────────────────────────────────────

	private static function table() {
		global $wpdb;

		return $wpdb->prefix . 'id5_texts';
	}

	public static function maybe_install() {
		if ( get_option( 'id5_texts_db' ) === self::DB_VERSION ) {
			return;
		}

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta(
			'CREATE TABLE ' . self::table() . " (
				text_key varchar(191) NOT NULL,
				locale varchar(5) NOT NULL,
				value longtext NOT NULL,
				updated_at datetime NOT NULL,
				updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (text_key, locale)
			) " . $wpdb->get_charset_collate() . ';'
		);

		update_option( 'id5_texts_db', self::DB_VERSION, false );
	}

	/**
	 * Every change in force: key => array( locale => wording ).
	 */
	public static function all() {
		global $wpdb;

		$rows = $wpdb->get_results( 'SELECT text_key, locale, value FROM ' . self::table() . ' ORDER BY text_key, locale' ); // phpcs:ignore WordPress.DB
		$out  = array();

		foreach ( (array) $rows as $row ) {
			$out[ $row->text_key ][ $row->locale ] = $row->value;
		}

		return $out;
	}

	/**
	 * Store one line in one language; an empty value removes the change.
	 */
	public static function put( $key, $locale, $value ) {
		global $wpdb;

		if ( '' === $value ) {
			$wpdb->delete( self::table(), array( 'text_key' => $key, 'locale' => $locale ) ); // phpcs:ignore WordPress.DB
		} else {
			$wpdb->replace( // phpcs:ignore WordPress.DB
				self::table(),
				array(
					'text_key'   => $key,
					'locale'     => $locale,
					'value'      => $value,
					'updated_at' => current_time( 'mysql', true ),
					'updated_by' => get_current_user_id(),
				)
			);
		}

		// From the first save on, an empty list means "the editors put
		// everything back", not "this WordPress was just installed".
		if ( ! get_option( 'id5_texts_used' ) ) {
			update_option( 'id5_texts_used', 1, false );
		}
	}

	// ─── What exists: the website's own list of lines ───────────────────────

	/**
	 * The website's catalogue: array( 'groups' => .., 'lines' => .. ), or a
	 * WP_Error when the website cannot be reached and nothing is remembered.
	 */
	public static function catalogue( $fresh = false ) {
		$cached = $fresh ? false : get_transient( self::CACHE );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$site   = untrailingslashit( (string) get_option( 'id5_public_site_url' ) );
		$secret = (string) get_option( 'id5_webhook_secret' );

		if ( '' === $site || '' === $secret ) {
			return new WP_Error( 'id5_not_connected', 'ยังไม่ได้ตั้งค่าการเชื่อมต่อกับเว็บไซต์' );
		}

		$timestamp = (string) time();
		$response  = wp_remote_get(
			$site . '/cms/texts',
			array(
				'timeout' => 20,
				'headers' => array(
					'Accept'          => 'application/json',
					'X-ID5-Timestamp' => $timestamp,
					'X-ID5-Signature' => hash_hmac( 'sha256', $timestamp . "\n", $secret ),
				),
			)
		);

		$data = is_wp_error( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) || empty( $data['lines'] ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			// The website is restarting or unreachable: keep working from the
			// last list it gave, which changes only when the site is upgraded.
			$last = get_option( 'id5_texts_catalogue_last' );

			return is_array( $last ) ? $last : new WP_Error( 'id5_unreachable', 'ไม่สามารถติดต่อเว็บไซต์เพื่ออ่านรายการข้อความได้ กรุณาลองใหม่อีกครั้ง' );
		}

		$catalogue = array(
			'groups' => isset( $data['groups'] ) && is_array( $data['groups'] ) ? $data['groups'] : array(),
			'lines'  => $data['lines'],
		);

		set_transient( self::CACHE, $catalogue, 5 * MINUTE_IN_SECONDS );
		update_option( 'id5_texts_catalogue_last', $catalogue, false );

		return $catalogue;
	}

	/**
	 * One line as the editing form needs it.
	 */
	private static function describe( $key, array $line, array $groups, array $changes ) {
		$default      = array();
		$value        = array();
		$placeholders = array();

		foreach ( self::LOCALES as $locale ) {
			$default[ $locale ] = isset( $line[ $locale ] ) ? (string) $line[ $locale ] : '';
			$value[ $locale ]   = isset( $changes[ $key ][ $locale ] ) ? $changes[ $key ][ $locale ] : $default[ $locale ];
			$placeholders       = array_merge( $placeholders, self::placeholders( $default[ $locale ] ) );
		}

		return array(
			'key'          => $key,
			'group'        => isset( $groups[ $line['group'] ] ) ? $groups[ $line['group'] ] : $line['group'],
			'html'         => ! empty( $line['html'] ),
			'default'      => $default,
			'value'        => $value,
			'changed'      => isset( $changes[ $key ] ) ? array_keys( $changes[ $key ] ) : array(),
			'placeholders' => array_values( array_unique( $placeholders ) ),
		);
	}

	/**
	 * The ":count"-style markers the website fills in when it prints a line.
	 */
	private static function placeholders( $text ) {
		return preg_match_all( '/(?<![A-Za-z0-9]):[a-z_]{2,}\b/', (string) $text, $m ) ? $m[0] : array();
	}

	// ─── The editing panel talks to these ───────────────────────────────────

	private static function guard() {
		check_ajax_referer( ID5_Site_Editor::NONCE, 'nonce' );

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_send_json_error( array( 'message' => 'คุณไม่มีสิทธิ์แก้ไขข้อความของเว็บไซต์' ), 403 );
		}
	}

	/**
	 * Lines by key (what is on the page being previewed) or by search words
	 * (anywhere on the website).
	 */
	public static function ajax_get() {
		self::guard();

		$catalogue = self::catalogue();

		if ( is_wp_error( $catalogue ) ) {
			wp_send_json_error( array( 'message' => $catalogue->get_error_message() ), 503 );
		}

		$changes = self::all();
		$lines   = array();

		if ( isset( $_POST['q'] ) && '' !== trim( (string) wp_unslash( $_POST['q'] ) ) ) {
			$needle = mb_strtolower( trim( sanitize_text_field( wp_unslash( $_POST['q'] ) ) ) );

			foreach ( $catalogue['lines'] as $key => $line ) {
				$haystack = '';

				foreach ( self::LOCALES as $locale ) {
					$haystack .= "\n" . ( isset( $line[ $locale ] ) ? $line[ $locale ] : '' ) . "\n" . ( isset( $changes[ $key ][ $locale ] ) ? $changes[ $key ][ $locale ] : '' );
				}

				if ( false !== mb_strpos( mb_strtolower( $haystack ), $needle ) ) {
					$lines[] = self::describe( $key, $line, $catalogue['groups'], $changes );

					if ( count( $lines ) >= 60 ) {
						break;
					}
				}
			}
		} else {
			$keys = isset( $_POST['keys'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['keys'] ) ) : array();

			foreach ( $keys as $key ) {
				if ( isset( $catalogue['lines'][ $key ] ) ) {
					$lines[] = self::describe( $key, $catalogue['lines'][ $key ], $catalogue['groups'], $changes );
				}
			}
		}

		wp_send_json_success( array( 'lines' => $lines ) );
	}

	public static function ajax_save() {
		self::guard();

		$key       = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		$catalogue = self::catalogue();

		if ( is_wp_error( $catalogue ) ) {
			wp_send_json_error( array( 'message' => $catalogue->get_error_message() ), 503 );
		}

		if ( ! isset( $catalogue['lines'][ $key ] ) ) {
			wp_send_json_error( array( 'message' => 'ไม่พบข้อความนี้ในเว็บไซต์ กรุณาโหลดหน้านี้ใหม่' ), 404 );
		}

		$line  = $catalogue['lines'][ $key ];
		$clean = array();

		foreach ( self::LOCALES as $locale ) {
			if ( ! isset( $_POST[ $locale ] ) ) {
				continue;
			}

			$default = isset( $line[ $locale ] ) ? (string) $line[ $locale ] : '';
			$value   = self::clean( wp_unslash( $_POST[ $locale ] ), ! empty( $line['html'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			// The website fills these in (a number, the search words). A line
			// saved without one would silently stop showing it.
			foreach ( self::placeholders( $default ) as $placeholder ) {
				if ( '' !== $value && false === strpos( $value, $placeholder ) ) {
					wp_send_json_error(
						array(
							'message' => sprintf( 'ข้อความ%s ต้องมีคำว่า %s อยู่ด้วย เพราะเว็บไซต์จะแทนที่คำนี้ด้วยข้อมูลจริง', 'th' === $locale ? 'ภาษาไทย' : 'ภาษาอังกฤษ', $placeholder ),
							'locale'  => $locale,
						),
						422
					);
				}
			}

			// Unchanged from the built-in wording means "no change": keep no row.
			$clean[ $locale ] = ( $value === $default ) ? '' : $value;
		}

		foreach ( $clean as $locale => $value ) {
			self::put( $key, $locale, $value );
		}

		if ( $clean ) {
			ID5_Webhook::notify( array( 'event' => 'texts.updated', 'key' => $key ) );
		}

		wp_send_json_success( array( 'line' => self::describe( $key, $line, $catalogue['groups'], self::all() ) ) );
	}

	/**
	 * Tidy what an editor typed. A plain line is printed escaped by the
	 * website, so it only loses invisible control characters; a line printed
	 * as HTML is cut down to simple inline formatting. The website applies its
	 * own check again when it receives the line.
	 */
	public static function clean( $value, $html ) {
		$value = wp_check_invalid_utf8( (string) $value );
		$value = str_replace( array( "\r\n", "\r" ), "\n", $value );
		$value = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]|\x{2063}/u', '', $value );
		$value = trim( (string) $value );

		return $html ? trim( wp_kses( $value, self::INLINE_TAGS ) ) : $value;
	}

	// ─── A pencil on a category: open that term's own form ──────────────────

	/**
	 * The website knows a category only by its ID; WordPress needs the
	 * taxonomy as well to show the form.
	 */
	public static function open_term() {
		$term = get_term( isset( $_GET['term'] ) ? absint( $_GET['term'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$link = ( $term && ! is_wp_error( $term ) ) ? get_edit_term_link( $term ) : '';

		wp_safe_redirect( $link ? $link : admin_url() );
		exit;
	}

	// ─── For the website ────────────────────────────────────────────────────

	public static function register_routes() {
		register_rest_route(
			ID5_REST::NAMESPACE,
			'/texts',
			array(
				'methods'             => 'GET',
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'callback'            => array( __CLASS__, 'rest_list' ),
			)
		);

		register_rest_route(
			ID5_REST::NAMESPACE,
			'/import/texts',
			array(
				'methods'             => 'POST',
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'callback'            => array( __CLASS__, 'rest_import' ),
			)
		);
	}

	public static function rest_list() {
		$texts = array();

		foreach ( self::all() as $key => $locales ) {
			foreach ( $locales as $locale => $value ) {
				$texts[] = array( 'key' => $key, 'locale' => $locale, 'value' => $value );
			}
		}

		return rest_ensure_response(
			array(
				'texts'       => $texts,
				'initialised' => (bool) get_option( 'id5_texts_used' ),
			)
		);
	}

	/**
	 * Take back the changes the website holds — used once when WordPress has
	 * been rebuilt from an older backup. Never notifies the website: it is
	 * the one sending.
	 */
	public static function rest_import( WP_REST_Request $request ) {
		$texts = $request->get_param( 'texts' );
		$count = 0;

		foreach ( is_array( $texts ) ? $texts : array() as $text ) {
			$key    = isset( $text['key'] ) ? sanitize_text_field( $text['key'] ) : '';
			$locale = isset( $text['locale'] ) ? sanitize_key( $text['locale'] ) : '';

			if ( '' === $key || strlen( $key ) > 191 || ! in_array( $locale, self::LOCALES, true ) || ! isset( $text['value'] ) ) {
				continue;
			}

			self::put( $key, $locale, self::clean( $text['value'], '_html' === substr( $key, -5 ) ) );
			$count++;
		}

		return rest_ensure_response( array( 'imported' => $count ) );
	}
}
