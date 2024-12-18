<?php
declare(strict_types=1);

namespace ThirdParty\Plugins ;

use Imagify\Tests\Unit\TestCase;
use Imagify\ThirdParty\Plugins\GravityForms;
use Mockery;
use GFForms;
use Brain\Monkey\Functions;
use Brain\Monkey;

/**
 * Tests for \Imagify\ThirdParty\Plugins\GravityForms.
 *
 * @covers \Imagify\ThirdParty\Plugins\GravityForms::imagify_gf_noconflict_styles
 * @covers \Imagify\ThirdParty\Plugins\GravityForms::imagify_gf_noconflict_scripts
 *
 * @group  ThirdParty
 */
class Test_GravityForms extends TestCase{

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnAsExpected( $config, $expected ) {
		Functions\when( 'is_plugin_active' )->justReturn( $config[ 'is_plugin_active' ] );
		Functions\when( 'get_option' )->justReturn( $config[ 'is_plugin_active' ] );

		$gf_forms = Mockery::mock('overload:' . GFForms::class);
		$gf_forms->expects()->is_gravity_page()->andReturn( $config[ 'is_plugin_active' ] );

		$gravity_forms = new GravityForms();

		$styles = apply_filters( 'gform_noconflict_styles', [] );
		$styles = $gravity_forms->imagify_gf_noconflict_styles( $styles );
		foreach ( $expected['styles'] as $style ) {
			$this->assertContains( $style, $styles );
		}

		$scripts = apply_filters( 'gform_noconflict_scripts', [] );
		$scripts = $gravity_forms->imagify_gf_noconflict_scripts( $scripts );
		foreach ( $expected['scripts'] as $script ) {
			$this->assertContains( $script, $scripts);
		}
	}
}
