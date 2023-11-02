<?php

namespace Imagify\Admin;

use Imagify\Traits\InstanceGetterTrait;
use Imagify\User\User;
use Imagify_Views;

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

		$data = [
			'quota_icon'       => $views->get_quota_icon(),
			'quota_class'      => $views->get_quota_class(),
			'plan_label'       => $user->plan_label,
			'plan_with_quota'  => $user->is_free() || $user->is_growth(),
			'unconsumed_quota' => $unconsumed_quota,
			'user_quota'       => $user->quota,
			'next_update'      => $user->next_date_update,
		];

		$template = $views->get_template( 'admin/admin-bar-status', $data );

		wp_send_json_success( $template );
	}
}
