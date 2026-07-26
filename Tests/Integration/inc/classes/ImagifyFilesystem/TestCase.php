<?php

namespace Imagify\Tests\Integration\inc\classes\ImagifyFilesystem;

use Imagify\Tests\Integration\TestCase as BaseTestCase;
use Imagify_Filesystem;

/**
 * Base test case for Imagify_Filesystem integration tests.
 */
abstract class TestCase extends BaseTestCase {
	protected $use_api = false;

	/**
	 * Filters added by a test, removed again in tear_down().
	 *
	 * @var array Array of [ tag, callback, priority ].
	 */
	protected $added_filters = [];

	/**
	 * Prepares the test environment before each test.
	 */
	public function set_up() {
		parent::set_up();

		$this->setPropertyValue( 'site_root_urls', Imagify_Filesystem::get_instance(), [] );
	}

	/**
	 * Cleans up the test environment after each test.
	 */
	public function tear_down() {
		foreach ( $this->added_filters as $filter ) {
			remove_filter( $filter[0], $filter[1], $filter[2] );
		}
		$this->added_filters = [];

		$this->setPropertyValue( 'site_root_urls', Imagify_Filesystem::get_instance(), [] );

		parent::tear_down();
	}

	/**
	 * Add a filter and register it for automatic removal in tear_down().
	 *
	 * @param string   $tag      Filter tag.
	 * @param callable $callback Filter callback.
	 * @param int      $priority Filter priority.
	 */
	protected function addTrackedFilter( $tag, $callback, $priority = 10 ) {
		add_filter( $tag, $callback, $priority, 2 );

		$this->added_filters[] = [ $tag, $callback, $priority ];
	}
}
