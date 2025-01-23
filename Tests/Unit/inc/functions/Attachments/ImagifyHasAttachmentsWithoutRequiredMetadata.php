<?php

namespace functions\Attachments;

use Imagify\Tests\Unit\TestCase;
use Brain\Monkey\Functions;
use wpdb;

/**
 *
 * @covers imagify_has_attachments_without_required_metadata
 */
class Test_ImagifyHasAttachmentsWithoutRequiredMetadata extends TestCase {

	private $wpdb;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once IMAGIFY_PLUGIN_TESTS_FIXTURES_DIR . '/wpdb.php';
	}

	public function setUp(): void {
		parent::setUp();

		$GLOBALS['wpdb'] = $this->wpdb = new wpdb( 'dbuser', 'dbpassword', 'dbname', 'dbhost' );

		$this->wpdb->posts = 'wp_posts';
	}


	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );

		parent::tearDown();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnExpected( $config, $expected ) {
		$this->wpdb->set_var( $expected );

		if( $expected ) {
			Functions\expect('get_post_stati')
				->times(1)
				->andReturn($config['statuses']);
			Functions\expect( 'esc_sql' )->andReturnFirstArg();
		}

		$result = imagify_has_attachments_without_required_metadata( $config['reset'] );

		if( $expected ) {
			$this->assertTrue( $result );
			return;
		}

		$this->assertFalse( $result );
	}
}
