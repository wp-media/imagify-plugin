<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetNextgenCoverage;

use Brain\Monkey\Functions;
use Imagify\Abilities\GetNextgenCoverage;
use Imagify\Stats\StatInterface;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Abilities\GetNextgenCoverage::execute().
 *
 * @covers \Imagify\Abilities\GetNextgenCoverage::execute
 * @group  MCP
 */
class Test_Execute extends TestCase {

	/**
	 * Tests that execute() returns an array with both expected keys.
	 */
	public function testReturnsArrayWithExpectedKeys(): void {
		$stat = Mockery::mock( StatInterface::class );
		$stat->shouldReceive( 'get_cached_stat' )->once()->andReturn( 0 );

		Functions\when( 'get_imagify_option' )->justReturn( 'webp' );

		$ability = new GetNextgenCoverage( $stat );
		$result  = $ability->execute();

		$this->assertArrayHasKey( 'missing_nextgen_count', $result );
		$this->assertArrayHasKey( 'nextgen_format', $result );
	}

	/**
	 * Tests that execute() returns the correct missing_nextgen_count from the stat service.
	 */
	public function testReturnsMissingNextgenCountFromStat(): void {
		$stat = Mockery::mock( StatInterface::class );
		$stat->shouldReceive( 'get_cached_stat' )->once()->andReturn( 5 );

		Functions\when( 'get_imagify_option' )->justReturn( 'webp' );

		$ability = new GetNextgenCoverage( $stat );
		$result  = $ability->execute();

		$this->assertSame( 5, $result['missing_nextgen_count'] );
	}

	/**
	 * Tests that execute() returns zero when no optimized media is missing next-gen.
	 */
	public function testReturnsZeroMissingCountWhenAllCovered(): void {
		$stat = Mockery::mock( StatInterface::class );
		$stat->shouldReceive( 'get_cached_stat' )->once()->andReturn( 0 );

		Functions\when( 'get_imagify_option' )->justReturn( 'avif' );

		$ability = new GetNextgenCoverage( $stat );
		$result  = $ability->execute();

		$this->assertSame( 0, $result['missing_nextgen_count'] );
	}

	/**
	 * Tests that execute() returns 'webp' when optimization_format is 'webp'.
	 */
	public function testReturnsWebpFormat(): void {
		$stat = Mockery::mock( StatInterface::class );
		$stat->shouldReceive( 'get_cached_stat' )->once()->andReturn( 3 );

		Functions\expect( 'get_imagify_option' )
			->once()
			->with( 'optimization_format' )
			->andReturn( 'webp' );

		$ability = new GetNextgenCoverage( $stat );
		$result  = $ability->execute();

		$this->assertSame( 'webp', $result['nextgen_format'] );
	}

	/**
	 * Tests that execute() returns 'avif' when optimization_format is 'avif'.
	 */
	public function testReturnsAvifFormat(): void {
		$stat = Mockery::mock( StatInterface::class );
		$stat->shouldReceive( 'get_cached_stat' )->once()->andReturn( 2 );

		Functions\expect( 'get_imagify_option' )
			->once()
			->with( 'optimization_format' )
			->andReturn( 'avif' );

		$ability = new GetNextgenCoverage( $stat );
		$result  = $ability->execute();

		$this->assertSame( 'avif', $result['nextgen_format'] );
	}

	/**
	 * Tests that execute() returns 'off' when optimization_format is 'off',
	 * and still returns the missing count as-is (format field signals that
	 * next-gen generation is disabled, but count is still reported).
	 */
	public function testReturnsOffFormatWithNonZeroCount(): void {
		$stat = Mockery::mock( StatInterface::class );
		$stat->shouldReceive( 'get_cached_stat' )->once()->andReturn( 12 );

		Functions\expect( 'get_imagify_option' )
			->once()
			->with( 'optimization_format' )
			->andReturn( 'off' );

		$ability = new GetNextgenCoverage( $stat );
		$result  = $ability->execute();

		$this->assertSame( 'off', $result['nextgen_format'] );
		$this->assertSame( 12, $result['missing_nextgen_count'] );
	}
}
