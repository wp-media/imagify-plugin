<?php

namespace Imagify\Tests\Unit\Inc\ThirdParty\NGG\Functions;

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
}
