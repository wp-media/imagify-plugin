<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\inc\classes\ImagifySettings;

use Imagify_Settings;
use Imagify\Tests\Integration\TestCase;
use Brain\Monkey\Functions;

/**
 * @covers Imagify_Settings::update_site_option_on_network
 *
 * @uses   imagify_check_nonce()
 * @uses   imagify_die()
 * @uses   imagify_maybe_redirect()
 *
 * @group  ImagifySettings
 */
class Test_UpdateSiteOptionOnNetwork extends TestCase {
	private $user_id;

	public function tear_down() {
		unset( $_POST['option_page'] );

		return parent::tear_down();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldUpdateNetworkSettings( $config, $expected ) {
		$_POST['option_page'] = $config['option_page'];

		if ( $config['user_can'] ) {
			$this->user_id = $this->factory->user->create( [ 'role' => 'administrator', ] );
			$admin         = get_role( 'administrator' );
			$admin->add_cap( 'manage_network_options' );
		} else {
			$this->user_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		}

		wp_set_current_user( $this->user_id );

		if ( empty( $config['option_page'] )
			 || 'imagify' !== $config['option_page']
		) {
			$this->shouldBailOut();
		} elseif ( $config['missing_options']
				   || ! $config['user_can']
				   || ! $config['nonce_check']
		) {
			$this->shouldDie( $config, $expected );
		} else {
			$this->shouldUpdateOptions( $config, $expected );
		}
	}

	public function shouldBailOut() {
		Functions\expect( 'apply_filters' )->never();

		Imagify_Settings::get_instance()->update_site_option_on_network();
	}

	public function shouldDie( $config, $expected ) {
		if ( ! $config['user_can'] ) {
			$this->expectException( 'WPDieException' );
		}

		if ( $config['nonce_check'] ) {
			$_REQUEST['_wpnonce'] = wp_create_nonce( 'imagify-options' );
		} else {
			$this->expectException( 'WPDieException' );
		}

		if ( $config['missing_options'] ) {
			add_filter(
				'allowed_options',
				function () {
					return [];
				} );

			$this->expectExceptionMessage( $expected['die_message'] );
		}

		Imagify_Settings::get_instance()->update_site_option_on_network();
	}

	public function shouldUpdateOptions( $config, $expected ) {
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'imagify-options' );
		$options              = [];

		foreach ( $config['options'] as $option => $value ) {
			$options[]        = $option;
			$_POST[ $option ] = $value;
		}

		add_filter(
			'allowed_options',
			function () use ( $config, $options ) {
				$settings['imagify'] = $options;

				return $settings;
			} );

		Functions\when( 'imagify_maybe_redirect' )
			->justReturn();

		Imagify_Settings::get_instance()->update_site_option_on_network();

		foreach ( $config['options'] as $option => $value ) {
			$this->assertSame( $value, get_site_option( $option ) );
		}
	}
}
