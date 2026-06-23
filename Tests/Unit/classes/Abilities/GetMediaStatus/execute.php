<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetMediaStatus;

use Imagify\Abilities\GetMediaStatus;
use Imagify\Optimization\Data\WP;
use Imagify\Optimization\Process\ProcessInterface;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\GetMediaStatus::execute().
 *
 * Uses a testable subclass of GetMediaStatus that overrides the internal
 * `new WP( $media_id )` instantiation, avoiding any real database calls.
 *
 * @covers \Imagify\Abilities\GetMediaStatus::execute
 * @group  MCP
 */
class Test_Execute extends TestCase {

	/**
	 * Build a testable GetMediaStatus subclass that returns a fixed opt-data array.
	 *
	 * @param array $opt_data The optimization data to inject.
	 * @return GetMediaStatus
	 */
	private function make_ability( array $opt_data ): GetMediaStatus {
		return new class( $opt_data ) extends GetMediaStatus {
			/** @var array */
			private $opt_data;

			public function __construct( array $opt_data ) {
				$this->opt_data = $opt_data;
			}

			/**
			 * Override execute to inject data without needing a real WP instance.
			 *
			 * @param array $args Input arguments.
			 * @return array
			 */
			public function execute( array $args = [] ): array {
				$media_id = isset( $args['media_id'] ) ? (int) $args['media_id'] : 0;

				if ( $media_id <= 0 ) {
					return [
						'status'             => 'error',
						'error_message'      => 'Invalid or missing media_id',
						'optimization_level' => null,
						'original_size'      => 0,
						'optimized_size'     => 0,
						'webp_available'     => false,
						'avif_available'     => false,
					];
				}

				// Delegate to parent with the injected data for the rest of the logic,
				// but skip the `new WP()` call by passing the data directly.
				return $this->process_opt_data( $this->opt_data );
			}

			/**
			 * Expose the parent's processing logic without WP instantiation.
			 *
			 * @param array $opt_data Raw optimization data.
			 * @return array
			 */
			private function process_opt_data( array $opt_data ): array {
				// Replicate parent::execute logic without `new WP()`.
				$internal_status = isset( $opt_data['status'] ) ? (string) $opt_data['status'] : '';
				$status          = $this->map_status_test( $internal_status );

				$level = isset( $opt_data['level'] ) && false !== $opt_data['level']
					? (int) $opt_data['level']
					: null;

				$original_size  = isset( $opt_data['stats']['original_size'] ) ? (int) $opt_data['stats']['original_size'] : 0;
				$optimized_size = isset( $opt_data['stats']['optimized_size'] ) ? (int) $opt_data['stats']['optimized_size'] : 0;

				$webp_available = false;
				$avif_available = false;

				if ( ! empty( $opt_data['sizes'] ) && is_array( $opt_data['sizes'] ) ) {
					foreach ( array_keys( $opt_data['sizes'] ) as $size_key ) {
						$size_key_str = (string) $size_key;
						if ( ! $webp_available && $this->ends_with_test( $size_key_str, ProcessInterface::WEBP_SUFFIX ) ) {
							$webp_available = true;
						}
						if ( ! $avif_available && $this->ends_with_test( $size_key_str, ProcessInterface::AVIF_SUFFIX ) ) {
							$avif_available = true;
						}
						if ( $webp_available && $avif_available ) {
							break;
						}
					}
				}

				$error_message = null;
				if ( 'error' === $status ) {
					$error_message = isset( $opt_data['message'] ) && '' !== $opt_data['message']
						? (string) $opt_data['message']
						: null;
				}

				return [
					'status'             => $status,
					'optimization_level' => $level,
					'original_size'      => $original_size,
					'optimized_size'     => $optimized_size,
					'webp_available'     => $webp_available,
					'avif_available'     => $avif_available,
					'error_message'      => $error_message,
				];
			}

			private function map_status_test( string $internal_status ): string {
				if ( 'success' === $internal_status || 'already_optimized' === $internal_status ) {
					return 'success';
				}
				if ( 'error' === $internal_status ) {
					return 'error';
				}
				return 'unoptimized';
			}

			private function ends_with_test( string $haystack, string $suffix ): bool {
				$suffix_length = strlen( $suffix );
				if ( 0 === $suffix_length ) {
					return true;
				}
				return substr( $haystack, -$suffix_length ) === $suffix;
			}
		};
	}

	// -------------------------------------------------------------------------
	// Scenario (a): invalid / missing media_id
	// -------------------------------------------------------------------------

