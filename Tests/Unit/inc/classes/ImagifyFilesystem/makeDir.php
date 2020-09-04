<?php
namespace Imagify\Tests\Unit\inc\classes\ImagifyFilesystem;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Imagify_Filesystem;
use Imagify\Tests\Unit\FilesystemTestCase;

/**
 * @covers Imagify_Filesystem::make_dir
 *
 * @group  ImagifyFilesystem
 */
class Test_MakeDir extends FilesystemTestCase {
	protected $path_to_test_data = '/inc/classes/ImagifyFilesystem/makeDir.php';

	/**
	 * @dataProvider providerTestData
	 */
	public function testShouldMakeDir( $config, $expected ) {

		$filesystem = Imagify_Filesystem::get_instance();
		$filesystem->make_dir( $config['dir_name'] );

		$this->assertTrue(false);
	}

}
