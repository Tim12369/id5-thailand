<?php
/**
 * Reads the first logins of the hosted WordPress.
 *
 * When the hosted site is created, every account is given a random password
 * and the list is stored encrypted with the maintainer's public key
 * (docker/wordpress/maintainer-public.pem). This fetches that ciphertext and
 * opens it with the matching private key, which exists only on the
 * maintainer's computer:
 *
 *   php docker/wordpress/tools/read-first-logins.php \
 *       --url=https://id5-thailand.onrender.com/wp \
 *       --key=C:/xampp/id5-maintainer-private.pem \
 *       [--out=C:/xampp/id5-logins.txt]
 *
 * Without --out the list is printed. Passwords should be changed after the
 * first login (WordPress → Profile), which makes this list obsolete.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$opt = getopt( '', array( 'url:', 'key:', 'out:', 'insecure' ) );

if ( empty( $opt['url'] ) || empty( $opt['key'] ) ) {
	fwrite( STDERR, "Usage: php read-first-logins.php --url=<WordPress address> --key=<private key file> [--out=<file>]\n" );
	exit( 1 );
}

$context = stream_context_create(
	array(
		'http' => array( 'timeout' => 120, 'ignore_errors' => true ),
		'ssl'  => isset( $opt['insecure'] ) ? array( 'verify_peer' => false, 'verify_peer_name' => false ) : array(),
	)
);

$body = @file_get_contents( rtrim( $opt['url'], '/' ) . '/wp-json/id5-host/v1/first-logins', false, $context );
$data = json_decode( (string) $body, true );

if ( empty( $data['sealed'] ) ) {
	fwrite( STDERR, "Nothing to read at that address" . ( $body ? ': ' . substr( $body, 0, 200 ) : ' (no answer).' ) . "\n" );
	exit( 1 );
}

$box     = json_decode( (string) base64_decode( $data['sealed'] ), true );
$private = openssl_pkey_get_private( (string) file_get_contents( $opt['key'] ) );

if ( ! $private || ! is_array( $box ) || ! openssl_private_decrypt( base64_decode( $box['k'] ), $key, $private, OPENSSL_PKCS1_OAEP_PADDING ) ) {
	fwrite( STDERR, "This key does not open it.\n" );
	exit( 1 );
}

$plain = openssl_decrypt( base64_decode( $box['c'] ), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, base64_decode( $box['iv'] ), base64_decode( $box['tag'] ) );
$list  = json_decode( (string) $plain, true );

if ( ! is_array( $list ) ) {
	fwrite( STDERR, "The stored list could not be read.\n" );
	exit( 1 );
}

$text = "ID5 — WordPress logins\r\n"
	. "Log in at: {$list['address']}\r\n"
	. "Created:   {$list['made_at']}\r\n"
	. "Keep this file private. Change each password after the first login.\r\n\r\n";

foreach ( $list['logins'] as $login ) {
	$text .= sprintf( "  %-14s %-18s %s\r\n", $login['login'], '(' . $login['role'] . ')', $login['password'] );
}

if ( ! empty( $opt['out'] ) ) {
	file_put_contents( $opt['out'], $text );
	echo 'Written to ' . $opt['out'] . ' (' . count( $list['logins'] ) . " accounts).\n";
} else {
	echo $text;
}
