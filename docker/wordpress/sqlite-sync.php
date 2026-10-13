<?php
/**
 * Keeps WordPress's SQLite database in PostgreSQL.
 *
 * WordPress runs here on a single SQLite file. This host wipes its disk at
 * every restart, so the file is mirrored, block by block, into the PostgreSQL
 * database the website already uses — the one thing on this host that lasts.
 *
 *   php sqlite-sync.php restore   write the saved database back to disk
 *                                 (exit 0 restored, 3 nothing saved yet, 1 error)
 *   php sqlite-sync.php push      save the database now
 *   php sqlite-sync.php daemon    save it whenever it changes, until stopped
 *
 * Only blocks that changed are sent, in one transaction, so what PostgreSQL
 * holds is always a complete, consistent database — never half of an update.
 *
 * Two containers can be alive at once for a moment (a new version starting
 * while the old one still answers). The one that restored last owns the saved
 * copy; the other notices and stops saving, so it cannot overwrite newer data.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

const ID5_DB    = '/var/www/wordpress-data/site.sqlite';
const ID5_STATE = '/var/www/wordpress-data/.sync-state';
const ID5_OWNER = '/var/www/wordpress-data/.instance';
const ID5_BLOCK = 65536;

// What happened last, for the status shown to the administrator in WordPress
// (this host's logs are not something an editor can read).
const ID5_STATUS = '/var/www/wordpress-data/.sync-status.json';

function id5_status( array $facts ) {
	$now = is_file( ID5_STATUS ) ? (array) json_decode( (string) file_get_contents( ID5_STATUS ), true ) : array();

	@file_put_contents( ID5_STATUS, json_encode( array_merge( $now, $facts ) ) );
}

function id5_log( $message ) {
	fwrite( STDOUT, '[id5-sync] ' . $message . "\n" );
}

function id5_env( $key, $default = '' ) {
	$value = getenv( $key );

	return ( false === $value || '' === $value ) ? $default : $value;
}

/**
 * The website's database, as it is configured for the website itself: either
 * the separate DB_* settings or one DB_URL.
 *
 * @return array{0:string,1:string,2:string} DSN, user, password.
 */
function id5_pg_settings() {
	$url = parse_url( id5_env( 'DB_URL', id5_env( 'DATABASE_URL' ) ) );

	if ( is_array( $url ) && ! empty( $url['host'] ) ) {
		parse_str( $url['query'] ?? '', $query );

		return array(
			sprintf( 'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s', $url['host'], $url['port'] ?? 5432, ltrim( $url['path'] ?? '/postgres', '/' ), $query['sslmode'] ?? id5_env( 'DB_SSLMODE', 'prefer' ) ),
			rawurldecode( $url['user'] ?? '' ),
			rawurldecode( $url['pass'] ?? '' ),
		);
	}

	return array(
		sprintf( 'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s', id5_env( 'DB_HOST', '127.0.0.1' ), id5_env( 'DB_PORT', '5432' ), id5_env( 'DB_DATABASE', 'postgres' ), id5_env( 'DB_SSLMODE', 'prefer' ) ),
		id5_env( 'DB_USERNAME' ),
		id5_env( 'DB_PASSWORD' ),
	);
}

function id5_pg() {
	list( $dsn, $user, $password ) = id5_pg_settings();

	$pdo = new PDO(
		$dsn,
		$user,
		$password,
		array(
			PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
			// A pooled connection (PgBouncer) cannot keep server-side statements.
			PDO::ATTR_EMULATE_PREPARES => true,
			PDO::ATTR_TIMEOUT          => 20,
		)
	);

	$pdo->exec( 'CREATE TABLE IF NOT EXISTS wp_sqlite_blocks (block integer PRIMARY KEY, hash char(32) NOT NULL, data bytea NOT NULL)' );
	$pdo->exec( 'CREATE TABLE IF NOT EXISTS wp_sqlite_meta (id smallint PRIMARY KEY, size bigint NOT NULL, blocks integer NOT NULL, generation bigint NOT NULL, owner text NOT NULL, updated_at timestamptz NOT NULL DEFAULT now())' );

	return $pdo;
}

/** This container's name for itself, made once per start. */
function id5_instance() {
	if ( ! is_file( ID5_OWNER ) ) {
		file_put_contents( ID5_OWNER, bin2hex( random_bytes( 8 ) ) );
	}

	return trim( (string) file_get_contents( ID5_OWNER ) );
}

/**
 * Write the saved database to disk and take ownership of it.
 *
 * @return bool False when nothing has been saved yet.
 */
