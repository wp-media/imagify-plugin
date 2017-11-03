<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Imagify DB class. It reunites tools to work with the DB.
 *
 * @since  1.6.13
 * @author Grégory Viguier
 */
class Imagify_DB {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0';

	/**
	 * Some hosts limit the number of JOINs in SQL queries, but we need them.
	 *
	 * @since  1.6.13
	 * @access public
	 * @author Grégory Viguier
	 */
	public static function unlimit_joins() {
		global $wpdb;
		static $done = false;

		if ( $done ) {
			return;
		}

		$done  = true;
		$query = 'SET SQL_BIG_SELECTS=1';

		/**
		 * Filter the SQL query allowing to remove the limit on JOINs.
		 *
		 * @since  1.6.13
		 * @author Grégory Viguier
		 *
		 * @param string|bool $query The query. False to prevent any query.
		 */
		$query = apply_filters( 'imagify_db_unlimit_joins_query', $query );

		if ( $query && is_string( $query ) ) {
			$wpdb->query( $query ); // WPCS: unprepared SQL ok.
		}
	}

	/**
	 * Change an array of values into a comma separated list, ready to be used in a `IN ()` clause.
	 *
	 * @since  1.6.13
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $values An array of values.
	 * @return string        A comma separated list of values.
	 */
	public static function prepare_values_list( $values ) {
		$values = esc_sql( (array) $values );
		$values = array_map( array( __CLASS__, 'quote_string' ), $values );
		return implode( ',', $values );
	}

	/**
	 * Wrap a value in quotes, unless it's an integer.
	 *
	 * @since  1.6.13
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int|string $value A value.
	 * @return int|string
	 */
	public static function quote_string( $value ) {
		return is_numeric( $value ) ? $value : "'" . addcslashes( $value, "'" ) . "'";
	}

	/**
	 * Get Imagify mime types, ready to be used in a `IN ()` clause.
	 *
	 * @since  1.6.13
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string A comma separated list of mime types.
	 */
	public static function get_mime_types() {
		static $mime_types;

		if ( ! isset( $mime_types ) ) {
			$mime_types = self::prepare_values_list( get_imagify_mime_type() );
		}

		return $mime_types;
	}

	/**
	 * Combine two arrays with some specific keys.
	 * We use this function to combine the result of 2 SQL queries.
	 *
	 * @since  1.6.13
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $keys            An array of keys.
	 * @param  array $values          An array of arrays like array( 'id' => id, 'value' => value ).
	 * @param  int   $keep_keys_order Set to true to return an array ordered like $keys instead of $values.
	 * @return array                  The combined arrays.
	 */
	public static function combine_query_results( $keys, $values, $keep_keys_order = false ) {
		if ( ! $keys || ! $values ) {
			return array();
		}

		$result = array();
		$keys   = array_flip( $keys );

		foreach ( $values as $v ) {
			if ( isset( $keys[ $v['id'] ] ) ) {
				$result[ $v['id'] ] = $v['value'];
			}
		}

		if ( $keep_keys_order ) {
			$keys = array_intersect_key( $keys, $result );
			return array_replace( $keys, $result );
		}

		return $result;
	}

	/**
	 * A helper to retrieve all values from one or several post metas, given a list of post IDs.
	 * The $wpdb cache is flushed to save memory.
	 *
	 * @since  1.6.13
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $metas An array of meta names like:
	 *                      array(
	 *                          'key1' => 'meta_name_1',
	 *                          'key2' => 'meta_name_2',
	 *                          'key3' => 'meta_name_3',
	 *                      )
	 *                      If a key contains 'data', the results will be unserialized.
	 * @param  array $ids   An array of post IDs.
	 * @return array        An array of arrays of results like:
	 *                      array(
	 *                          'key1' => array( post_id_1 => 'result_1', post_id_2 => 'result_2', post_id_3 => 'result_3' ),
	 *                          'key2' => array( post_id_1 => 'result_4', post_id_3 => 'result_5' ),
	 *                          'key3' => array( post_id_1 => 'result_6', post_id_2 => 'result_7' ),
	 *                      )
	 */
	public static function get_metas( $metas, $ids ) {
		global $wpdb;

		if ( ! $ids ) {
			return array_fill_keys( array_keys( $metas ), array() );
		}

		$sql_ids = implode( ',', $ids );

		foreach ( $metas as $result_name => $meta_name ) {
			$metas[ $result_name ] = $wpdb->get_results( // WPCS: unprepared SQL ok.
				"SELECT pm.post_id as id, pm.meta_value as value
				FROM $wpdb->postmeta as pm
				WHERE pm.meta_key = '$meta_name'
					AND pm.post_id IN ( $sql_ids )
				ORDER BY pm.post_id DESC",
				ARRAY_A
			);

			$wpdb->flush();
			$metas[ $result_name ] = self::combine_query_results( $ids, $metas[ $result_name ], true );

			if ( strpos( $result_name, 'data' ) !== false ) {
				$metas[ $result_name ] = array_map( 'maybe_unserialize', $metas[ $result_name ] );
			}
		}

		return $metas;
	}
}
