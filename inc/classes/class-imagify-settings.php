<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

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
	const VERSION = '1.0';

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


	/** ----------------------------------------------------------------------------------------- */
	/** ON FORM SUBMIT ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * On form submit, handle values that are not part of the form.
	 * This must be hooked before Imagify_Options::sanitize_and_validate_on_update().
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param  string $values The option value.
	 * @return array
	 */
	public function populate_values_on_save( $values ) {
		$values = is_array( $values ) ? $values : array();

		// Version.
		if ( empty( $values['version'] ) ) {
			$values['version'] = IMAGIFY_VERSION;
		}

		// Disabled thumbnail sizes.
		if ( isset( $values['sizes'] ) && is_array( $values['sizes'] ) ) {
			$values['disallowed-sizes'] = array();

			if ( ! empty( $values['sizes'] ) ) {
				foreach ( $values['sizes'] as $size_key => $size_value ) {
					if ( false === strpos( $size_key, '-hidden' ) ) {
						continue;
					}

					$size_key = str_replace( '-hidden', '', $size_key );

					if ( ! isset( $values['sizes'][ $size_key ] ) ) {
						$values['disallowed-sizes'][ $size_key ] = 1;
					}
				}
			}
		}

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
		return imagify_get_capacity();
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
		if ( ! $value || isset( $old_value['api_key'], $value['api_key'] ) && $old_value['api_key'] === $value['api_key'] ) {
			return;
		}

		if ( is_wp_error( get_imagify_user() ) ) {
			Imagify_Notices::renew_notice( 'wrong-api-key' );
			delete_site_transient( 'imagify_check_licence_1' );
		} else {
			Imagify_Notices::dismiss_notice( 'wrong-api-key' );
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
					$value = $_POST[ $option ];
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
}