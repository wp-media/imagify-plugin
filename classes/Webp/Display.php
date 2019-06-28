<?php
namespace Imagify\Webp;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Display webp images on the site.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class Display {
	use \Imagify\Traits\InstanceGetterTrait;

	/**
	 * Server conf object.
	 *
	 * @var    \Imagify\WriteFile\WriteFileInterface
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $server_conf;

	/**
	 * Init.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		add_filter( 'imagify_settings_on_save',   [ $this, 'maybe_add_rewrite_rules' ] );
		add_action( 'imagify_settings_webp_info', [ $this, 'maybe_add_webp_info' ] );
		add_action( 'imagify_activation',         [ $this, 'activate' ] );
		add_action( 'imagify_deactivation',       [ $this, 'deactivate' ] );

		Picture\Display::get_instance()->init();
		RewriteRules\Display::get_instance()->init();
	}

	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * If display webp images, add the webp type to the .htaccess/etc file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $values The option values.
	 * @return array
	 */
	public function maybe_add_rewrite_rules( $values ) {
		$old_value = (bool) get_imagify_option( 'display_webp' );
		// See \Imagify_Options->validate_values_on_update() for why we use 'convert_to_webp' here.
		$new_value = ! empty( $values['display_webp'] ) && ! empty( $values['convert_to_webp'] );

		if ( $old_value === $new_value ) {
			// No changes.
			return $values;
		}

		if ( ! $this->get_server_conf() ) {
			return $values;
		}

		if ( $new_value ) {
			// Add the webp file type.
			$result = $this->get_server_conf()->add();
		} else {
			// Remove the webp file type.
			$result = $this->get_server_conf()->remove();
		}

		if ( ! is_wp_error( $result ) ) {
			return $values;
		}

		// Display an error message.
		if ( is_multisite() && strpos( wp_get_referer(), network_admin_url( '/' ) ) === 0 ) {
			\Imagify_Notices::get_instance()->add_network_temporary_notice( $result->get_error_message() );
		} else {
			\Imagify_Notices::get_instance()->add_site_temporary_notice( $result->get_error_message() );
		}

		return $values;
	}

	/**
	 * If the conf file is not writable, add a warning.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function maybe_add_webp_info() {
		$conf = $this->get_server_conf();

		if ( ! $conf ) {
			return;
		}

		$writable = $conf->is_file_writable();

		if ( ! is_wp_error( $writable ) ) {
			return;
		}

		$rules = $conf->get_new_contents();

		if ( ! $rules ) {
			// Uh?
			return;
		}

		echo '<br/>';

		printf(
			/* translators: %s is a file name. */
			esc_html__( 'Imagify does not seem to be able to edit or create a %s file, you will have to add the following lines manually to it:', 'imagify' ),
			'<code>' . $this->get_file_path( true ) . '</code>'
		);

		echo '<pre class="code">' . esc_html( $rules ) . '</pre>';
	}

	/**
	 * Add rules on plugin activation.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function activate() {
		$conf = $this->get_server_conf();

		if ( ! $conf ) {
			return;
		}
		if ( ! get_imagify_option( 'display_webp' ) ) {
			return;
		}
		if ( is_wp_error( $conf->is_file_writable() ) ) {
			return;
		}

		$conf->add();
	}

	/**
	 * Remove rules on plugin deactivation.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function deactivate() {
		$conf = $this->get_server_conf();

		if ( ! $conf ) {
			return;
		}
		if ( ! get_imagify_option( 'display_webp' ) ) {
			return;
		}

		$file_path  = $conf->get_file_path();
		$filesystem = \Imagify_Filesystem::get_instance();

		if ( ! $filesystem->exists( $file_path ) ) {
			return;
		}
		if ( ! $filesystem->is_writable( $file_path ) ) {
			return;
		}

		$conf->remove();
	}

	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the path to the directory conf file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $relative True to get a path relative to the site’s root.
	 * @return string|bool    The file path. False on failure.
	 */
	public function get_file_path( $relative = false ) {
		if ( ! $this->get_server_conf() ) {
			return false;
		}

		$file_path = $this->get_server_conf()->get_file_path();

		if ( $relative ) {
			return \Imagify_Filesystem::get_instance()->make_path_relative( $file_path );
		}

		return $file_path;
	}

	/**
	 * Get the webp display method by validating the given value.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $values The option values.
	 * @return string        'picture' or 'rewrite'.
	 */
	public function get_display_webp_method( $values ) {
		$options = \Imagify_Options::get_instance();
		$default = $options->get_default_values();
		$default = $default['display_webp_method'];
		$method  = ! empty( $values['display_webp_method'] ) ? $values['display_webp_method'] : '';

		return $options->sanitize_and_validate( 'display_webp_method', $method, $default );
	}

	/**
	 * Get the server conf instance.
	 * Note: nothing needed for nginx.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return \Imagify\WriteFile\WriteFileInterface
	 */
	protected function get_server_conf() {
		global $is_apache, $is_iis7;

		if ( isset( $this->server_conf ) ) {
			return $this->server_conf;
		}

		if ( $is_apache ) {
			$this->server_conf = new Apache();
		} elseif ( $is_iis7 ) {
			$this->server_conf = new IIS();
		} else {
			$this->server_conf = false;
		}

		return $this->server_conf;
	}
}
