<?php
declare(strict_types=1);

namespace Imagify\CLI;

use Imagify\Bulk\Bulk;

/**
 * Command class for bulk restoration of original images.
 */
class BulkRestoreCommand extends AbstractCommand {
	/**
	 * Executes the command.
	 *
	 * @param array $arguments Positional arguments (contexts).
	 * @param array $options   Optional arguments.
	 */
	public function __invoke( $arguments, $options ) {
		$bulk    = Bulk::get_instance();
		$success = 0;
		$errors  = 0;

		foreach ( $arguments as $context ) {
			$media_ids = $bulk->get_bulk_instance( $context )->get_optimized_media_ids();

			if ( empty( $media_ids ) ) {
				\WP_CLI::log( sprintf( 'No restorable images found for context "%s".', $context ) );
				continue;
			}

			$total    = count( $media_ids );
			$progress = \WP_CLI\Utils\make_progress_bar(
				sprintf( 'Restoring %d image(s) for context "%s"', $total, $context ),
				$total
			);

			foreach ( $media_ids as $media_id ) {
				$result = imagify_get_optimization_process( $media_id, $context )->restore();

				if ( is_wp_error( $result ) ) {
					++$errors;
					\WP_CLI::warning( sprintf( 'Failed to restore #%d: %s', $media_id, $result->get_error_message() ) );
				} else {
					++$success;
				}

				$progress->tick();
			}

			$progress->finish();
		}

		if ( 0 === $success && 0 === $errors ) {
			\WP_CLI::log( 'Nothing to restore.' );
			return;
		}

		if ( $errors > 0 ) {
			\WP_CLI::warning( sprintf( 'Restore completed: %d succeeded, %d failed.', $success, $errors ) );
		} else {
			\WP_CLI::success( sprintf( '%d image(s) successfully restored.', $success ) );
		}
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_command_name(): string {
		return 'bulk-restore';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return 'Restore original images from their backup files.';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_synopsis(): array {
		return [
			[
				'type'        => 'positional',
				'name'        => 'contexts',
				'description' => 'The context(s) to restore. Possible values are wp and custom-folders.',
				'optional'    => false,
				'repeating'   => true,
			],
		];
	}
}
