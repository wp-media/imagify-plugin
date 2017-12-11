<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Class that display the "custom folders" files.
 *
 * @package Imagify
 * @since   1.7
 * @author  Grégory Viguier
 */
class Imagify_Files_List_Table extends WP_List_Table {

	/**
	 * Class version.
	 *
	 * @var   string
	 * @since 1.7
	 * @author Grégory Viguier
	 */
	const VERSION = '1.0';

	/**
	 * Class version.
	 *
	 * @var   string
	 * @since 1.7
	 * @author Grégory Viguier
	 */
	const PER_PAGE_OPTION = 'imagify_files_per_page';

	/**
	 * List of the folders containing the listed files.
	 *
	 * @var    array
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access protected
	 */
	protected $folders = array();

	/**
	 * Constructor.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param array $args An associative array of arguments.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( array(
			'plural' => 'imagify-files',
			'screen' => isset( $args['screen'] ) ? convert_to_screen( $args['screen'] ) : null,
		) );

		$this->modes = array(
			'list' => __( 'List View' ),
		);
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function prepare_items() {
		global $wpdb;

		add_screen_option( 'per_page', array(
			'label'   => __( 'Number of files per page', 'imagify' ),
			'default' => 20,
			'option'  => self::PER_PAGE_OPTION,
		) );

		$files_db = Imagify_Files_DB::get_instance();
		$table    = $files_db->get_table_name();
		$prim_key = esc_sql( $files_db->get_primary_key() );
		$per_page = $this->get_items_per_page( self::PER_PAGE_OPTION );

		$this->set_pagination_args( array(
			'total_items' => (int) $wpdb->get_var( "SELECT COUNT($prim_key) FROM $table" ), // WPCS: unprepared SQL ok.
			'per_page'    => $per_page,
		) );

		// Get items.
		$page     = $this->get_pagenum();
		$offset   = ( $page - 1 ) * $per_page;
		$orderbys = $this->get_sortable_columns();
		$orderby  = 'path';
		$order    = 'ASC';
		$folders  = array();

		$sent_orderby = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_STRING );
		$sent_order   = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_STRING );

		if ( ! empty( $sent_orderby ) && isset( $orderbys[ $sent_orderby ] ) ) {
			$orderby = $sent_orderby;
			$order   = is_array( $orderbys[ $orderby ] ) ? 'DESC' : 'ASC';

			if ( 'optimization' === $orderby ) {
				$orderby = 'percent';
			}
		}

		if ( $sent_order ) {
			$order = 'ASC' === strtoupper( $sent_order ) ? 'ASC' : 'DESC';
		}

		$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY %s %s LIMIT %d, %d", $orderby, $order, $offset, $per_page ) ); // WPCS: unprepared SQL ok.

		if ( $this->items ) {
			foreach ( $this->items as $i => $item ) {
				foreach ( $item as $key => $value ) {
					$item->$key = $files_db->cast( $value, $key );
				}

				$folders[ $item->folder_id ] = $item->folder_id;

				$this->items[ $i ] = get_imagify_attachment( 'File', $item, 'files_list_row' );
				$this->items[ $i ]->folder_id   = $item->folder_id;
				$this->items[ $i ]->folder_path = false;
			}

			$folders = array_filter( $folders );

			if ( $folders ) {
				$folders_db = Imagify_Folders_DB::get_instance();
				$table      = $folders_db->get_table_name();
				$folders    = Imagify_DB::prepare_values_list( $folders );
				$folders    = $wpdb->get_results( "SELECT * FROM $table WHERE folder_id IN ( $folders )" ); // WPCS: unprepared SQL ok.

				if ( $folders ) {
					foreach ( $folders as $folder ) {
						foreach ( $folder as $key => $value ) {
							$folder->$key = $folders_db->cast( $value, $key );
						}

						$this->folders[ $folder->folder_id ] = $folder;
					}

					foreach ( $this->items as $i => $item ) {
						if ( $item->folder_id && isset( $this->folders[ $item->folder_id ] ) ) {
							$item->folder_path = $this->folders[ $item->folder_id ]->path;
						}
					}
				}
			}
		}
	}

	/**
	 * Allow to save the screen options when submitted by the user.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param  bool|int $status Screen option value. Default false to skip.
	 * @param  string   $option The option name.
	 * @param  int      $value  The number of rows to use.
	 * @return int|bool
	 */
	public static function save_screen_options( $status, $option, $value ) {
		if ( self::PER_PAGE_OPTION === $option ) {
			return (int) $value;
		}

		return $status;
	}

