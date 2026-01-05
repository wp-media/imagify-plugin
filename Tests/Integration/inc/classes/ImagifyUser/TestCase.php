<?php

namespace Imagify\Tests\Integration\inc\classes\ImagifyUser;

use Imagify;
use Imagify\Tests\Integration\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase {
	protected $originalUserInstance;

	public function set_up() {
		parent::set_up();

		$this->originalUserInstance = $this->resetPropertyValue( 'user', Imagify::class );

		//Clean up the transients for API cache
		delete_transient('imagify_user_cache');
	}

	public function tear_down() {
		parent::tear_down();

		// Restore the user on the static property.
		$this->setPropertyValue( 'user', Imagify::class, $this->originalUserInstance );

		//Clean up the transients for API cache
		delete_transient('imagify_user_cache');



	}
}
