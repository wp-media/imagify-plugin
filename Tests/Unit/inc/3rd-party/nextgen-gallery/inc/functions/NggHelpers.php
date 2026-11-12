<?php

namespace Imagify\Tests\Unit\Inc\ThirdParty\NGG\Functions;

use Brain\Monkey\Functions;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for NGG helper functions in inc/3rd-party/nextgen-gallery/inc/functions/common.php.
 *
 * These tests cover the no-NGG code paths that execute in any environment where NGG is not
 * installed (unit CI, fresh WP, NGG v4 before POPE classes load). They verify:
 *   – the guard returns false when neither Mixin nor C_Gallery_Storage exist;
 *   – the slug helpers fall back to their v3 / legacy values.
 *
 * The NGG common.php file is loaded before patchwork starts (via bootstrap.php) so
 * class_exists() behaves as the plain PHP built-in and naturally returns false for
 * absent NGG classes.
 *
 * @covers ::imagify_ngg_has_pope_storage
 * @covers ::imagify_get_ngg_parent_menu_slug
 * @covers ::imagify_get_ngg_manage_gallery_url
 * @covers ::imagify_get_ngg_bulk_screen_slug
 * @covers ::imagify_get_ngg_bulk_screen_id
 *
 * @group NGG
 */
class Test_NggHelpers extends TestCase {

	public function testHasPOPEStorageReturnsFalseWhenNGGAbsent() {
		// Neither Mixin nor C_Gallery_Storage are loaded in the unit test environment.
		$this->assertFalse( imagify_ngg_has_pope_storage() );
	}

	public function testGetNggParentMenuSlugFallsBackToNextgenGallery() {
		// Imagely\NGG\Admin\App absent, NGGFOLDER not defined → legacy 'nextgen-gallery'.
		$this->assertSame( 'nextgen-gallery', imagify_get_ngg_parent_menu_slug() );
	}

	public function testGetNggManageGalleryUrlFallsBackToNggalleryManageGallery() {
		// Imagely\NGG\Admin\App absent → v3 'nggallery-manage-gallery' slug.
		$this->assertSame( 'nggallery-manage-gallery', imagify_get_ngg_manage_gallery_url() );
	}

	public function testGetNggBulkScreenSlugReturnsImagifySlug() {
		$this->assertSame( 'imagify-ngg-bulk-optimization', imagify_get_ngg_bulk_screen_slug() );
	}

	public function testGetNggBulkScreenIdWithNoHooksUsesParentSlug() {
		global $admin_page_hooks;
		$prev               = $admin_page_hooks;
		$admin_page_hooks   = [];
		// sanitize_title is a WP function — stub it to return the input unchanged.
		Functions\when( 'sanitize_title' )->returnArg();
		// With no hooks, falls back to sanitize_title('nextgen-gallery') = 'nextgen-gallery'.
		$result             = imagify_get_ngg_bulk_screen_id();
		$admin_page_hooks   = $prev;
		$this->assertSame( 'nextgen-gallery_page_imagify-ngg-bulk-optimization', $result );
	}

	public function testGetNggBulkScreenIdUsesHookWhenAvailable() {
		global $admin_page_hooks;
		$prev                                   = $admin_page_hooks;
		$admin_page_hooks['nextgen-gallery']    = 'gallery';
		$result                                 = imagify_get_ngg_bulk_screen_id();
		$admin_page_hooks                       = $prev;
		$this->assertSame( 'gallery_page_imagify-ngg-bulk-optimization', $result );
	}
}
