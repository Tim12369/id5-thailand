<?php
/**
 * WordPress configuration for the editors' install that lives in the same
 * container as the public website, at /wp (docs/free-hosting.md).
 *
 * Nothing secret is written here, and nothing extra has to be set in the
 * hosting panel: every key is derived from the application key the website
 * already has (APP_KEY), and the data is kept in the PostgreSQL database the
 * website already uses.
 */

$id5_env = static function ( $key, $default = '' ) {
	$value = getenv( $key );

	return ( false === $value || '' === $value ) ? $default : $value;
};

$id5_refuse = static function ( $message ) {
	if ( 'cli' === PHP_SAPI ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}

	http_response_code( 503 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Retry-After: 60' );
	exit( $message );
};

// The site is created by the start-up script, never through the browser
// installer — which on a public address would let the first visitor make
// themselves the administrator.
if ( isset( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( $_SERVER['SCRIPT_NAME'], 'wp-admin/install.php' ) ) {
	$id5_refuse( 'This site is being set up. Please come back shortly.' );
}

// Set by the start-up script when the saved data could not be brought back.
// Starting with an empty site instead would overwrite the saved one.
if ( file_exists( '/var/www/wordpress-data/.offline' ) && 'cli' !== PHP_SAPI ) {
	$id5_refuse( 'The editing site is starting up. Please try again in a minute.' );
}

$id5_key = $id5_env( 'APP_KEY' );

if ( strlen( $id5_key ) < 32 ) {
	$id5_refuse( 'Configuration incomplete: APP_KEY is not set.' );
}

/** A secret for one purpose, derived from the application key. */
$id5_derive = static function ( $purpose, $length = 64 ) use ( $id5_key ) {
	return substr( hash_hmac( 'sha256', $purpose, $id5_key ) . hash_hmac( 'sha256', $purpose . '/2', $id5_key ), 0, $length );
};

// The same every time the service starts, or every editor would be logged out
// whenever it wakes up.
foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ) as $id5_name ) {
	define( $id5_name, $id5_derive( 'wordpress/' . $id5_name, 128 ) );
}

// ── Database: one SQLite file, through the SQLite Database Integration
//    drop-in (wp-content/db.php). The file is mirrored into PostgreSQL by
//    docker/wordpress/sqlite-sync.php, because this host's disk does not last.
define( 'DB_ENGINE', 'sqlite' );
define( 'DB_PATH', '/var/www/wordpress-data/site.sqlite' );

// Unused by SQLite, but WordPress expects them to exist.
define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', '' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wp_';

// ── Address ─────────────────────────────────────────────────────────────────
// The host terminates HTTPS and forwards plain HTTP to the container.
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && false !== strpos( $_SERVER['HTTP_X_FORWARDED_PROTO'], 'https' ) ) {
	$_SERVER['HTTPS'] = 'on';
}

$id5_site = rtrim( $id5_env( 'APP_URL', $id5_env( 'RENDER_EXTERNAL_URL', 'http://localhost' ) ), '/' );

define( 'ID5_PUBLIC_SITE_URL', $id5_site );
define( 'WP_HOME', $id5_site . '/wp' );
define( 'WP_SITEURL', $id5_site . '/wp' );
define( 'FORCE_SSL_ADMIN', 0 === strpos( $id5_site, 'https://' ) );

// ── The connection to the website, shared without anyone typing it in ────────
define( 'ID5_WEBHOOK_SECRET', $id5_env( 'CMS_WEBHOOK_SECRET', $id5_derive( 'id5-webhook', 48 ) ) );
// Told about changes over the local web server: both live in this container.
define( 'ID5_WEBHOOK_URL', 'http://127.0.0.1:' . (int) $id5_env( 'PORT', '80' ) . '/cms/webhook' );
define( 'ID5_SYNC_USER', $id5_env( 'CMS_WORDPRESS_USER', 'id5-sync' ) );
define( 'ID5_SYNC_APP_PASSWORD', $id5_env( 'CMS_WORDPRESS_APP_PASSWORD', $id5_derive( 'id5-sync-app-password', 24 ) ) );

// ── This host ───────────────────────────────────────────────────────────────
define( 'WP_ENVIRONMENT_TYPE', $id5_env( 'WP_ENVIRONMENT_TYPE', 'production' ) );

// Uploaded files are also kept in PostgreSQL (ID5_Uploads_Store) and put back
// when the service starts.
define( 'ID5_PERSIST_UPLOADS', true );
// The website's database, as configured for the website itself: either the
// separate DB_* settings or one DB_URL.
$id5_db = parse_url( $id5_env( 'DB_URL', $id5_env( 'DATABASE_URL' ) ) );

if ( is_array( $id5_db ) && ! empty( $id5_db['host'] ) ) {
	parse_str( $id5_db['query'] ?? '', $id5_db_query );

	define(
		'ID5_FILES_PG',
		array(
			'dsn'      => sprintf( 'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s', $id5_db['host'], $id5_db['port'] ?? 5432, ltrim( $id5_db['path'] ?? '/postgres', '/' ), $id5_db_query['sslmode'] ?? $id5_env( 'DB_SSLMODE', 'prefer' ) ),
			'user'     => rawurldecode( $id5_db['user'] ?? '' ),
			'password' => rawurldecode( $id5_db['pass'] ?? '' ),
		)
	);
} else {
	define(
		'ID5_FILES_PG',
		array(
			'dsn'      => sprintf( 'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s', $id5_env( 'DB_HOST', '127.0.0.1' ), $id5_env( 'DB_PORT', '5432' ), $id5_env( 'DB_DATABASE', 'postgres' ), $id5_env( 'DB_SSLMODE', 'prefer' ) ),
			'user'     => $id5_env( 'DB_USERNAME' ),
			'password' => $id5_env( 'DB_PASSWORD' ),
		)
	);
}

// Nothing may be installed or updated from the dashboard — it would vanish at
// the next restart. Updates arrive as a new image.
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
