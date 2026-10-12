<?php
/**
 * Automatic numbering on the ITA page.
 *
 * Every ITA section shows its number in a yellow badge, and every topic shows
 * its own in a coloured one. Both are ordinary fields an editor can type — but
 * a section is most often created from the small "add new section" box on the
 * topic form, which only asks for a name, and the badge then came out empty.
 *
 * So a number left blank is filled in here with the next one in sequence, in
 * the style already in use ("1, 2, 3", "O1, O2", "01, 02"). A number an editor
 * typed is never changed.
 *
 * The same goes for a person added to a row of the organisation chart: left
 * without a position, they take the place after the last person of the row.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

class ID5_Numbering {

	const TAXONOMY  = 'id5_ita_section';
	const POST_TYPE = 'id5_ita_card';
	const VERSION   = '1';

	const PERSON         = 'id5_person';
	const PERSON_SECTION = 'id5_org_section';

	public static function init() {
		// After ID5_Fields has stored whatever the form sent (priority 10)…
		add_action( 'created_' . self::TAXONOMY, array( __CLASS__, 'number_section' ), 20 );
		// …and before ID5_Webhook announces the save (priority 20). The generic
		// hook, not save_post_{type}: that one fires before the fields are stored.
		add_action( 'save_post', array( __CLASS__, 'number_topic' ), 15, 2 );
		add_action( 'save_post', array( __CLASS__, 'place_person' ), 15, 2 );
		add_action( 'admin_init', array( __CLASS__, 'fill_existing_blanks' ) );
	}

	// ─── The sequence ───────────────────────────────────────────────────────

	/**
	 * The number that follows a list of existing ones, written the same way.
	 *
	 * "1","2" -> "3";  "O1","O2" -> "O3";  "01","02","03" -> "04".
	 * With nothing to follow, $first is used.
	 *
	 * @param string[] $existing Numbers already in use.
	 * @param string   $first    What to start a new sequence with.
	 */
	public static function next( array $existing, $first ) {
		$highest = -1;
		$prefix  = '';
		$width   = 0;

		foreach ( $existing as $value ) {
			if ( ! preg_match( '/^(\D*)(\d+)\s*$/u', trim( (string) $value ), $m ) ) {
				continue;
			}

			if ( (int) $m[2] > $highest ) {
				$highest = (int) $m[2];
				$prefix  = $m[1];
				// Leading zeros mean a fixed width ("01"); otherwise none.
				$width = '0' === $m[2][0] && strlen( $m[2] ) > 1 ? strlen( $m[2] ) : 0;
			}
		}

		if ( $highest < 0 ) {
			// Nothing numeric to follow: count what is there and carry on.
			return $existing ? (string) ( count( $existing ) + 1 ) : $first;
		}

		return $prefix . ( $width ? str_pad( (string) ( $highest + 1 ), $width, '0', STR_PAD_LEFT ) : (string) ( $highest + 1 ) );
	}

	// ─── Sections ───────────────────────────────────────────────────────────

	/**
	 * Give a new section the next number, and place it after the others.
	 */
	public static function number_section( $term_id ) {
		$numbers = array();
		$orders  = array();

		$others = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'exclude'    => array( (int) $term_id ),
			)
		);

		foreach ( is_wp_error( $others ) ? array() : $others as $other ) {
			$number = (string) get_term_meta( $other->term_id, ID5_Fields::PREFIX . 'section_number', true );

			if ( '' !== trim( $number ) ) {
				$numbers[] = $number;
			}

			$orders[] = (int) get_term_meta( $other->term_id, ID5_Fields::PREFIX . 'sort_order', true );
		}

		if ( '' === trim( (string) get_term_meta( $term_id, ID5_Fields::PREFIX . 'section_number', true ) ) ) {
			update_term_meta( $term_id, ID5_Fields::PREFIX . 'section_number', self::next( $numbers, '1' ) );
		}

		// An order of 0 is what the form sends when nobody touched it; a new
		// section belongs at the end, not at the top.
		if ( $orders && 0 === (int) get_term_meta( $term_id, ID5_Fields::PREFIX . 'sort_order', true ) ) {
			update_term_meta( $term_id, ID5_Fields::PREFIX . 'sort_order', max( $orders ) + 1 );
		}
	}

	// ─── Topics ─────────────────────────────────────────────────────────────

	/**
	 * Give a topic saved without a number the next one in its section.
	 */
	public static function number_topic( $post_id, $post ) {
		if ( self::POST_TYPE !== $post->post_type || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || in_array( $post->post_status, array( 'auto-draft', 'trash' ), true ) ) {
			return;
		}

		if ( '' !== trim( (string) get_post_meta( $post_id, ID5_Fields::PREFIX . 'card_number', true ) ) ) {
			return;
		}

		$sections = wp_get_object_terms( $post_id, self::TAXONOMY, array( 'fields' => 'ids' ) );

		if ( is_wp_error( $sections ) || ! $sections ) {
			return; // numbered when it is given a section
		}

		$siblings = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post__not_in'   => array( (int) $post_id ),
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'taxonomy' => self::TAXONOMY,
						'terms'    => array( (int) $sections[0] ),
					),
				),
			)
		);

		$numbers = array();

		foreach ( $siblings as $sibling ) {
			$number = (string) get_post_meta( $sibling, ID5_Fields::PREFIX . 'card_number', true );

			if ( '' !== trim( $number ) ) {
				$numbers[] = $number;
			}
		}

		update_post_meta( $post_id, ID5_Fields::PREFIX . 'card_number', self::next( $numbers, '01' ) );
	}

	// ─── People on the organisation chart ───────────────────────────────────

	/**
	 * Put a person saved without a position at the end of their row.
	 *
	 * The people of a row are numbered 1, 2, 3…; 0 is what the form sends when
	 * nobody touched the box, and it would put a newcomer in front of everyone.
	 */
	public static function place_person( $post_id, $post ) {
		if ( self::PERSON !== $post->post_type || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || in_array( $post->post_status, array( 'auto-draft', 'trash' ), true ) ) {
			return;
		}

		$row      = trim( (string) get_post_meta( $post_id, ID5_Fields::PREFIX . 'tier_label_th', true ) );
		$sections = wp_get_object_terms( $post_id, self::PERSON_SECTION, array( 'fields' => 'ids' ) );

		if ( 0 !== (int) $post->menu_order || '' === $row || is_wp_error( $sections ) || ! $sections ) {
			return;
		}

		$last = get_posts(
			array(
				'post_type'      => self::PERSON,
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page' => 1,
				'post__not_in'   => array( (int) $post_id ),
				'orderby'        => 'menu_order',
				'order'          => 'DESC',
				'meta_key'       => ID5_Fields::PREFIX . 'tier_label_th', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $row, // phpcs:ignore WordPress.DB.SlowDBQuery
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'taxonomy' => self::PERSON_SECTION,
						'terms'    => array( (int) $sections[0] ),
					),
				),
			)
		);

		if ( ! $last ) {
			return; // the first person of a new row
		}

		global $wpdb;

		$position = (int) $last[0]->menu_order + 1;

		// Straight to the table: wp_update_post() would run this hook again.
		$wpdb->update( $wpdb->posts, array( 'menu_order' => $position ), array( 'ID' => (int) $post_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		clean_post_cache( $post_id );

		// The same object goes on to the hooks that follow.
		$post->menu_order = $position;
	}

	// ─── Sections made before this existed ──────────────────────────────────

	/**
	 * Number the sections that were created without one, oldest first, once.
	 */
	public static function fill_existing_blanks() {
		if ( get_option( 'id5_numbering_filled' ) === self::VERSION ) {
			return;
		}

		update_option( 'id5_numbering_filled', self::VERSION, false );

		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
			)
		);

		$changed = false;

		foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
			if ( '' === trim( (string) get_term_meta( $term->term_id, ID5_Fields::PREFIX . 'section_number', true ) ) ) {
				self::number_section( $term->term_id );
				$changed = true;
			}
		}

		if ( $changed ) {
			ID5_Webhook::notify( array( 'event' => 'terms.updated', 'taxonomy' => self::TAXONOMY ) );
		}
	}
}
