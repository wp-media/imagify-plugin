<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles the plugin settings.
 *
 * @since 1.7
 */
class Imagify_Settings {

	/**
	 * Class version.
	 *
	 * @var   string
	 * @since 1.7
	 */
	const VERSION = '1.0.1';

	/**
	 * The settings group.
	 *
	 * @var   string
	 * @since 1.7
	 */
	protected $settings_group;

	/**
	 * The option name.
	 *
	 * @var   string
	 * @since 1.7
	 */
	protected $option_name;

	/**
	 * The options instance.
	 *
	 * @var   object
	 * @since 1.7
	 */
	protected $options;

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @since  1.7
	 * @access protected
	 */
	protected static $_instance;

	/**
	 * The constructor.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access protected
	 */
	protected function __construct() {
		$this->options        = Imagify_Options::get_instance();
		$this->option_name    = $this->options->get_option_name();
		$this->settings_group = IMAGIFY_SLUG;
	}

	/**
	 * Get the main Instance.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return object Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Launch the hooks.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function init() {
		add_filter( 'sanitize_option_' . $this->option_name,           array( $this, 'populate_values_on_save' ), 5 );
		add_action( 'admin_init',                                      array( $this, 'register' ) );
		add_filter( 'option_page_capability_' . $this->settings_group, array( $this, 'get_capability' ) );

		if ( imagify_is_active_for_network() ) {
			add_filter( 'pre_update_site_option_' . $this->option_name, array( $this, 'maybe_set_redirection' ), 10, 2 );
			add_action( 'update_site_option_' . $this->option_name,     array( $this, 'after_save_network_options' ), 10, 3 );
			add_action( 'admin_post_update',                            array( $this, 'update_site_option_on_network' ) );
		} else {
			add_filter( 'pre_update_option_' . $this->option_name,      array( $this, 'maybe_set_redirection' ), 10, 2 );
			add_action( 'update_option_' . $this->option_name,          array( $this, 'after_save_options' ), 10, 2 );
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** VARIOUS HELPERS ========================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the name of the settings group.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return string
	 */
	public function get_settings_group() {
		return $this->settings_group;
	}

	/**
	 * Get the URL to use as form action.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return string
	 */
	public function get_form_action() {
		return imagify_is_active_for_network() ? admin_url( 'admin-post.php' ) : admin_url( 'options.php' );
	}

