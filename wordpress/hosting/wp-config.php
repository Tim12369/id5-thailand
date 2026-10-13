<?php
/**
 * WordPress configuration for the hosted editing install.
 *
 * Nothing secret is written here: every value comes from the environment,
 * which is set in the hosting panel. See docs/free-hosting.md for the list.
 */

$id5_env = static function ( $key, $default = '' ) {
	$value = getenv( $key );

	return ( false === $value || '' === $value ) ? $default : $value;
};

// The database arrives already populated (an import), never through the
// browser installer — which on a public address would let the first visitor
// make themselves the administrator.
if ( isset( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( $_SERVER['SCRIPT_NAME'], 'wp-admin/install.php' ) ) {
	http_response_code( 503 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	exit( 'This site is being set up. Please come back shortly.' );
}

// One long random secret, from which the eight keys and salts are derived.
// They must stay the same across restarts or every editor would be logged out
// each time the service wakes up.
$id5_secret = $id5_env( 'ID5_WP_SECRET' );

if ( strlen( $id5_secret ) < 32 ) {
	http_response_code( 503 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	exit( 'Configuration incomplete: ID5_WP_SECRET must be set to a long random value.' );
}

foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ) as $id5_name ) {
	define( $id5_name, hash_hmac( 'sha256', $id5_name, $id5_secret ) . hash_hmac( 'sha256', $id5_name . '/2', $id5_secret ) );
}

// ── Database ────────────────────────────────────────────────────────────────
define( 'DB_NAME', $id5_env( 'WORDPRESS_DB_NAME', 'wordpress' ) );
define( 'DB_USER', $id5_env( 'WORDPRESS_DB_USER' ) );
define( 'DB_PASSWORD', $id5_env( 'WORDPRESS_DB_PASSWORD' ) );
define( 'DB_HOST', $id5_env( 'WORDPRESS_DB_HOST', 'localhost' ) ); // host:port
define( 'DB_CHARSET', 'utf8mb4' );

// TiDB (TiDB Cloud's free plan, port 4000) has no utf8mb4_unicode_520_ci, the
// collation WordPress would pick by itself; name one it does have. On MySQL
// and MariaDB the choice is left to WordPress.
$id5_tidb = false !== strpos( DB_HOST, 'tidbcloud' ) || (bool) preg_match( '/:4000$/', DB_HOST );

define( 'DB_COLLATE', $id5_env( 'WORDPRESS_DB_COLLATE', $id5_tidb ? 'utf8mb4_general_ci' : '' ) );

// Managed databases are reached over the internet and require TLS.
if ( 'off' !== $id5_env( 'WORDPRESS_DB_SSL', 'on' ) ) {
	define( 'MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL );
}

$table_prefix = $id5_env( 'WORDPRESS_TABLE_PREFIX', 'wp_' );

// ── Address ─────────────────────────────────────────────────────────────────
// The host terminates HTTPS and forwards plain HTTP to the container.
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && false !== strpos( $_SERVER['HTTP_X_FORWARDED_PROTO'], 'https' ) ) {
	$_SERVER['HTTPS'] = 'on';
}

// RENDER_EXTERNAL_URL is provided by Render itself; ID5_WP_URL overrides it
// (a custom domain, or another host).
$id5_url = rtrim( $id5_env( 'ID5_WP_URL', $id5_env( 'RENDER_EXTERNAL_URL' ) ), '/' );

if ( '' !== $id5_url ) {
	define( 'WP_HOME', $id5_url );
	define( 'WP_SITEURL', $id5_url );
	define( 'FORCE_SSL_ADMIN', 0 === strpos( $id5_url, 'https://' ) );
}

// ── This host ───────────────────────────────────────────────────────────────
define( 'WP_ENVIRONMENT_TYPE', $id5_env( 'WP_ENVIRONMENT_TYPE', 'production' ) );

// The disk is wiped on every restart: uploads are also kept in the database
// (ID5_Uploads_Store), and nothing may be installed or updated from the
// dashboard — it would silently vanish. Updates arrive as a new image.
define( 'ID5_PERSIST_UPLOADS', true );
define( 'DISALLOW_FILE_MODS', true );
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'WP_AUTO_UPDATE_CORE', false );

// A small database: keep a few revisions of each item rather than all of them.
define( 'WP_POST_REVISIONS', 5 );

define( 'WP_DEBUG', false );
define( 'WP_DEBUG_DISPLAY', false );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
