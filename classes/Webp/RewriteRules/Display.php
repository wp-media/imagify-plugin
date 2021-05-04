<?php
namespace Imagify\Webp\RewriteRules;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Display WebP images on the site with rewrite rules.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class Display {
	use \Imagify\Traits\InstanceGetterTrait;

	/**
	 * Option value.
	 *
	 * @var    string
	 * @since  1.9
	 * @author Grégory Viguier
	 */
	const OPTION_VALUE = 'rewrite';

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
	}

	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * If display WebP images via rewrite rules, add the rules to the .htaccess/etc file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $values The option values.
	 * @return array
	 */
	public function maybe_add_rewrite_rules( $values ) {
		global $is_apache, $is_iis7, $is_nginx;

		// Display WebP?
		$was_enabled = (bool) get_imagify_option( 'display_webp' );
		// See \Imagify_Options->validate_values_on_update() for why we use 'convert_to_webp' here.
		$is_enabled  = ! empty( $values['display_webp'] ) && ! empty( $values['convert_to_webp'] );

		// Which method?
		$old_value = get_imagify_option( 'display_webp_method' );
		$new_value = ! empty( $values['display_webp_method'] ) ? $values['display_webp_method'] : '';

		// Decide when to add or remove rules.
		$is_rewrite    = self::OPTION_VALUE === $new_value;
		$was_rewrite   = self::OPTION_VALUE === $old_value;
		$add_or_remove = false;

		if ( $is_enabled && $is_rewrite && ( ! $was_enabled || ! $was_rewrite ) ) {
			// Display WebP & use rewrite method, but only if one of the values changed: add rules.
			$add_or_remove = 'add';
		} elseif ( $was_enabled && $was_rewrite && ( ! $is_enabled || ! $is_rewrite ) ) {
			// Was displaying WebP & was using rewrite method, but only if one of the values changed: remove rules.
			$add_or_remove = 'remove';
		} else {
			return $values;
		}

		if ( $is_apache ) {
			$rules = new Apache();
		} elseif ( $is_iis7 ) {
			$rules = new IIS();
		} elseif ( $is_nginx ) {
			$rules = new Nginx();
		} else {
			return $values;
		}

		if ( 'add' === $add_or_remove ) {
			// Add the rewrite rules.
			$result = $rules->add();
		} else {
			// Remove the rewrite rules.
			$result = $rules->remove();
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
		global $is_nginx;

		$conf = $this->get_server_conf();

		if ( ! $conf ) {
			return;
		}

		$writable = $conf->is_file_writable();

		if ( is_wp_error( $writable ) ) {
			$rules = $conf->get_new_contents();

			if ( ! $rules ) {
				// Uh?
				return;
			}

			printf(
				/* translators: %s is a file name. */
				esc_html__( 'If you choose to use rewrite rules, you will have to add the following lines manually to the %s file:', 'imagify' ),
				'<code>' . $this->get_file_path( true ) . '</code>'
			);

			echo '<pre class="code">' . esc_html( $rules ) . '</pre>';
		} elseif ( $is_nginx ) {
			printf(
				/* translators: %s is a file name. */
				esc_html__( 'If you choose to use rewrite rules, the file %s will be created and must be included into the server’s configuration file (then restart the server).', 'imagify' ),
				'<code>' . $this->get_file_path( true ) . '</code>'
			);
		}
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
		if ( self::OPTION_VALUE !== get_imagify_option( 'display_webp_method' ) ) {
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
		if ( self::OPTION_VALUE !== get_imagify_option( 'display_webp_method' ) ) {
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
	 * Get the server conf instance.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return \Imagify\WriteFile\WriteFileInterface
	 */
	protected function get_server_conf() {
		global $is_apache, $is_iis7, $is_nginx;

		if ( isset( $this->server_conf ) ) {
			return $this->server_conf;
		}

		if ( $is_apache ) {
			$this->server_conf = new Apache();
		} elseif ( $is_iis7 ) {
			$this->server_conf = new IIS();
		} elseif ( $is_nginx ) {
			$this->server_conf = new Nginx();
		} else {
			$this->server_conf = false;
		}

		return $this->server_conf;
	}
}
