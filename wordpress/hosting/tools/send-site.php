<?php
/**
 * Copies a WordPress install (its database and its uploaded files) into the
 * database of the hosted install.
 *
 * Run once, from the computer that has the working WordPress, to move it to the
 * host; see docs/free-hosting.md. It only reads from the source.
 *
 *   php send-site.php \
 *       --from=C:/xampp/htdocs/id5-wordpress \
 *       --to="mysql://USER:PASSWORD@HOST:PORT/DATABASE" \
 *       --wp-url=https://id5-cms.onrender.com \
 *       --site-url=https://id5-thailand.onrender.com \
 *       [--no-ssl] [--replace] [--keep-passwords] [--credentials=FILE]
 *
 * What it does on the way:
 *   - rewrites the old addresses to the new ones everywhere, including inside
 *     PHP-serialized and JSON values;
 *   - leaves out what belongs to the old machine: caches, login sessions;
 *   - gives every person a new random password (the old ones were chosen for a
 *     private development machine) and prints them once;
 *   - stores every Media Library file in the `id5_files` table, where the
 *     hosted install keeps its uploads (ID5_Uploads_Store).
 *
 * It refuses to touch a destination that already holds a WordPress unless
 * --replace is given.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$opt = getopt( '', array( 'from:', 'to:', 'wp-url:', 'site-url:', 'no-ssl', 'replace', 'keep-passwords', 'credentials:' ) );

foreach ( array( 'from', 'to', 'wp-url', 'site-url' ) as $required ) {
	if ( empty( $opt[ $required ] ) ) {
		fwrite( STDERR, "Missing --{$required}. See the comment at the top of this file.\n" );
		exit( 1 );
	}
}

$say = static function ( $message ) {
	fwrite( STDOUT, $message . "\n" );
};

$fail = static function ( $message ) {
	fwrite( STDERR, 'ERROR: ' . $message . "\n" );
	exit( 1 );
};

// ── The source: the working WordPress ───────────────────────────────────────
$from = rtrim( str_replace( '\\', '/', $opt['from'] ), '/' );

if ( ! is_file( $from . '/wp-load.php' ) ) {
	$fail( "No WordPress found in {$from}." );
}

$_SERVER['HTTP_HOST']      = 'localhost';
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';

define( 'WP_USE_THEMES', false );

require $from . '/wp-load.php';

global $wpdb;

$old_wp   = untrailingslashit( home_url() );
$old_site = untrailingslashit( (string) get_option( 'id5_public_site_url' ) );
$new_wp   = untrailingslashit( $opt['wp-url'] );
$new_site = untrailingslashit( $opt['site-url'] );

// Every spelling an address can have inside stored values.
$map = array();

foreach ( array_filter( array( $old_wp => $new_wp, $old_site => $new_site ) ) as $old => $new ) {
	$map[ $old ]                                 = $new;
	$map[ str_replace( '/', '\\/', $old ) ]      = str_replace( '/', '\\/', $new );
	$map[ rawurlencode( $old ) ]                 = rawurlencode( $new );
}

/**
 * Replace addresses in one stored value, keeping PHP-serialized data valid
 * (its strings carry their own length, so a plain replace would corrupt it).
 */
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
			// An object cannot be walked safely without its class; such values
			// (none in a stock install) are left as they are.
			return is_object( $data ) ? $value : serialize( $rewrite( $data ) ); // phpcs:ignore
		}
	}

	return strtr( $value, $map );
};

// ── The destination ─────────────────────────────────────────────────────────
$to = parse_url( $opt['to'] );

if ( empty( $to['host'] ) || empty( $to['user'] ) || empty( $to['path'] ) ) {
	$fail( '--to must look like mysql://USER:PASSWORD@HOST:PORT/DATABASE' );
}

mysqli_report( MYSQLI_REPORT_OFF );

$dst = mysqli_init();
$dst->options( MYSQLI_OPT_CONNECT_TIMEOUT, 20 );

if ( ! @$dst->real_connect( $to['host'], rawurldecode( $to['user'] ), rawurldecode( $to['pass'] ?? '' ), ltrim( $to['path'], '/' ), (int) ( $to['port'] ?? 3306 ), null, isset( $opt['no-ssl'] ) ? 0 : MYSQLI_CLIENT_SSL ) ) {
	$fail( 'Could not connect to the destination database: ' . mysqli_connect_error() );
}

