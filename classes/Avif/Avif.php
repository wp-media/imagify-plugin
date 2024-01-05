<?php
namespace Imagify\Avif;

use Imagify\Notices\Notices;
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

	public function maybe_bulk_optimize_callback() {
		// start bulk optimization if option wasn't enabled.
	}
}
