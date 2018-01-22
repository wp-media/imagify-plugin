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
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $folders = array();

	/**
	 * Constructor.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
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
	 * @access public
	 * @author Grégory Viguier
	 */
	public function prepare_items() {
		global $wpdb;

		add_screen_option( 'per_page', array(
			'label'   => __( 'Number of files per page', 'imagify' ),
			'default' => 20,
			'option'  => self::PER_PAGE_OPTION,
		) );

		$files_db    = Imagify_Files_DB::get_instance();
		$files_table = $files_db->get_table_name();
		$files_key   = esc_sql( $files_db->get_primary_key() );
		$per_page    = $this->get_items_per_page( self::PER_PAGE_OPTION );

		// Prepare the query to get items.
		$page     = $this->get_pagenum();
		$offset   = ( $page - 1 ) * $per_page;
		$orderbys = $this->get_sortable_columns();
		$orderby  = 'path';
		$order    = 'ASC';
		$folders  = array();
		$file_ids = array();
		$where    = '';

		$sent_orderby  = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_STRING );
		$sent_order    = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_STRING );
		$type_filter   = self::get_folder_type_filter();
		$folder_filter = self::get_folder_filter();
		$status_filter = self::get_status_filter();

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

		if ( $folder_filter ) {
			// Display only files from a specific plugin, theme, or custom folder.
			$where = "WHERE folder_id = $folder_filter";

		} elseif ( $type_filter ) {
			// Display only files from plugins, themes, or custom folders.
			if ( 'themes' === $type_filter ) {
				// Where the folders are themes.
				$where = array_keys( imagify_get_theme_folders() );
				$where = Imagify_Folders_DB::get_instance()->get_column_in( 'folder_id', 'path', $where );

			} elseif ( 'plugins' === $type_filter ) {
				// Where the folders are plugins.
				$where = array_keys( imagify_get_plugin_folders() );
				$where = Imagify_Folders_DB::get_instance()->get_column_in( 'folder_id', 'path', $where );

			} else {
				// Where the folders are not themes nor plugins.
				$where = array_merge( imagify_get_theme_folders(), imagify_get_plugin_folders() );
				$where = array_keys( $where );
				$where = Imagify_Folders_DB::get_instance()->get_column_not_in( 'folder_id', 'path', $where );
			}

			$where = $where ? Imagify_DB::prepare_values_list( $where ) : 0;
			$where = "WHERE folder_id IN ( $where )";
		}

		if ( $status_filter ) {
			// Display files optimized, not optimized, or with error.
			$where .= $where ? ' AND ' : 'WHERE ';

			switch ( $status_filter ) {
				case 'optimized':
					$where .= "( status = 'success' OR status = 'already_optimized' )";
					break;
				case 'unoptimized':
					$where .= 'status IS NULL';
					break;
				case 'errors':
					$where .= "status = 'error'";
					break;
			}
		}

		// Pagination.
		$this->set_pagination_args( array(
			'total_items' => (int) $wpdb->get_var( "SELECT COUNT($files_key) FROM $files_table $where" ), // WPCS: unprepared SQL ok.
			'per_page'    => $per_page,
		) );

		// Get items.
		$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $files_table $where ORDER BY $orderby $order LIMIT %d, %d", $offset, $per_page ) ); // WPCS: unprepared SQL ok.

		if ( ! $this->items ) {
			return;
		}

		// Prepare items.
		foreach ( $this->items as $i => $item ) {
			// Cast values.
			$item = $files_db->cast_row( $item );

			// Store the folders used by the items to get their data later in 1 query.
			$folders[ $item->folder_id ] = $item->folder_id;

			// Store the item IDs to store transients later in 1 query.
			$file_ids[ $item->file_id ] = $item->file_id;

			// Use Imagify objects + add related folder ID and path (set later).
			$this->items[ $i ] = get_imagify_attachment( 'File', $item, 'files_list_row' );
			$this->items[ $i ]->folder_id   = $item->folder_id;
			$this->items[ $i ]->folder_path = false;
		}

		$folders = array_filter( $folders );

		// Cache transient values.
		imagify_load_network_options( $file_ids, array(
			'_site_transient_imagify-file-async-in-progress-',
			'_site_transient_timeout_imagify-file-async-in-progress-',
		) );

		if ( ! $folders ) {
			return;
		}

		// Get folders data.
		$folders_db    = Imagify_Folders_DB::get_instance();
		$folders_table = $folders_db->get_table_name();
		$folders       = Imagify_DB::prepare_values_list( $folders );
		$folders       = $wpdb->get_results( "SELECT * FROM $folders_table WHERE folder_id IN ( $folders )" ); // WPCS: unprepared SQL ok.

		if ( ! $folders ) {
			return;
		}

		// Cast folders data and store data into a property.
		foreach ( $folders as $folder ) {
			$folder = $folders_db->cast_row( $folder );

			$this->folders[ $folder->folder_id ] = $folder;
		}

		// Set folders path to each item.
		foreach ( $this->items as $i => $item ) {
			if ( $item->folder_id && isset( $this->folders[ $item->folder_id ] ) ) {
				$item->folder_path = $this->folders[ $item->folder_id ]->path;
			}
		}
	}

	/**
	 * Message to be displayed when there are no items.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function no_items() {
		/* translators: 1 is a link tag start, 2 is the link tag end. */
		printf( __( 'No files yet. Launch a %1$sbulk optimization%2$s to see them appear here.', 'imagify' ), '<a href="' . esc_url( get_imagify_admin_url( 'files-bulk-optimization' ) ) . '">', '</a>' );
	}

	/**
	 * Display views.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function views() {
		global $wpdb;

		// Get all folders.
		$folders_table = Imagify_Folders_DB::get_instance()->get_table_name();
		$folders       = $wpdb->get_results( "SELECT folder_id, path FROM $folders_table" ); // WPCS: unprepared SQL ok.

		if ( ! $folders ) {
			return;
		}

		// Group folders by type.
		$themes  = Imagify_Settings::get_themes();
		$plugins = Imagify_Settings::get_plugins();
		$groups  = array(
			'themes'         => array(),
			'plugins'        => array(),
			'custom-folders' => array(),
		);

		foreach ( $folders as $folder ) {
			if ( isset( $themes[ $folder->path ] ) ) {
				$groups['themes'][ $folder->folder_id ] = $themes[ $folder->path ];
			} elseif ( isset( $plugins[ $folder->path ] ) ) {
				$groups['plugins'][ $folder->folder_id ] = $plugins[ $folder->path ];
			} elseif ( '{{ABSPATH}}/' === $folder->path ) {
				$groups['custom-folders'][ $folder->folder_id ] = __( 'Site\'s root', 'imagify' );
			} else {
				$groups['custom-folders'][ $folder->folder_id ] = '/' . trim( imagify_make_file_path_relative( Imagify_Files_Scan::remove_placeholder( $folder->path ) ), '/' );
			}
		}

		$groups       = array_filter( $groups );
		$type_filters = array(
			'themes'         => __( 'Themes', 'imagify' ),
			'plugins'        => __( 'Plugins', 'imagify' ),
			'custom-folders' => __( 'Custom folders', 'imagify' ),
		);
		$status_filters = array(
			''            => __( 'All images', 'imagify' ),
			'optimized'   => __( 'Optimized','imagify' ),
			'unoptimized' => __( 'Unoptimized','imagify' ),
			'errors'      => __( 'Errors','imagify' ),
		);

		// Get submitted values.
		$type_filter   = self::get_folder_type_filter();
		$folder_filter = self::get_folder_filter();
		$status_filter = self::get_status_filter();

		$this->screen->render_screen_reader_content( 'heading_views' );
		?>
		<div class="wp-filter">
			<div class="filter-items">

				<?php if ( count( $groups ) > 1 ) { ?>
					<label for="folder-type-filter" class="screen-reader-text"><?php _e( 'Filter by folder type', 'imagify' ); ?></label>
					<select class="folder-filters" name="folder-type-filter" id="folder-type-filter">
						<?php
						printf( '<option value="%s"%s>%s</option>', '', selected( $type_filter, '', false ), esc_html__( 'All Folder types', 'imagify' ) );

						foreach ( $groups as $type => $folders ) {
							printf( '<option value="%s"%s>%s</option>', $type, selected( $type_filter, $type, false ), esc_html( $type_filters[ $type ] ) );
						}
						?>
					</select>
				<?php } ?>

				<label for="folder-filter" class="screen-reader-text"><?php _e( 'Filter by folder', 'imagify' ); ?></label>
				<select class="folder-filters" name="folder-filter" id="folder-filter">
					<?php
					printf( '<option value="%s"%s>%s</option>', '', selected( $folder_filter, 0, false ), esc_html__( 'All Folders', 'imagify' ) );

					foreach ( $groups as $type => $folders ) {
						echo '<optgroup label="' . esc_attr( $type_filters[ $type ] ) . '">';

						natsort( $folders );

						foreach ( $folders as $folder_id => $label ) {
							printf( '<option value="%d"%s>%s</option>', $folder_id, selected( $folder_filter, $folder_id, false ), esc_html( $label ) );
						}

						echo '</optgroup>';
					}
					?>
				</select>

				<label for="status-filter" class="screen-reader-text"><?php _e( 'Filter by status', 'imagify' ); ?></label>
				<select class="folder-filters" name="status-filter" id="status-filter">
					<?php
					foreach ( $status_filters as $status => $label ) {
						printf( '<option value="%s"%s>%s</option>', $status, selected( $status_filter, $status, false ), esc_html( $label ) );
					}
					?>
				</select>

				<?php submit_button( __( 'Filter', 'imagify' ), '', 'filter_action', false, array( 'id' => 'folders-query-submit' ) ); ?>

				<?php $this->extra_tablenav( 'bar' ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list of bulk actions available on this table.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'imagify-bulk-refresh-status' => __( 'Refresh status', 'imagify' ),
		);
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
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
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'folder'             => 'folder',
			'optimization'       => array( 'optimization', true ),
			'status'             => 'status',
			'optimization_level' => array( 'optimization_level', true ),
		);
	}

	/**
	 * Get a column contents.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $column The column "name": "cb", "title", "optimization_level", etc.
	 * @param  object $item   The current File object.
	 * @return string         HTML contents,
	 */
	public function get_column( $column, $item ) {
		if ( ! method_exists( $this, 'column_' . $column ) ) {
			return '';
		}

		ob_start();
		call_user_func( array( $this, 'column_' . $column ), $item );
		return ob_get_clean();
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
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
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param object $item The current File object.
	 */
	public function column_title( $item ) {
		$item        = $this->maybe_set_item_folder( $item );
		$url         = $item->get_original_url();
		$base        = ! empty( $item->folder_path ) ? Imagify_Files_Scan::remove_placeholder( $item->folder_path ) : '';
		$title       = imagify_make_file_path_relative( $item->get_original_path(), $base );
		$dimensions  = $item->get_dimensions();
		$orientation = $dimensions['width'] > $dimensions['height'] ? ' landscape' : ' portrait';
		$orientation = $dimensions['width'] && $dimensions['height'] ? $orientation : '';

		if ( ! wp_doing_ajax() && $item->get_optimized_size( false ) > 100000 ) {
			// LazyLoad.
			$image_tag  = '<img src="' . esc_url( IMAGIFY_ASSETS_IMG_URL . 'lazyload.png' ) . '" data-lazy-src="' . esc_url( $url ) . '" alt="" />';
			$image_tag .= '<noscript><img src="' . esc_url( $url ) . '" alt="" /></noscript>';
		} else {
			$image_tag = '<img src="' . esc_url( $url ) . '" class="hide-if-no-js" alt="" />';
		}
		?>
		<strong class="has-media-icon">
			<a href="<?php echo esc_url( $url ); ?>" target="_blank">
				<span class="media-icon image-icon<?php echo $orientation; ?>">
					<span class="centered">
						<?php echo $image_tag; ?>
					</span>
				</span>
				<?php echo esc_html( $title ); ?>
			</a>
		</strong>
		<p class="filename">
			<?php $this->comparison_tool_button( $item ); ?>
		</p>
		<?php
	}

	/**
	 * Handles the parent folder column output.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param object $item The current File object.
	 */
	public function column_folder( $item ) {
		static $themes_and_plugins;

		$item = $this->maybe_set_item_folder( $item );

		if ( empty( $item->folder_path ) ) {
			return;
		}

		if ( ! isset( $themes_and_plugins ) ) {
			$themes_and_plugins = array_merge( Imagify_Settings::get_themes(), Imagify_Settings::get_plugins() );
		}

		$format = '%s';
		$filter = self::get_folder_filter();

		if ( $filter !== $item->folder_id ) {
			$format = '<a href="' . esc_url( add_query_arg( 'folder-filter', $item->folder_id, get_imagify_admin_url( 'files-list' ) ) ) . '">%s</a>';
		}

		if ( isset( $themes_and_plugins[ $item->folder_path ] ) ) {
			// It's a theme or a plugin.
			printf( $format, esc_html( $themes_and_plugins[ $item->folder_path ] ) );
		} elseif ( '{{ABSPATH}}/' === $item->folder_path ) {
			// It's the site's root.
			printf( $format, __( 'Site\'s root', 'imagify' ) );
		} else {
			// It's a custom folder.
			printf( $format, '<code>/' . trim( imagify_make_file_path_relative( Imagify_Files_Scan::remove_placeholder( $item->folder_path ) ), '/' ) . '</code>' );
		}
	}

	/**
	 * Handles the optimization data column output.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param object $item The current File object.
	 */
	public function column_optimization( $item ) {
		?>
		<ul class="imagify-datas-list">
			<li class="imagify-data-item">
				<span class="data"><?php esc_html_e( 'Original Filesize:', 'imagify' ); ?></span>
				<strong class="data-value original"><?php echo esc_html( $item->get_original_size() ); ?></strong>
			</li>
			<?php if ( $item->is_optimized() ) { ?>
				<li class="imagify-data-item">
					<span class="data"><?php esc_html_e( 'New Filesize:', 'imagify' ); ?></span>
					<strong class="data-value big optimized"><?php echo esc_html( $item->get_optimized_size() ); ?></strong>
				</li>
				<li class="imagify-data-item">
					<span class="data"><?php esc_html_e( 'Original Saving:', 'imagify' ); ?></span>
					<strong class="data-value">
						<span class="imagify-chart">
							<span class="imagify-chart-container">
								<canvas class="imagify-consumption-chart imagify-consumption-chart-<?php echo $item->get_id(); ?>" width="15" height="15"></canvas>
								<?php if ( wp_doing_ajax() ) { ?>
									<script type="text/javascript">jQuery( window ).trigger( "canvasprinted.imagify", [ ".imagify-consumption-chart-<?php echo $item->get_id(); ?>" ] ); </script>
								<?php } ?>
							</span>
						</span>
						<span class="imagify-chart-value"><?php echo $item->get_saving_percent(); ?></span>%
					</strong>
				</li>
			<?php } ?>
		</ul>
		<?php
	}

	/**
	 * Handles the status column output.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param object $item The current File object.
	 */
	public function column_status( $item ) {
		$status     = $item->get_status();
		$error_text = $item->get_optimized_error();
		$row        = $item->get_row();
		$messages   = array();

		if ( ! $status ) {
			// File is not optimized.
			$messages[] = esc_html_x( 'Not optimized', 'image', 'imagify' );
		} elseif ( $error_text ) {
			// Error or already optimized.
			$messages[] = esc_html( $error_text );
		}

		if ( ! $row['modified'] && ! $messages ) {
			// No need to display this if we already have another message to display.
			$messages[] = esc_html__( 'No changes found', 'imagify' );
		} elseif ( $row['modified'] ) {
			// The file has changed or is missing.
			$messages[] = esc_html__( 'The file has changed', 'imagify' );
		}

		echo implode( '<br/>', $messages );

		$this->refresh_status_button( $item );
	}

	/**
	 * Handles the optimization level column output.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param object $item The current File object.
	 */
	public function column_optimization_level( $item ) {
		if ( ! $item->has_error() ) {
			echo $item->get_optimization_level_label( '%ICON% %s' );
		}
	}

	/**
	 * Handles the actions column output.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param object $item The current File object.
	 */
	public function column_actions( $item ) {
		static $done = false;

		if ( ! imagify_valid_key() ) {
			// Stop the process if the API key isn't valid.
			if ( ! $done ) {
				// No need to display this on every row.
				$done = true;
				esc_html_e( 'Invalid API key', 'imagify' );
				echo '<br/><a href="' . esc_url( get_imagify_admin_url() ) . '">' . __( 'Check your Settings', 'imagify' ) . '</a>';
			}
			return;
		}

		$transient_name = 'imagify-file-async-in-progress-' . $item->get_id();

		if ( false !== get_site_transient( $transient_name ) ) {
			echo '<div class="button"><span class="imagify-spinner"></span>' . __( 'Optimizing...', 'imagify' ) . '</div>';
			return;
		}

		$this->optimize_button( $item );
		$this->retry_button( $item );
		$this->reoptimize_buttons( $item );
		$this->restore_button( $item );
	}

	/**
	 * Prints a button to optimize the file.
	 *
	 * @since  1.7
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param object $item The current File object.
	 */
	protected function optimize_button( $item ) {
		if ( $item->get_status() ) {
			// Already optimized.
			return;
		}

		$url = get_imagify_admin_url( 'optimize-file', array(
			'attachment_id' => $item->get_id(),
		) );
		$level = imagify_get_optimization_level_label( Imagify_Options::get_instance()->get( 'optimization_level' ) );
		/* translators: %s is an optimization level. */
		$title = sprintf( __( 'Optimize this file to %s.' ), $level );
		$class = 'button-primary button-imagify-optimize' . ( $item->has_backup() ? ' file-has-backup' : '' );
		?>
		<a id="imagify-optimize-<?php echo $item->get_id(); ?>" href="<?php echo esc_url( $url ); ?>" title="<?php echo esc_attr( $title ); ?>" class="<?php echo $class; ?>" data-waiting-label="<?php esc_attr_e( 'Optimizing...', 'imagify' ); ?>">
			<?php esc_html_e( 'Optimize', 'imagify' ); ?>
		</a>
		<?php
	}

	/**
	 * Prints a button to retry to optimize the file.
	 *
	 * @since  1.7
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param object $item The current File object.
	 */
	protected function retry_button( $item ) {
		if ( ! $item->is_already_optimized() && ! $item->has_error() ) {
			// Not optimized or successfully optimized.
			return;
		}

		$url = get_imagify_admin_url( 'optimize-file', array(
			'attachment_id' => $item->get_id(),
		) );
		$level = imagify_get_optimization_level_label( Imagify_Options::get_instance()->get( 'optimization_level' ) );
		/* translators: %s is an optimization level. */
		$title = sprintf( __( 'Optimize this file to %s.' ), $level );
		$class = 'button button-imagify-optimize' . ( $item->has_backup() ? ' file-has-backup' : '' );
		?>
		<a id="imagify-optimize-<?php echo $item->get_id(); ?>" href="<?php echo esc_url( $url ); ?>" title="<?php echo esc_attr( $title ); ?>" class="<?php echo $class; ?>" data-waiting-label="<?php esc_attr_e( 'Optimizing...', 'imagify' ); ?>">
			<?php esc_html_e( 'Try again', 'imagify' ); ?>
		</a><br/>
		<?php
	}

	/**
	 * Prints buttons to re-optimize the file to other levels.
	 *
	 * @since  1.7
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param object $item The current File object.
	 */
	protected function reoptimize_buttons( $item ) {
		if ( ! $item->get_status() ) {
			// Not optimized yet.
			return;
		}

		$is_optimized   = $item->is_optimized();
		$has_backup     = $item->has_backup();
		$can_reoptimize = $has_backup || ! $is_optimized;

		// Don't display anything if there is no backup or the image has been optimized.
		if ( ! $can_reoptimize ) {
			return;
		}

		/**
		 * If the image is optimized, don't display the Retry button for the level the image is optimized in.
		 * If not, don't display the Retry button for the level set in the plugin settings.
		 */
		$skip_level = $is_optimized ? $item->get_optimization_level() : Imagify_Options::get_instance()->get( 'optimization_level' );
		$args       = array(
			'attachment_id' => $item->get_id(),
		);
		$labels = array(
			0 => __( 'Normal', 'imagify' ),
			1 => __( 'Aggressive', 'imagify' ),
			2 => __( 'Ultra', 'imagify' ),
		);

		foreach ( $labels as $level => $label ) {
			if ( $skip_level === $level ) {
				continue;
			}

			$args['optimization_level'] = $level;
			?>
			<a href="<?php echo esc_url( get_imagify_admin_url( 'reoptimize-file', $args ) ); ?>" class="button-imagify-reoptimize" data-waiting-label="<?php esc_attr_e( 'Optimizing...', 'imagify' ); ?>">
				<span class="dashicons dashicons-admin-generic"></span>
				<span class="imagify-hide-if-small">
					<?php
					/* translators: %s is an optimization level. */
					printf( esc_html__( 'Re-Optimize to %s', 'imagify' ), '</span>' . esc_html( $label ) . '<span class="imagify-hide-if-small">' );
					?>
				</span>
			</a><br/>
			<?php
		}
	}

	/**
	 * Prints a button to restore the file.
	 *
	 * @since  1.7
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param object $item The current File object.
	 */
	protected function restore_button( $item ) {
		if ( ! $item->is_optimized() || ! $item->has_backup() ) {
			return;
		}

		$url = get_imagify_admin_url( 'restore-file', array(
			'attachment_id' => $item->get_id(),
		) );
		?>
		<a id="imagify-restore-<?php echo $item->get_id(); ?>" href="<?php echo esc_url( $url ); ?>" class="button-imagify-restore file-has-backup" data-waiting-label="<?php esc_attr_e( 'Restoring...', 'imagify' ); ?>">
			<span class="dashicons dashicons-image-rotate"></span>
			<?php esc_html_e( 'Restore Original', 'imagify' ); ?>
		</a>
		<?php
	}

	/**
	 * Prints a button to check if the file has been modified or not.
	 *
	 * @since  1.7
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param object $item The current File object.
	 */
	protected function refresh_status_button( $item ) {
		$url = get_imagify_admin_url( 'refresh-file-modified', array(
			'attachment_id' => $item->get_id(),
		) );
		?>
		<br/>
		<a id="imagify-refresh-status-<?php echo $item->get_id(); ?>" href="<?php echo esc_url( $url ); ?>" class="button-imagify-refresh-status" data-waiting-label="<?php esc_attr_e( 'Refreshing status...', 'imagify' ); ?>">
			<span class="dashicons dashicons-image-rotate"></span>
			<?php esc_html_e( 'Refresh status', 'imagify' ); ?>
		</a>
		<?php
	}

	/**
	 * Prints a button for the comparison tool (before / after optimization).
	 *
	 * @since  1.7
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param object $item The current File object.
	 */
	protected function comparison_tool_button( $item ) {
		if ( ! $item->is_optimized() || ! $item->has_backup() ) {
			return;
		}

		$file_path = $item->get_original_path();

		if ( ! $file_path || ! imagify_get_filesystem()->exists( $file_path ) ) {
			return;
		}

		$dimensions = $item->get_dimensions();

		if ( $dimensions['width'] < 360 ) {
			return;
		}

		$backup_url = $item->get_backup_url();

		printf(
			'<a href="%1$s" data-id="%2$d" data-backup-src="%3$s" data-full-src="%4$s" data-full-width="%5$d" data-full-height="%6$d" data-target="#imagify-comparison-%2$d" class="imagify-compare-images imagify-modal-trigger" target="_blank">%7$s</a>',
			esc_url( $backup_url ),
			$item->get_id(),
			esc_url( $backup_url ),
			esc_url( $item->get_original_url() ),
			$dimensions['width'],
			$dimensions['height'],
			esc_html__( 'Compare Original VS Optimized', 'imagify' )
		);

		if ( wp_doing_ajax() ) {
			?>
			<script type="text/javascript">jQuery( window ).trigger( 'comparisonprinted.imagify', [ <?php echo $item->get_id(); ?> ] ); </script>
			<?php
		}
	}

	/**
	 * Add the folder_id and folder_path properties to the $item if not set yet.
	 * It may happen if the $item doesn't come from the prepare() method.
	 *
	 * @since  1.7
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  object $item The current File object.
	 * @return object       The current File object.
	 */
	protected function maybe_set_item_folder( $item ) {
		if ( isset( $item->folder_path ) ) {
			return $item;
		}

		$item->folder_id   = 0;
		$item->folder_path = false;

		$row = $item->get_row();

		if ( ! $row['folder_id'] ) {
			return $item;
		}

		$folder = Imagify_Folders_DB::get_instance()->get( $row['folder_id'] );

		if ( ! $folder ) {
			return $item;
		}

		$item->folder_id   = $folder['folder_id'];
		$item->folder_path = $folder['path'];

		return $item;
	}

	/**
	 * Get the name of the default primary column.
	 *
	 * @since  1.7
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return string Name of the default primary column, in this case, 'title'.
	 */
	protected function get_default_primary_column_name() {
		return 'title';
	}

	/**
	 * Get a list of CSS classes for the WP_List_Table table tag.
	 *
	 * @since  1.7
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return array List of CSS classes for the table tag.
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', 'media', $this->_args['plural'] );
	}

	/**
	 * Allow to save the screen options when submitted by the user.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
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
	 * Get the requested folder type filter.
	 * If a folder filter is requested, this folder type filter is ommited.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public static function get_folder_type_filter() {
		static $filter;

		if ( isset( $filter ) ) {
			return $filter;
		}

		if ( self::get_folder_filter() ) {
			$filter = '';
			return $filter;
		}

		$values = array(
			'themes'         => 1,
			'plugins'        => 1,
			'custom-folders' => 1,
		);
		$filter = trim( filter_input( INPUT_GET, 'folder-type-filter', FILTER_SANITIZE_STRING ) );
		$filter = isset( $values[ $filter ] ) ? $filter : '';

		return $filter;
	}

	/**
	 * Get the requested folder filter.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public static function get_folder_filter() {
		static $filter;

		if ( ! isset( $filter ) ) {
			$filter = (int) filter_input( INPUT_GET, 'folder-filter', FILTER_SANITIZE_NUMBER_INT );
		}

		return $filter;
	}

	/**
	 * Get the requested status filter.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public static function get_status_filter() {
		static $filter;

		if ( isset( $filter ) ) {
			return $filter;
		}

		$values = array(
			'optimized'   => 1,
			'unoptimized' => 1,
			'errors'       => 1,
		);
		$filter = trim( filter_input( INPUT_GET, 'status-filter', FILTER_SANITIZE_STRING ) );
		$filter = isset( $values[ $filter ] ) ? $filter : '';

		return $filter;
	}
}
