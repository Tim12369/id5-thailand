<?php
/**
 * Router for running WordPress on PHP's built-in web server during local
 * development:
 *
 *     php -S 127.0.0.1:8081 -t /path/to/wordpress /path/to/dev-router.php
 *
 * It exists to make the built-in server behave like the real web server
 * (Apache or Nginx) that production uses, in the two ways WordPress depends on:
 *
 *  1. A directory requested without its trailing slash is redirected to the
 *     slashed URL. Every link in the WordPress admin menu is relative
 *     ("post-new.php?post_type=..."), so when the admin is opened as
 *     "/wp-admin" rather than "/wp-admin/" the browser resolves those links
 *     against the site root and every one of them returns 404. Real web
 *     servers add the slash automatically; the built-in server does not.
 *
 *  2. Anything that is not a real file is handed to WordPress's front
 *     controller, which is what makes pretty permalinks and /wp-json/ work.
 *
 * Not used in production — there the web server does both jobs itself.
 */

$root = rtrim( str_replace( '\\', '/', $_SERVER['DOCUMENT_ROOT'] ), '/' );
$uri  = rawurldecode( (string) parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
$path = $root . $uri;

// 1. Directory without a trailing slash: redirect, keeping the query string.
if ( '/' !== substr( $uri, -1 ) && is_dir( $path ) ) {
	$query = (string) parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY );

	header( 'Location: ' . $uri . '/' . ( '' !== $query ? '?' . $query : '' ), true, 301 );
	exit;
}

// A real file, or a directory with its own index: let the server handle it.
if ( '/' !== $uri && ( is_file( $path ) || ( is_dir( $path ) && is_file( rtrim( $path, '/' ) . '/index.php' ) ) ) ) {
	return false;
}

// 2. Everything else belongs to WordPress.
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';

require $root . '/index.php';
