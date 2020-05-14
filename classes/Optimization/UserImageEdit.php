<?php

namespace Imagify\Optimization;

use Imagify\Traits\InstanceGetterTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Class that handles the optimized images that are manually edited by the user with the "Edit Image" button.
 *
 * @since 1.9.10
 */
class UserImageEdit {
	use InstanceGetterTrait;

	/**
	 * Init.
	 *
	 * @since 1.9.10
	 */
	public function init() {
		add_action( 'wp_ajax_image-editor', [ $this, 'maybe_restore_media_before_edition' ], -5 ); // Before WP’s hook (priority 1).
	}

	/**
	 * Before the image is edited, restore the media.
	 *
	 * @since 1.9.10
	 */
	public function maybe_restore_media_before_edition() {
		$media_id = filter_input(
			INPUT_POST,
			'postid',
			FILTER_VALIDATE_INT,
			[
				'options' => [
					'default'   => 0,
					'min_range' => 0,
				],
			]
		);

		$action = filter_input( INPUT_POST, 'do', FILTER_SANITIZE_STRING );

		if ( empty( $media_id ) || 'save' !== $action ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $media_id ) ) {
			return;
		}

		if ( ! check_ajax_referer( "image_editor-$media_id", false, false ) ) {
			return;
		}

		$process = imagify_get_optimization_process( $media_id, 'wp' );

		if ( ! $process->is_valid() || ! $process->get_data()->is_optimized() ) {
			// Nothing to do if the media is not optimized or invalid.
			return;
		}

		// Work here.
	}
}
