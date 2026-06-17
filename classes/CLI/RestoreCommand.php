<?php
declare(strict_types=1);

namespace Imagify\CLI;

use Imagify\Bulk\Bulk;

/**
 * Command class for the bulk restore.
 *
 * Restores all optimized media back to their original state.
 *
 * ## EXAMPLES
 *
 *     # Restore all optimized images (library + custom folders).
 *     $ wp imagify restore
 *
 *     # Restore only WordPress media library images.
 *     $ wp imagify restore library
 *
 *     # Restore only custom folders images.
 *     $ wp imagify restore custom-folders
 *
 *     # Restore both contexts explicitly.
 *     $ wp imagify restore library custom-folders
 *
 * @since 2.3
 */
class RestoreCommand extends AbstractCommand {

	/**
	 * Map of user-friendly context names to internal context identifiers.
	 *
	 * @var array<string, string>
	 */
	private const CONTEXT_MAP = [
		'library'        => 'wp',
		'custom-folders' => 'custom-folders',
	];

	/**
	 * Executes the command.
	 *
	 * @param array $arguments Positional argument.
	 * @param array $options   Optional arguments.
	 */
	public function __invoke( $arguments, $options ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( empty( $arguments ) ) {
			$arguments = array_keys( self::CONTEXT_MAP );
		}

		$contexts = [];

		foreach ( $arguments as $arg ) {
			if ( ! isset( self::CONTEXT_MAP[ $arg ] ) ) {
				\WP_CLI::error(
					sprintf(
						'Invalid context: "%s". Valid values are: %s',
						$arg,
						implode( ', ', array_keys( self::CONTEXT_MAP ) )
					)
				);
				return;
			}

			$contexts[] = self::CONTEXT_MAP[ $arg ];
		}

		$total_restored = 0;
		$total_errors   = 0;

		foreach ( $contexts as $index => $context ) {
			$label = $arguments[ $index ];

			\WP_CLI::log( sprintf( 'Restoring optimized media for: %s', $label ) );

			$result = Bulk::get_instance()->run_restore( $context );

			if ( ! $result['success'] ) {
				\WP_CLI::warning( sprintf( 'No optimized media to restore for: %s', $label ) );
				continue;
			}

			$total_restored += $result['restored'];
			$total_errors   += $result['errors'];

			\WP_CLI::log(
				sprintf(
					'%s: %d restored, %d errors out of %d total.',
					ucfirst( $label ),
					$result['restored'],
					$result['errors'],
					$result['total']
				)
			);
		}

		if ( 0 === $total_restored && 0 === $total_errors ) {
			\WP_CLI::warning( 'No optimized media found to restore.' );
			return;
		}

		if ( $total_errors > 0 ) {
			\WP_CLI::warning(
				sprintf(
					'Restore completed with errors: %d restored, %d failed.',
					$total_restored,
					$total_errors
				)
			);
			return;
		}

		\WP_CLI::success(
			sprintf(
				'Restore completed: %d media restored successfully.',
				$total_restored
			)
		);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_command_name(): string {
		return 'restore';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return 'Restore all optimized media to their original state';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_synopsis(): array {
		return [
			[
				'type'        => 'positional',
				'name'        => 'contexts',
				'description' => 'The context(s) to restore. Possible values are library and custom-folders. Defaults to all if omitted.',
				'optional'    => true,
				'repeating'   => true,
			],
		];
	}
}
