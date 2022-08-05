<?php

namespace Imagify\CLI;

use Imagify\Bulk\Bulk;

class GenerateMissingWebpCommand extends AbstractCommand {
	/**
	 * {@inheritdoc}
	 */
	public function __invoke( $arguments, $options ) {
		Bulk::get_instance()->run_generate_webp( $arguments );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_command_name() {
		return 'generate-missing-webp';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description() {
		return 'Run the generation of the missing WebP versions';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_synopsis() {
		return [
			[
				'type'        => 'positional',
				'name'        => 'contexts',
				'description' => 'The context(s) to run the missing WebP generation for',
				'optional'    => false,
				'repeating'   => true,
			],
		];
	}
}
