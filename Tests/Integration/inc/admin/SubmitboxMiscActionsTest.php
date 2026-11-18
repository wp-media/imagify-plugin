<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\inc\admin;

use Imagify_Requirements;
use Imagify\Tests\Integration\TestCase;

/**
 * Tests _imagify_attachment_submitbox_misc_actions(): the Imagify panel of the Edit Media screen.
 *
 * Whatever the state of the media, the panel content must sit in the container the JS looks for, so the
 * Optimize / Re-optimize / Restore buttons can run through AJAX and update the panel in place.
 *
 * @group EditMedia
 */
class SubmitboxMiscActionsTest extends TestCase {
	/**
	 * No API calls here: a valid key is faked.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Absolute paths created by the fixtures, removed in tear_down().
	 *
	 * @var array
	 */
	private $created_files = [];

	/**
	 * Load the meta box (admin only) and fake a valid API key and an administrator.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		require_once IMAGIFY_PLUGIN_ROOT . 'inc/admin/meta-boxes.php';

		add_filter( 'pre_get_imagify_option_api_key', [ $this, 'fakeApiKey' ] );
		// Resetting the cache also deletes the license transient: set it after.
		Imagify_Requirements::reset_cache( 'api_key_valid' );
		set_site_transient( 'imagify_check_licence_1', 1 );
		Imagify_Requirements::is_api_key_valid();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * Return an API key, so the license check (cached by the transient set in set_up()) passes without any API call.
	 *
	 * @return string
	 */
	public function fakeApiKey(): string {
		return 'abcdefghijklmnopqrstuvwxyz1234567890';
	}

	/**
	 * Clean up.
	 *
	 * @return void
	 */
	public function tear_down() {
		foreach ( $this->created_files as $path ) {
			wp_delete_file( $path );
		}

		$this->created_files = [];

		remove_filter( 'pre_get_imagify_option_api_key', [ $this, 'fakeApiKey' ] );
		Imagify_Requirements::reset_cache( 'api_key_valid' );

		unset( $GLOBALS['post'] );

		parent::tear_down();
	}

	/**
	 * Create a JPEG attachment, with its file on disk and the data Imagify requires.
	 *
	 * @return int
	 */
	private function create_attachment(): int {
		$uploads  = wp_upload_dir();
		$filename = 'imagify-submitbox-' . uniqid() . '.jpg';
		$path     = trailingslashit( $uploads['basedir'] ) . $filename;

		wp_mkdir_p( dirname( $path ) );
		file_put_contents( $path, 'not-a-real-jpeg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		$this->created_files[] = $path;

		$attachment_id = self::factory()->attachment->create_object(
			[
				'file'           => $filename,
				'post_mime_type' => 'image/jpeg',
				'post_status'    => 'inherit',
			]
		);

		update_post_meta( $attachment_id, '_wp_attached_file', $filename );
		update_post_meta( $attachment_id, '_wp_attachment_metadata', [ 'file' => $filename ] );

		return $attachment_id;
	}

	/**
	 * Mark an attachment as optimized.
	 *
	 * @param  int $attachment_id The attachment ID.
	 * @return void
	 */
	private function set_optimized( int $attachment_id ): void {
		update_post_meta( $attachment_id, '_imagify_status', 'success' );
		update_post_meta(
			$attachment_id,
			'_imagify_data',
			[
				'sizes' => [
					'full' => [
						'success'        => true,
						'original_size'  => 1000,
						'optimized_size' => 800,
						'percent'        => 20.0,
					],
				],
				'stats' => [
					'original_size'  => 1000,
					'optimized_size' => 800,
					'percent'        => 20.0,
				],
			]
		);
	}

	/**
	 * Print the panel the way WordPress does on the Edit Media screen, and return what it printed.
	 *
	 * @param  int $attachment_id The attachment ID.
	 * @return string
	 */
	private function get_panel( int $attachment_id ): string {
		$GLOBALS['post'] = get_post( $attachment_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		// On the Edit Media screen, $pagenow is post.php and $typenow is attachment.
		add_filter( 'imagify_is_media_page', '__return_true' );

		ob_start();
		_imagify_attachment_submitbox_misc_actions();

		$output = (string) ob_get_clean();

		remove_filter( 'imagify_is_media_page', '__return_true' );

		return $output;
	}

	/**
	 * Build the regular expression matching the container, with the given content right inside it.
	 *
	 * @param  int    $attachment_id The attachment ID.
	 * @param  string $content       A regular expression the container content must start with.
	 * @return string
	 */
	private function container_regex( int $attachment_id, string $content ): string {
		return sprintf(
			'#<div class="imagify-data-actions-container" data-id="%d" data-context="wp">\s*%s#s',
			$attachment_id,
			$content
		);
	}

	/**
	 * Test: the Optimize button is in the container, and still links to admin-post.php (no-JS fallback).
	 *
	 * @return void
	 */
	public function testShouldPrintOptimizeButtonInContainer(): void {
		$attachment_id = $this->create_attachment();
		$output        = $this->get_panel( $attachment_id );

		$this->assertMatchesRegularExpression(
			$this->container_regex( $attachment_id, '<a href="[^"]*admin-post\.php[^"]*"[^>]*class="button-primary button-imagify-optimize"' ),
			$output
		);
		$this->assertStringContainsString( 'data-processing-label', $output );
		$this->assertStringContainsString( '<h4>Imagify</h4>', $output );
	}

	/**
	 * Test: the processing state is in the container.
	 *
	 * @return void
	 */
	public function testShouldPrintProcessingStateInContainer(): void {
		$attachment_id = $this->create_attachment();

		set_transient( sprintf( 'imagify_wp_%d_process_locked', $attachment_id ), 'optimizing', 600 );

		$output = $this->get_panel( $attachment_id );

		$this->assertMatchesRegularExpression( $this->container_regex( $attachment_id, '.*Optimizing\.\.\.' ), $output );
		$this->assertStringContainsString( '<h4>Imagify</h4>', $output );

		delete_transient( sprintf( 'imagify_wp_%d_process_locked', $attachment_id ) );
	}

	/**
	 * Test: the optimization data is in the container, with the compact layout of the Edit Media screen.
	 *
	 * @return void
	 */
	public function testShouldPrintOptimizationDataInContainer(): void {
		$attachment_id = $this->create_attachment();

		$this->set_optimized( $attachment_id );

		$output = $this->get_panel( $attachment_id );

		$this->assertMatchesRegularExpression( $this->container_regex( $attachment_id, '.*Original Saving' ), $output );
		$this->assertStringContainsString( '<h4>Imagify</h4>', $output );
		$this->assertStringNotContainsString( 'imagify-datas-list', $output );
	}
}
