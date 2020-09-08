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

	public function setUp() {
		parent::setUp();
		$this->abspath = $this->filesystem->getUrl( $this->config['vfs_dir'] );
		$this->stubSetUrlSchema();

		Functions\expect('get_option')->andReturnUsing( function ($key){
			switch ( $key ){
				case 'home':
					return 'http://www.example.com/';
					break;
				case 'siteurl':
					return 'http://www.example.com/';
					break;
			}
		} );
	}

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
