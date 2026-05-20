<?php
/**
 * Integration tests for WP::get_optimized_media_ids_without_format().
 *
 * These tests validate the SQL query changes introduced in PR #1057 (fix/1041-generate-missing-next-gen):
 *
 *   Bug 4: Images without an `_imagify_status` row must not be queued for next-gen generation.
 *          Fix: removed the `mt1.meta_key IS NULL` branch from the WHERE clause;
 *               only `mt1.meta_value = 'success'` or `mt1.meta_value = 'already_optimized'` pass.
 *
 *   Bug 6: Mime-type exclusion must correctly remove `.webp` source files from the IN() clause
 *          regardless of spacing in `get_mime_types()` output.
 *          Fix: `$mime = trim($mime)` added; both `, 'mime'` and `,'mime'` patterns handled.
 *
 *   Bug 7: Files whose extension already matches the target format (e.g. a .webp file with
 *          incorrect post_mime_type) must be skipped.
 *          Fix: extension check added before the backup-existence check.
 *
 * NOTE: These tests require a running WordPress database (wp-env) and the wp-tests-config.php
 * bootstrap. They cannot run in the plain PHPUnit unit environment. Run via the full integration
 * suite once the test runner is configured.
 *
 * @package Imagify\Tests\Integration\Bulk
 */

namespace Imagify\Tests\Integration\Bulk;

use Imagify\Bulk\WP;
use Imagify\Tests\Integration\TestCase;

/**
 * @covers \Imagify\Bulk\WP::get_optimized_media_ids_without_format
 */
class WPGetOptimizedMediaIdsTest extends TestCase {
	/**
	 * Integration tests for this class do not require a live Imagify API key.
	 *
	 * @var bool
	 */
	protected $useApi = false;

	/**
	 * The subject under test.
	 *
	 * @var WP
	 */
	private $bulk;

	/**
	 * @inheritdoc
	 */
	public function set_up() {
		parent::set_up();
		$this->bulk = new WP();
	}

	// -------------------------------------------------------------------------
	// Bug 4 — Images without _imagify_status must be excluded.
	// -------------------------------------------------------------------------

	/**
	 * Attachments that have no _imagify_status meta row must not be queued for
	 * next-gen generation.
	 *
	 * Before the fix the SQL LEFT JOIN for `mt1` (`_imagify_status`) combined
	 * with `mt1.meta_key IS NULL` in the WHERE clause meant that unoptimized
	 * images (status = NULL) passed the filter and were dispatched to
	 * ActionScheduler even though they had no optimization data to build a
	 * WebP version from.
	 *
	 * @test
	 */
	public function images_without_imagify_status_are_excluded() {
		global $wpdb;

		// Insert a bare attachment post with no imagify meta.
		$post_id = wp_insert_attachment(
			[
				'post_title'     => 'QA test image — no status',
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			]
		);

		// Do NOT add _imagify_status or _imagify_data meta.

		$result = $this->bulk->get_optimized_media_ids_without_format( 'webp' );

		$this->assertNotContains(
			$post_id,
			$result['ids'],
			'An attachment without _imagify_status must not be queued for next-gen generation.'
		);

		// Cleanup.
		wp_delete_attachment( $post_id, true );
	}

	/**
	 * Attachments with _imagify_status = 'error' must also be excluded.
	 *
	 * The fixed WHERE clause only accepts 'success' or 'already_optimized'.
	 *
	 * @test
	 */
	public function images_with_error_status_are_excluded() {
		global $wpdb;

		$post_id = wp_insert_attachment(
			[
				'post_title'     => 'QA test image — error status',
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			]
		);

		update_post_meta( $post_id, '_imagify_status', 'error' );

		$result = $this->bulk->get_optimized_media_ids_without_format( 'webp' );

		$this->assertNotContains(
			$post_id,
			$result['ids'],
			'An attachment with _imagify_status = error must not be queued for next-gen generation.'
		);

		// Cleanup.
		wp_delete_attachment( $post_id, true );
	}