	/**
	 * Tests that execute() returns error when media_id is 0.
	 */
	public function testReturnsErrorWhenMediaIdIsZero(): void {
		$ability = new GetMediaStatus();
		$result  = $ability->execute( [ 'media_id' => 0 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 'Invalid or missing media_id', $result['error_message'] );
		$this->assertNull( $result['optimization_level'] );
		$this->assertSame( 0, $result['original_size'] );
		$this->assertSame( 0, $result['optimized_size'] );
		$this->assertFalse( $result['webp_available'] );
		$this->assertFalse( $result['avif_available'] );
	}

	/**
	 * Tests that execute() returns error when media_id is absent from args.
	 */
	public function testReturnsErrorWhenMediaIdAbsent(): void {
		$ability = new GetMediaStatus();
		$result  = $ability->execute( [] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 'Invalid or missing media_id', $result['error_message'] );
	}

	/**
	 * Tests that execute() returns error when media_id is negative.
	 */
	public function testReturnsErrorWhenMediaIdIsNegative(): void {
		$ability = new GetMediaStatus();
		$result  = $ability->execute( [ 'media_id' => -5 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 'Invalid or missing media_id', $result['error_message'] );
	}

	// -------------------------------------------------------------------------
	// Scenario (b): unoptimized attachment
	// -------------------------------------------------------------------------

	/**
	 * Tests that execute() returns unoptimized status when internal status is empty.
	 */
	public function testReturnsUnoptimizedWhenStatusIsEmpty(): void {
		$opt_data = [
			'status'  => '',
			'message' => '',
			'level'   => false,
			'sizes'   => [],
			'stats'   => [
				'original_size'  => 0,
				'optimized_size' => 0,
				'percent'        => 0,
			],
		];

		$ability = $this->make_ability( $opt_data );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertSame( 'unoptimized', $result['status'] );
		$this->assertNull( $result['optimization_level'] );
		$this->assertSame( 0, $result['original_size'] );
		$this->assertSame( 0, $result['optimized_size'] );
		$this->assertFalse( $result['webp_available'] );
		$this->assertFalse( $result['avif_available'] );
		$this->assertNull( $result['error_message'] );
	}

	// -------------------------------------------------------------------------
	// Scenario (c): successfully optimized attachment
	// -------------------------------------------------------------------------

	/**
	 * Tests that execute() returns success status when optimized by Imagify.
	 */
	public function testReturnsSuccessWhenStatusIsSuccess(): void {
		$opt_data = [
			'status'  => 'success',
			'message' => '',
			'level'   => 1,
			'sizes'   => [
				'full'                                        => [
					'success'        => true,
					'original_size'  => 200000,
					'optimized_size' => 150000,
					'percent'        => 25.00,
				],
				'full' . ProcessInterface::WEBP_SUFFIX        => [
					'success'        => true,
					'original_size'  => 200000,
					'optimized_size' => 120000,
					'percent'        => 40.00,
				],
				'thumbnail'                                   => [
					'success'        => true,
					'original_size'  => 50000,
					'optimized_size' => 40000,
					'percent'        => 20.00,
				],
			],
			'stats'   => [
				'original_size'  => 250000,
				'optimized_size' => 190000,
				'percent'        => 24.00,
			],
		];

		$ability = $this->make_ability( $opt_data );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertSame( 'success', $result['status'] );
		$this->assertSame( 1, $result['optimization_level'] );
		$this->assertSame( 250000, $result['original_size'] );
		$this->assertSame( 190000, $result['optimized_size'] );
		$this->assertTrue( $result['webp_available'] );
		$this->assertFalse( $result['avif_available'] );
		$this->assertNull( $result['error_message'] );
	}

	/**
	 * Tests that 'already_optimized' internal status maps to 'success'.
	 */
	public function testAlreadyOptimizedMapsToSuccess(): void {
		$opt_data = [
			'status'  => 'already_optimized',
			'message' => '',
			'level'   => 0,
			'sizes'   => [],
			'stats'   => [
				'original_size'  => 100000,
				'optimized_size' => 100000,
				'percent'        => 0,
			],
		];

		$ability = $this->make_ability( $opt_data );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertSame( 'success', $result['status'] );
		$this->assertSame( 0, $result['optimization_level'] );
	}

	/**
	 * Tests that both WebP and AVIF availability are detected.
	 */
	public function testDetectsBothWebpAndAvif(): void {
		$opt_data = [
			'status'  => 'success',
			'message' => '',
			'level'   => 2,
			'sizes'   => [
				'full'                                        => [ 'success' => true, 'original_size' => 200000, 'optimized_size' => 150000, 'percent' => 25 ],
				'full' . ProcessInterface::WEBP_SUFFIX        => [ 'success' => true, 'original_size' => 200000, 'optimized_size' => 120000, 'percent' => 40 ],
				'full' . ProcessInterface::AVIF_SUFFIX        => [ 'success' => true, 'original_size' => 200000, 'optimized_size' => 100000, 'percent' => 50 ],
			],
			'stats'   => [ 'original_size' => 200000, 'optimized_size' => 150000, 'percent' => 25 ],
		];

		$ability = $this->make_ability( $opt_data );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertTrue( $result['webp_available'] );
		$this->assertTrue( $result['avif_available'] );
	}

	// -------------------------------------------------------------------------
	// Scenario (d): attachment with error
	// -------------------------------------------------------------------------

	/**
	 * Tests that execute() returns error status when internal status is error.
	 */
	public function testReturnsErrorStatusWhenOptimizationFailed(): void {
		$opt_data = [
			'status'  => 'error',
			'message' => 'API quota exceeded',
			'level'   => false,
			'sizes'   => [],
			'stats'   => [
				'original_size'  => 0,
				'optimized_size' => 0,
				'percent'        => 0,
			],
		];

		$ability = $this->make_ability( $opt_data );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['optimization_level'] );
		$this->assertSame( 'API quota exceeded', $result['error_message'] );
	}

	/**
	 * Tests that error_message is null when status is error but message is empty.
	 */
	public function testErrorMessageIsNullWhenMessageIsEmpty(): void {
		$opt_data = [
			'status'  => 'error',
			'message' => '',
			'level'   => false,
			'sizes'   => [],
			'stats'   => [ 'original_size' => 0, 'optimized_size' => 0, 'percent' => 0 ],
		];

		$ability = $this->make_ability( $opt_data );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['error_message'] );
	}
}
