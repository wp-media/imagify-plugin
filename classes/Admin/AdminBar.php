<?php
declare( strict_types=1 );

namespace Imagify\Admin;

use Imagify\EventManagement\SubscriberInterface;
use Imagify\User\User;
use Imagify_Views;
use WP_Admin_Bar;

/**
 * Admin bar handler
 */
class AdminBar implements SubscriberInterface {
	/**
	 * User instance.
	 *
	 * @var User
	 */
	private $user;

	/**
	 * AdminBar constructor.
	 *
	 * @param User $user User instance.
	 */
	public function __construct( User $user ) {
		$this->user = $user;
	}

	/**
	 * Returns an array of events this subscriber listens to
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'wp_ajax_imagify_get_admin_bar_profile' => 'get_admin_bar_profile_callback',
			'admin_bar_menu'                        => [ 'add_imagify_admin_bar_menu', IMAGIFY_INT_MAX ],
		];
	}

	/**
	 * Add Imagify menu in the admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance, passed by reference.
	 */
	public function add_imagify_admin_bar_menu( $wp_admin_bar ) {
		if ( ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			return;
		}

		if ( ! get_imagify_option( 'admin_bar_menu' ) ) {
			return;
		}

		// Parent.
		$wp_admin_bar->add_menu(
			[
				'id'    => 'imagify',
				'title' => 'Imagify',
				'href'  => get_imagify_admin_url(),
			]
		);

		// Settings.
		$wp_admin_bar->add_menu(
			[
				'parent' => 'imagify',
				'id'     => 'imagify-settings',
				'title'  => __( 'Settings' ),
				'href'   => get_imagify_admin_url(),
			]
		);

		// Bulk Optimization.
		if ( ! is_network_admin() ) {
			$wp_admin_bar->add_menu(
				[
					'parent' => 'imagify',
					'id'     => 'imagify-bulk-optimization',
					'title'  => __( 'Bulk Optimization', 'imagify' ),
					'href'   => get_imagify_admin_url( 'bulk-optimization' ),
				]
			);
		}

		// Documentation.
		$wp_admin_bar->add_menu(
			[
				'parent' => 'imagify',
				'id'     => 'imagify-documentation',
				'title'  => __( 'Documentation', 'imagify' ),
				'href'   => imagify_get_external_url( 'documentation' ),
				'meta'   => [
					'target' => '_blank',
				],
			]
		);

		// Rate it.
		$wp_admin_bar->add_menu(
			[
				'parent' => 'imagify',
				'id'     => 'imagify-rate-it',
				/* translators: %s is WordPress.org. */
				'title'  => sprintf( __( 'Rate Imagify on %s', 'imagify' ), 'WordPress.org' ),
				'href'   => imagify_get_external_url( 'rate' ),
				'meta'   => [
					'target' => '_blank',
				],
			]
		);

		// Quota & Profile informations.
		if (
			( defined( 'IMAGIFY_HIDDEN_ACCOUNT' ) && IMAGIFY_HIDDEN_ACCOUNT )
			||
			! get_imagify_option( 'api_key' )
		) {
			return;
		}

		$wp_admin_bar->add_menu(
			[
				'parent' => 'imagify',
				'id'     => 'imagify-upgrade-plan',
				'title'  => '<div id="wp-admin-bar-imagify-pricing-content" class="hide-if-no-js"></div>',
			]
		);

		$wp_admin_bar->add_menu(
			[
				'parent' => 'imagify',
				'id'     => 'imagify-profile',
				'title'  => wp_nonce_field( 'imagify-get-admin-bar-profile', 'imagifygetadminbarprofilenonce', false, false ) . '<div id="wp-admin-bar-imagify-profile-loading" class="hide-if-no-js">' . __( 'Loading...', 'imagify' ) . '</div><div id="wp-admin-bar-imagify-profile-content" class="hide-if-no-js"></div>',
			]
		);
	}

	/**
	 * Get admin bar profile output.
	 *
	 * @return void
	 */
	public function get_admin_bar_profile_callback() {
		imagify_check_nonce( 'imagify-get-admin-bar-profile', 'imagifygetadminbarprofilenonce' );

		if ( ! imagify_get_context( 'wp' )->current_user_can( 'manage' ) ) {
			imagify_die();
		}

		$views            = Imagify_Views::get_instance();
		$unconsumed_quota = $views->get_quota_percent();
		$text             = '';
		$button_text      = '';
		$upgrade_link     = '';

		if ( $this->user->is_free() ) {
			$text         = esc_html__( 'Upgrade your plan now for more!', 'imagify' ) . '<br>' .
			esc_html__( 'From $5.99/month only, keep going with image optimization!', 'imagify' );
			$button_text  = esc_html__( 'Upgrade My Plan', 'imagify' );
			$upgrade_link = IMAGIFY_APP_DOMAIN . '/subscription/?utm_source=plugin&utm_medium=notification';
		} elseif ( $this->user->is_growth() ) {
			$text = esc_html__( 'Switch to Infinite plan for unlimited optimization:', 'imagify' ) . '<br>';

			if ( $this->user->is_monthly ) {
				$text        .= esc_html__( 'For $11.99/month, optimize as many images as you like!', 'imagify' );
				$upgrade_link = IMAGIFY_APP_DOMAIN . '/subscription/plan_switch/?label=infinite&payment_plan=1&utm_source=plugin&utm_medium=notification ';
			} else {
				$text        .= esc_html__( 'For $9.99/month, optimize as many images as you like!', 'imagify' );
				$upgrade_link = IMAGIFY_APP_DOMAIN . '/subscription/plan_switch/?label=infinite&payment_plan=2&utm_source=plugin&utm_medium=notification ';
			}

			$button_text = esc_html__( 'Switch To Infinite Plan', 'imagify' );
		}

		$data = [
			'quota_icon'       => $views->get_quota_icon(),
			'quota_class'      => $views->get_quota_class(),
			'plan_label'       => $this->user->plan_label,
			'plan_with_quota'  => $this->user->is_free() || $this->user->is_growth(),
			'unconsumed_quota' => $unconsumed_quota,
			'user_quota'       => $this->user->get_quota(),
			'next_update'      => $this->user->next_date_update,
			'text'             => $text,
			'button_text'      => $button_text,
			'upgrade_link'     => $upgrade_link,
		];

		$template = [
			'admin_bar_status'  => $views->get_template( 'admin/admin-bar-status', $data ),
			'admin_bar_pricing' => $views->get_template(
				'admin/admin-bar-pricing',
				[
					'upgrade_pricing' => $this->user->is_free() && ( $this->user->get_percent_unconsumed_quota() > 20 ),
				]
			),
		];

		wp_send_json_success( $template );
	}
}
