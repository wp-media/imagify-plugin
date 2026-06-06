<?php

namespace Imagify\Tests\Integration\inc\classes\ImagifyUser;

use Imagify;
use Imagify\Tests\Integration\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase {
	protected $originalUserInstance;

	public function set_up() {
		parent::set_up();

		// Skip API-dependent tests when no API key is configured (e.g. fork PRs without repo secrets).
		if ( '' === $this->getApiCredential( 'IMAGIFY_TESTS_API_KEY' ) ) {
			$this->markTestSkipped( 'IMAGIFY_TESTS_API_KEY is not configured.' );
		}

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