$run = static function ( $sql ) use ( $dst, $fail ) {
	if ( false === $dst->query( $sql ) ) {
		$fail( $dst->error . "\n  while running: " . substr( $sql, 0, 300 ) );
	}
};

$dst->set_charset( 'utf8mb4' );
// WordPress stores "no date yet" as 0000-00-00, which strict mode rejects.
$run( "SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'" );
$run( 'SET SESSION foreign_key_checks = 0' );
@$dst->query( 'SET SESSION sql_require_primary_key = 0' ); // managed MySQL; harmless elsewhere

$prefix   = $wpdb->prefix;
$existing = $dst->query( "SHOW TABLES LIKE '" . $dst->real_escape_string( $prefix ) . "%'" );
$existing = $existing ? array_column( $existing->fetch_all(), 0 ) : array();

if ( $existing && ! isset( $opt['replace'] ) ) {
	$fail( 'The destination already holds ' . count( $existing ) . " table(s) starting with {$prefix}. Nothing was changed.\n  Add --replace to overwrite that site with this one." );
}

foreach ( $existing as $table ) {
	$run( 'DROP TABLE `' . str_replace( '`', '', $table ) . '`' );
}

// ── Tables ──────────────────────────────────────────────────────────────────
$tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $prefix ) . '%' ) );
$files  = $prefix . 'id5_files';
$total  = 0;

foreach ( $tables as $table ) {
	if ( $table === $files ) {
		continue; // rebuilt below from the files themselves
	}

	$create = $wpdb->get_row( "SHOW CREATE TABLE `{$table}`", ARRAY_N ); // phpcs:ignore
	$ddl    = $create[1];

	// Spellings MariaDB prints that MySQL does not read.
	$ddl = preg_replace( '/\bcurrent_timestamp\(\)/i', 'CURRENT_TIMESTAMP', $ddl );
	$ddl = preg_replace( '/\s+PAGE_CHECKSUM=\d+|\s+TRANSACTIONAL=\d+|\s+ROW_FORMAT=PAGE/i', '', $ddl );
	$ddl = preg_replace( '/ENGINE=(Aria|MyISAM)/i', 'ENGINE=InnoDB', $ddl );

	$run( $ddl );

	$where = '';

	if ( $table === $wpdb->options ) {
		$where = " WHERE option_name NOT LIKE '\\_transient\\_%' AND option_name NOT LIKE '\\_site\\_transient\\_%'";
	} elseif ( $table === $wpdb->usermeta ) {
		$where = " WHERE meta_key <> 'session_tokens'";
	}

	$rows   = 0;
	$offset = 0;

	do {
		$batch = $wpdb->get_results( "SELECT * FROM `{$table}`{$where} LIMIT {$offset}, 500", ARRAY_A ); // phpcs:ignore
		$sql   = '';

		foreach ( $batch as $row ) {
			$values = array();

			foreach ( $row as $value ) {
				$values[] = null === $value ? 'NULL' : "'" . $dst->real_escape_string( $rewrite( $value ) ) . "'";
			}

			$line = '(' . implode( ',', $values ) . ')';

			if ( '' !== $sql && strlen( $sql ) + strlen( $line ) > 700000 ) {
				$run( $sql );
				$sql = '';
			}

			$sql .= ( '' === $sql ? "INSERT INTO `{$table}` (`" . implode( '`,`', array_keys( $row ) ) . '`) VALUES ' : ',' ) . $line;
			++$rows;
		}

		if ( '' !== $sql ) {
			$run( $sql );
		}

		$offset += 500;
	} while ( 500 === count( $batch ) );

	$total += $rows;
	$say( sprintf( '  %-32s %6d rows', $table, $rows ) );
}

// ── Settings that belong to the new home ────────────────────────────────────
$set_option = static function ( $name, $value ) use ( $dst, $run, $wpdb ) {
	$run( "DELETE FROM `{$wpdb->options}` WHERE option_name = '" . $dst->real_escape_string( $name ) . "'" );
	$run( "INSERT INTO `{$wpdb->options}` (option_name, option_value, autoload) VALUES ('" . $dst->real_escape_string( $name ) . "', '" . $dst->real_escape_string( $value ) . "', 'yes')" );
};

