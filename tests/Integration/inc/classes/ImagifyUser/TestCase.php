<?php

namespace Imagify\tests\Integration\inc\classes\ImagifyUser;

use Imagify;
use Imagify\tests\Integration\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase {
	protected $originalUserInstance;

	public function setUp() {
		parent::setUp();

		$this->originalUserInstance = $this->resetPropertyValue( 'user', Imagify::class );
	}

	public function tearDown() {
		parent::tearDown();

		// Restore the user on the static property.
		$this->setPropertyValue( 'user', Imagify::class, $this->originalUserInstance );

	}
}