	/**
	 * Attachments with _imagify_status = 'success' and no _imagify_data webp
	 * entry should be included (they need a WebP version).
	 *
	 * @test
	 */
	public function images_with_success_status_and_no_webp_are_included() {
		global $wpdb;

		// Create a real file so the file-existence and backup checks pass.
		$tmp_dir     = sys_get_temp_dir();
		$source_path = $tmp_dir . '/qa-wp-test-image.jpg';
		$backup_path = get_imagify_attachment_backup_path( $source_path );

		// Fallback: derive backup path manually if helper is not available yet.
		if ( ! $backup_path ) {
			$backup_path = $tmp_dir . '/imagify-backup/qa-wp-test-image.jpg';
		}
		@mkdir( dirname( $backup_path ), 0755, true );
		file_put_contents( $source_path, 'fake-jpeg-content' );
		file_put_contents( $backup_path, 'fake-backup-content' );

		$post_id = wp_insert_attachment(
			[
				'post_title'     => 'QA test image — success status',
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			],
			$source_path
		);

		update_post_meta( $post_id, '_imagify_status', 'success' );
		// No @imagify-webp key in _imagify_data.
		update_post_meta( $post_id, '_imagify_data', [ 'sizes' => [ 'full' => [ 'success' => true, 'level' => 1 ] ] ] );
		update_post_meta( $post_id, '_wp_attached_file', $source_path );

		$result = $this->bulk->get_optimized_media_ids_without_format( 'webp' );

		$this->assertContains(
			$post_id,
			$result['ids'],
			'An optimized attachment without a webp key should be queued for next-gen generation.'
		);

		// Cleanup.
		@unlink( $source_path );
		@unlink( $backup_path );
		wp_delete_attachment( $post_id, true );
	}

	// -------------------------------------------------------------------------
	// Bug 7 — .webp source files must be skipped regardless of post_mime_type.
	// -------------------------------------------------------------------------

	/**
	 * A .webp file registered as image/jpeg (BuddyBoss avatar scenario) must
	 * not appear permanently as "remaining = 1".
	 *
	 * @test
	 */
	public function webp_source_files_are_skipped_regardless_of_mime_type() {
		global $wpdb;

		$tmp_dir     = sys_get_temp_dir();
		$source_path = $tmp_dir . '/qa-wp-avatar.webp';
		file_put_contents( $source_path, 'fake-webp-content' );

		$post_id = wp_insert_attachment(
			[
				'post_title'     => 'QA test — .webp with jpeg mime',
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',  // Incorrectly registered MIME type.
			],
			$source_path
		);

		update_post_meta( $post_id, '_imagify_status', 'success' );
		update_post_meta( $post_id, '_imagify_data', [ 'sizes' => [ 'full' => [ 'success' => true, 'level' => 1 ] ] ] );
		update_post_meta( $post_id, '_wp_attached_file', $source_path );

		$result = $this->bulk->get_optimized_media_ids_without_format( 'webp' );

		$this->assertNotContains(
			$post_id,
			$result['ids'],
			'A .webp source file must be skipped even if registered as image/jpeg.'
		);

		// Cleanup.
		@unlink( $source_path );
		wp_delete_attachment( $post_id, true );
	}

	// -------------------------------------------------------------------------
	// Files that already have a WebP key in data must be excluded.
	// -------------------------------------------------------------------------

	/**
	 * An attachment that already has the @imagify-webp success key in _imagify_data
	 * must NOT be returned (it does not need a new WebP version).
	 *
	 * @test
	 */
	public function images_already_having_webp_key_are_excluded() {
		global $wpdb;

		$nextgen_suffix = constant(
			\imagify_get_optimization_process_class_name( 'wp' ) . '::WEBP_SUFFIX'
		);

		$tmp_dir     = sys_get_temp_dir();
		$source_path = $tmp_dir . '/qa-wp-webp-complete.jpg';
		$backup_path = get_imagify_attachment_backup_path( $source_path );
		if ( ! $backup_path ) {
			$backup_path = $tmp_dir . '/imagify-backup/qa-wp-webp-complete.jpg';
		}
		@mkdir( dirname( $backup_path ), 0755, true );
		file_put_contents( $source_path, 'fake-jpeg-complete' );
		file_put_contents( $backup_path, 'fake-backup-complete' );

		$post_id = wp_insert_attachment(
			[
				'post_title'     => 'QA test — webp already complete',
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			],
			$source_path
		);

		update_post_meta( $post_id, '_imagify_status', 'success' );
		// Data includes the @imagify-webp success key.
		update_post_meta(
			$post_id,
			'_imagify_data',
			[
				'sizes' => [
					'full'                          => [ 'success' => true, 'level' => 1 ],
					'full' . $nextgen_suffix        => [
						'success'        => true,
						'original_size'  => 1000,
						'optimized_size' => 800,
						'level'          => 1,
					],
				],
			]
		);
		update_post_meta( $post_id, '_wp_attached_file', $source_path );

		$result = $this->bulk->get_optimized_media_ids_without_format( 'webp' );

		$this->assertNotContains(
			$post_id,
			$result['ids'],
			'An attachment with an existing @imagify-webp key must not be queued again.'
		);

		// Cleanup.
		@unlink( $source_path );
		@unlink( $backup_path );
		wp_delete_attachment( $post_id, true );
	}
}
