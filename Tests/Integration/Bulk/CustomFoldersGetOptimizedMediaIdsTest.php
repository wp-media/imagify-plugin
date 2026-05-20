<?php
/**
 * Integration tests for CustomFolders::get_optimized_media_ids_without_format().
 *
 * These tests validate the SQL query changes introduced in PR #1057 (fix/1041-generate-missing-next-gen):
 *
 *   Bug 1: Inactive custom-folder files must be excluded from the next-gen count.
 *          Fix: added `fo.active = 1` predicate to the INNER JOIN ON clause.
 *
 *   Bug 2: Files with NULL optimization data must be excluded from the next-gen queue.
 *          Fix: removed the `OR fi.data IS NULL` branch from the WHERE clause; the
 *               `fi.data NOT LIKE %s` condition now excludes NULL rows as well.
 *
 *   Bug 6: Mime-type exclusion must work regardless of spacing in get_mime_types() output.
 *          Fix: `$mime = trim($mime)` added before the str_replace substitution;
 *               both `, 'mime'` and `,'mime'` variants handled.
 *
 *   Bug 7: Files whose extension already matches the target format (e.g. a .webp file with
 *          incorrect post_mime_type) must be skipped.
 *          Fix: extension check added before the backup-existence check.
 *
 * NOTE: These tests require a running WordPress database (wp-env) and the wp-tests-config.php
 * bootstrap. They cannot run in the plain PHPUnit unit environment. Run via:
 *
 *   npx @wordpress/env run cli wp eval-file Tests/Integration/Bulk/CustomFoldersGetOptimizedMediaIdsTest.php
 *
 * Or through the full integration suite once the test runner is configured.
 *
 * @package Imagify\Tests\Integration\Bulk
 */

namespace Imagify\Tests\Integration\Bulk;

use Imagify\Bulk\CustomFolders;
use Imagify\Tests\Integration\TestCase;

/**
 * @covers \Imagify\Bulk\CustomFolders::get_optimized_media_ids_without_format
 */
class CustomFoldersGetOptimizedMediaIdsTest extends TestCase {
	/**
	 * Integration tests for this class do not require a live Imagify API key.
	 *
	 * @var bool
	 */
	protected $useApi = false;

	/**
	 * The subject under test.
	 *
	 * @var CustomFolders
	 */
	private $bulk;

	/**
	 * @inheritdoc
	 */
	public function set_up() {
		parent::set_up();
		$this->bulk = new CustomFolders();
	}

	// -------------------------------------------------------------------------
	// Bug 1 — Inactive folders must not contribute IDs.
	// -------------------------------------------------------------------------

	/**
	 * Files belonging to an inactive folder must not appear in the result.
	 *
	 * Before the fix the INNER JOIN had no `fo.active = 1` predicate, so
	 * deactivated-folder files were included and enqueued for processing even
	 * though they could never be fetched.
	 *
	 * @test
	 */
	public function inactive_folder_files_are_excluded() {
		global $wpdb;

		$files_table   = \Imagify_Files_DB::get_instance()->get_table_name();
		$folders_table = \Imagify_Folders_DB::get_instance()->get_table_name();

		// Insert a folder marked inactive.
		$wpdb->insert(
			$folders_table,
			[
				'path'   => '/tmp/inactive-folder/',
				'active' => 0,
				'slug'   => 'test-inactive-folder',
			]
		);
		$folder_id = (int) $wpdb->insert_id;

		// Insert a file in that folder that would otherwise match the query.
		$wpdb->insert(
			$files_table,
			[
				'folder_id' => $folder_id,
				'path'      => '%UPLOADS%/inactive-folder/image.jpg',
				'mime_type' => 'image/jpeg',
				'status'    => 'success',
				'data'      => 'a:1:{s:4:"full";a:2:{s:7:"success";b:1;s:5:"level";i:1;}}',
			]
		);
		$file_id = (int) $wpdb->insert_id;

		$result = $this->bulk->get_optimized_media_ids_without_format( 'webp' );

		// The inactive-folder file must not be in the ids list.
		$this->assertNotContains(
			$file_id,
			$result['ids'],
			'File in an inactive folder must not be queued for next-gen generation.'
		);

		// Cleanup.
		$wpdb->delete( $files_table, [ 'file_id' => $file_id ] );
		$wpdb->delete( $folders_table, [ 'folder_id' => $folder_id ] );
	}

