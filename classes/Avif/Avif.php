<?php
namespace Imagify\Avif;

use Imagify\Traits\InstanceGetterTrait;

/**
 * AVIF image
 */
class Avif {
	use InstanceGetterTrait;

	/**
	 * Class init: launch hooks.
	 *
	 * @since 2.2
	 */
	public function init() {
		add_action( 'update_option_imagify_settings', [ $this, 'maybe_bulk_optimize_callback' ] );
	}

	/**
	 * Update Options callback to start bulk optimization.
	 *
	 * @return void
	 */
	public function maybe_bulk_optimize_callback() {
		$level = \Imagify_Options::get_instance()->get( 'optimization_level' );
		$contexts = $this->get_contexts();
		foreach ( $contexts as $context ) {
			do_action( 'imagify_bulk_optimize', $context, $level );
		}
	}

	/**
	 * Get the context for the bulk optimization page.
	 *
	 * @return array The array of unique contexts ('wp' or 'custom-folders').
	 */
	public function get_contexts() {
		$contexts = [];
		$types = [];

		// Library: in each site.
		if ( ! is_network_admin() ) {
			$types['library|wp'] = 1;
		}

		// Custom folders: in network admin only if network activated, in each site otherwise.
		if ( imagify_can_optimize_custom_folders() && ( imagify_is_active_for_network() && is_network_admin() || ! imagify_is_active_for_network() ) ) {
			$types['custom-folders|custom-folders'] = 1;
		}

		/**
		 * Filter the types to display in the bulk optimization page.
		 *
		 * @since  1.7.1
		 * @author Grégory Viguier
		 *
		 * @param array $types The folder types displayed on the page. If a folder type is "library", the context should be suffixed after a pipe character. They are passed as array keys.
		 */
		$types = apply_filters( 'imagify_bulk_page_types', $types );
		$types = array_filter( (array) $types );

		if ( isset( $types['library|wp'] ) && ! in_array( 'wp', $contexts, true ) ) {
			$contexts[] = 'wp';
		}

		if ( isset( $types['custom-folders|custom-folders'] ) ) {
			$folders_instance = \Imagify_Folders_DB::get_instance();

			if ( ! $folders_instance->has_items() ) {
				// New Feature!
				if ( ! in_array( 'wp', $contexts, true ) ) {
					$contexts[] = 'wp';
				}
			} elseif ( $folders_instance->has_active_folders() && ! in_array( 'custom-folders', $contexts, true ) ) {
				$contexts[] = 'custom-folders';
			}
		}

		return $contexts;
	}
}
