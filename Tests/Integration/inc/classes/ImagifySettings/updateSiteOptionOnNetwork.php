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

	public function set_up() {
		parent::set_up();

		// `add_settings_error()` writes to this global; isolate it from the other tests.
		$GLOBALS['wp_settings_errors'] = [];
	}

	public function tear_down() {
		unset( $_POST['option_page'] );
		unset( $GLOBALS['wp_settings_errors'] );

		return parent::tear_down();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldUpdateNetworkSettings( $config, $expected ) {
		$_POST['option_page'] = $config['option_page'];

		if ( $config['user_can'] ) {
			$this->user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
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

		$this->assertSame( [], get_settings_errors() );
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
				}
			);

			$this->expectExceptionMessage( $expected['die_message'] );
		}

		try {
			Imagify_Settings::get_instance()->update_site_option_on_network();
		} finally {
			$this->assertSame( [], get_settings_errors() );
		}
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
			}
		);

		Functions\when( 'imagify_maybe_redirect' )
			->justReturn();

		Imagify_Settings::get_instance()->update_site_option_on_network();

		foreach ( $config['options'] as $option => $value ) {
			$this->assertSame( $value, get_site_option( $option ) );
		}

		$errors = get_settings_errors();

		$this->assertCount( 1, $errors );
		$this->assertSame( 'general', $errors[0]['setting'] );
		$this->assertSame( 'settings_updated', $errors[0]['code'] );
		$this->assertSame( 'Settings saved.', $errors[0]['message'] );
		$this->assertSame( 'success', $errors[0]['type'] );
	}

	/**
	 * A success notice must not be stacked on top of somebody else's error.
	 *
	 * Core's options.php only queues "Settings saved." when nothing else has
	 * queued a settings error (`if ( ! count( get_settings_errors() ) )`). This
	 * handler replaces options.php on network installs, so it has to make the same
	 * check - otherwise a plugin that reports a real problem during the same save
	 * request gets a contradictory "Settings saved." rendered underneath it.
	 */
	public function testShouldNotAddSuccessNoticeWhenAnotherErrorIsAlreadyQueued() {
		$_POST['option_page'] = 'imagify';

		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$admin   = get_role( 'administrator' );
		$admin->add_cap( 'manage_network_options' );
		wp_set_current_user( $user_id );

		// The nonce is bound to the current user, so it must be created after the switch.
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'imagify-options' );

		// Stand in for another plugin reporting a problem on the same request.
		add_settings_error( 'some_other_plugin', 'went_wrong', 'Something failed.', 'error' );

		add_filter(
			'allowed_options',
			function () {
				return [ 'imagify' => [] ];
			}
		);

		Functions\when( 'imagify_maybe_redirect' )->justReturn();

		Imagify_Settings::get_instance()->update_site_option_on_network();

		$codes = wp_list_pluck( get_settings_errors(), 'code' );

		$this->assertContains( 'went_wrong', $codes, 'The other plugin\'s error must survive.' );
		$this->assertNotContains(
			'settings_updated',
			$codes,
			'A false "Settings saved." must not be queued alongside an existing error.'
		);
	}
}
