<?php

namespace Imagify\Admin;

use Imagify\Traits\InstanceGetterTrait;
use Imagify\User\User;
use Imagify_Views;

/**
 * Admin bar handler
 */
class AdminBar {
	use InstanceGetterTrait;

	/**
	 * Launch the hooks.
	 *
	 * @return void
	 */
	public function init() {
		if ( wp_doing_ajax() ) {
				add_action( 'wp_ajax_imagify_get_admin_bar_profile', array( $this, 'get_admin_bar_profile_callback' ) );
		}
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

		if ( $user->is_free() ) {
			$text         = esc_html__( 'Upgrade your plan now for more!', 'rocket' ) . '<br>' .
			esc_html__( 'From $5.99/month only, keep going with image optimization!', 'rocket' );
			$button_text  = esc_html__( 'Upgrade My Plan', 'rocket' );
			$upgrade_link = IMAGIFY_APP_DOMAIN . '/subscription/?utm_source=plugin&utm_medium=notification';
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
		];

		$template = $views->get_template( 'admin/admin-bar-status', $data );

		wp_send_json_success( $template );
	}
}
