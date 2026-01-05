<?php
declare(strict_types=1);

namespace Imagify\Avif;

use Imagify\EventManagement\SubscriberInterface;
use Imagify\Notices\Notices;
use Imagify\WriteFile\WriteFileInterface;

/**
 * Display AVIF images on the site using picture tag.
 */
class Display implements SubscriberInterface {
	/**
	 * Server conf object.
	 *
	 * @var WriteFileInterface|null
	 * @since 1.9
	 */
	protected $server_conf = null;

	/**
	 * Returns an array of events this subscriber listens to
	 *
	 * @return array
	 */
	public static function get_subscribed_events() {
		return [
			'imagify_settings_on_save' => [ 'maybe_add_rewrite_rules', 12 ],
			'imagify_activation'       => 'activate',
			'imagify_deactivation'     => 'deactivate',
		];
	}

	/**
	 * If display Next-Gen images, add the AVIF type to the .htaccess/etc file.
	 *
	 * @since 1.9
	 *
	 * @param array $values The option values.
	 *
	 * @return array
	 */
	public function maybe_add_rewrite_rules( $values ) {
		if ( ! $this->get_server_conf() ) {
			return $values;
		}

		$enabled     = isset( $values['display_nextgen'] ) ? true : false;
		$was_enabled = (bool) get_imagify_option( 'display_nextgen' );

		$result = false;

		if ( $enabled && ! $was_enabled ) {
			// Add the WebP file type.
			$result = $this->get_server_conf()->add();
		} elseif ( ! $enabled && $was_enabled ) {
			// Remove the WebP file type.
			$result = $this->get_server_conf()->remove();
		}

		if ( ! is_wp_error( $result ) ) {
			return $values;
		}

		// Display an error message.
		if ( is_multisite() && strpos( wp_get_referer(), network_admin_url( '/' ) ) === 0 ) {
			Notices::get_instance()->add_network_temporary_notice( $result->get_error_message() );

			return $values;
		}

		Notices::get_instance()->add_site_temporary_notice( $result->get_error_message() );

		return $values;
	}

	/**
	 * Add rules on plugin activation.
	 *
	 * @since 1.9
	 */
	public function activate() {
		$conf = $this->get_server_conf();

		if ( ! $conf ) {
			return;
		}

		if ( ! get_imagify_option( 'display_nextgen' ) ) {
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
	 * @since 1.9
	 */
	public function deactivate() {
		$conf = $this->get_server_conf();

		if ( ! $conf ) {
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

	/**
	 * Get the path to the directory conf file.
	 *
	 * @since 1.9
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
	 * Note: nothing needed for nginx.
	 *
	 * @since 1.9
	 *
	 * @return WriteFileInterface
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
		}

		return $this->server_conf;
	}
}