function id5_restore() {
	$pdo = id5_pg();

	$pdo->beginTransaction();

	$meta = $pdo->query( 'SELECT size, blocks, generation FROM wp_sqlite_meta WHERE id = 1 FOR UPDATE' )->fetch( PDO::FETCH_ASSOC );

	if ( ! $meta ) {
		$pdo->commit();
		id5_status( array( 'start' => 'nothing was saved yet', 'start_at' => time() ) );

		return false;
	}

	$tmp    = ID5_DB . '.restoring';
	$handle = fopen( $tmp, 'wb' );
	$hashes = array();
	$select = $pdo->prepare( "SELECT block, hash, encode(data, 'base64') AS data FROM wp_sqlite_blocks WHERE block >= ? AND block < ? ORDER BY block" );

	for ( $from = 0; $from < (int) $meta['blocks']; $from += 32 ) {
		$select->execute( array( $from, $from + 32 ) );

		foreach ( $select->fetchAll( PDO::FETCH_ASSOC ) as $row ) {
			if ( (int) $row['block'] !== count( $hashes ) ) {
				throw new RuntimeException( 'The saved database has a gap at block ' . count( $hashes ) . '.' );
			}

			fwrite( $handle, base64_decode( $row['data'] ) );
			$hashes[] = $row['hash'];
		}
	}

	if ( count( $hashes ) !== (int) $meta['blocks'] ) {
		throw new RuntimeException( 'The saved database is incomplete.' );
	}

	ftruncate( $handle, (int) $meta['size'] );
	fclose( $handle );

	$pdo->prepare( 'UPDATE wp_sqlite_meta SET owner = ? WHERE id = 1' )->execute( array( id5_instance() ) );
	$pdo->commit();

	// A journal left by anything else must not be replayed onto this file.
	@unlink( ID5_DB . '-wal' );
	@unlink( ID5_DB . '-shm' );
	rename( $tmp, ID5_DB );

	file_put_contents( ID5_STATE, json_encode( array( 'hashes' => $hashes, 'generation' => (int) $meta['generation'] ) ) );
	id5_log( sprintf( 'restored %s in %d blocks (generation %d).', number_format( (int) $meta['size'] ) . ' bytes', count( $hashes ), $meta['generation'] ) );
	id5_status( array( 'start' => 'restored generation ' . (int) $meta['generation'], 'start_at' => time() ) );

	return true;
}

/**
 * Save the database if it differs from what is saved.
 *
 * @return string 'saved', 'unchanged', 'absent' or 'not-owner'.
 */
function id5_push() {
	if ( ! is_file( ID5_DB ) ) {
		return 'absent';
	}

	// A consistent copy, taken page by page while WordPress keeps working. The
	// page layout is preserved, so unchanged parts hash the same as last time.
	$snapshot = ID5_DB . '.snapshot';
	@unlink( $snapshot );

	$source = new SQLite3( ID5_DB, SQLITE3_OPEN_READONLY );
	$source->busyTimeout( 15000 );
	$copy = new SQLite3( $snapshot );

	if ( ! $source->backup( $copy ) ) {
		throw new RuntimeException( 'Could not take a copy of the database: ' . $source->lastErrorMsg() );
	}

	$copy->close();
	$source->close();

	$state   = is_file( ID5_STATE ) ? (array) json_decode( (string) file_get_contents( ID5_STATE ), true ) : array();
	$known   = isset( $state['hashes'] ) ? (array) $state['hashes'] : array();
	$size    = filesize( $snapshot );
	$handle  = fopen( $snapshot, 'rb' );
	$hashes  = array();
	$changed = array();

	for ( $block = 0; ! feof( $handle ); $block++ ) {
		$data = fread( $handle, ID5_BLOCK );

		if ( false === $data || '' === $data ) {
			break;
		}

		$hashes[ $block ] = md5( $data );

		if ( ! isset( $known[ $block ] ) || $known[ $block ] !== $hashes[ $block ] ) {
			$changed[] = $block;
		}
	}

	if ( ! $changed && count( $hashes ) === count( $known ) ) {
		fclose( $handle );
		unlink( $snapshot );

		return 'unchanged';
	}

	$pdo = id5_pg();
	$pdo->beginTransaction();

	$meta = $pdo->query( 'SELECT generation, owner FROM wp_sqlite_meta WHERE id = 1 FOR UPDATE' )->fetch( PDO::FETCH_ASSOC );

	if ( $meta && $meta['owner'] !== id5_instance() ) {
		$pdo->rollBack();
		fclose( $handle );
		unlink( $snapshot );

		return 'not-owner';
	}

	$upsert = $pdo->prepare( "INSERT INTO wp_sqlite_blocks (block, hash, data) VALUES (?, ?, decode(?, 'base64')) ON CONFLICT (block) DO UPDATE SET hash = EXCLUDED.hash, data = EXCLUDED.data" );

	foreach ( $changed as $block ) {
		fseek( $handle, $block * ID5_BLOCK );
		$upsert->execute( array( $block, $hashes[ $block ], base64_encode( fread( $handle, ID5_BLOCK ) ) ) );
	}

	fclose( $handle );

	$pdo->prepare( 'DELETE FROM wp_sqlite_blocks WHERE block >= ?' )->execute( array( count( $hashes ) ) );

	$generation = $meta ? (int) $meta['generation'] + 1 : 1;

	$pdo->prepare( 'INSERT INTO wp_sqlite_meta (id, size, blocks, generation, owner, updated_at) VALUES (1, ?, ?, ?, ?, now()) ON CONFLICT (id) DO UPDATE SET size = EXCLUDED.size, blocks = EXCLUDED.blocks, generation = EXCLUDED.generation, owner = EXCLUDED.owner, updated_at = now()' )
		->execute( array( $size, count( $hashes ), $generation, id5_instance() ) );

	$pdo->commit();
	unlink( $snapshot );

	file_put_contents( ID5_STATE, json_encode( array( 'hashes' => array_values( $hashes ), 'generation' => $generation ) ) );
	id5_log( sprintf( 'saved %d changed block(s) of %d (generation %d).', count( $changed ), count( $hashes ), $generation ) );
	id5_status( array( 'saved_at' => time(), 'generation' => $generation, 'size' => $size, 'error' => null ) );

	return 'saved';
}

