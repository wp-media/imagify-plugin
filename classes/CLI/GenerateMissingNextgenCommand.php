<?php
declare(strict_types=1);

namespace Imagify\CLI;

use Imagify\Bulk\Bulk;

/**
 * Command class for the missing Nextgen generation
 */
class GenerateMissingNextgenCommand extends AbstractCommand {
	/**
	 * Executes the command.
	 *
	 * @param array $arguments Positional argument.
	 * @param array $options Optional arguments.
	 */
	public function __invoke( $arguments, $options ) {
		Bulk::get_instance()->run_generate_nextgen( $arguments );

		\WP_CLI::log( 'Imagify missing next-gen images generation triggered.' );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_command_name(): string {
		return 'generate-missing-nextgen';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return 'Run the generation of the missing next-gen images versions';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_synopsis(): array {
		return [
			[
				'type'        => 'positional',
				'name'        => 'contexts',
				'description' => 'The context(s) to run the missing next-gen images generation for. Possible values are wp and custom-folders.',
				'optional'    => false,
				'repeating'   => true,
			],
		];
	}
}