$set_option( 'siteurl', $new_wp );
$set_option( 'home', $new_wp );
$set_option( 'id5_public_site_url', $new_site );
$set_option( 'id5_webhook_url', $new_site . '/cms/webhook' );

// ── People ──────────────────────────────────────────────────────────────────
$credentials = array();

if ( ! isset( $opt['keep-passwords'] ) ) {
	foreach ( get_users( array( 'fields' => array( 'ID', 'user_login' ) ) ) as $user ) {
		$roles = (array) get_userdata( $user->ID )->roles;
		// The account the website signs in with uses an application password,
		// never this one; it still gets a new one so the old one stops working.
		$password = wp_generate_password( 20, false );

		$run( "UPDATE `{$wpdb->users}` SET user_pass = '" . $dst->real_escape_string( wp_hash_password( $password ) ) . "' WHERE ID = " . (int) $user->ID );

		$credentials[] = array( $user->user_login, implode( ', ', $roles ), $password );
	}
}

// ── Uploaded files ──────────────────────────────────────────────────────────
$run(
	"CREATE TABLE `{$files}` (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		path varchar(191) NOT NULL,
		seq int(10) unsigned NOT NULL DEFAULT 0,
		data longblob NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY path_seq (path, seq)
	) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci"
);
$set_option( 'id5_uploads_store_db', '1' );

$base   = trailingslashit( wp_get_upload_dir()['basedir'] );
$sent   = 0;
$bytes  = 0;
$absent = array();

foreach ( get_posts( array( 'post_type' => 'attachment', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $attachment ) {
	$main = get_post_meta( $attachment, '_wp_attached_file', true );
	$meta = wp_get_attachment_metadata( $attachment, true );
	$list = $main ? array( $main ) : array();
	$dir  = $main && '.' !== dirname( $main ) ? dirname( $main ) . '/' : '';

	foreach ( is_array( $meta ) && ! empty( $meta['sizes'] ) ? $meta['sizes'] : array() as $size ) {
		if ( ! empty( $size['file'] ) ) {
			$list[] = $dir . $size['file'];
		}
	}

	foreach ( array_unique( $list ) as $relative ) {
		if ( ! is_file( $base . $relative ) ) {
			$absent[] = $relative;
			continue;
		}

		$handle = fopen( $base . $relative, 'rb' ); // phpcs:ignore
		$seq    = 0;

		while ( ! feof( $handle ) ) {
			$chunk = fread( $handle, 524288 ); // phpcs:ignore

			if ( false === $chunk || ( '' === $chunk && $seq > 0 ) ) {
				break;
			}

			$run( "INSERT INTO `{$files}` (path, seq, data) VALUES ('" . $dst->real_escape_string( $relative ) . "', {$seq}, UNHEX('" . bin2hex( $chunk ) . "'))" );
			$bytes += strlen( $chunk );
			++$seq;
		}

		fclose( $handle ); // phpcs:ignore
		++$sent;
	}
}

$run( 'SET SESSION foreign_key_checks = 1' );

// ── Report ──────────────────────────────────────────────────────────────────
$say( '' );
$say( sprintf( 'Copied %d tables, %d rows, and %d uploaded file(s) (%s).', count( $tables ), $total, $sent, size_format( $bytes ) ?: '0 B' ) );
$say( "Addresses rewritten: {$old_wp} -> {$new_wp}" . ( $old_site ? " ; {$old_site} -> {$new_site}" : '' ) );

if ( $absent ) {
	$say( 'Listed in the Media Library but missing on this computer, so not copied: ' . implode( ', ', array_slice( $absent, 0, 8 ) ) . ( count( $absent ) > 8 ? ' …' : '' ) );
}

if ( $credentials ) {
	$report = "New passwords for {$new_wp}/wp-admin/ (shown once; change them after logging in):\n";

	foreach ( $credentials as $c ) {
		$report .= sprintf( "  %-16s %-28s %s\n", $c[0], '(' . $c[1] . ')', $c[2] );
	}

	if ( ! empty( $opt['credentials'] ) ) {
		file_put_contents( $opt['credentials'], $report ); // phpcs:ignore
		$say( 'New passwords written to ' . $opt['credentials'] . ' — keep that file private.' );
	} else {
		$say( '' );
		$say( rtrim( $report ) );
	}
}
