<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\inc\classes\ImagifySettings;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Imagify_Settings;
use Imagify\Tests\Unit\TestCase;

/**
 * @covers Imagify_Settings::update_site_option_on_network
 *
 * @group  ImagifySettings
 */
class Test_UpdateSiteOptionOnNetwork extends TestCase {

	public function setUp(): void {
		parent::setUp();

		if ( ! defined( 'IMAGIFY_SLUG' ) ) {
			define( 'IMAGIFY_SLUG', 'imagify' );
		}

		Functions\when( 'imagify_is_active_for_network' )->justReturn( true );

		$this->stubTranslationFunctions();
	}

	public function tearDown(): void {
		unset( $_POST['option_page'] );

		parent::tearDown();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldUpdateNetworkSettings( $config, $expected ) {
		$_POST['option_page'] = $config['option_page'];

		Functions\when( 'current_user_can' )->justReturn( $config['user_can'] );
		Functions\when( 'imagify_check_nonce' )->justReturn( $config['nonce_check'] );

		if ( empty( $config['option_page'] )
			 || 'imagify' !== $config['option_page']
		) {
			$this->shouldBailOut( $config );
		} elseif ( $config['missing_options']
				   || ! $config['user_can']
				   || ! $config['nonce_check']
		) {
			$this->shouldDie( $config );
		} else {
			$this->shouldUpdateOptions( $config, $expected );
		}
	}

	public function shouldBailOut( $config ) {
		if ( 'imagify' !== $config['option_page'] ) {
			Filters\expectApplied( 'option_page_capability_imagify' )->never();
		} else {
			Filters\expectApplied( 'option_page_capability_imagify' )
				->once()
				->andReturn( $config['user_can'] );
		}

		Imagify_Settings::get_instance()->update_site_option_on_network();
	}

	public function shouldDie( $config ) {
		Filters\expectApplied( 'option_page_capability_imagify' )
			->once()
			->andReturn( $config['user_can'] );
		Functions\when( 'imagify_die' )
			->justReturn();
		Functions\when( 'imagify_check_nonce' )
			->justReturn( $config['nonce_check'] );
		Functions\when( 'apply_filters_deprecated' )
			->justReturn();

		if ( $config['missing_options'] ) {
			Filters\expectApplied( 'allowed_options' )
				->andReturn( $config['options'] );
		}

		Imagify_Settings::get_instance()->update_site_option_on_network();
	}

	public function shouldUpdateOptions( $config, $expected ) {
		$options = [];

		foreach ( $config['options'] as $option => $value ) {
			$options[] = $option;
			$_POST[ $option ] = $value;
			Functions\expect( 'update_site_option' )
				->once()
				->with( $option, $value );
		}

		Filters\expectApplied( 'option_page_capability_imagify' )
			->once()
			->andReturn( true );
		Functions\when( 'imagify_check_nonce' )
			->justReturn( true );
		Functions\when( 'apply_filters_deprecated' )
			->justReturn();
		Filters\expectApplied( 'allowed_options' )
			->once()
			->andReturn( [ 'imagify' => $options ] );
		Functions\when('wp_unslash')->returnArg();

		Functions\expect( 'imagify_maybe_redirect' )
			->once()
			->andReturn();

		Imagify_Settings::get_instance()->update_site_option_on_network();
	}
}
