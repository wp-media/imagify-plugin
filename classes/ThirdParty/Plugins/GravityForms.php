<?php
declare(strict_types=1);

namespace Imagify\ThirdParty\Plugins;

use GFForms;
use Imagify\EventManagement\SubscriberInterface;

/**
 * Subscriber for compatibility with GravityForms
 */
class GravityForms implements SubscriberInterface {
	/**
	 * Returns an array of events that this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		if ( ! class_exists( 'GFCommon' ) ) {
			return [];
		}

		return [
			'gform_noconflict_styles'  => 'imagify_gf_noconflict_styles',
			'gform_noconflict_scripts' => 'imagify_gf_noconflict_scripts',
		];
	}

	/**
	 * Register imagify styles to gravity forms conflict styles
	 *
	 * @param array $styles Array fo registered styles.
	 *
	 * @return array
	 */
	public function imagify_gf_noconflict_styles( $styles ): array {
		if ( ! $this->is_gravity_forms_no_conflict_mode_enabled() ) {
			return $styles;
		}

		$styles[] = 'imagify-admin-bar';
		$styles[] = 'imagify-admin';
		$styles[] = 'imagify-notices';
		$styles[] = 'imagify-pricing-modal';

		return $styles;
	}

	/**
	 * Register Imagify scripts to gravity forms conflict scripts
	 *
	 * @param array $scripts Array fo registered scripts.
	 *
	 * @return array
	 */
	public function imagify_gf_noconflict_scripts( $scripts ): array {
		if ( ! $this->is_gravity_forms_no_conflict_mode_enabled() ) {
			return $scripts;
		}

		$scripts[] = 'imagify-admin-bar';
		$scripts[] = 'imagify-sweetalert';
		$scripts[] = 'imagify-admin';
		$scripts[] = 'imagify-notices';
		$scripts[] = 'imagify-pricing-modal';

		return $scripts;
	}

	/**
	 * Check if gravity form is active and no_conflict mode is enabled.
	 *
	 * @return bool
	 */
	private function is_gravity_forms_no_conflict_mode_enabled(): bool {
		return (bool) get_option( 'gform_enable_noconflict', false );
	}
}