	/**
	 * Message to be displayed when there are no items.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function no_items() {
		/* translators: 1 is a link tag start, 2 is the link tag end. */
		printf( __( 'No files yet. Launch a %1$sbulk optimization%2$s to see them appear here.', 'imagify' ), '<a href="' . esc_url( get_imagify_admin_url( 'bulk-optimization' ) ) . '">', '</a>' );
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list of bulk actions available on this table.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array();
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'                 => '<input type="checkbox" />',
			'title'              => __( 'File', 'imagify' ),
			'folder'             => __( 'Folder', 'imagify' ),
			'optimization'       => __( 'Optimization', 'imagify' ),
			'status'             => __( 'Status', 'imagify' ),
			'optimization_level' => __( 'Optimization level', 'imagify' ),
			'actions'            => __( 'Actions', 'imagify' ),
		);
	}

	/**
	 * Get a list of sortable columns. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * The second format will make the initial sorting order be descending.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'folder'             => 'orderby',
			'optimization'       => array( 'orderby', true ),
			'status'             => 'orderby',
			'optimization_level' => array( 'orderby', true ),
		);
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param object $item The current File object.
	 */
	public function column_cb( $item ) {
		?>
		<label class="screen-reader-text" for="cb-select-<?php echo $item->get_id(); ?>"><?php _e( 'Select file', 'imagify' ); ?></label>
		<input id="cb-select-<?php echo $item->get_id(); ?>" type="checkbox" name="bulk_select[]" value="<?php echo $item->get_id(); ?>" />
		<?php
	}

	/**
	 * Handles the title column output.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param object $item The current File object.
	 */
	public function column_title( $item ) {
		$base = ! empty( $item->folder_path ) ? Imagify_Files_Scan::remove_placeholder( $item->folder_path ) : '';
		?>
		<strong class="has-media-icon">
			<span class="media-icon image-icon"><img src="<?php echo esc_url( $item->get_original_url() ); ?>" alt="" width="60" /></span>
			<?php echo esc_html( imagify_make_file_path_relative( $item->get_original_path(), $base ) ); ?>
		</strong>
		<?php
	}

	/**
	 * Handles the parent folder column output.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param object $item The current File object.
	 */
	public function column_folder( $item ) {
		static $themes_and_plugins;

		if ( empty( $item->folder_path ) ) {
			return;
		}

		if ( ! isset( $themes_and_plugins ) ) {
			$themes_and_plugins = array_merge( Imagify_Settings::get_themes(), Imagify_Settings::get_plugins() );
		}

		if ( isset( $themes_and_plugins[ $item->folder_path ] ) ) {
			// It's a theme or a plugin.
			echo esc_html( $themes_and_plugins[ $item->folder_path ] );
		} else {
			// It's a custom folder.
			echo '<code>' . imagify_make_file_path_relative( Imagify_Files_Scan::remove_placeholder( $item->folder_path ) ) . '</code>';
		}
	}

	/**
	 * Handles the optimization data column output.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param object $item The current File object.
	 */
	public function column_optimization( $item ) {
		?>
		////
		<?php
	}

	/**
	 * Handles the status column output.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param object $item The current File object.
	 */
	public function column_status( $item ) {
		$status = $item->get_status();

		if ( ! $status ) {
			esc_html_e( 'Not optimized', 'imagify' );
			return;
		}
		?>
		////
		<?php
	}

	/**
	 * Handles the optimization level column output.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param object $item The current File object.
	 */
	public function column_optimization_level( $item ) {
		$level = $item->get_optimization_level();

		if ( false === $level ) {
			return;
		}
		?>
		<code><?php echo $level; ?></code>
		<?php
	}

	/**
	 * Handles the actions column output.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param object $item The current File object.
	 */
	public function column_actions( $item ) {
		?>
		Actions here.
		<?php
	}

	/**
	 * Get the name of the default primary column.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access protected
	 *
	 * @return string Name of the default primary column, in this case, 'title'.
	 */
	protected function get_default_primary_column_name() {
		return 'title';
	}
}
