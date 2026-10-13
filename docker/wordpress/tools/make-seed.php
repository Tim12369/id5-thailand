<?php
/**
 * Writes the starting content of the hosted WordPress: docker/wordpress/seed.
 *
 * The hosted install is created empty the first time the service starts. The
 * seed is what it is then filled with — the content of a working WordPress,
 * read from the computer that has it:
 *
 *   php docker/wordpress/tools/make-seed.php --from=C:/xampp/htdocs/id5-wordpress
 *
 * The seed is committed to the repository, which is public, so it holds
 * content only and nothing that could let anyone in:
 *
 *   - no passwords or password hashes (every account gets a new random
 *     password when the site is created), no login sessions, no application
 *     passwords, no e-mail addresses;
 *   - no secrets, keys or tokens from the settings — only an explicit list of
 *     ordinary settings is taken;
 *   - no items in the trash, no revisions, no unfinished auto-drafts.
 *
 * It is used once. After the site exists, editors change it online and the
 * seed is never read again.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$opt  = getopt( '', array( 'from:' ) );
$from = rtrim( str_replace( '\\', '/', $opt['from'] ?? '' ), '/' );

if ( ! is_file( $from . '/wp-load.php' ) ) {
	fwrite( STDERR, "Usage: php make-seed.php --from=<folder of the working WordPress>\n" );
	exit( 1 );
}

$_SERVER['HTTP_HOST']      = 'localhost';
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';

define( 'WP_USE_THEMES', false );

require $from . '/wp-load.php';

global $wpdb;

$seed = dirname( __DIR__ ) . '/seed';

// Start clean, so nothing from an earlier seed lingers.
$wipe = static function ( $dir ) use ( &$wipe ) {
	foreach ( is_dir( $dir ) ? scandir( $dir ) : array() as $item ) {
		if ( '.' === $item || '..' === $item ) {
			continue;
		}

		is_dir( "$dir/$item" ) ? $wipe( "$dir/$item" ) : unlink( "$dir/$item" );
	}

	if ( is_dir( $dir ) ) {
		rmdir( $dir );
	}
};

$wipe( $seed );
mkdir( $seed . '/tables', 0777, true );
mkdir( $seed . '/uploads', 0777, true );

$tables = array();

$write = static function ( $table, array $rows ) use ( $seed, &$tables, $wpdb ) {
	$name    = substr( $table, strlen( $wpdb->prefix ) );
	$columns = $rows ? array_keys( $rows[0] ) : array();
	$lines   = array();

	foreach ( $rows as $row ) {
		$lines[] = json_encode( array_values( $row ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE );
	}

	// One row per line: a change to the seed shows as a readable difference.
	file_put_contents( "$seed/tables/$name.jsonl", $lines ? implode( "\n", $lines ) . "\n" : '' );

	$tables[ $name ] = array( 'columns' => $columns, 'rows' => count( $rows ) );
};

// ── Content ─────────────────────────────────────────────────────────────────
$posts = $wpdb->get_results(
	"SELECT * FROM {$wpdb->posts}
	 WHERE post_status NOT IN ('trash', 'auto-draft') AND post_type <> 'revision'
	 ORDER BY ID",
	ARRAY_A
);

$base    = trailingslashit( wp_get_upload_dir()['basedir'] );
$uploads = array();
$kept    = array();

foreach ( $posts as $post ) {
	if ( 'attachment' === $post['post_type'] ) {
		$main = get_post_meta( $post['ID'], '_wp_attached_file', true );

		// An entry whose file is gone would only be a broken thumbnail.
		if ( ! $main || ! is_file( $base . $main ) ) {
			continue;
		}

		$meta = wp_get_attachment_metadata( $post['ID'], true );
		$dir  = '.' === dirname( $main ) ? '' : dirname( $main ) . '/';
		$list = array( $main );

		foreach ( is_array( $meta ) && ! empty( $meta['sizes'] ) ? $meta['sizes'] : array() as $size ) {
			if ( ! empty( $size['file'] ) && is_file( $base . $dir . $size['file'] ) ) {
				$list[] = $dir . $size['file'];
			}
		}

		foreach ( array_unique( $list ) as $relative ) {
			@mkdir( dirname( "$seed/uploads/$relative" ), 0777, true );
			copy( $base . $relative, "$seed/uploads/$relative" );
			$uploads[] = $relative;
		}
	}

	// Never a password-protected post's password.
	$post['post_password'] = '';

	$kept[ (int) $post['ID'] ] = $post;
}

$write( $wpdb->posts, array_values( $kept ) );

$ids = implode( ',', array_keys( $kept ) ) ?: '0';

$write(
	$wpdb->postmeta,
	$wpdb->get_results(
		"SELECT * FROM {$wpdb->postmeta}
		 WHERE post_id IN ($ids)
		   AND meta_key NOT IN ('_edit_lock', '_edit_last', '_wp_desired_post_slug', '_pingme', '_encloseme')
		   AND meta_key NOT LIKE '\\_wp\\_trash\\_%'
		 ORDER BY meta_id",
		ARRAY_A
	)
);

$write( $wpdb->terms, $wpdb->get_results( "SELECT * FROM {$wpdb->terms} ORDER BY term_id", ARRAY_A ) );
$write( $wpdb->termmeta, $wpdb->get_results( "SELECT * FROM {$wpdb->termmeta} ORDER BY meta_id", ARRAY_A ) );
$write( $wpdb->term_taxonomy, $wpdb->get_results( "SELECT * FROM {$wpdb->term_taxonomy} ORDER BY term_taxonomy_id", ARRAY_A ) );
$write( $wpdb->term_relationships, $wpdb->get_results( "SELECT * FROM {$wpdb->term_relationships} WHERE object_id IN ($ids) ORDER BY object_id, term_taxonomy_id", ARRAY_A ) );

// The wording editors have changed.
$texts = $wpdb->prefix . 'id5_texts';

if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $texts ) ) ) {
	$write( $texts, $wpdb->get_results( "SELECT * FROM `{$texts}` ORDER BY text_key, locale", ARRAY_A ) );
}

// Nothing is carried over for these; they are emptied of WordPress's samples.
foreach ( array( $wpdb->comments, $wpdb->commentmeta, $wpdb->links ) as $table ) {
	$write( $table, array() );
}

// ── People: who they are, never how they get in ─────────────────────────────
$users = array();

foreach ( $wpdb->get_results( "SELECT * FROM {$wpdb->users} ORDER BY ID", ARRAY_A ) as $user ) {
	$user['user_pass']           = '';
	$user['user_email']          = $user['user_login'] . '@id5.invalid';
	$user['user_url']            = '';
	$user['user_activation_key'] = '';

	$users[] = $user;
}

$write( $wpdb->users, $users );

$write(
	$wpdb->usermeta,
	$wpdb->get_results(
		"SELECT * FROM {$wpdb->usermeta}
		 WHERE meta_key IN ('nickname', 'first_name', 'last_name', 'description', 'rich_editing', 'syntax_highlighting',
		                    'comment_shortcuts', 'admin_color', 'use_ssl', 'show_admin_bar_front', 'locale',
		                    '{$wpdb->prefix}capabilities', '{$wpdb->prefix}user_level')
		 ORDER BY umeta_id",
		ARRAY_A
	)
);

// ── Settings: an explicit list, so nothing secret can ride along ─────────────
$theme   = get_option( 'stylesheet' );
$allowed = array(
	// WordPress's own, ordinary settings.
	'blogname', 'blogdescription', 'WPLANG', 'timezone_string', 'gmt_offset', 'date_format', 'time_format', 'start_of_week',
	'permalink_structure', 'category_base', 'tag_base', 'default_category', 'default_post_format', 'show_on_front',
	'page_on_front', 'page_for_posts', 'posts_per_page', 'blog_public', 'blog_charset', 'users_can_register',
	'default_role', 'default_comment_status', 'default_ping_status', 'comment_registration', 'close_comments_for_old_posts',
	'uploads_use_yearmonth_folders', 'thumbnail_size_w', 'thumbnail_size_h', 'thumbnail_crop', 'medium_size_w',
	'medium_size_h', 'medium_large_size_w', 'medium_large_size_h', 'large_size_w', 'large_size_h', 'image_default_size',
	'image_default_align', 'image_default_link_type', 'template', 'stylesheet', 'current_theme', 'db_version',
	'initial_db_version', 'fresh_site', 'nav_menu_options', 'wp_page_for_privacy_policy', 'show_avatars',
	'category_children', 'id5_content_category_children', 'id5_form_category_children', 'sticky_posts', 'site_icon',
	$wpdb->prefix . 'user_roles',
	"theme_mods_{$theme}",
	// The plugin's own. (Its connection settings are applied on the host.)
	'id5_site_name', 'id5_roles_version', 'id5_numbering_filled', 'id5_texts_db', 'id5_texts_used',
);

$options = array();
$skipped = array();

foreach ( $wpdb->get_results( "SELECT * FROM {$wpdb->options} ORDER BY option_name", ARRAY_A ) as $option ) {
	$name = $option['option_name'];

	// The Customizer keeps the website's settings under names of its own.
	$mine = 0 === strpos( $name, 'id5_setting_' ) || 0 === strpos( $name, 'id5_customizer' ) || 0 === strpos( $name, 'id5_site_' ) || 0 === strpos( $name, 'id5_home_' ) || 0 === strpos( $name, 'id5_footer_' ) || 0 === strpos( $name, 'id5_hero_' );

	if ( in_array( $name, $allowed, true ) || $mine ) {
		$options[] = $option;
	} elseif ( 0 !== strpos( $name, '_transient_' ) && 0 !== strpos( $name, '_site_transient_' ) ) {
		$skipped[] = $name;
	}
}

$write( $wpdb->options, $options );

// ── What the seed is, and where it came from ────────────────────────────────
file_put_contents(
	"$seed/manifest.json",
	json_encode(
		array(
			'made_at'   => gmdate( 'c' ),
			'wordpress' => get_bloginfo( 'version' ),
			// The addresses inside the content, replaced by the host's own on loading.
			'wp_url'    => untrailingslashit( home_url() ),
			'site_url'  => untrailingslashit( (string) get_option( 'id5_public_site_url' ) ),
			'tables'    => $tables,
			'uploads'   => array_values( array_unique( $uploads ) ),
		),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	) . "\n"
);

foreach ( $tables as $name => $info ) {
	printf( "  %-22s %6d rows\n", $name, $info['rows'] );
}

printf( "  %-22s %6d files\n", 'uploads', count( array_unique( $uploads ) ) );
echo "\nSettings left out (review: none of these may be needed, none may be secret):\n  " . wordwrap( implode( ', ', $skipped ), 110, "\n  " ) . "\n";
