<?php
declare(strict_types=1);

namespace Imagify\CLI;

use Imagify\Bulk\Bulk;

/**
 * Command class for the bulk optimization
 */
class BulkOptimizeCommand extends AbstractCommand {
	/**
	 * Executes the command.
	 *
	 * @param array $arguments Positional argument.
	 * @param array $options Optional arguments.
	 */
	public function __invoke( $arguments, $options ) {
		$level = 2;

		if ( isset( $options['lossless'] ) ) {
			$level = 0;
		}

		foreach ( $arguments as $context ) {
			Bulk::get_instance()->run_optimize( $context, $level );
		}

		\WP_CLI::log( 'Imagify bulk optimization triggered.' );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_command_name(): string {
		return 'bulk-optimize';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return 'Run the bulk optimization';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_synopsis(): array {
		return [
			[
				'type'        => 'positional',
				'name'        => 'contexts',
				'description' => 'The context(s) to run the bulk optimization for. Possible values are wp and custom-folders.',
				'optional'    => false,
				'repeating'   => true,
			],
			[
				'type'        => 'flag',
				'name'        => 'lossless',
				'description' => 'Use lossless compression.',
				'optional'    => true,
			],
		];
	}
}
