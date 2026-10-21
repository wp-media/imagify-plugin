<?php
declare( strict_types=1 );

namespace Imagify\Tests\Integration\classes\WriteFile\AbstractIISDirConfFile;

use Imagify\Avif\IIS as AvifIIS;
use Imagify\Tests\Integration\TestCase;
use Imagify\Webp\IIS as WebpIIS;

/**
 * Integration tests for the remove-then-add sequence \Imagify\WriteFile\IISSelfHealSubscriber
 * relies on: \Imagify\WriteFile\AbstractIISDirConfFile::add()/remove() must degrade to a
 * \WP_Error, never a fatal, when a write fails mid-sequence.
 *
 * @covers \Imagify\WriteFile\AbstractIISDirConfFile::insert_contents
 * @group  WriteFile
 * @group  IIS
 */
class Test_RemoveThenAddSequence extends TestCase {
	protected $useApi = false;

	/**
	 * Absolute path to the temporary web.config file under test.
	 *
	 * @var string
	 */
	private $config_path;

	public function set_up() {
		parent::set_up();

		if ( ! function_exists( 'saveDomDocument' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}

		$this->config_path = wp_tempnam( 'imagify-web-config' );

		add_filter( 'imagify_dir_conf_path', [ $this, 'filter_conf_path' ] );
	}

	public function tear_down() {
		remove_filter( 'imagify_dir_conf_path', [ $this, 'filter_conf_path' ] );

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

	private function seed( string $xml ) {
		file_put_contents( $this->config_path, $xml );
	}

	private function xpath(): \DOMXPath {
		$doc                     = new \DOMDocument();
		$doc->preserveWhiteSpace = false;
		$doc->load( $this->config_path );

		return new \DOMXPath( $doc );
	}

	private function staticContentCount(): int {
		return $this->xpath()->query( '/configuration/system.webServer/staticContent' )->length;
	}

	private function mimeMapCount( string $extension ): int {
		return $this->xpath()->query( "//staticContent/mimeMap[@fileExtension='" . $extension . "']" )->length;
	}

	public function testPartialFailureLeavesValidDegradedStateWithoutFatal() {
		$this->seed(
			'<configuration><system.webServer>'
			. '<staticContent name="Imagify: webp file type 1"><mimeMap fileExtension=".webp" mimeType="image/webp" /></staticContent>'
			. '<staticContent name="Imagify: avif file type 1"><mimeMap fileExtension=".avif" mimeType="image/avif" /></staticContent>'
			. '</system.webServer></configuration>'
		);

		// Reproduce the remove-then-add sequence IISSelfHealSubscriber runs, forcing the
		// AVIF add() to fail mid-sequence (permission/lock) via the edition-disabled filter.
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
