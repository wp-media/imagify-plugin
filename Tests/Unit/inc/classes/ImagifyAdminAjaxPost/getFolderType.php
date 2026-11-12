<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\inc\classes\ImagifyAdminAjaxPost;

use Brain\Monkey\Functions;
use Imagify_Admin_Ajax_Post;
use Imagify\Tests\Unit\TestCase;
use ReflectionClass;

/**
 * @covers Imagify_Admin_Ajax_Post::get_folder_type
 * @covers Imagify_Admin_Ajax_Post::get_request_param
 *
 * @group  ImagifyAdminAjaxPost
 */
class Test_GetFolderType extends TestCase {
	/**
	 * Original $_GET and $_POST, restored after each test.
	 *
	 * @var array
	 */
	private $superglobals = [];

	public function setUp(): void {
		parent::setUp();

		$this->superglobals = [ $_GET, $_POST ];

		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_text_field' )->alias(
			function ( $value ) {
				return is_string( $value ) ? trim( strip_tags( $value ) ) : '';
			}
		);
	}

	public function tearDown(): void {
		[ $_GET, $_POST ] = $this->superglobals;

		parent::tearDown();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnExpected( $config, $expected ) {
		$_GET  = $config['get'];
		$_POST = $config['post'];

		// The constructor needs a real filesystem, which this method does not use.
		$ajax = ( new ReflectionClass( Imagify_Admin_Ajax_Post::class ) )->newInstanceWithoutConstructor();

		$this->assertSame( $expected, $ajax->get_folder_type( ...$config['args'] ) );
	}
}
