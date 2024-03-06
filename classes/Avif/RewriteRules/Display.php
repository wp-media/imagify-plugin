<?php
declare(strict_types=1);

namespace Imagify\Avif\RewriteRules;

use Imagify\EventManagement\SubscriberInterface;
use Imagify\Notices\Notices;
use Imagify\WriteFile\WriteFileInterface;

/**
 * Display Avif images on the site with rewrite rules.
 */
class Display implements SubscriberInterface {
	/**
	 * Configuration file writer.
	 *
	 * @var WriteFileInterface|null
	 */
	protected $server_conf = null;

	/**
	 * Option value.
	 *
	 * @var string
	 */
	const OPTION_VALUE = 'rewrite';

	/**
	 * Returns an array of events this subscriber listens to
	 *
	 * @return array
	 */
	public static function get_subscribed_events() {
		return [
			'imagify_settings_on_save'   => [ 'maybe_add_rewrite_rules', 11 ],
			'imagify_settings_webp_info' => 'maybe_add_avif_info',
			'imagify_activation'         => 'activate',
			'imagify_deactivation'       => 'deactivate',
		];
	}

	/**
	 * If display AVIF images via rewrite rules, add the rules to the .htaccess/etc file.
	 *
	 * @since 1.9
	 *
	 * @param array $values The option values.
	 *
	 * @return array
	 */
	public function maybe_add_rewrite_rules( $values ) {
		$was_enabled = (bool) get_imagify_option( 'display_nextgen' );
		$is_enabled  = ! empty( $values['display_nextgen'] );

		// Which method?
		$old_value = get_imagify_option( 'display_nextgen_method' );
		$new_value = ! empty( $values['display_nextgen_method'] ) ? $values['display_nextgen_method'] : '';

		// Decide when to add or remove rules.
		$is_rewrite  = self::OPTION_VALUE === $new_value;
		$was_rewrite = self::OPTION_VALUE === $old_value;

		if ( ! $this->get_server_conf() ) {
			return $values;
		}

		$result = false;

		if ( $is_enabled && $is_rewrite && ( ! $was_enabled || ! $was_rewrite ) ) {
			// Add the rewrite rules.
			$result = $this->get_server_conf()->add();
		} elseif ( $was_enabled && $was_rewrite && ( ! $is_enabled || ! $is_rewrite ) ) {
			// Remove the rewrite rules.
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
	 * If the conf file is not writable, add a warning.
	 */
	public function maybe_add_avif_info() {
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
		}
	}

	/**
	 * Add rules on plugin activation.
	 */
	public function activate() {
		$conf = $this->get_server_conf();

		if ( ! $conf ) {
			return;
		}

		if ( ! get_imagify_option( 'display_nextgen' ) ) {
			return;
		}

		if ( self::OPTION_VALUE !== get_imagify_option( 'display_nextgen_method' ) ) {
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

		if ( ! get_imagify_option( 'display_nextgen' ) ) {
			return;
		}

		if ( self::OPTION_VALUE !== get_imagify_option( 'display_nextgen_method' ) ) {
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
	 * @param bool $relative True to get a path relative to the site’s root.
	 *
	 * @return string|bool The file path. False on failure.
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
	 * @return WriteFileInterface
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
		}

		return $this->server_conf;
	}
}
