<?php
declare(strict_types=1);

namespace Imagify\Bulk;

/**
 * Interface for the bulk optimizer.
 *
 * Abstracts the `run_optimize()` entry point so that callers (e.g. MCP abilities)
 * can depend on this interface rather than the final `Bulk` class, keeping them
 * independently testable.
 *
 * @since 2.3.0
 */
interface BulkOptimizerInterface {

	/**
	 * Runs the bulk optimization for the given context and optimization level.
	 *
	 * @param string $context            Optimization context: 'wp' or 'custom-folders'.
	 * @param int    $optimization_level Optimization level (0–2).
	 * @return array{success: bool, message: string}
	 */
	public function run_optimize( string $context, int $optimization_level ): array;
}
