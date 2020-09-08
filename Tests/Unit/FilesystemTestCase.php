<?php

namespace Imagify\Tests\Unit;

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
		$this->stubWpNormalizePath();
		$this->redefineImagifyDirectFilesystem();
	}
}
