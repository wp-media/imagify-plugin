<?php
declare( strict_types=1 );

namespace Imagify\Tests\Integration\classes\WriteFile\IISSelfHealSubscriber;

use Imagify\Tests\Integration\TestCase;
use Imagify\WriteFile\IISSelfHealSubscriber;

/**
 * Integration tests for \Imagify\WriteFile\IISSelfHealSubscriber::maybe_self_heal_static_content(),
 * the issue #509 self-heal migration: collapse duplicate Imagify-created <staticContent>
 * siblings on upgrade.
 *
 * @covers \Imagify\WriteFile\IISSelfHealSubscriber::maybe_self_heal_static_content
 * @group  WriteFile
 * @group  IIS
 * @group  Upgrader
 */
class Test_MaybeSelfHealStaticContent extends TestCase {
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
	 * The broken state: two Imagify-created <staticContent> siblings (webp + avif),
	 * optionally preceded by a foreign one.
	 *
	 * @param bool $with_foreign_block Whether to also seed a foreign <staticContent>.
	 * @return string
	 */
	private function brokenState( bool $with_foreign_block = false ): string {
		$foreign = $with_foreign_block
			? '<staticContent><mimeMap fileExtension=".foo" mimeType="image/foo" /></staticContent>'
			: '';

		return '<configuration><system.webServer>'
			. $foreign
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

	private function runSelfHeal() {
		( new IISSelfHealSubscriber() )->maybe_self_heal_static_content( '2.3.3', '2.3.3' );
	}

	/**
	 * @dataProvider brokenStateProvider
	 */
	public function testShouldCollapseDuplicateStaticContentIntoOne( bool $with_foreign_block ) {
		$GLOBALS['is_iis7'] = true;
		update_imagify_option( 'display_nextgen', 1 );

		$this->seed( $this->brokenState( $with_foreign_block ) );

		$this->runSelfHeal();

		// Both formats live inside ONE shared collection (verifies non-XOR gating).
		$this->assertSame( 1, $this->staticContentCount() );
		$this->assertSame( 1, $this->mimeMapCount( '.webp' ) );
		$this->assertSame( 1, $this->mimeMapCount( '.avif' ) );

		if ( $with_foreign_block ) {
			$this->assertSame( 1, $this->mimeMapCount( '.foo' ) );
		}
	}

	public function brokenStateProvider(): array {
		return [
			'no foreign block'      => [ false ],
			'with a foreign block'  => [ true ],
		];
	}

	public function testShouldOnlyRemoveWhenDisplayNextgenIsOff() {
		$GLOBALS['is_iis7'] = true;
		update_imagify_option( 'display_nextgen', 0 );

		$this->seed( $this->brokenState() );

		$this->runSelfHeal();

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

		$this->runSelfHeal();

		// Untouched: the IIS-instance guard means the body never ran.
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
		$this->runSelfHeal();

		remove_filter( 'imagify_disable_dir_conf_edition', $disable );

		// is_file_writable() returned WP_Error, so the file was left untouched.
		$this->assertSame( $broken, file_get_contents( $this->config_path ) );
	}
}
