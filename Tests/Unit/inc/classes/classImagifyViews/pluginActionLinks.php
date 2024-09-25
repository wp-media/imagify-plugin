<?php
namespace Imagify\Tests\Unit\inc\classes\classImagifyViews;

use Brain\Monkey\Functions;
use Imagify\Tests\Unit\TestCase;
use Imagify_Views;

/**
 * Tests for \Imagify_Views->plugin_action_links().
 *
 * @covers \Imagify_Views::plugin_action_links
 * @group  ImagifyAPI
 */
class Test_PluginActionLinks extends TestCase {
    protected $imagify_views;
    protected $user_data = [
        'id'                           => 1,
        'email'                        => 'imagify@example.com',
        'plan_id'                      => '2',
        'plan_label'                   => 'free',
        'quota'                        => 456,
        'extra_quota'                  => 0,
        'extra_quota_consumed'         => 0,
        'consumed_current_month_quota' => 123,
        'next_date_update'             => '',
        'is_active'                    => 1,
        'is_monthly'                   => true,
    ];

    public function setUp(): void {
        parent::setUp();
        
        // Create Imagify_Views instance without calling the constructor
        $reflection = new \ReflectionClass(Imagify_Views::class);
        $this->imagify_views = $reflection->newInstanceWithoutConstructor();
    }

	/**
	 * Test \Imagify_Views->plugin_action_links() should return Documentation link if plan label is not starter.
	 */
	public function testShouldReturnDocumentationLinkAmongPluginLinksIfPlanLabelIsNotStarter() {
        Functions\when( 'imagify_get_cached_user' )->justReturn( (object) $this->user_data );
        Functions\when( 'get_imagify_user' )->justReturn( (object) $this->user_data );
        Functions\when( 'imagify_get_external_url' )->justReturn( 'https://example.org' );
        Functions\when( 'get_imagify_admin_url' )->justReturn( 'https://example.org' );

        $plugin_action_links = $this->imagify_views->plugin_action_links([]);
        $plugin_action_links = implode( '|', $plugin_action_links );

        $this->assertStringContainsString( 'Documentation', $plugin_action_links );
	}

	/**
	 * Test \Imagify_Views->plugin_action_links() should return Upgrade link if plan label is starter.
	 */
	public function testShouldReturnUpgradeLinkAmongPluginLinksIfPlanLabelIsStarter() {
        $this->user_data['plan_id'] = '1';

        Functions\when( 'imagify_get_cached_user' )->justReturn( (object) $this->user_data );
        Functions\when( 'get_imagify_user' )->justReturn( (object) $this->user_data );
        Functions\when( 'imagify_get_external_url' )->justReturn( 'https://example.org' );
        Functions\when( 'get_imagify_admin_url' )->justReturn( 'https://example.org' );

        $plugin_action_links = $this->imagify_views->plugin_action_links([]);
        $plugin_action_links = implode( '|', $plugin_action_links );

        $this->assertStringContainsString( 'Upgrade', $plugin_action_links );
        $this->assertStringContainsString( 'class="imagify-plugin-upgrade"', $plugin_action_links );
	}
}
