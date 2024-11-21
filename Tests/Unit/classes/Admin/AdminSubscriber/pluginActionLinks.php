<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes;

use Mockery;
use Brain\Monkey\Functions;
use Imagify\Tests\Unit\TestCase;
use Imagify\User\User;
use Imagify\Admin\AdminSubscriber;

/**
 * Tests for \Imagify\Admin\AdminSubscriber->plugin_action_links().
 *
 * @covers \Imagify\Admin\AdminSubscriber::plugin_action_links
 * @group  ImagifyAPI
 */
class Test_PluginActionLinks extends TestCase {
    protected $admin_subscriber, $user, $plan_id;

    public function setUp(): void {
        parent::setUp();

        $this->user = Mockery::mock( User::class );
        $this->admin_subscriber = new AdminSubscriber( $this->user );

		$this->stubTranslationFunctions();
		$this->stubEscapeFunctions();
    }

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnAsExpected( $config, $expected ) {
        $this->user->plan_id = $config['plan_id'];

        Functions\when( 'imagify_get_external_url' )->justReturn( 'https://example.org' );
        Functions\when( 'get_imagify_admin_url' )->justReturn( 'https://example.org' );

        $plugin_action_links = $this->admin_subscriber->plugin_action_links([]);
        $plugin_action_links = implode( '|', $plugin_action_links );

        foreach ( $expected as $text ) {
            $this->assertStringContainsString( $text, $plugin_action_links );
        }
	}
}
