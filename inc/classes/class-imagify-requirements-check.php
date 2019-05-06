<?php
defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

/**
 * Class to check if the current WordPress and PHP versions meet our requirements.
 *
 * @since  1.9
 * @source Based on class WP_Rocket_Requirements_Check from WP Rocket plugin.
 * @author Grégory Viguier
 * @author Remy Perona
 */
class Imagify_Requirements_Check {
	/**
	 * Plugin Name.
	 *
	 * @var    string
	 * @since  1.9
	 * @access private
	 * @author Grégory Viguier
	 */
	private $plugin_name;

	/**
	 * Plugin filepath.
	 *
	 * @var    string
	 * @since  1.9
	 * @access private
	 * @author Grégory Viguier
	 */
	private $plugin_file;

	/**
	 * Plugin version.
	 *
	 * @var    string
	 * @since  1.9
	 * @access private
	 * @author Grégory Viguier
	 */
	private $plugin_version;

	/**
	 * Last plugin version handling the current version of WP.
	 *
	 * @var    string
	 * @since  1.9
	 * @access private
	 * @author Grégory Viguier
	 */
	private $wp_last_version;

	/**
	 * Last plugin version handling the current version of PHP.
	 *
	 * @var    string
	 * @since  1.9
	 * @access private
	 * @author Grégory Viguier
	 */
	private $php_last_version;

	/**
	 * Required WordPress version.
	 *
	 * @var    string
	 * @since  1.9
	 * @access private
	 * @author Grégory Viguier
	 */
	private $wp_version;

	/**
	 * Required PHP version.
	 *
	 * @var    string
	 * @since  1.9
	 * @access private
	 * @author Grégory Viguier
	 */
	private $php_version;

	/**
	 * Constructor.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $args {
	 *     Arguments to populate the class properties.
	 *
	 *     @type string $plugin_name         Plugin name.
	 *     @type string $plugin_file         Plugin filepath.
	 *     @type string $plugin_version      Plugin version.
	 *     @type string $wp_last_version     Last plugin version handling the current version of WP.
	 *     @type string $php_last_version    Last plugin version handling the current version of PHP.
	 *     @type string $wp_version          Required WordPress version.
	 *     @type string $php_version         Required PHP version.
	 * }
	 */
	public function __construct( $args ) {
		foreach ( array( 'plugin_name', 'plugin_file', 'plugin_version', 'wp_last_version', 'php_last_version', 'wp_version', 'php_version' ) as $setting ) {
			if ( isset( $args[ $setting ] ) ) {
				$this->$setting = $args[ $setting ];
			}
		}

		if ( empty( $this->wp_last_version ) ) {
			$this->wp_last_version = '1.6.14.2';
		}

		if ( empty( $this->php_last_version ) ) {
			$this->php_last_version = '1.8.4.1';
		}
	}

	/**
	 * Check if all requirements are ok, if not, display a notice and the rollback.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function check() {
		if ( ! $this->php_passes() || ! $this->wp_passes() ) {
			add_action( 'admin_notices',               array( $this, 'print_notice' ) );
			add_action( 'admin_post_imagify_rollback', array( $this, 'rollback' ) );

			return false;
		}

		return true;
	}

	/**
	 * Check if the current PHP version is equal or superior to the required PHP version.
	 *
	 * @since  1.9
	 * @access private
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	private function php_passes() {
		return version_compare( PHP_VERSION, $this->php_version ) >= 0;
	}

	/**
	 * Check if the current WordPress version is equal or superior to the required PHP version.
	 *
	 * @since  1.9
	 * @access private
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	private function wp_passes() {
		global $wp_version;

		return version_compare( $wp_version, $this->wp_version ) >= 0;
	}

	/**
	 * Get the last version of the plugin that can run with the current WP and PHP versions.
	 *
	 * @since  1.9
	 * @access private
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	private function get_last_version() {
		$last_version = '';

		if ( ! $this->php_passes() ) {
			$last_version = $this->php_last_version;
		}

		if ( ! $this->wp_passes() ) {
			$last_version = ! $last_version || version_compare( $last_version, $this->wp_last_version ) > 0 ? $this->wp_last_version : $last_version;
		}

		return $last_version;
	}

	/**
	 * Tell if the current user can rollback.
	 *
	 * @since  1.9
	 * @access private
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	private function current_user_can() {
		$describer = 'manage';
		$capacity  = $this->is_active_for_network() ? 'manage_network_options' : 'manage_options';
		// This filter is documented in classes/Context/AbstractContext.php.
		$capacity  = (string) apply_filters( 'imagify_capacity', $capacity, $describer, 'wp' );

		$user_can = current_user_can( $capacity );
		// This filter is documented in classes/Context/AbstractContext.php.
		$user_can = (bool) apply_filters( 'imagify_current_user_can', $user_can, $capacity, $describer, null, 'wp' );

		return $user_can;
	}

	/**
	 * Tell if Imagify is activated on the network.
	 *
	 * @since  1.9
	 * @access private
	 * @author Grégory Viguier
	 *
	 * return bool True if Imagify is activated on the network.
	 */
	private function is_active_for_network() {
		if ( ! is_multisite() ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active_for_network( plugin_basename( $this->plugin_file ) );
	}

	/**
	 * Warn if PHP version is less than 5.4 and offers to rollback.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function print_notice() {
		if ( ! $this->current_user_can() ) {
			return;
		}

		imagify_load_translations();

		$message      = array();
		$required     = array();
		$rollback_url = wp_nonce_url( admin_url( 'admin-post.php?action=imagify_rollback' ), 'imagify_rollback' );

		if ( ! $this->php_passes() ) {
			/* translators: %1$s = Plugin name, %2$s = PHP version required. */
			$message[]  = sprintf( esc_html__( 'To use this %1$s version, please ask your web host how to upgrade your server to PHP %2$s or higher.', 'imagify' ), $this->plugin_name, $this->php_version );
			$required[] = 'PHP ' . $this->php_version;
		}