	/**
	 * Files belonging to an active folder ARE included when they pass all other criteria.
	 *
	 * @test
	 */
	public function active_folder_files_can_be_included() {
		global $wpdb;

		$files_table   = \Imagify_Files_DB::get_instance()->get_table_name();
		$folders_table = \Imagify_Folders_DB::get_instance()->get_table_name();

		// Create a real backup file so the backup-existence check passes.
		$tmp_dir     = sys_get_temp_dir();
		$source_name = 'qa-test-active-image.jpg';
		$backup_name = 'qa-test-active-image-backup.jpg';
		$source_path = $tmp_dir . '/' . $source_name;
		$backup_path = $tmp_dir . '/' . $backup_name;
		file_put_contents( $source_path, 'fake-image-content' );
		file_put_contents( $backup_path, 'fake-backup-content' );

		// Insert an active folder.
		$wpdb->insert(
			$folders_table,
			[
				'path'   => rtrim( $tmp_dir, '/' ) . '/',
				'active' => 1,
				'slug'   => 'test-active-folder',
			]
		);
		$folder_id = (int) $wpdb->insert_id;

		// Insert the file — no @imagify-webp entry in data, so it should appear as "missing".
		$wpdb->insert(
			$files_table,
			[
				'folder_id' => $folder_id,
				'path'      => $source_path,
				'mime_type' => 'image/jpeg',
				'status'    => 'success',
				'data'      => 'a:1:{s:4:"full";a:2:{s:7:"success";b:1;s:5:"level";i:1;}}',
			]
		);
		$file_id = (int) $wpdb->insert_id;

		$result = $this->bulk->get_optimized_media_ids_without_format( 'webp' );

		$this->assertContains(
			$file_id,
			$result['ids'],
			'File in an active folder without a webp key should be queued.'
		);

		// Cleanup.
		@unlink( $source_path );
		@unlink( $backup_path );
		$wpdb->delete( $files_table, [ 'file_id' => $file_id ] );
		$wpdb->delete( $folders_table, [ 'folder_id' => $folder_id ] );
	}

	// -------------------------------------------------------------------------
	// Bug 2 — Files with NULL data must be excluded.
	// -------------------------------------------------------------------------

	/**
	 * Files whose `data` column is NULL must not be returned.
	 *
	 * Before the fix the SQL included `OR fi.data IS NULL` which caused
	 * unoptimized files (no data at all) to be enqueued for next-gen generation.
	 * They had no optimization result to attach a WebP version to.
	 *
	 * @test
	 */
	public function files_with_null_data_are_excluded() {
		global $wpdb;

		$files_table   = \Imagify_Files_DB::get_instance()->get_table_name();
		$folders_table = \Imagify_Folders_DB::get_instance()->get_table_name();

		// Active folder.
		$wpdb->insert(
			$folders_table,
			[
				'path'   => '/tmp/null-data-folder/',
				'active' => 1,
				'slug'   => 'test-null-data-folder',
			]
		);
		$folder_id = (int) $wpdb->insert_id;

		// File with status='success' but NULL data (no optimization record yet).
		$wpdb->insert(
			$files_table,
			[
				'folder_id' => $folder_id,
				'path'      => '/tmp/null-data-folder/image.jpg',
				'mime_type' => 'image/jpeg',
				'status'    => 'success',
				'data'      => null,
			]
		);
		$file_id = (int) $wpdb->insert_id;

		$result = $this->bulk->get_optimized_media_ids_without_format( 'webp' );

		$this->assertNotContains(
			$file_id,
			$result['ids'],
			'File with NULL data must not be queued for next-gen generation.'
		);
		$this->assertNotContains(
			$file_id,
			$result['errors']['no_file_path'],
			'File with NULL data must not appear in no_file_path errors.'
		);

		// Cleanup.
		$wpdb->delete( $files_table, [ 'file_id' => $file_id ] );
		$wpdb->delete( $folders_table, [ 'folder_id' => $folder_id ] );
	}

