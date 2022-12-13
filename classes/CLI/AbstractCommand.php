<?php
declare(strict_types=1);

namespace Imagify\CLI;

/**
 * Abstrat class for CLI Command
 */
abstract class AbstractCommand implements CommandInterface {
	/**
	 * {@inheritdoc}
	 */
	final public function get_name(): string {
		return sprintf( 'imagify %s', $this->get_command_name() );
	}

	/**
	 * Get the "imagify" command name.
	 *
	 * @return string
	 */
	abstract protected function get_command_name(): string;

	/**
	 * {@inheritdoc}
	 */
	public function get_synopsis(): array {
		return [];
	}
}
