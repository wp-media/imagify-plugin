<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tools\InternalStateList;

use Imagify\Tests\Unit\TestCase;
use Imagify\Tools\InternalStateList;

/**
 * Tests for \Imagify\Tools\InternalStateList static methods.
 *
 * Locks the canonical lists so any accidental removal is caught immediately.
 *
 * @covers \Imagify\Tools\InternalStateList
 * @group  Tools
 */
class Test_SharedList extends TestCase {

	/**
	 * Tests that get_bulk_transients() returns the expected canonical array.
	 */
	public function testGetBulkTransientsReturnsExpectedArray(): void {
		$expected = [
			'imagify_custom-folders_optimize_running',
			'imagify_wp_optimize_running',
			'imagify_bulk_optimization_complete',
			'imagify_missing_next_gen_total',
			'imagify_bulk_optimization_result',
			'imagify_bulk_optimization_infos',
			'imagify_bulk_optimization_level',
		];

		$this->assertSame( $expected, InternalStateList::get_bulk_transients() );
	}

	/**
	 * Asserts that user-data/account transients are NOT present in the bulk list.
	 */
	public function testGetBulkTransientsDoesNotContainUserCacheTransients(): void {
		$user_cache_transients = [
			'imagify_user',
			'imagify_user_cache',
			'imagify_user_images_count',
			'imagify_large_library',
			'imagify_attachments_number_modal',
			'imagify_stat_without_next_gen',
			'imagify_max_image_size',
			'imagify_check_licence_1',
			'imagify_check_api_version',
			'imagify_check_api_key_validity',
			'imagify_settings',
			'imagify_data',
		];

		$bulk_transients = InternalStateList::get_bulk_transients();

		foreach ( $user_cache_transients as $cache_transient ) {
			$this->assertNotContains( $cache_transient, $bulk_transients );
		}
	}

	/**
	 * Tests that get_locked_transient_patterns() returns plain (unescaped) LIKE templates.
	 */
	public function testGetLockedTransientPatternsReturnsExpectedArray(): void {
		$expected = [
			'_transient_%imagify-auto-optimize-%',
			'_transient_%imagify_rpc_%',
			'_transient_imagify_%_process_locked',
			'_site_transient_imagify_%_process_lock%',
			'_transient_imagify_client_side_scaled_%',
			'_transient_timeout_imagify_client_side_scaled_%',
			'_transient_imagify_awaiting_subsizes_%',
			'_transient_timeout_imagify_awaiting_subsizes_%',
		];

		$this->assertSame( $expected, InternalStateList::get_locked_transient_patterns() );
	}

	/**
	 * Tests that get_scheduler_hooks() returns the expected canonical array.
	 */
	public function testGetSchedulerHooksReturnsExpectedArray(): void {
		$expected = [
			'imagify_optimize_media',
			'imagify_convert_next_gen',
		];

		$this->assertSame( $expected, InternalStateList::get_scheduler_hooks() );
	}
}
