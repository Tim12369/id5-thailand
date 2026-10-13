<?php
/**
 * Start-up tasks for the editors' WordPress, run by docker/entrypoint.sh after
 * the saved database has been put back (sqlite-sync.php restore) and before
 * the web server starts.
 *
 *   php boot.php            every start: create the site if there is none,
 *                           apply settings, put the uploaded files back
 *   php boot.php install    (internal) create an empty WordPress
 *   php boot.php seed       (internal) fill it from docker/wordpress/seed
 *
 * Creating the site happens once in its life: the first time the service
 * starts with nothing saved. Every later start finds the saved database and
 * only applies settings and restores files.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

const ID5_WP   = '/var/www/wordpress';
const ID5_DATA = '/var/www/wordpress-data';
const ID5_SEED = '/usr/local/share/id5/seed';
const ID5_KEY  = '/usr/local/share/id5/maintainer-public.pem';

$phase = $argv[1] ?? 'start';

$say = static function ( $message ) {
	fwrite( STDOUT, '[id5-wp] ' . $message . "\n" );
};

$url = rtrim( getenv( 'APP_URL' ) ?: ( getenv( 'RENDER_EXTERNAL_URL' ) ?: 'http://localhost' ), '/' );

$_SERVER['HTTP_HOST']      = (string) parse_url( $url, PHP_URL_HOST );
$_SERVER['SERVER_NAME']    = $_SERVER['HTTP_HOST'];
$_SERVER['REQUEST_URI']    = '/wp/';
$_SERVER['REQUEST_METHOD'] = 'GET';

if ( 0 === strpos( $url, 'https://' ) ) {
	$_SERVER['HTTPS'] = 'on';
}

/** Does the database hold a WordPress yet? */
$exists = static function () {
	if ( ! is_file( ID5_DATA . '/site.sqlite' ) ) {
		return false;
	}

	try {
		$db = new PDO( 'sqlite:' . ID5_DATA . '/site.sqlite' );

		return (bool) $db->query( "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'wp_options'" )->fetchColumn();
	} catch ( Throwable $e ) {
		return false;
	}
};

// ── install: an empty WordPress ─────────────────────────────────────────────
if ( 'install' === $phase ) {
	define( 'WP_INSTALLING', true );

	// No "your new site" e-mail: there is nobody to send it to.
	function wp_new_blog_notification() {} // phpcs:ignore

	require ID5_WP . '/wp-load.php';
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// A placeholder administrator; the seed replaces every account.
	wp_install( 'ID5 CMS', 'setup-' . bin2hex( random_bytes( 4 ) ), 'setup@id5.invalid', false, '', wp_generate_password( 40, true ), 'th' );
	update_option( 'active_plugins', array( 'id5-cms/id5-cms.php' ) );

	$say( 'an empty WordPress ' . get_bloginfo( 'version' ) . ' was created.' );
	exit( 0 );
}

// ── No site yet (the first start ever): create it, then carry on ────────────
if ( 'start' === $phase && ! $exists() ) {
	$say( 'no WordPress yet: creating it.' );

	passthru( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' install', $code );

	if ( 0 === $code ) {
		passthru( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' seed', $code );
	}

	if ( 0 !== $code || ! $exists() ) {
		$say( 'creating the site failed.' );
		exit( 1 );
	}

	// Tells the start-up script to have the website fetch everything once.
	touch( ID5_DATA . '/.just-created' );

	// Continue in a fresh process, which sees the new site.
	passthru( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' start', $code );
	exit( $code );
}

require ID5_WP . '/wp-load.php';

global $wpdb;

