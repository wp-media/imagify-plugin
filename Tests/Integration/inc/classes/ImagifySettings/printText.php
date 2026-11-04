<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\inc\classes\ImagifySettings;

use Imagify_Settings;
use Imagify\Tests\Integration\TestCase;

/**
 * @covers Imagify_Settings::print_text
 *
 * @group  ImagifySettings
 */
class Test_PrintText extends TestCase {

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldPrintTheExpectedMarkup( $config, $expected, $expected_html_api = null ) {
		if ( null !== $expected_html_api && $this->kses_uses_html_api() ) {
			$expected = $expected_html_api;
		}

		ob_start();
		Imagify_Settings::print_text( $config['text'] );
		$output = ob_get_clean();

		$this->assertSame( $expected, $output );
	}

	/**
	 * Whether wp_kses() runs on the HTML API implementation (WordPress 7.2+).
	 *
	 * Mirrors the check in wp_kses(): the new implementation serializes spec-compliant
	 * HTML, so some outputs differ from the legacy parser while staying equally safe.
	 *
	 * @return bool
	 */
	private function kses_uses_html_api(): bool {
		return function_exists( 'wp_sanitize_html_kses' )
			&& ! apply_filters( 'wp_kses_force_legacy_parser', true );
	}
}
