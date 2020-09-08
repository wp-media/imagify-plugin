<?php
namespace Imagify\Tests\Integration\inc\classes\ImagifyFilesystem;

use Imagify_Filesystem;
use Imagify\Tests\Integration\FilesystemTestCase;

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
		$filesystem->make_dir( $this->filesystem->getUrl( $config['dir_name'] ) );

		$this->assertSame( $expected['created'], $this->filesystem->exists( $this->filesystem->getUrl( $config['dir_name'] ) ) );
		$this->assertSame( $expected['created'], $this->filesystem->exists( $this->filesystem->getUrl( $config['dir_name']."/index.php" ) ) );
	}

}
