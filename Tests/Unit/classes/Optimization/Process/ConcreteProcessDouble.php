<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Optimization\Process;

use Imagify\Optimization\Process\AbstractProcess;

/**
 * Minimal concrete AbstractProcess subclass used only to instantiate a testable object via
 * ReflectionClass::newInstanceWithoutConstructor() — no logic is added or overridden.
 */
class ConcreteProcessDouble extends AbstractProcess {
	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $id Whatever.
	 */
	public static function constructor_accepts( $id ) {
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_missing_sizes() {
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function optimize_missing_thumbnails() {
		return true;
	}
}