// ── seed: the starting content ──────────────────────────────────────────────
if ( 'seed' === $phase ) {
	$manifest = json_decode( (string) file_get_contents( ID5_SEED . '/manifest.json' ), true );

	if ( ! is_array( $manifest ) ) {
		$say( 'no seed to load; the site starts empty.' );
		exit( 0 );
	}

	// The plugin makes its own tables on its first ordinary request.
	if ( class_exists( 'ID5_Texts' ) ) {
		ID5_Texts::maybe_install();
	}

	// The addresses in the content are those of the computer the seed was
	// made on. PHP-serialized values carry their own lengths, so they are
	// rewritten value by value rather than with a blind replace.
	$map = array();

	foreach ( array_filter( array( (string) $manifest['wp_url'] => WP_HOME, (string) $manifest['site_url'] => ID5_PUBLIC_SITE_URL ) ) as $old => $new ) {
		$map[ $old ]                            = $new;
		$map[ str_replace( '/', '\\/', $old ) ] = str_replace( '/', '\\/', $new );
		$map[ rawurlencode( $old ) ]            = rawurlencode( $new );
	}

	$rewrite = static function ( $value ) use ( &$rewrite, $map ) {
		if ( is_array( $value ) ) {
			$out = array();

			foreach ( $value as $key => $item ) {
				$out[ is_string( $key ) ? strtr( $key, $map ) : $key ] = $rewrite( $item );
			}

			return $out;
		}

		if ( ! is_string( $value ) || '' === $value ) {
			return $value;
		}

		$touched = false;

		foreach ( $map as $old => $new ) {
			$touched = $touched || false !== strpos( $value, (string) $old );
		}

		if ( ! $touched ) {
			return $value;
		}

		if ( is_serialized( $value ) ) {
			$data = @unserialize( $value, array( 'allowed_classes' => false ) ); // phpcs:ignore

			if ( false !== $data || 'b:0;' === $value ) {
				return is_object( $data ) ? $value : serialize( $rewrite( $data ) ); // phpcs:ignore
			}
		}

		return strtr( $value, $map );
	};

	$loaded = 0;

	foreach ( $manifest['tables'] as $name => $info ) {
		$table = $wpdb->prefix . preg_replace( '/[^a-z0-9_]/', '', $name );

		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
			$say( "skipped {$table}: this WordPress has no such table." );
			continue;
		}

		// Options are merged over the fresh install's; every other table is
		// replaced, which also clears WordPress's "Hello world" samples.
		$merge = $table === $wpdb->options;

		if ( ! $merge ) {
			$wpdb->query( "DELETE FROM `{$table}`" ); // phpcs:ignore
		}

		$columns = array_map(
			static function ( $column ) {
				return preg_replace( '/[^A-Za-z0-9_]/', '', (string) $column );
			},
			(array) $info['columns']
		);
		$file    = ID5_SEED . '/tables/' . $name . '.jsonl';
		$handle  = is_file( $file ) ? fopen( $file, 'rb' ) : false;

		if ( ! $handle || ! $columns ) {
			continue;
		}

		$wpdb->query( 'START TRANSACTION' );

		while ( false !== ( $line = fgets( $handle ) ) ) { // phpcs:ignore
			$row = json_decode( $line, true );

			if ( ! is_array( $row ) || count( $row ) !== count( $columns ) ) {
				continue;
			}

			$row = array_combine( $columns, array_map( $rewrite, $row ) );

			if ( $merge ) {
				// Through WordPress, so caches and autoloading stay right.
				update_option( $row['option_name'], maybe_unserialize( $row['option_value'] ) );
				++$loaded;
				continue;
			}

			$marks  = array();
			$values = array();

			foreach ( $row as $value ) {
				if ( null === $value ) {
					$marks[] = 'NULL';
				} else {
					$marks[]  = '%s';
					$values[] = $value;
				}
			}

			$sql = "INSERT INTO `{$table}` (`" . implode( '`,`', $columns ) . '`) VALUES (' . implode( ',', $marks ) . ')';

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( false === $wpdb->query( $values ? $wpdb->prepare( $sql, $values ) : $sql ) ) {
				$say( "could not load a row into {$table}: " . $wpdb->last_error );
			} else {
				++$loaded;
			}
		}

		$wpdb->query( 'COMMIT' );
		fclose( $handle );
	}

	wp_cache_flush();

	// The plugin's roles, in case the seed's copy predates this version.
	update_option( 'active_plugins', array( 'id5-cms/id5-cms.php' ) );

	// Uploaded files: onto the disk, and into the store that survives it.
	$base  = trailingslashit( wp_get_upload_dir()['basedir'] );
	$files = 0;

	foreach ( (array) $manifest['uploads'] as $relative ) {
		$source = ID5_SEED . '/uploads/' . $relative;

		if ( false !== strpos( $relative, '..' ) || ! is_file( $source ) ) {
			continue;
		}

		wp_mkdir_p( dirname( $base . $relative ) );
		copy( $source, $base . $relative );

		if ( class_exists( 'ID5_Uploads_Store' ) && ID5_Uploads_Store::enabled() ) {
			ID5_Uploads_Store::keep( $relative, $base . $relative );
		}

		++$files;
	}

	// Every person gets a new random password. Nobody has seen them yet, so
	// they are stored encrypted for the maintainer — the only way out of here
	// that does not involve printing passwords into a log.
	$logins = array();

	foreach ( get_users( array( 'fields' => array( 'ID', 'user_login' ) ) ) as $user ) {
		$password = wp_generate_password( 20, false );

		wp_set_password( $password, $user->ID );

		if ( ! defined( 'ID5_SYNC_USER' ) || ID5_SYNC_USER !== $user->user_login ) {
			$logins[] = array(
				'login'    => $user->user_login,
				'role'     => implode( ', ', (array) get_userdata( $user->ID )->roles ),
				'password' => $password,
			);
		}
	}

	$public = is_readable( ID5_KEY ) ? openssl_pkey_get_public( (string) file_get_contents( ID5_KEY ) ) : false;

	if ( $public && $logins ) {
		$key = random_bytes( 32 );
		$iv  = random_bytes( 12 );
		$tag = '';

		$sealed = openssl_encrypt( (string) wp_json_encode( array( 'address' => wp_login_url(), 'made_at' => gmdate( 'c' ), 'logins' => $logins ) ), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );

		if ( false !== $sealed && openssl_public_encrypt( $key, $wrapped, $public, OPENSSL_PKCS1_OAEP_PADDING ) ) {
			update_option(
				'id5_host_first_logins',
				base64_encode( (string) wp_json_encode( array( 'k' => base64_encode( $wrapped ), 'iv' => base64_encode( $iv ), 'tag' => base64_encode( $tag ), 'c' => base64_encode( $sealed ) ) ) ),
				false
			);
		}
	}

	update_option( 'id5_host_seeded_at', gmdate( 'c' ), false );

	$say( sprintf( 'the site was filled from the seed: %d rows, %d uploaded file(s), %d account(s) given new passwords.', $loaded, $files, count( $logins ) ) );
	exit( 0 );
}

// ── start: every time the service starts ────────────────────────────────────
if ( function_exists( 'id5_host_apply_settings' ) ) {
	id5_host_apply_settings();
}

$say( 'address: ' . home_url() . ' ; public website: ' . get_option( 'id5_public_site_url' ) );

if ( class_exists( 'ID5_Uploads_Store' ) && ID5_Uploads_Store::enabled() ) {
	$count = ID5_Uploads_Store::restore_all();

	$say( sprintf( 'uploads: %d restored, %d already in place%s.', $count['restored'], $count['present'], $count['failed'] ? ', ' . $count['failed'] . ' FAILED' : '' ) );
}
