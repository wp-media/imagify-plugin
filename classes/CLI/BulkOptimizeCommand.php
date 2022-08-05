<?php

namespace Imagify\CLI;

use Imagify\Bulk\Bulk;

class BulkOptimizeCommand extends AbstractCommand {
	/**
	 * {@inheritdoc}
	 */
	public function __invoke( $arguments, $options ) {
		Bulk::get_instance()->run_optimize( $arguments );

		\WP_CLI::log( 'Imagify bulk optimization triggered.' );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_command_name() {
		return 'bulk-optimize';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description() {
		return 'Run the bulk optimization';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_synopsis() {
		return [
			[
				'type'        => 'positional',
				'name'        => 'contexts',
				'description' => 'The context(s) to run the bulk optimization for',
				'optional'    => false,
				'repeating'   => true,
			],
		];
	}
}
