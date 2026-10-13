<?php
/**
 * Plugin Name: ID5 — TiDB compatibility
 * Description: Lets WordPress run on a TiDB database (TiDB Cloud's free plan), which speaks MySQL but lacks one thing WordPress still uses.
 *
 * WordPress counts the rows of a paginated list ("page 1 of 12") by adding
 * SQL_CALC_FOUND_ROWS to the query and then asking SELECT FOUND_ROWS(). MySQL
 * has deprecated both and TiDB refuses the first outright, so every list of
 * posts, users or comments would fail.
 *
 * Here the keyword is taken out of the query, and the question that follows
 * is answered by counting the same query without its LIMIT. On any other
 * database this file does nothing.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'query',
	static function ( $sql ) {
		static $tidb = null;
		static $last = null;

		if ( null === $tidb ) {
			global $wpdb;

			$tidb = false !== stripos( (string) $wpdb->db_server_info(), 'tidb' );
		}

		if ( ! $tidb || ! is_string( $sql ) ) {
			return $sql;
		}

		if ( preg_match( '/^\s*SELECT\s+SQL_CALC_FOUND_ROWS\s/i', $sql ) ) {
			$sql  = preg_replace( '/^(\s*SELECT)\s+SQL_CALC_FOUND_ROWS\s/i', '$1 ', $sql, 1 );
			$last = $sql;

			return $sql;
		}

		if ( null !== $last && preg_match( '/^\s*SELECT\s+FOUND_ROWS\(\s*\)\s*;?\s*$/i', $sql ) ) {
			// The same rows, all of them: drop the page window from the end.
			$all = preg_replace( '/\sLIMIT\s+\d+(?:\s*,\s*\d+|\s+OFFSET\s+\d+)?\s*;?\s*$/i', '', $last );

			return "SELECT COUNT(*) FROM ( {$all} ) AS id5_found_rows";
		}

		return $sql;
	}
);
