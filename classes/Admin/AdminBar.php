<?php

namespace Imagify\Admin;

use Imagify\Traits\InstanceGetterTrait;
use Imagify\User\User;

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
		$message          = '';

		if ( $unconsumed_quota <= 20 ) {
			$message  = '<div class="imagify-error">';
				$message .= '<p><i class="dashicons dashicons-warning" aria-hidden="true"></i><strong>' . __( 'Oops, It\'s almost over!', 'imagify' ) . '</strong></p>';
				/* translators: %s is a line break. */
				$message .= '<p>' . sprintf( __( 'You have almost used all your credit.%sDon\'t forget to upgrade your subscription to continue optimizing your images.', 'imagify' ), '<br/><br/>' ) . '</p>';
				$message .= '<p class="center txt-center text-center"><a class="btn imagify-btn-ghost" href="' . esc_url( imagify_get_external_url( 'subscription' ) ) . '" target="_blank">' . __( 'View My Subscription', 'imagify' ) . '</a></p>';
			$message .= '</div>';
		}

		if ( 0 === $unconsumed_quota ) {
			$message  = '<div class="imagify-error">';
				$message .= '<p><i class="dashicons dashicons-warning" aria-hidden="true"></i><strong>' . __( 'Oops, It\'s Over!', 'imagify' ) . '</strong></p>';
				$message .= '<p>' . sprintf(
					/* translators: 1 is a data quota, 2 is a date. */
					__( 'You have consumed all your credit for this month. You will have <strong>%1$s back on %2$s</strong>.', 'imagify' ),
					imagify_size_format( $user->quota * pow( 1024, 2 ) ),
					date_i18n( get_option( 'date_format' ), strtotime( $user->next_date_update ) )
				) . '</p>';
				$message .= '<p class="center txt-center text-center"><a class="btn imagify-btn-ghost" href="' . esc_url( imagify_get_external_url( 'subscription' ) ) . '" target="_blank">' . __( 'Upgrade My Subscription', 'imagify' ) . '</a></p>';
			$message .= '</div>';
		}

		// Custom HTML.
		$quota_section  = '<div class="imagify-admin-bar-quota">';
			$quota_section .= '<div class="imagify-abq-row">';

		if ( 1 === $user->plan_id ) {
			$quota_section .= '<div class="imagify-meteo-icon">' . $views->get_quota_icon() . '</div>';
		}

		$quota_section .= '<div class="imagify-account">';
			$quota_section .= '<p class="imagify-meteo-title">' . __( 'Account status', 'imagify' ) . '</p>';
			$quota_section .= '<p class="imagify-meteo-subs">' . __( 'Your subscription:', 'imagify' ) . '&nbsp;<strong class="imagify-user-plan">' . $user->plan_label . '</strong></p>';
		$quota_section .= '</div>'; // .imagify-account
		$quota_section .= '</div>'; // .imagify-abq-row

		if ( 1 === $user->plan_id ) {
			$quota_section .= '<div class="imagify-abq-row">';
				$quota_section .= '<div class="imagify-space-left">';
					/* translators: %s is a data quota. */
					$quota_section .= '<p>' . sprintf( __( 'You have %s space credit left', 'imagify' ), '<span class="imagify-unconsumed-percent">' . $unconsumed_quota . '%</span>' ) . '</p>';
					$quota_section .= '<div class="' . $views->get_quota_class() . '">';
						$quota_section .= '<div style="width: ' . $unconsumed_quota . '%;" class="imagify-unconsumed-bar imagify-progress"></div>';
					$quota_section .= '</div>'; // .imagify-bar-{negative|neutral|positive}
				$quota_section .= '</div>'; // .imagify-space-left
			$quota_section .= '</div>'; // .imagify-abq-row
		}

		$quota_section .= '<p class="imagify-abq-row">';
			$quota_section .= '<a class="imagify-account-link" href="' . esc_url( imagify_get_external_url( 'subscription' ) ) . '" target="_blank">';
				$quota_section .= '<span class="dashicons dashicons-admin-users"></span>';
				$quota_section .= '<span class="button-text">' . __( 'View my subscription', 'imagify' ) . '</span>';
			$quota_section .= '</a>'; // .imagify-account-link
		$quota_section .= '</p>'; // .imagify-abq-row
		$quota_section .= '</div>'; // .imagify-admin-bar-quota
		$quota_section .= $message;

		wp_send_json_success( $quota_section );
	}
}