/** Something that changes whenever WordPress writes to its database. */
function id5_fingerprint() {
	clearstatcache();

	$print = '';

	foreach ( array( ID5_DB, ID5_DB . '-wal' ) as $file ) {
		$print .= is_file( $file ) ? filemtime( $file ) . ':' . filesize( $file ) . ';' : '-;';
	}

	return $print;
}

$command = $argv[1] ?? '';

try {
	if ( 'restore' === $command ) {
		// The database may be waking from its own sleep: give it a few tries.
		for ( $attempt = 1; ; $attempt++ ) {
			try {
				exit( id5_restore() ? 0 : 3 );
			} catch ( Throwable $e ) {
				if ( $attempt >= 8 ) {
					id5_status( array( 'start' => 'the saved copy could not be read: ' . $e->getMessage(), 'start_at' => time() ) );

					throw $e;
				}

				id5_log( 'could not read the saved database yet (' . $e->getMessage() . '), attempt ' . $attempt . '/8' );
				sleep( 5 );
			}
		}
	}

	if ( 'push' === $command ) {
		id5_log( 'push: ' . id5_push() );
		exit( 0 );
	}

	if ( 'daemon' === $command ) {
		$stop = false;

		id5_status( array( 'daemon_pid' => getmypid(), 'daemon_started_at' => time(), 'instance' => id5_instance() ) );

		if ( function_exists( 'pcntl_async_signals' ) ) {
			pcntl_async_signals( true );

			foreach ( array( SIGTERM, SIGINT ) as $signal ) {
				pcntl_signal(
					$signal,
					static function () use ( &$stop ) {
						$stop = true;
					}
				);
			}
		}

		$saved = null;

		while ( ! $stop ) {
			$now = id5_fingerprint();

			if ( $now !== $saved ) {
				// Let a burst of writes (one page save is many statements) settle.
				usleep( 700000 );
				$now = id5_fingerprint();

				try {
					$result = id5_push();

					if ( 'not-owner' === $result ) {
						id5_log( 'a newer instance has taken over the saved database; this one stops saving.' );
						id5_status( array( 'error' => 'a newer instance has taken over; this one no longer saves', 'error_at' => time() ) );
						// 9: deliberate, so the start-up script does not start it again.
						exit( 9 );
					}

					$saved = $now;
					id5_status( array( 'checked_at' => time(), 'last' => $result ) );
				} catch ( Throwable $e ) {
					id5_log( 'could not save just now (' . $e->getMessage() . '); will try again.' );
					id5_status( array( 'error' => $e->getMessage(), 'error_at' => time() ) );
					sleep( 5 );
				}
			}

			sleep( 2 );
		}

		// Stopping: whatever changed in the last moments is saved before leaving.
		try {
			id5_log( 'stopping; final save: ' . id5_push() );
		} catch ( Throwable $e ) {
			id5_log( 'final save failed: ' . $e->getMessage() );
		}

		exit( 0 );
	}
} catch ( Throwable $e ) {
	id5_log( 'ERROR: ' . $e->getMessage() );
	exit( 1 );
}

fwrite( STDERR, "Usage: php sqlite-sync.php restore|push|daemon\n" );
exit( 2 );
