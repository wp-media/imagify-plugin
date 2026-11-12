<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Imagifybeat\Stubs;

use Imagify\Bulk\BulkInterface;
use Imagify\Bulk\Noop;

/**
 * Stub for the Bulk singleton: records the contexts it is asked for.
 */
class BulkStub {
	/**
	 * Contexts passed to get_bulk_instance(), in order.
	 *
	 * @var string[]
	 */
	public $contexts = [];

	/**
	 * Get a bulk instance that returns the context name as its data, and 2 media without next-gen version.
	 *
	 * @param  string $context The context name.
	 * @return BulkInterface
	 */
	public function get_bulk_instance( string $context ): BulkInterface {
		$this->contexts[] = $context;

		return new class( $context ) extends Noop {
			/**
			 * Context name.
			 *
			 * @var string
			 */
			private $context;

			/**
			 * Constructor.
			 *
			 * @param string $context Context name.
			 */
			public function __construct( string $context ) {
				$this->context = $context;
			}

			/**
			 * {@inheritdoc}
			 */
			public function get_context_data(): array {
				return [ 'context' => $this->context ];
			}

			/**
			 * {@inheritdoc}
			 */
			public function get_optimized_media_ids_without_format( $format ) {
				return [
					'ids'    => [ 1, 2 ],
					'errors' => [],
				];
			}
		};
	}
}