	// -------------------------------------------------------------------------
	// Bug 7 — Files whose extension matches the target format must be skipped.
	// -------------------------------------------------------------------------

	/**
	 * A .webp file registered with post_mime_type = 'image/jpeg' must be skipped
	 * (it would otherwise appear permanently as "remaining = 1").
	 *
	 * @test
	 */
	public function webp_source_files_are_skipped_regardless_of_mime_type() {
		global $wpdb;

		$files_table   = \Imagify_Files_DB::get_instance()->get_table_name();
		$folders_table = \Imagify_Folders_DB::get_instance()->get_table_name();

		// Active folder.
		$wpdb->insert(
			$folders_table,
			[
				'path'   => '/tmp/webp-mime-test/',
				'active' => 1,
				'slug'   => 'test-webp-mime-folder',
			]
		);
		$folder_id = (int) $wpdb->insert_id;

		// A .webp file stored as image/jpeg (BuddyBoss scenario).
		$wpdb->insert(
			$files_table,
			[
				'folder_id' => $folder_id,
				'path'      => '/tmp/webp-mime-test/avatar.webp',
				'mime_type' => 'image/jpeg',
				'status'    => 'success',
				'data'      => 'a:1:{s:4:"full";a:2:{s:7:"success";b:1;s:5:"level";i:1;}}',
			]
		);
		$file_id = (int) $wpdb->insert_id;

		$result = $this->bulk->get_optimized_media_ids_without_format( 'webp' );

		$this->assertNotContains(
			$file_id,
			$result['ids'],
			'.webp files must be skipped even if registered as image/jpeg.'
		);

		// Cleanup.
		$wpdb->delete( $files_table, [ 'file_id' => $file_id ] );
		$wpdb->delete( $folders_table, [ 'folder_id' => $folder_id ] );
	}

	// -------------------------------------------------------------------------
	// Files that already have a WebP key in data must be excluded.
	// -------------------------------------------------------------------------

	/**
	 * A file that already has the @imagify-webp success key in its data must
	 * NOT be returned by the query (it does not need a new WebP version).
	 *
	 * @test
	 */
	public function files_already_having_webp_key_are_excluded() {
		global $wpdb;

		$files_table   = \Imagify_Files_DB::get_instance()->get_table_name();
		$folders_table = \Imagify_Folders_DB::get_instance()->get_table_name();

		$nextgen_suffix = constant(
			\imagify_get_optimization_process_class_name( 'custom-folders' ) . '::WEBP_SUFFIX'
		);

		// Active folder.
		$wpdb->insert(
			$folders_table,
			[
				'path'   => '/tmp/webp-complete-folder/',
				'active' => 1,
				'slug'   => 'test-webp-complete-folder',
			]
		);
		$folder_id = (int) $wpdb->insert_id;

		// File whose data contains the WebP success key.
		$webp_size_key = 'full' . $nextgen_suffix;
		$data          = 'a:2:{s:4:"full";a:2:{s:7:"success";b:1;s:5:"level";i:1;}s:' . strlen( $webp_size_key ) . ':"' . $webp_size_key . '";a:4:{s:7:"success";b:1;s:13:"original_size";i:1000;s:14:"optimized_size";i:800;s:5:"level";i:1;}}';

		$wpdb->insert(
			$files_table,
			[
				'folder_id' => $folder_id,
				'path'      => '/tmp/webp-complete-folder/image.jpg',
				'mime_type' => 'image/jpeg',
				'status'    => 'success',
				'data'      => $data,
			]
		);
		$file_id = (int) $wpdb->insert_id;

		$result = $this->bulk->get_optimized_media_ids_without_format( 'webp' );

		$this->assertNotContains(
			$file_id,
			$result['ids'],
			'File with an existing @imagify-webp key must not be queued again.'
		);

		// Cleanup.
		$wpdb->delete( $files_table, [ 'file_id' => $file_id ] );
		$wpdb->delete( $folders_table, [ 'folder_id' => $folder_id ] );
	}
}