	/**
	 * Tell if we're submitting the settings form.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return bool
	 */
	public function is_form_submit() {
		return filter_input( INPUT_POST, 'option_page', FILTER_SANITIZE_STRING ) === $this->settings_group && filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING ) === 'update';
	}


	/** ----------------------------------------------------------------------------------------- */
	/** ON FORM SUBMIT ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * On form submit, handle some specific values.
	 * This must be hooked before Imagify_Options::sanitize_and_validate_on_update().
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param  array $values The option values.
	 * @return array
	 */
	public function populate_values_on_save( $values ) {
		if ( ! $this->is_form_submit() ) {
			return $values;
		}

		$values = is_array( $values ) ? $values : array();

		/**
		 * Disabled thumbnail sizes.
		 */
		$values = $this->populate_disallowed_sizes( $values );

		/**
		 * Custom folders.
		 */
		$values = $this->populate_custom_folders( $values );

		/**
		 * Filter settings when saved via the settings page.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param array $values The option values.
		 */
		$values = apply_filters( 'imagify_settings_on_save', $values );

		return (array) $values;
	}

	/**
	 * On form submit, handle disallowed thumbnail sizes.
	 *
	 * @since  1.7
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  array $values The option values.
	 * @return array
	 */
	protected function populate_disallowed_sizes( $values ) {
		$values['disallowed-sizes'] = array();

		if ( isset( $values['disallowed-sizes-reversed'] ) && is_array( $values['disallowed-sizes-reversed'] ) ) {
			$checked = ! empty( $values['disallowed-sizes-checked'] ) && is_array( $values['disallowed-sizes-checked'] ) ? array_flip( $values['disallowed-sizes-checked'] ) : array();

			if ( ! empty( $values['disallowed-sizes-reversed'] ) ) {
				foreach ( $values['disallowed-sizes-reversed'] as $size_key ) {
					if ( ! isset( $checked[ $size_key ] ) ) {
						// The checkbox is not checked: the size is disabled.
						$values['disallowed-sizes'][ $size_key ] = 1;
					}
				}
			}
		}

		unset( $values['disallowed-sizes-reversed'], $values['disallowed-sizes-checked'] );

		return $values;
	}

	/**
	 * On form submit, handle the custom folders.
	 *
	 * @since  1.7
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  array $values The option values.
	 * @return array
	 */
	protected function populate_custom_folders( $values ) {
		if ( ! imagify_can_optimize_custom_folders() ) {
			// The databases are not ready or the user has not the permission.
			unset( $values['custom_folders'] );
			return $values;
		}

		if ( ! isset( $values['custom_folders'] ) ) {
			// No selected folders: set them all inactive.
			Imagify_Custom_Folders::deactivate_all_folders();
			// Remove files that are in inactive folders and are not optimized.
			Imagify_Custom_Folders::remove_unoptimized_files_from_inactive_folders();
			// Remove empty inactive folders.
			Imagify_Custom_Folders::remove_empty_inactive_folders();

			return $values;
		}

		if ( ! is_array( $values['custom_folders'] ) ) {
			// Invalid value.
			unset( $values['custom_folders'] );
			return $values;
		}

		$selected = array_filter( $values['custom_folders'] );
		unset( $values['custom_folders'] );

		if ( ! $selected ) {
			// No selected folders: set them all inactive.
			Imagify_Custom_Folders::deactivate_all_folders();
			// Remove files that are in inactive folders and are not optimized.
			Imagify_Custom_Folders::remove_unoptimized_files_from_inactive_folders();
			// Remove empty inactive folders.
			Imagify_Custom_Folders::remove_empty_inactive_folders();

			return $values;
		}

		// Normalize the paths, remove duplicates, and remove sub-paths.
		$selected = array_map( 'sanitize_text_field', $selected );
		$selected = array_map( 'wp_normalize_path', $selected );
		$selected = array_map( 'trailingslashit', $selected );
		$selected = array_flip( array_flip( $selected ) );
		$selected = Imagify_Custom_Folders::remove_sub_paths( $selected );

		// Remove the active status from the folders that are not selected.
		Imagify_Custom_Folders::deactivate_not_selected_folders( $selected );

		// Add the active status to the folders that are selected (and already in the DB).
		$selected = Imagify_Custom_Folders::activate_selected_folders( $selected );

		// If we still have paths here, they need to be added to the DB with an active status.
		Imagify_Custom_Folders::insert_folders( $selected );

		// Remove files that are in inactive folders and are not optimized.
		Imagify_Custom_Folders::remove_unoptimized_files_from_inactive_folders();

		// Reassign files to active folders.
		Imagify_Custom_Folders::reassign_inactive_files();

		// Remove empty inactive folders.
		Imagify_Custom_Folders::remove_empty_inactive_folders();

		return $values;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** SETTINGS API ============================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Add Imagify' settings to the settings API whitelist.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function register() {
		register_setting( $this->settings_group, $this->option_name );
	}

	/**
	 * Set the user capacity needed to save Imagify's main options from the settings page.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function get_capability() {
		return imagify_get_context( 'wp' )->get_capacity( 'manage' );
	}

	/**
	 * If the user clicked the "Save & Go to Bulk Optimizer" button, set a redirection to the bulk optimizer.
	 * We use this hook because it can be triggered even if the option value hasn't changed.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param  mixed $value     The new, unserialized option value.
	 * @param  mixed $old_value The old option value.
	 * @return mixed            The option value.
	 */
	public function maybe_set_redirection( $value, $old_value ) {
		if ( isset( $_POST['submit-goto-bulk'] ) ) { // WPCS: CSRF ok.
			$_REQUEST['_wp_http_referer'] = esc_url_raw( get_admin_url( get_current_blog_id(), 'upload.php?page=imagify-bulk-optimization' ) );
		}

		return $value;
	}

	/**
	 * Used to launch some actions after saving the network options.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param string $option     Name of the network option.
	 * @param mixed  $value      Current value of the network option.
	 * @param mixed  $old_value  Old value of the network option.
	 */
	public function after_save_network_options( $option, $value, $old_value ) {
		$this->after_save_options( $old_value, $value );
	}

	/**
	 * Used to launch some actions after saving the options.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $value     The new option value.
	 */
	public function after_save_options( $old_value, $value ) {
		$old_key = isset( $old_value['api_key'] ) ? $old_value['api_key'] : '';
		$new_key = isset( $value['api_key'] )     ? $value['api_key']     : '';

		if ( $old_key === $new_key ) {
			return;
		}

		// Handle API key validation cache and notices.
		if ( Imagify_Requirements::is_api_key_valid( true ) ) {
			Imagify_Notices::dismiss_notice( 'wrong-api-key' );
		} else {
			Imagify_Notices::renew_notice( 'wrong-api-key' );
		}
	}

	/**
	 * `options.php` does not handle network options. Let's use `admin-post.php` for multisite installations.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function update_site_option_on_network() {
		if ( empty( $_POST['option_page'] ) || $_POST['option_page'] !== $this->settings_group ) { // WPCS: CSRF ok.
			return;
		}

		/** This filter is documented in /wp-admin/options.php. */
		$capability = apply_filters( 'option_page_capability_' . $this->settings_group, 'manage_network_options' );

		if ( ! current_user_can( $capability ) ) {
			imagify_die();
		}

		imagify_check_nonce( $this->settings_group . '-options' );

		$whitelist_options = apply_filters( 'whitelist_options', array() );

		if ( ! isset( $whitelist_options[ $this->settings_group ] ) ) {
			imagify_die( __( '<strong>ERROR</strong>: options page not found.' ) );
		}

		$options = $whitelist_options[ $this->settings_group ];

		if ( $options ) {
			foreach ( $options as $option ) {
				$option = trim( $option );
				$value  = null;

				if ( isset( $_POST[ $option ] ) ) {
					$value = wp_unslash( $_POST[ $option ] );
					if ( ! is_array( $value ) ) {
						$value = trim( $value );
					}
					$value = wp_unslash( $value );
				}

				update_site_option( $option, $value );
			}
		}

		/**
		 * Redirect back to the settings page that was submitted.
		 */
		imagify_maybe_redirect( false, array( 'settings-updated' => 'true' ) );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** FIELDS ================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Display a single checkbox.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param array $args Arguments:
	 *                    {option_name}   string   The option name. E.g. 'disallowed-sizes'. Mandatory.
	 *                    {label}         string   The label to use.
	 *                    {info}          string   Text to display in an "Info box" after the field. A 'aria-describedby' attribute will automatically be created.
	 *                    {attributes}    array    A list of HTML attributes, as 'attribute' => 'value'.
	 *                    {current_value} int|bool USE ONLY WHEN DEALING WITH DATA THAT IS NOT SAVED IN THE PLUGIN OPTIONS. If not provided, the field will automatically get the value from the options.
	 */
	public function field_checkbox( $args ) {
		$args = array_merge( [
			'option_name'   => '',
			'label'         => '',
			'info'          => '',
			'attributes'    => [],
			// To not use the plugin settings: use an integer.
			'current_value' => null,
		], $args );

		if ( ! $args['option_name'] || ! $args['label'] ) {
			return;
		}

		if ( is_numeric( $args['current_value'] ) || is_bool( $args['current_value'] ) ) {
			// We don't use the plugin settings.
			$current_value = (int) (bool) $args['current_value'];
		} else {
			// This is a normal plugin setting.
			$current_value = $this->options->get( $args['option_name'] );
		}

		$option_name_class = sanitize_html_class( $args['option_name'] );
		$attributes        = [
			'name' => $this->option_name . '[' . $args['option_name'] . ']',
			'id'   => 'imagify_' . $option_name_class,
		];

		if ( $args['info'] && empty( $attributes['aria-describedby'] ) ) {
			$attributes['aria-describedby'] = 'describe-' . $option_name_class;
		}

		$attributes         = array_merge( $attributes, $args['attributes'] );
		$args['attributes'] = self::build_attributes( $attributes );
		?>
		<input type="checkbox" value="1" <?php checked( $current_value, 1 ); ?><?php echo $args['attributes']; ?> />
		<!-- Empty onclick attribute to make clickable labels on iTruc & Mac -->
		<label for="<?php echo $attributes['id']; ?>" onclick=""><?php echo $args['label']; ?></label>
		<?php
		if ( ! $args['info'] ) {
			return;
		}
		?>
		<span id="<?php echo $attributes['aria-describedby']; ?>" class="imagify-info">
			<span class="dashicons dashicons-info"></span>
			<?php echo $args['info']; ?>
		</span>
		<?php
	}

	/**
	 * Display a checkbox group.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param array $args Arguments:
	 *                    {option_name}     string The option name. E.g. 'disallowed-sizes'. Mandatory.
	 *                    {legend}          string Label to use for the <legend> tag.
	 *                    {values}          array  List of values to display, in the form of 'value' => 'Label'. Mandatory.
	 *                    {disabled_values} array  Values to be disabled. Values are the array keys.
	 *                    {reverse_check}   bool   If true, the values that will be stored in the option are the ones that are unchecked. It requires special treatment when saving (detect what values are unchecked).
	 *                    {attributes}      array  A list of HTML attributes, as 'attribute' => 'value'.
	 *                    {current_values}  array  USE ONLY WHEN DEALING WITH DATA THAT IS NOT SAVED IN THE PLUGIN OPTIONS. If not provided, the field will automatically get the value from the options.
	 */
	public function field_checkbox_list( $args ) {
		$args = array_merge( [
			'option_name'     => '',
			'legend'          => '',
			'values'          => [],
			'disabled_values' => [],
			'reverse_check'   => false,
			'attributes'      => [],
			// To not use the plugin settings: use an array.
			'current_values'  => false,
		], $args );

		if ( ! $args['option_name'] || ! $args['values'] ) {
			return;
		}

		if ( is_array( $args['current_values'] ) ) {
			// We don't use the plugin settings.
			$current_values = $args['current_values'];
		} else {
			// This is a normal plugin setting.
			$current_values = $this->options->get( $args['option_name'] );
		}

		$option_name_class = sanitize_html_class( $args['option_name'] );
		$attributes        = array_merge( [
			'name'  => $this->option_name . '[' . $args['option_name'] . ( $args['reverse_check'] ? '-checked' : '' ) . '][]',
			'id'    => 'imagify_' . $option_name_class . '_%s',
			'class' => 'imagify-row-check',
		], $args['attributes'] );

		$id_attribute = $attributes['id'];
		unset( $attributes['id'] );
		$args['attributes'] = self::build_attributes( $attributes );

		$current_values    = array_diff_key( $current_values, $args['disabled_values'] );
		$nb_of_values      = count( $args['values'] );
		$display_check_all = $nb_of_values > 3;
		$nb_of_checked     = 0;
		?>
		<fieldset class="imagify-check-group<?php echo $nb_of_values > 5 ? ' imagify-is-scrollable' : ''; ?>">
			<?php
			if ( $args['legend'] ) {
				?>
				<legend class="screen-reader-text"><?php echo $args['legend']; ?></legend>
				<?php
			}

			foreach ( $args['values'] as $value => $label ) {
				$input_id = sprintf( $id_attribute, sanitize_html_class( $value ) );
				$disabled = isset( $args['disabled_values'][ $value ] );

				if ( $args['reverse_check'] ) {
					$checked = ! $disabled && ! isset( $current_values[ $value ] );
				} else {
					$checked = ! $disabled && isset( $current_values[ $value ] );
				}

				$nb_of_checked = $checked ? $nb_of_checked + 1 : $nb_of_checked;

				if ( $args['reverse_check'] ) {
					echo '<input type="hidden" name="' . $this->option_name . '[' . $args['option_name'] . '-reversed][]" value="' . esc_attr( $value ) . '" />';
				}
				?>
				<p>
					<input type="checkbox" value="<?php echo esc_attr( $value ); ?>" id="<?php echo $input_id; ?>"<?php echo $args['attributes']; ?> <?php checked( $checked ); ?> <?php disabled( $disabled ); ?>/>
					<label for="<?php echo $input_id; ?>" onclick=""><?php echo $label; ?></label>
				</p>
				<?php
			}
			?>
		</fieldset>
		<?php
		if ( $display_check_all ) {
			if ( $args['reverse_check'] ) {
				$all_checked = ! array_intersect_key( $args['values'], $current_values );
			} else {
				$all_checked = ! array_diff_key( $args['values'], $current_values );
			}
			?>
			<p class="hide-if-no-js imagify-select-all-buttons">
				<button type="button" class="imagify-link-like imagify-select-all<?php echo $all_checked ? ' imagify-is-inactive" aria-disabled="true' : ''; ?>" data-action="select"><?php _e( 'Select All', 'imagify' ); ?></button>

				<span class="imagify-pipe"></span>

				<button type="button" class="imagify-link-like imagify-select-all<?php echo $nb_of_checked ? '' : ' imagify-is-inactive" aria-disabled="true'; ?>" data-action="unselect"><?php _e( 'Unselect All', 'imagify' ); ?></button>
			</p>
			<?php
		}
	}

	/**
	 * Display a radio list group.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $args {
	 *     Arguments.
	 *
	 *     @type string $option_name   The option name. E.g. 'disallowed-sizes'. Mandatory.
	 *     @type string $legend        Label to use for the <legend> tag.
	 *     @type string $info          Text to display in an "Info box" after the field. A 'aria-describedby' attribute will automatically be created.
	 *     @type array  $values        List of values to display, in the form of 'value' => 'Label'. Mandatory.
	 *     @type array  $attributes    A list of HTML attributes, as 'attribute' => 'value'.
	 *     @type array  $current_value USE ONLY WHEN DEALING WITH DATA THAT IS NOT SAVED IN THE PLUGIN OPTIONS. If not provided, the field will automatically get the value from the options.
	 * }
	 */
	public function field_radio_list( $args ) {
		$args = array_merge( [
			'option_name'     => '',
			'legend'          => '',
			'info'            => '',
			'values'          => [],
			'attributes'      => [],
			// To not use the plugin settings: use an array.
			'current_value'   => false,
		], $args );

		if ( ! $args['option_name'] || ! $args['values'] ) {
			return;
		}

		if ( is_array( $args['current_value'] ) ) {
			// We don't use the plugin settings.
			$current_value = $args['current_value'];
		} else {
			// This is a normal plugin setting.
			$current_value = $this->options->get( $args['option_name'] );
		}

		$option_name_class = sanitize_html_class( $args['option_name'] );
		$attributes        = array_merge( [
			'name'  => $this->option_name . '[' . $args['option_name'] . ']',
			'id'    => 'imagify_' . $option_name_class . '_%s',
			'class' => 'imagify-row-radio',
		], $args['attributes'] );

		$id_attribute = $attributes['id'];
		unset( $attributes['id'] );
		$args['attributes'] = self::build_attributes( $attributes );
		?>
		<fieldset class="imagify-radio-group">
			<?php
			if ( $args['legend'] ) {
				?>
				<legend class="screen-reader-text"><?php echo $args['legend']; ?></legend>
				<?php
			}

			foreach ( $args['values'] as $value => $label ) {
				$input_id = sprintf( $id_attribute, sanitize_html_class( $value ) );
				?>
				<input type="radio" value="<?php echo esc_attr( $value ); ?>" id="<?php echo $input_id; ?>"<?php echo $args['attributes']; ?> <?php checked( $current_value, $value ); ?>/>
				<label for="<?php echo $input_id; ?>" onclick=""><?php echo $label; ?></label>
				<br/>
				<?php
			}
			?>
		</fieldset>
		<?php
		if ( ! $args['info'] ) {
			return;
		}
		?>
		<span id="<?php echo $attributes['aria-describedby']; ?>" class="imagify-info">
			<span class="dashicons dashicons-info"></span>
			<?php echo $args['info']; ?>
		</span>
		<?php
	}

	/**
	 * Display a text box.
	 *
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $args Arguments:
	 *                    {option_name}   string   The option name. E.g. 'disallowed-sizes'. Mandatory.
	 *                    {label}         string   The label to use.
	 *                    {info}          string   Text to display in an "Info box" after the field. A 'aria-describedby' attribute will automatically be created.
	 *                    {attributes}    array    A list of HTML attributes, as 'attribute' => 'value'.
	 *                    {current_value} int|bool USE ONLY WHEN DEALING WITH DATA THAT IS NOT SAVED IN THE PLUGIN OPTIONS. If not provided, the field will automatically get the value from the options.
	 */
	public function field_text_box( $args ) {
		$args = array_merge( [
			'option_name'   => '',
			'label'         => '',
			'info'          => '',
			'attributes'    => [],
			// To not use the plugin settings.
			'current_value' => null,
		], $args );

		if ( ! $args['option_name'] || ! $args['label'] ) {
			return;
		}

		if ( is_numeric( $args['current_value'] ) || is_string( $args['current_value'] ) ) {
			// We don't use the plugin settings.
			$current_value = $args['current_value'];
		} else {
			// This is a normal plugin setting.
			$current_value = $this->options->get( $args['option_name'] );
		}

		$option_name_class = sanitize_html_class( $args['option_name'] );
		$attributes        = [
			'name' => $this->option_name . '[' . $args['option_name'] . ']',
			'id'   => 'imagify_' . $option_name_class,
		];

		if ( $args['info'] && empty( $attributes['aria-describedby'] ) ) {
			$attributes['aria-describedby'] = 'describe-' . $option_name_class;
		}

		$attributes         = array_merge( $attributes, $args['attributes'] );
		$args['attributes'] = self::build_attributes( $attributes );
		?>
		<!-- Empty onclick attribute to make clickable labels on iTruc & Mac -->
		<label for="<?php echo $attributes['id']; ?>" onclick=""><?php echo $args['label']; ?></label>
		<input type="text" value="<?php echo esc_attr( $current_value ); ?>"<?php echo $args['attributes']; ?> />
		<?php
		if ( ! $args['info'] ) {
			return;
		}
		?>
		<span id="<?php echo $attributes['aria-describedby']; ?>" class="imagify-info">
			<span class="dashicons dashicons-info"></span>
			<?php echo $args['info']; ?>
		</span>
		<?php
	}

	/**
	 * Display a simple hidden input.
	 *
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $args Arguments:
	 *                    {option_name}   string   The option name. E.g. 'disallowed-sizes'. Mandatory.
	 *                    {attributes}    array    A list of HTML attributes, as 'attribute' => 'value'.
	 *                    {current_value} int|bool USE ONLY WHEN DEALING WITH DATA THAT IS NOT SAVED IN THE PLUGIN OPTIONS. If not provided, the field will automatically get the value from the options.
	 */
	public function field_hidden( $args ) {
		$args = array_merge( [
			'option_name'   => '',
			'attributes'    => [],
			// To not use the plugin settings.
			'current_value' => null,
		], $args );

		if ( ! $args['option_name'] ) {
			return;
		}

		if ( is_numeric( $args['current_value'] ) || is_string( $args['current_value'] ) ) {
			// We don't use the plugin settings.
			$current_value = $args['current_value'];
		} else {
			// This is a normal plugin setting.
			$current_value = $this->options->get( $args['option_name'] );
		}

		$option_name_class = sanitize_html_class( $args['option_name'] );
		$attributes        = [
			'name' => $this->option_name . '[' . $args['option_name'] . ']',
			'id'   => 'imagify_' . $option_name_class,
		];

		$attributes         = array_merge( $attributes, $args['attributes'] );
		$args['attributes'] = self::build_attributes( $attributes );
		?>
		<input type="hidden" value="<?php echo esc_attr( $current_value ); ?>"<?php echo $args['attributes']; ?> />
		<?php
	}


	/** ----------------------------------------------------------------------------------------- */
	/** FIELD VALUES ============================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the thumbnail sizes.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array A list of thumbnail sizes in the form of 'medium' => 'medium - 300 × 300'.
	 */
	public static function get_thumbnail_sizes() {
		static $sizes;

		if ( isset( $sizes ) ) {
			return $sizes;
		}

		$sizes = get_imagify_thumbnail_sizes();

		foreach ( $sizes as $size_key => $size_data ) {
			$sizes[ $size_key ] = sprintf( '%s - %d &times; %d',  esc_html( stripslashes( $size_data['name'] ) ), $size_data['width'], $size_data['height'] );
		}

		return $sizes;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Create HTML attributes from an array.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $attributes A list of attribute pairs.
	 * @return string            HTML attributes.
	 */
	public static function build_attributes( $attributes ) {
		if ( ! $attributes || ! is_array( $attributes ) ) {
			return '';
		}

		$out = '';

		foreach ( $attributes as $attribute => $value ) {
			$out .= ' ' . $attribute . '="' . esc_attr( $value ) . '"';
		}

		return $out;
	}
}
