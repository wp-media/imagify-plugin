<?php
/**
 * Stub for imagify_nextgen_images_formats() in unit tests.
 *
 * Returns a minimal non-empty formats array so that tests can assert a
 * non-empty $formats argument is forwarded to run_generate_nextgen().
 *
 * The real implementation lives in inc/common/attachments.php, which cannot
 * be loaded in unit tests because it registers WordPress hooks and calls
 * WordPress functions unavailable in the unit test environment.
 *
 * @return array
 */
function imagify_nextgen_images_formats(): array {
	return [ 'webp' => 'webp' ];
}
