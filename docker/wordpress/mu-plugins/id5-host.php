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
