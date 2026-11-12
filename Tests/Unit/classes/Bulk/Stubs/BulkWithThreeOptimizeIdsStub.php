<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Bulk\Stubs;

use Imagify\Bulk\BulkInterface;

/**
 * Stub bulk: three eligible media IDs for optimization.
 */
class BulkWithThreeOptimizeIdsStub implements BulkInterface {

	/**
	 * {@inheritdoc}
	 */
	public function get_unoptimized_media_ids( $optimization_level ) {
		return [ 1, 2, 3 ];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_optimized_media_ids(): array {
		return [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_optimized_media_ids_without_format( $format ) {
		return [
			'ids'    => [],
			'errors' => [
				'no_file_path' => [],
				'no_backup'    => [],
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function has_optimized_media_without_nextgen() {
		return 0;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_context_data() {
		return [];
	}
}
