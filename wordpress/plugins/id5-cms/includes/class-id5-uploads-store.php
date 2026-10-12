<?php
/**
 * Keeps the Media Library's files in the database as well as on disk.
 *
 * Some hosts give WordPress a disk that is wiped whenever the service restarts
 * or goes to sleep. The database survives; the uploaded pictures would not, and
 * every picture in the Media Library would turn into a broken thumbnail.
 *
 * With ID5_PERSIST_UPLOADS defined, every file of every attachment — the
 * original and the sizes WordPress makes from it — is also written to the
 * table below, and restore_all() puts back whatever is missing. The container's
 * start-up script calls it before the web server starts.
 *
 * Without the constant (an ordinary host with a lasting disk) nothing here is
 * hooked and the table is never created.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

class ID5_Uploads_Store {

	const VERSION = '1';

	/** Small enough for any server's packet limit. */
	const CHUNK = 524288;

	public static function enabled() {
		return defined( 'ID5_PERSIST_UPLOADS' ) && ID5_PERSIST_UPLOADS;
	}

	public static function init() {
		if ( ! self::enabled() ) {
			return;
		}

		add_action( 'init', array( __CLASS__, 'install' ), 1 );

		// A new upload, once WordPress has made its sizes; and an edited image.
		add_filter( 'wp_generate_attachment_metadata', array( __CLASS__, 'on_metadata' ), 99, 2 );
		add_filter( 'wp_update_attachment_metadata', array( __CLASS__, 'on_metadata' ), 99, 2 );
		// Files with no sizes to make (a PDF on a server without a previewer).
		add_action( 'add_attachment', array( __CLASS__, 'keep_attachment' ), 99 );
		// Before WordPress removes the files, while they can still be listed.
		add_action( 'delete_attachment', array( __CLASS__, 'forget_attachment' ), 1 );

		// The sizes above 1024px are for themes that print huge pictures. The
		// website keeps its own copy of what it shows; here they would only
		// multiply the space every photo takes in the database.
		add_filter( 'intermediate_image_sizes_advanced', array( __CLASS__, 'fewer_sizes' ) );
		add_filter( 'big_image_size_threshold', array( __CLASS__, 'photo_limit' ) );
	}

	public static function table() {
		global $wpdb;

		return $wpdb->prefix . 'id5_files';
	}

	public static function install() {
		if ( get_option( 'id5_uploads_store_db' ) === self::VERSION ) {
			return;
		}

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table = self::table();

		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				path varchar(191) NOT NULL,
				seq int(10) unsigned NOT NULL DEFAULT 0,
				data longblob NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY path_seq (path, seq)
			) {$wpdb->get_charset_collate()};"
		);

		update_option( 'id5_uploads_store_db', self::VERSION, false );
	}

	// ─── Keeping ────────────────────────────────────────────────────────────

	public static function on_metadata( $metadata, $attachment_id ) {
		self::keep_attachment( $attachment_id, is_array( $metadata ) ? $metadata : null );

		return $metadata;
	}

	/**
	 * Every file that belongs to an attachment, relative to the uploads folder.
	 *
	 * @param int        $attachment_id Attachment.
	 * @param array|null $metadata      Its metadata, when the caller has a newer
	 *                                  copy than the database.
	 * @return string[]
	 */
	public static function files_of( $attachment_id, $metadata = null ) {
		$main = get_post_meta( $attachment_id, '_wp_attached_file', true );

		if ( ! $main ) {
			return array();
		}

		if ( null === $metadata ) {
			$metadata = wp_get_attachment_metadata( $attachment_id, true );
		}

		$files = array( $main );
		$dir   = '.' === dirname( $main ) ? '' : dirname( $main ) . '/';

		foreach ( is_array( $metadata ) && ! empty( $metadata['sizes'] ) ? $metadata['sizes'] : array() as $size ) {
			if ( ! empty( $size['file'] ) ) {
				$files[] = $dir . $size['file'];
			}
		}

		return array_values( array_unique( $files ) );
	}

	public static function keep_attachment( $attachment_id, $metadata = null ) {
		$base = trailingslashit( wp_get_upload_dir()['basedir'] );

		foreach ( self::files_of( $attachment_id, is_array( $metadata ) ? $metadata : null ) as $relative ) {
			if ( is_file( $base . $relative ) ) {
				self::keep( $relative, $base . $relative );
			}
		}
	}

	/**
	 * Store one file, replacing any earlier copy.
	 *
	 * @return bool False when the database refused it; the upload itself is
	 *              unaffected, it just would not survive a restart.
	 */
	public static function keep( $relative, $absolute ) {
		global $wpdb;

		$handle = fopen( $absolute, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		if ( ! $handle ) {
			return false;
		}

		$table = self::table();
		$wpdb->delete( $table, array( 'path' => $relative ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		$seq = 0;
		$ok  = true;

		while ( ! feof( $handle ) ) {
			$chunk = fread( $handle, self::CHUNK ); // phpcs:ignore WordPress.WP.AlternativeFunctions

			if ( false === $chunk || ( '' === $chunk && $seq > 0 ) ) {
				break;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$ok = false !== $wpdb->query( $wpdb->prepare( "INSERT INTO {$table} (path, seq, data) VALUES (%s, %d, UNHEX(%s))", $relative, $seq, bin2hex( $chunk ) ) );

			if ( ! $ok ) {
				break;
			}

			++$seq;
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		if ( ! $ok ) {
			$wpdb->delete( $table, array( 'path' => $relative ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		return $ok;
	}

	public static function forget_attachment( $attachment_id ) {
		global $wpdb;

		foreach ( self::files_of( $attachment_id ) as $relative ) {
			$wpdb->delete( self::table(), array( 'path' => $relative ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
	}

	// ─── Restoring ──────────────────────────────────────────────────────────

	/**
	 * Write back every stored file that is missing from the uploads folder.
	 *
	 * @return array{restored:int,present:int,failed:int}
	 */
	public static function restore_all() {
		global $wpdb;

		$count = array( 'restored' => 0, 'present' => 0, 'failed' => 0 );
		$table = self::table();
		$base  = trailingslashit( wp_get_upload_dir()['basedir'] );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( (array) $wpdb->get_col( "SELECT DISTINCT path FROM {$table} ORDER BY path" ) as $relative ) {
			// Stored paths are WordPress's own, but nothing is ever written
			// outside the uploads folder whatever a row says.
			if ( '' === $relative || false !== strpos( $relative, '..' ) || '/' === $relative[0] ) {
				++$count['failed'];
				continue;
			}

			if ( is_file( $base . $relative ) ) {
				++$count['present'];
				continue;
			}

			++$count[ self::restore( $relative, $base . $relative ) ? 'restored' : 'failed' ];
		}

		return $count;
	}

	private static function restore( $relative, $absolute ) {
		global $wpdb;

		if ( ! wp_mkdir_p( dirname( $absolute ) ) ) {
			return false;
		}

		$table  = self::table();
		$handle = fopen( $absolute . '.part', 'wb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		if ( ! $handle ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$chunks = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE path = %s", $relative ) );

		// One chunk in memory at a time.
		for ( $seq = 0; $seq < $chunks; $seq++ ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$data = $wpdb->get_var( $wpdb->prepare( "SELECT data FROM {$table} WHERE path = %s AND seq = %d", $relative, $seq ) );

			if ( null === $data ) {
				fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				unlink( $absolute . '.part' ); // phpcs:ignore WordPress.WP.AlternativeFunctions

				return false;
			}

			fwrite( $handle, $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		return rename( $absolute . '.part', $absolute ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

	// ─── Keeping the database small ─────────────────────────────────────────

	public static function fewer_sizes( $sizes ) {
		unset( $sizes['1536x1536'], $sizes['2048x2048'] );

		return $sizes;
	}

	/** Photos straight from a camera are scaled to this before being stored. */
	public static function photo_limit() {
		return 1920;
	}
}
