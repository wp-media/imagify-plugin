<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Bulk\Stubs;

use Imagify\Bulk\BulkInterface;

/**
 * Stub bulk: two eligible media ids, no errors.
 */
class BulkWithIdsStub implements BulkInterface {

	/**
	 * {@inheritdoc}
	 */
	public function get_unoptimized_media_ids( $optimization_level ) {
		return [];
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
			'ids'    => [ 10, 20 ],
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
