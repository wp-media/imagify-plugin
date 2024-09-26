<?php
declare( strict_types=1 );

namespace Imagify\Admin;

use Imagify\EventManagement\SubscriberInterface;
use Imagify\User\User;
use Imagify_Views;

/**
 * Admin bar handler
 */
class AdminBar implements SubscriberInterface {
	/**
	 * Returns an array of events this subscriber listens to
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'wp_ajax_imagify_get_admin_bar_profile' => 'get_admin_bar_profile_callback',
		];
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

		$user             = new User();
		$views            = Imagify_Views::get_instance();
		$unconsumed_quota = $views->get_quota_percent();
		$text             = '';
		$button_text      = '';
		$upgrade_link     = '';
		$upgrade_button   = '';

		if ( $user->is_free() ) {
			$text         = esc_html__( 'Upgrade your plan now for more!', 'rocket' ) . '<br>' .
			esc_html__( 'From $5.99/month only, keep going with image optimization!', 'rocket' );
			$button_text  = esc_html__( 'Upgrade My Plan', 'rocket' );
			$upgrade_link = IMAGIFY_APP_DOMAIN . '/subscription/?utm_source=plugin&utm_medium=notification';

			if ( $user->get_percent_unconsumed_quota() <= 20 ) {
				$upgrade_button = '<button id="imagify-get-pricing-modal" data-nonce="' . wp_create_nonce( 'imagify_get_pricing_' . get_current_user_id() ) . '" data-target="#imagify-pricing-modal" type="button" class="imagify-modal-trigger imagify-admin-bar-upgrade-plan">' . __( 'Upgrade Plan', 'imagify' ) . '</button>';
			}
		} elseif ( $user->is_growth() ) {
			$text = esc_html__( 'Switch to Infinite plan for unlimited optimization:', 'rocket' ) . '<br>';

			if ( $user->is_monthly ) {
				$text         .= esc_html__( 'For $9.99/month, optimize as many images as you like!', 'rocket' );
				$upgrade_link = IMAGIFY_APP_DOMAIN . '/subscription/plan_switch/?label=infinite&payment_plan=1&utm_source=plugin&utm_medium=notification ';
			} else {
				$text         .= esc_html__( 'For $99.9/year, optimize as many images as you like!', 'rocket' );
				$upgrade_link = IMAGIFY_APP_DOMAIN . '/subscription/plan_switch/?label=infinite&payment_plan=2&utm_source=plugin&utm_medium=notification ';
			}

			$button_text = esc_html__( 'Switch To Infinite Plan', 'rocket' );
		}

		$data = [
			'quota_icon'       => $views->get_quota_icon(),
			'quota_class'      => $views->get_quota_class(),
			'plan_label'       => $user->plan_label,
			'plan_with_quota'  => $user->is_free() || $user->is_growth(),
			'unconsumed_quota' => $unconsumed_quota,
			'user_quota'       => $user->quota,
			'next_update'      => $user->next_date_update,
			'text'             => $text,
			'button_text'      => $button_text,
			'upgrade_link'     => $upgrade_link,
			'upgrade_button'   => $upgrade_button,
		];

		$template = $views->get_template( 'admin/admin-bar-status', $data );

		wp_send_json_success( $template );
	}
}
