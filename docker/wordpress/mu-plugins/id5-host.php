<?php
/**
 * Plugin Name: ID5 — hosting
 * Description: What the editors' WordPress needs to live inside the website's own container: its connection settings applied from the environment, and nothing of its own shown to visitors.
 *
 * This host has no command line and nothing is typed into a settings screen:
 * the connection to the public website (its address, the shared secret, the
 * account it signs in with) is applied at start-up by docker/wordpress/boot.php,
 * which calls id5_host_apply_settings() below.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Apply the connection to the public website. Safe to run at every start.
 */
function id5_host_apply_settings() {
	if ( defined( 'ID5_PUBLIC_SITE_URL' ) ) {
		update_option( 'id5_public_site_url', ID5_PUBLIC_SITE_URL );
		update_option( 'id5_webhook_url', defined( 'ID5_WEBHOOK_URL' ) ? ID5_WEBHOOK_URL : ID5_PUBLIC_SITE_URL . '/cms/webhook' );
	}

	if ( defined( 'ID5_WEBHOOK_SECRET' ) ) {
		update_option( 'id5_webhook_secret', ID5_WEBHOOK_SECRET );
	}

	// REST addresses (/wp-json/…) need "pretty" permalinks.
	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}

	// The account the website signs in with, and its application password.
	if ( ! defined( 'ID5_SYNC_USER' ) || ! defined( 'ID5_SYNC_APP_PASSWORD' ) ) {
		return;
	}

	$user = get_user_by( 'login', ID5_SYNC_USER );

	if ( ! $user ) {
		$id = wp_insert_user(
			array(
				'user_login'   => ID5_SYNC_USER,
				'user_pass'    => wp_generate_password( 32, true ),
				'user_email'   => ID5_SYNC_USER . '@id5.invalid',
				'display_name' => 'ID5 website sync',
				'role'         => 'editor',
			)
		);

		if ( is_wp_error( $id ) ) {
			return;
		}

		$user = get_user_by( 'id', $id );
	}

	foreach ( WP_Application_Passwords::get_user_application_passwords( $user->ID ) as $item ) {
		if ( WP_Application_Passwords::check_password( ID5_SYNC_APP_PASSWORD, $item['password'] ) ) {
			return; // already in place
		}
	}

	update_user_meta(
		$user->ID,
		WP_Application_Passwords::USERMETA_KEY_APPLICATION_PASSWORDS,
		array(
			array(
				'uuid'      => wp_generate_uuid4(),
				'app_id'    => '',
				'name'      => 'ID5 website',
				'password'  => WP_Application_Passwords::hash_password( ID5_SYNC_APP_PASSWORD ),
				'created'   => time(),
				'last_used' => null,
				'last_ip'   => null,
			),
		)
	);

	update_network_option( get_main_network_id(), WP_Application_Passwords::OPTION_KEY_IN_USE, true );
}

// The front of this install is not a website: send anyone who lands on it to
// the dashboard, or to the login screen.
add_action(
	'template_redirect',
	static function () {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		wp_safe_redirect( is_user_logged_in() ? admin_url() : wp_login_url() );
		exit;
	}
);

// ─── Is the site being saved? ───────────────────────────────────────────────
// This host wipes its disk at every restart; what survives is the copy kept
// in PostgreSQL (docker/wordpress/sqlite-sync.php). Nobody can read the host's
// logs from WordPress, so the state of that copy is reported here.

/**
 * @return array{local:array,saved:array|null,problem:string|null}
 */
function id5_host_save_status() {
	$file  = dirname( DB_PATH ) . '/.sync-status.json';
	$local = is_file( $file ) ? (array) json_decode( (string) file_get_contents( $file ), true ) : array();
	$saved = null;
	$fault = null;

	try {
		if ( defined( 'ID5_FILES_PG' ) ) {
			$pdo   = new PDO( ID5_FILES_PG['dsn'], ID5_FILES_PG['user'], ID5_FILES_PG['password'], array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => true, PDO::ATTR_TIMEOUT => 10 ) );
			$saved = $pdo->query( 'SELECT generation, blocks, size, owner, EXTRACT(EPOCH FROM (now() - updated_at))::int AS age FROM wp_sqlite_meta WHERE id = 1' )->fetch( PDO::FETCH_ASSOC ) ?: null;
		}
	} catch ( Exception $e ) {
		$fault = $e->getMessage();
	}

	$alive = ! empty( $local['daemon_pid'] ) && is_dir( '/proc/' . (int) $local['daemon_pid'] );

	if ( $fault ) {
		$problem = 'the saved copy cannot be reached';
	} elseif ( ! $saved ) {
		$problem = 'nothing has been saved yet';
	} elseif ( ! $alive ) {
		$problem = 'the saving process is not running';
	} elseif ( ! empty( $local['error'] ) && (int) ( $local['error_at'] ?? 0 ) >= (int) ( $local['saved_at'] ?? 0 ) ) {
		$problem = 'the last attempt to save failed';
	} elseif ( ! empty( $local['instance'] ) && $saved['owner'] !== $local['instance'] ) {
		$problem = 'the saved copy belongs to another instance';
	} else {
		$problem = null;
	}

	return array( 'local' => $local + array( 'daemon_alive' => $alive ), 'saved' => $saved, 'fault' => $fault, 'problem' => $problem );
}