		if ( ! $this->wp_passes() ) {
			/* translators: %1$s = Plugin name, %2$s = WordPress version required. */
			$message[]  = sprintf( esc_html__( 'To use this %1$s version, please upgrade WordPress to version %2$s or higher.', 'imagify' ), $this->plugin_name, $this->wp_version );
			$required[] = 'WordPress ' . $this->wp_version;
		}

		$message  = '<p>' . implode( '<br/>', $message ) . "</p>\n";
		$required = wp_sprintf_l( '%l', $required );

		/* translators: %1$s = Plugin name, %2$s = Plugin version, $3$s is something like "PHP 5.4" or "PHP 5.4 and WordPress 4.0". */
		$message = '<p>' . sprintf( esc_html__( 'To function properly, %1$s %2$s requires at least %3$s.', 'imagify' ), '<strong>' . $this->plugin_name . '</strong>', $this->plugin_version, $required ) . "</p>\n" . $message;

		$message .= '<p>' . esc_html__( 'If you are not able to upgrade, you can rollback to the previous version by using the button below.', 'imagify' ) . "</p>\n";
		/* translators: %s = Previous plugin version. */
		$message .= '<p class="submit"><a href="' . esc_url( $rollback_url ) . '" class="button">' . sprintf( __( 'Re-install version %s', 'imagify' ), $this->get_last_version() ) . '</a></p>';

		echo '<div class="notice notice-error">' . $message . '</div>';
	}

	/**
	 * Do the rollback.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function rollback() {
		check_ajax_referer( 'imagify_rollback' );

		if ( ! $this->current_user_can() ) {
			wp_die();
		}

		imagify_load_translations();

		$plugin_transient = get_site_transient( 'update_plugins' );
		$plugin_basename  = plugin_basename( $this->plugin_file );
		$plugin_folder    = dirname( $plugin_basename );
		$last_version     = $this->get_last_version();
		$package_filename = $plugin_folder . '.' . $last_version . '.zip';

		$plugin_transient->checked[ $plugin_basename ] = $last_version;

		if ( ! empty( $plugin_transient->response[ $plugin_basename ] ) ) {
			$tmp_obj = $plugin_transient->response[ $plugin_basename ];
		} elseif ( ! empty( $plugin_transient->no_update[ $plugin_basename ] ) ) {
			$tmp_obj = $plugin_transient->no_update[ $plugin_basename ];
		} else {
			$tmp_obj = (object) array(
				'id'          => 'w.org/plugins/' . $plugin_folder,
				'slug'        => $plugin_folder,
				'plugin'      => $plugin_basename,
				'new_version' => $last_version,
				'url'         => 'https://wordpress.org/plugins/' . $plugin_folder . '/',
				'package'     => 'https://downloads.wordpress.org/plugin/' . $package_filename,
				'icons'       => array(),
				'banners'     => array(),
				'banners_rtl' => array(),
			);
		}

		$tmp_obj->new_version = $last_version;
		$tmp_obj->package     = preg_replace( '@/[^/]+$@', '/' . $package_filename, $tmp_obj->package );

		$plugin_transient->response[ $plugin_basename ] = $tmp_obj;
		unset( $plugin_transient->no_update[ $plugin_basename ] );

		set_site_transient( 'update_plugins', $plugin_transient );

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		/* translators: %s is the plugin name. */
		$title         = sprintf( __( '%s Update Rollback', 'imagify' ), $this->plugin_name );
		$nonce         = 'upgrade-plugin_' . $plugin_basename;
		$url           = 'update.php?action=upgrade-plugin&plugin=' . rawurlencode( $plugin_basename );
		$upgrader_skin = new Plugin_Upgrader_Skin( compact( 'title', 'nonce', 'url', 'plugin' ) );
		$upgrader      = new Plugin_Upgrader( $upgrader_skin );

		$upgrader->upgrade( $plugin_basename );

		wp_die(
			'',
			/* translators: %s is the plugin name. */
			sprintf( __( '%s Update Rollback', 'imagify' ), $this->plugin_name ),
			array( 'response' => 200 )
		);
	}
}
