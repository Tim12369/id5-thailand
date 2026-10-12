<?php
/**
 * Start-up tasks for the hosted WordPress, run by entrypoint.sh before the web
 * server starts:
 *
 *   1. wait for the database to answer;
 *   2. apply the connection to the public website from the environment, so it
 *      can be changed in the hosting panel without logging in to WordPress;
 *   3. put the Media Library's files back on the disk, which this host wipes
 *      on every restart (ID5_Uploads_Store).
 *
 * It never stops the web server from starting: a problem is printed to the
 * log and WordPress comes up regardless.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

$env = static function ( $key, $default = '' ) {
	$value = getenv( $key );

	return ( false === $value || '' === $value ) ? $default : $value;
};

$say = static function ( $message ) {
	fwrite( STDOUT, '[id5] ' . $message . "\n" );
};

// ── 1. The database ─────────────────────────────────────────────────────────
$host = $env( 'WORDPRESS_DB_HOST', 'localhost' );
$port = 3306;

if ( preg_match( '/^(.+):(\d+)$/', $host, $m ) ) {
	list( , $host, $port ) = $m;
}

$prefix = $env( 'WORDPRESS_TABLE_PREFIX', 'wp_' );
$link   = null;

mysqli_report( MYSQLI_REPORT_OFF );

for ( $attempt = 1; $attempt <= 30; $attempt++ ) {
	$link = mysqli_init();
	$link->options( MYSQLI_OPT_CONNECT_TIMEOUT, 5 );

	$flags = 'off' !== $env( 'WORDPRESS_DB_SSL', 'on' ) ? MYSQLI_CLIENT_SSL : 0;

	if ( @$link->real_connect( $host, $env( 'WORDPRESS_DB_USER' ), $env( 'WORDPRESS_DB_PASSWORD' ), $env( 'WORDPRESS_DB_NAME', 'wordpress' ), (int) $port, null, $flags ) ) {
		break;
	}

	$say( 'database not answering yet (' . mysqli_connect_error() . '), attempt ' . $attempt . '/30' );
	$link = null;
	sleep( 3 );
}

if ( ! $link ) {
	$say( 'gave up waiting for the database; WordPress will report the connection error itself.' );
	exit( 0 );
}

$installed = $link->query( "SHOW TABLES LIKE '" . $link->real_escape_string( $prefix . 'options' ) . "'" );

if ( ! $installed || 0 === $installed->num_rows ) {
	$say( 'the database is empty. Import the site first (docs/free-hosting.md); until then WordPress has nothing to show.' );
	exit( 0 );
}

$link->close();

// ── WordPress itself ────────────────────────────────────────────────────────
$url = rtrim( $env( 'ID5_WP_URL', $env( 'RENDER_EXTERNAL_URL', 'http://localhost' ) ), '/' );

$_SERVER['HTTP_HOST']      = (string) parse_url( $url, PHP_URL_HOST );
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_NAME']    = $_SERVER['HTTP_HOST'];

if ( 0 === strpos( $url, 'https://' ) ) {
	$_SERVER['HTTPS'] = 'on';
}

define( 'WP_USE_THEMES', false );

require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

if ( ! class_exists( 'ID5_Uploads_Store' ) ) {
	$result = activate_plugin( 'id5-cms/id5-cms.php' );
	$say( is_wp_error( $result ) ? 'could not activate the ID5 plugin: ' . $result->get_error_message() : 'ID5 plugin activated.' );

	if ( ! class_exists( 'ID5_Uploads_Store' ) ) {
		exit( 0 );
	}
}

// ── 2. The connection to the public website ─────────────────────────────────
$site = rtrim( $env( 'ID5_PUBLIC_SITE_URL' ), '/' );

if ( '' !== $site ) {
	update_option( 'id5_public_site_url', esc_url_raw( $site ) );
	update_option( 'id5_webhook_url', esc_url_raw( $site . '/cms/webhook' ) );
}

if ( '' !== $env( 'ID5_WEBHOOK_SECRET' ) ) {
	update_option( 'id5_webhook_secret', sanitize_text_field( $env( 'ID5_WEBHOOK_SECRET' ) ) );
}

$say( 'public website: ' . ( get_option( 'id5_public_site_url' ) ?: 'not set' ) . '; shared secret: ' . ( get_option( 'id5_webhook_secret' ) ? 'present' : 'MISSING' ) );

// ── 3. The Media Library's files ────────────────────────────────────────────
ID5_Uploads_Store::install();

$count = ID5_Uploads_Store::restore_all();

$say( sprintf( 'uploads: %d restored, %d already in place%s.', $count['restored'], $count['present'], $count['failed'] ? ', ' . $count['failed'] . ' FAILED' : '' ) );