add_action(
	'rest_api_init',
	static function () {
		// For anyone: only whether it is being saved, and how recent the copy is.
		register_rest_route(
			'id5-host/v1',
			'/saved',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static function () {
					$status = id5_host_save_status();

					return array(
						'ok'         => null === $status['problem'],
						'problem'    => $status['problem'],
						'generation' => $status['saved'] ? (int) $status['saved']['generation'] : null,
						'saved_ago'  => $status['saved'] ? (int) $status['saved']['age'] : null,
						'started'    => isset( $status['local']['start'] ) ? preg_replace( '/:.*/s', '', (string) $status['local']['start'] ) : null,
					);
				},
			)
		);
	}
);

// For the administrator, on the connection settings screen: the details.
add_action(
	'admin_notices',
	static function () {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'settings_page_id5-cms' !== $screen->id || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = id5_host_save_status();
		$local  = $status['local'];
		$when   = static function ( $time ) {
			return $time ? human_time_diff( (int) $time ) . ' ที่แล้ว' : '—';
		};

		printf(
			'<div class="notice notice-%s" id="id5-host-save-status"><p><strong>การบันทึกข้อมูลของ WordPress:</strong> %s</p><p style="font-family:monospace;font-size:12px;white-space:pre-wrap">%s</p></div>',
			$status['problem'] ? 'error' : 'success',
			$status['problem'] ? 'มีปัญหา — ' . esc_html( $status['problem'] ) : 'ปกติ ข้อมูลถูกบันทึกไว้อย่างปลอดภัย',
			esc_html(
				implode(
					"\n",
					array(
						'when this instance started: ' . ( $local['start'] ?? 'unknown' ) . ' (' . $when( $local['start_at'] ?? 0 ) . ')',
						'saving process: ' . ( $local['daemon_alive'] ? 'running' : 'NOT running' ) . ', started ' . $when( $local['daemon_started_at'] ?? 0 ),
						'last save from here: ' . $when( $local['saved_at'] ?? 0 ) . ( isset( $local['generation'] ) ? ', generation ' . (int) $local['generation'] : '' ),
						'last check: ' . $when( $local['checked_at'] ?? 0 ) . ' (' . ( $local['last'] ?? '—' ) . ')',
						'last error: ' . ( empty( $local['error'] ) ? 'none' : $local['error'] . ' (' . $when( $local['error_at'] ?? 0 ) . ')' ),
						'saved copy: ' . ( $status['saved'] ? 'generation ' . (int) $status['saved']['generation'] . ', ' . (int) $status['saved']['blocks'] . ' blocks, ' . size_format( (int) $status['saved']['size'] ) . ', ' . (int) $status['saved']['age'] . ' s old' : 'none' ) . ( $status['fault'] ? ' — ' . $status['fault'] : '' ),
						'uploaded files kept: ' . ( class_exists( 'ID5_Uploads_Store' ) ? ID5_Uploads_Store::count() : '?' ),
					)
				)
			)
		);
	}
);

// The first logins. When the site is created the start-up script gives every
// person a random password and stores them encrypted with the maintainer's
// public key (docker/wordpress/maintainer-public.pem). What is served here is
// that ciphertext: useless to anyone without the private key, which never
// leaves the maintainer's computer. Changing a password in WordPress makes the
// stored one obsolete; the entry can be removed from the settings screen.
add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'id5-host/v1',
			'/first-logins',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static function () {
					$sealed = get_option( 'id5_host_first_logins' );

					return $sealed ? array( 'sealed' => $sealed ) : new WP_Error( 'id5_none', 'Nothing here.', array( 'status' => 404 ) );
				},
			)
		);
	}
);
