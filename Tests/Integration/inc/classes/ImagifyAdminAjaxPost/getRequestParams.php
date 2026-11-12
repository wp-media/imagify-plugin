<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\inc\classes\ImagifyAdminAjaxPost;

use Imagify_Admin_Ajax_Post;
use Imagify\Tests\Integration\TestCase;

/**
 * @covers Imagify_Admin_Ajax_Post::get_context
 * @covers Imagify_Admin_Ajax_Post::get_folder_type
 * @covers Imagify_Admin_Ajax_Post::get_imagify_action
 *
 * @group  ImagifyAdminAjaxPost
 */
class Test_GetRequestParams extends TestCase {
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	public function tear_down() {
		unset( $_GET['context'], $_POST['context'], $_GET['folder_type'], $_POST['folder_type'], $_GET['imagify_action'], $_POST['imagify_action'] );

		parent::tear_down();
	}

	public function testShouldReadContextFromTheRequestedMethod() {
		$_GET['context']  = 'WP';
		$_POST['context'] = 'custom-folders';

		$ajax = Imagify_Admin_Ajax_Post::get_instance();

		$this->assertSame( 'wp', $ajax->get_context() );
		$this->assertSame( 'custom-folders', $ajax->get_context( 'POST' ) );
	}

	public function testShouldReturnNoopContextWhenMissingFromTheRequestedMethod() {
		$_POST['context'] = 'wp';

		$ajax = Imagify_Admin_Ajax_Post::get_instance();

		$this->assertSame( 'noop', $ajax->get_context() );
		$this->assertSame( 'noop', $ajax->get_context( 'POST', 'other' ) );
	}

	public function testShouldReturnNoopContextWhenNotAString() {
		$_GET['context'] = [ 'wp' ];

		$this->assertSame( 'noop', Imagify_Admin_Ajax_Post::get_instance()->get_context() );
	}

	public function testShouldReadFolderType() {
		$_GET['folder_type'] = 'library <b>';

		$ajax = Imagify_Admin_Ajax_Post::get_instance();

		$this->assertSame( 'library', $ajax->get_folder_type() );
		$this->assertSame( 'noop', $ajax->get_folder_type( 'POST' ) );
	}

	public function testShouldReadImagifyActionOrDefaultToOptimize() {
		$_POST['imagify_action'] = 'restore';

		$ajax = Imagify_Admin_Ajax_Post::get_instance();

		$this->assertSame( 'restore', $ajax->get_imagify_action( 'POST' ) );
		$this->assertSame( 'optimize', $ajax->get_imagify_action() );
	}
}
