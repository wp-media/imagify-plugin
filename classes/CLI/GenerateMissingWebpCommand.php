<?php
declare(strict_types=1);

namespace Imagify\CLI;

use Imagify\Bulk\Bulk;

class GenerateMissingWebpCommand extends AbstractCommand {
	/**
	 * {@inheritdoc}
	 */
	public function __invoke( $arguments, $options ) {
		Bulk::get_instance()->run_generate_webp( $arguments );

		\WP_CLI::log( 'Imagify missing WebP generation triggered.' );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_command_name(): string {
		return 'generate-missing-webp';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return 'Run the generation of the missing WebP versions';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_synopsis(): array {
		return [
			[
				'type'        => 'positional',
				'name'        => 'contexts',
				'description' => 'The context(s) to run the missing WebP generation for. Possible values are wp and custom-folders.',
				'optional'    => false,
				'repeating'   => true,
			],
		];
	}
}
