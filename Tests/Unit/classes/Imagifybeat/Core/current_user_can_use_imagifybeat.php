<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Imagifybeat\Core;

use Imagify\Imagifybeat\Core;
use Imagify\Tests\Unit\classes\Imagifybeat\Stubs\ImagifybeatMocksTrait;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Imagifybeat\Core::current_user_can_use_imagifybeat().
 *
 * @covers \Imagify\Imagifybeat\Core::current_user_can_use_imagifybeat
 * @group  Imagifybeat
 */
class Test_CurrentUserCanUseImagifybeat extends TestCase {
	use ImagifybeatMocksTrait;

	/**
	 * Test: the user can use Imagifybeat with any of the capabilities of the screens that poll it.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The allowed "context:describer" pairs.
	 * @param bool  $expected Whether the user can use Imagifybeat.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->mockContexts( $config['allowed'] );

		$this->assertSame( $expected, Core::get_instance()->current_user_can_use_imagifybeat() );
	}
}
