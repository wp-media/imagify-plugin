<?php

namespace Imagify\CLI;

abstract class AbstractCommand implements CommandInterface {
	/**
	 * {@inheritdoc}
	 */
	final public function get_name() {
		return sprintf( 'imagify %s', $this->get_command_name() );
	}

	/**
	 * Get the "imagify" command name.
	 *
	 * @return string
	 */
	abstract protected function get_command_name();

	/**
	 * {@inheritdoc}
	 */
	public function get_synopsis() {
		return [];
	}
}
