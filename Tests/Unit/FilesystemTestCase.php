<?php

namespace Imagify\Tests\Unit;

use Imagify\Tests\VirtualFilesystemTrait;
use WPMedia\PHPUnit\Unit\VirtualFilesystemTestCase;

abstract class FilesystemTestCase extends VirtualFilesystemTestCase {
	use VirtualFilesystemTrait;

	public function setUp() {
		$this->initDefaultStructure();

		parent::setUp();

		//$this->redefineRocketDirectFilesystem();
	}
}
