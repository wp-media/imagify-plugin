<?php
declare( strict_types=1 );

namespace Imagify\Tests\Integration\classes\WriteFile\AbstractIISDirConfFile;

use Imagify\Avif\IIS as AvifIIS;
use Imagify\Tests\Integration\TestCase;
use Imagify\Webp\IIS as WebpIIS;

/**
 * Integration tests for the issue #509 self-heal migration in
 * inc/admin/upgrader.php::_imagify_new_upgrade() (the 2.3.1 version block):
 * collapse duplicate Imagify-created <staticContent> siblings on upgrade.
 *
 * @covers ::_imagify_new_upgrade
 * @group  WriteFile
 * @group  IIS
 * @group  Upgrader
 */
class SelfHealMigrationTest extends TestCase {
	protected $useApi = false;

	/**
	 * Absolute path to the temporary web.config file under test.
	 *
	 * @var string
	 */
	private $config_path;

	/**
	 * Previous value of the display_nextgen option, restored on tear down.
	 *
	 * @var mixed
	 */
	private $previous_display_nextgen;

	public function set_up() {
		parent::set_up();

		if ( ! function_exists( 'saveDomDocument' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}

		// The upgrader is only loaded under is_admin(); load it for the migration function.
		if ( ! function_exists( '_imagify_new_upgrade' ) ) {
			require_once IMAGIFY_PLUGIN_ROOT . 'inc/admin/upgrader.php';
		}

		$this->config_path = wp_tempnam( 'imagify-web-config' );

		$this->previous_display_nextgen = get_imagify_option( 'display_nextgen' );

		add_filter( 'imagify_dir_conf_path', [ $this, 'filter_conf_path' ] );
	}

	public function tear_down() {
		remove_filter( 'imagify_dir_conf_path', [ $this, 'filter_conf_path' ] );

		update_imagify_option( 'display_nextgen', $this->previous_display_nextgen );

		unset( $GLOBALS['is_iis7'] );

		if ( $this->config_path && file_exists( $this->config_path ) ) {
			unlink( $this->config_path );
		}

		parent::tear_down();
	}

	/**
	 * Redirect the conf writers to our temp file.
	 *
	 * @return string
	 */
	public function filter_conf_path() {
		return $this->config_path;
	}

	/**
	 * Seed the temp web.config with the given XML string.
	 *
	 * @param string $xml Raw web.config content.
	 */
	private function seed( string $xml ) {
		file_put_contents( $this->config_path, $xml );
	}

	/**
	 * Load the temp web.config into a DOMXPath for assertions.
	 *
	 * @return \DOMXPath
	 */
	private function xpath(): \DOMXPath {
		$doc                     = new \DOMDocument();
		$doc->preserveWhiteSpace = false;
		$doc->load( $this->config_path );

		return new \DOMXPath( $doc );
	}

	/**
	 * The broken state: two Imagify-created <staticContent> siblings (webp + avif).
	 *
	 * @return string
	 */
	private function brokenState(): string {
		return '<configuration><system.webServer>'
			. '<staticContent name="Imagify: webp file type 1"><mimeMap fileExtension=".webp" mimeType="image/webp" /></staticContent>'
			. '<staticContent name="Imagify: avif file type 1"><mimeMap fileExtension=".avif" mimeType="image/avif" /></staticContent>'
			. '</system.webServer></configuration>';
	}

	private function staticContentCount(): int {
		return $this->xpath()->query( '/configuration/system.webServer/staticContent' )->length;
	}

	private function mimeMapCount( string $extension ): int {
		return $this->xpath()->query( "//staticContent/mimeMap[@fileExtension='" . $extension . "']" )->length;
	}

	public function testShouldCollapseDuplicateImagifyStaticContentWithBothFormats() {
		$GLOBALS['is_iis7'] = true;
		update_imagify_option( 'display_nextgen', 1 );

		$this->seed( $this->brokenState() );

		// Sanity: the seeded broken state genuinely has two siblings.
		$this->assertSame( 2, $this->staticContentCount() );

		\_imagify_new_upgrade( '2.3.0', '2.3.0' );

		// Both formats live inside ONE shared collection (verifies non-XOR gating).
		$this->assertSame( 1, $this->staticContentCount() );
		$this->assertSame( 1, $this->mimeMapCount( '.webp' ) );
		$this->assertSame( 1, $this->mimeMapCount( '.avif' ) );
	}

	public function testShouldPreserveForeignStaticContentWhileCollapsingImagifyDuplicates() {
		$GLOBALS['is_iis7'] = true;
		update_imagify_option( 'display_nextgen', 1 );

		$this->seed(
			'<configuration><system.webServer>'
			. '<staticContent><mimeMap fileExtension=".foo" mimeType="image/foo" /></staticContent>'
			. '<staticContent name="Imagify: webp file type 1"><mimeMap fileExtension=".webp" mimeType="image/webp" /></staticContent>'
			. '<staticContent name="Imagify: avif file type 1"><mimeMap fileExtension=".avif" mimeType="image/avif" /></staticContent>'
			. '</system.webServer></configuration>'
		);

		\_imagify_new_upgrade( '2.3.0', '2.3.0' );

		$this->assertSame( 1, $this->staticContentCount() );
		$this->assertSame( 1, $this->mimeMapCount( '.foo' ) );
		$this->assertSame( 1, $this->mimeMapCount( '.webp' ) );
		$this->assertSame( 1, $this->mimeMapCount( '.avif' ) );
	}

	public function testShouldOnlyRemoveWhenDisplayNextgenIsOff() {
		$GLOBALS['is_iis7'] = true;
		update_imagify_option( 'display_nextgen', 0 );

		$this->seed( $this->brokenState() );

		\_imagify_new_upgrade( '2.3.0', '2.3.0' );

		// Imagify blocks removed; no re-add. An empty <staticContent/> left behind is schema-valid.
		$this->assertLessThanOrEqual( 1, $this->staticContentCount() );
		$this->assertSame( 0, $this->mimeMapCount( '.webp' ) );
		$this->assertSame( 0, $this->mimeMapCount( '.avif' ) );
	}

	public function testShouldNotTouchWebConfigOnNonIisServer() {
		unset( $GLOBALS['is_iis7'] );
		update_imagify_option( 'display_nextgen', 1 );

		$broken = $this->brokenState();
		$this->seed( $broken );

		\_imagify_new_upgrade( '2.3.0', '2.3.0' );

		// Untouched: positive-conditional guard means the body never ran.
		$this->assertSame( $broken, file_get_contents( $this->config_path ) );
	}

	public function testShouldSkipGracefullyWhenConfEditionDisabled() {
		$GLOBALS['is_iis7'] = true;
		update_imagify_option( 'display_nextgen', 1 );

		$broken = $this->brokenState();
		$this->seed( $broken );

		$disable = function () {
			return true;
		};
		add_filter( 'imagify_disable_dir_conf_edition', $disable );

		// Must not fatal.
		\_imagify_new_upgrade( '2.3.0', '2.3.0' );

		remove_filter( 'imagify_disable_dir_conf_edition', $disable );

		// is_file_writable() returned WP_Error, so the file was left untouched.
		$this->assertSame( $broken, file_get_contents( $this->config_path ) );
	}

	public function testPartialFailureLeavesValidDegradedStateWithoutFatal() {
		$GLOBALS['is_iis7'] = true;
		update_imagify_option( 'display_nextgen', 1 );

		$this->seed( $this->brokenState() );

		// Manually reproduce the remove-then-add sequence, forcing the AVIF add() to fail
		// mid-sequence (permission/lock) via the edition-disabled filter.
		$webp = new WebpIIS();
		$avif = new AvifIIS();

		$this->assertNotWPError( $webp->remove() );
		$this->assertNotWPError( $avif->remove() );
		$this->assertNotWPError( $webp->add() );

		$disable = function () {
			return true;
		};
		add_filter( 'imagify_disable_dir_conf_edition', $disable );

		$result = $avif->add();

		remove_filter( 'imagify_disable_dir_conf_edition', $disable );

		// Degraded but valid: WP_Error returned (no throw), one staticContent, webp present, avif absent.
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 1, $this->staticContentCount() );
		$this->assertSame( 1, $this->mimeMapCount( '.webp' ) );
		$this->assertSame( 0, $this->mimeMapCount( '.avif' ) );
	}
}
