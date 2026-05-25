#!/usr/bin/env bash
# Seed the local wp-env with the minimal state needed for Imagify E2E tests.
#
# Idempotent: safe to re-run; conflicts are silently ignored.
#
# What it does:
#   1. Sets the Imagify API key from IMAGIFY_TESTS_API_KEY env var (if set).
#   2. Uploads a small test JPEG to the media library so optimization tests
#      have something to act on.

set -euo pipefail

wp() { npx --yes @wordpress/env run cli wp "$@"; }

echo "  • Configuring Imagify options..."
wp option update imagify_settings '{"api_key":""}' --format=json >/dev/null 2>&1 || true

if [[ -n "${IMAGIFY_TESTS_API_KEY:-}" ]]; then
	echo "  • Setting API key from IMAGIFY_TESTS_API_KEY..."
	wp eval "
		\$settings = get_option( 'imagify_settings', [] );
		\$settings['api_key'] = '${IMAGIFY_TESTS_API_KEY}';
		update_option( 'imagify_settings', \$settings );
	" >/dev/null
else
	echo "  • IMAGIFY_TESTS_API_KEY not set — skipping API key configuration."
	echo "    Set IMAGIFY_TESTS_API_KEY to enable optimization tests."
fi

echo "  • Uploading a test image to the media library..."
# Download a small public-domain JPEG inside the container then import it.
wp eval '
	$url = "https://picsum.photos/seed/imagify-e2e/400/300";
	$tmp = download_url( $url );
	if ( is_wp_error( $tmp ) ) {
		WP_CLI::warning( "Could not download test image: " . $tmp->get_error_message() );
	} else {
		$file_array = [
			"name"     => "imagify-e2e-test.jpg",
			"tmp_name" => $tmp,
		];
		$id = media_handle_sideload( $file_array, 0, "Imagify E2E test image" );
		if ( is_wp_error( $id ) ) {
			WP_CLI::warning( "Could not import test image: " . $id->get_error_message() );
		} else {
			WP_CLI::log( "Test image imported with ID " . $id );
		}
	}
' 2>/dev/null || echo "  ⚠ Image import step skipped (network not reachable inside container)."

echo "  ✓ Seed complete."
