<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

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
	const VERSION = '1.0.1';

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
	 * First half of escaping for LIKE special characters % and _ before preparing for MySQL.
	 * Use this only before wpdb::prepare() or esc_sql().  Reversing the order is very bad for security.
	 *
	 * Example Prepared Statement:
	 *     $wild = '%';
	 *     $find = 'only 43% of planets';
	 *     $like = $wild . $wpdb->esc_like( $find ) . $wild;
	 *     $sql  = $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_content LIKE %s", $like );
	 *
	 * Example Escape Chain:
	 *     $sql  = esc_sql( $wpdb->esc_like( $input ) );
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $text The raw text to be escaped. The input typed by the user should have no extra or deleted slashes.
	 * @return string       Text in the form of a LIKE phrase. The output is not SQL safe. Call $wpdb::prepare() or real_escape next.
	 */
	public static function esc_like( $text ) {
		global $wpdb;

		if ( method_exists( $wpdb, 'esc_like' ) ) {
			// Introduced in WP 4.0.0.
			return $wpdb->esc_like( $text );
		}

		return addcslashes( $text, '_%\\' );
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
			$mime_types = self::prepare_values_list( imagify_get_mime_types() );
		}

		return $mime_types;
	}

	/**
	 * Get post statuses related to attachments, ready to be used in a `IN ()` clause.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string A comma separated list of post statuses.
	 */
	public static function get_post_statuses() {
		static $statuses;

		if ( ! isset( $statuses ) ) {
			$statuses = self::prepare_values_list( imagify_get_post_statuses() );
		}

		return $statuses;
	}

	/**
	 * Get the SQL JOIN clause to use to get only attachments that have the required WP metadata.
	 * It returns an empty string if the database has no attachments without the required metadada.
	 * It also triggers Imagify_DB::unlimit_joins().
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $id_field An ID field to match the metadata ID against in the JOIN clause.
	 *                          Default is the posts table `ID` field, using the `p` alias: `p.ID`.
	 *                          In case of "false" value or PEBKAC, fallback to the same field without alias.
	 * @param  bool   $matching Set to false to get a query to fetch metas NOT matching the file extensions.
	 * @param  bool   $test     Test if the site has attachments without required metadata before returning the query. False to bypass the test and get the query anyway.
	 * @return string
	 */
	public static function get_required_wp_metadata_join_clause( $id_field = 'p.ID', $matching = true, $test = true ) {
		global $wpdb;

		if ( $test && ! imagify_has_attachments_without_required_metadata() ) {
			return '';
		}

		self::unlimit_joins();
		$clause = '';

		if ( ! $id_field || ! is_string( $id_field ) ) {
			$id_field = "$wpdb->posts.ID";
		}

		$join = $matching ? 'INNER' : 'LEFT';

		foreach ( self::get_required_wp_metadata_aliases() as $meta_name => $alias ) {
			$clause .= "
			$join JOIN $wpdb->postmeta AS $alias
				ON ( $id_field = $alias.post_id AND $alias.meta_key = '$meta_name' )";
		}

		return $clause;
	}

	/**
	 * Get the SQL part to be used in a WHERE clause, to get only attachments that have (in)valid '_wp_attached_file' and '_wp_attachment_metadata' metadatas.
	 * It returns an empty string if the database has no attachments without the required metadada.
	 *
	 * @since  1.7
	 * @since  1.7.1.2 Use a single $arg parameter instead of 3. New $prepared parameter.
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $args {
	 *                   Optional. An array of arguments.
	 *
	 *                   string $aliases  The aliases to use for the meta values.
	 *                   bool   $matching Set to false to get a query to fetch invalid metas.
	 *                   bool   $test     Test if the site has attachments without required metadata before returning the query. False to bypass the test and get the query anyway.
	 *                   bool   $prepared Set to true if the query will be prepared with using $wpdb->prepare().
	 * }.
	 * @return string A query.
	 */
	public static function get_required_wp_metadata_where_clause( $args = array() ) {
		static $query = array();

		$args = imagify_merge_intersect( $args, array(
			'aliases'  => array(),
			'matching' => true,
			'test'     => true,
			'prepared' => false,
		) );

		list( $aliases, $matching, $test, $prepared ) = array_values( $args );

		if ( $test && ! imagify_has_attachments_without_required_metadata() ) {
			return '';
		}

		if ( $aliases && is_string( $aliases ) ) {
			$aliases = array(
				'_wp_attached_file' => $aliases,
			);
		} elseif ( ! is_array( $aliases ) ) {
			$aliases = array();
		}

		$aliases = imagify_merge_intersect( $aliases, self::get_required_wp_metadata_aliases() );
		$key     = implode( '|', $aliases ) . '|' . (int) $matching;

		if ( isset( $query[ $key ] ) ) {
			return $prepared ? str_replace( '%', '%%', $query[ $key ] ) : $query[ $key ];
		}

		unset( $args['prepared'] );
		$alias_1    = $aliases['_wp_attached_file'];
		$alias_2    = $aliases['_wp_attachment_metadata'];
		$extensions = self::get_extensions_where_clause( $args );

		if ( $matching ) {
			$query[ $key ] = "AND $alias_1.meta_value NOT LIKE '%://%' AND $alias_1.meta_value NOT LIKE '_:\\\\\%' $extensions";
		} else {
			$query[ $key ] = "AND ( $alias_2.meta_value IS NULL OR $alias_1.meta_value IS NULL OR $alias_1.meta_value LIKE '%://%' OR $alias_1.meta_value LIKE '_:\\\\\%' $extensions )";
		}

		return $prepared ? str_replace( '%', '%%', $query[ $key ] ) : $query[ $key ];
	}

	/**
	 * Get the SQL part to be used in a WHERE clause, to get only attachments that have a valid file extensions.
	 * It returns an empty string if the database has no attachments without the required metadada.
	 *
	 * @since  1.7
	 * @since  1.7.1.2 Use a single $arg parameter instead of 3. New $prepared parameter.
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $args {
	 *                   Optional. An array of arguments.
	 *
	 *                   string $alias    The alias to use for the meta value.
	 *                   bool   $matching Set to false to get a query to fetch metas NOT matching the file extensions.
	 *                   bool   $test     Test if the site has attachments without required metadata before returning the query. False to bypass the test and get the query anyway.
	 *                   bool   $prepared Set to true if the query will be prepared with using $wpdb->prepare().
	 * }.
	 * @return string A query.
	 */
	public static function get_extensions_where_clause( $args = false ) {
		static $extensions;
		static $query = array();

		$args = imagify_merge_intersect( $args, array(
			'alias'    => array(),
			'matching' => true,
			'test'     => true,
			'prepared' => false,
		) );

		list( $alias, $matching, $test, $prepared ) = array_values( $args );

		if ( $test && ! imagify_has_attachments_without_required_metadata() ) {
			return '';
		}

		if ( ! isset( $extensions ) ) {
			$extensions = array_keys( imagify_get_mime_types() );
			$extensions = implode( '|', $extensions );
			$extensions = explode( '|', $extensions );
		}

		if ( ! $alias ) {
			$alias = self::get_required_wp_metadata_aliases();
			$alias = $alias['_wp_attached_file'];
		}

		$key = $alias . '|' . (int) $matching;

		if ( isset( $query[ $key ] ) ) {
			return $prepared ? str_replace( '%', '%%', $query[ $key ] ) : $query[ $key ];
		}

		if ( $matching ) {
			$query[ $key ] = "AND ( LOWER( $alias.meta_value ) LIKE '%." . implode( "' OR LOWER( $alias.meta_value ) LIKE '%.", $extensions ) . "' )";
		} else {
			$query[ $key ] = "OR ( LOWER( $alias.meta_value ) NOT LIKE '%." . implode( "' AND LOWER( $alias.meta_value ) NOT LIKE '%.", $extensions ) . "' )";
		}

		return $prepared ? str_replace( '%', '%%', $query[ $key ] ) : $query[ $key ];
	}

	/**
	 * Get the aliases used for the metas in self::get_required_wp_metadata_join_clause(), self::get_required_wp_metadata_where_clause(), and self::get_extensions_where_clause().
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array An array with the meta name as key and its alias as value.
	 */
	public static function get_required_wp_metadata_aliases() {
		return array(
			'_wp_attached_file'       => 'imrwpmt1',
			'_wp_attachment_metadata' => 'imrwpmt2',
		);
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

	/**
	 * Create/Upgrade the table in the database.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $table_name   The (prefixed) table name.
	 * @param  string $schema_query Query representing the table schema.
	 * @return bool                 True on success. False otherwise.
	 */
	public static function create_table( $table_name, $schema_query ) {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$schema_query    = trim( $schema_query );
		$charset_collate = $wpdb->get_charset_collate();

		dbDelta( "CREATE TABLE $table_name ($schema_query) $charset_collate;" );

		return empty( $wpdb->last_error ) && self::table_exists( $table_name );
	}

	/**
	 * Tell if the given table exists.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $table_name Full name of the table (with DB prefix).
	 * @return bool
	 */
	public static function table_exists( $table_name ) {
		global $wpdb;

		$escaped_table = self::esc_like( $table_name );
		$result        = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $escaped_table ) );

		return $result === $table_name;
	}
}
