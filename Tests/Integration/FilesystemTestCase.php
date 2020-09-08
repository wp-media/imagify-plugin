<?php

namespace Imagify\Tests\Integration;

use Imagify\Tests\VirtualFilesystemTrait;
use Imagify\Tests\StubTrait;
use WPMedia\PHPUnit\Unit\VirtualFilesystemTestCase;

abstract class FilesystemTestCase extends VirtualFilesystemTestCase {
	use VirtualFilesystemTrait;
	use StubTrait;

	public function setUp() {
		$this->initDefaultStructure();

		parent::setUp();
		$this->stubImagifyGetConstant();
		$this->redefineImagifyDirectFilesystem();
	}
}
