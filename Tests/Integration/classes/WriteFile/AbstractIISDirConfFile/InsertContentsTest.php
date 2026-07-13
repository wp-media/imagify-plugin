<?php
declare( strict_types=1 );

namespace Imagify\Tests\Integration\classes\WriteFile\AbstractIISDirConfFile;

use Imagify\Avif\IIS as AvifIIS;
use Imagify\Tests\Integration\TestCase;
use Imagify\Webp\IIS as WebpIIS;

/**
 * Integration tests for \Imagify\WriteFile\AbstractIISDirConfFile::insert_contents()
 * exercised through the real \Imagify\Webp\IIS / \Imagify\Avif\IIS writers and a real
 * temp web.config + DOMDocument.
 *
 * @covers \Imagify\WriteFile\AbstractIISDirConfFile::insert_contents
 * @covers \Imagify\Webp\IIS::get_raw_new_contents
 * @covers \Imagify\Avif\IIS::get_raw_new_contents
 * @group  WriteFile
 * @group  IIS
 */
class InsertContentsTest extends TestCase {
	protected $useApi = false;

	/**
	 * Absolute path to the temporary web.config file under test.
	 *
	 * @var string
	 */
	private $config_path;

	public function set_up() {
		parent::set_up();

		// saveDomDocument() lives in wp-admin/includes/misc.php.
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
	 * Assert the number of <staticContent> collections under system.webServer.
	 *
	 * @param int $expected Expected count.
	 */
	private function assertStaticContentCount( int $expected ) {
		$this->assertSame(
			$expected,
			$this->xpath()->query( '/configuration/system.webServer/staticContent' )->length,
			'Unexpected number of <staticContent> collections.'
		);
	}

	/**
	 * Assert the number of <mimeMap> entries for a given file extension.
	 *
	 * @param string $extension File extension (e.g. ".webp").
	 * @param int    $expected  Expected count.
	 */
	private function assertMimeMapCount( string $extension, int $expected ) {
		$this->assertSame(
			$expected,
			$this->xpath()->query( "//staticContent/mimeMap[@fileExtension='" . $extension . "']" )->length,
			"Unexpected number of {$extension} mimeMap entries."
		);
	}

	public function testShouldCreateSingleStaticContentOnFreshFile() {
		$this->seed( '<configuration><system.webServer/></configuration>' );

		( new WebpIIS() )->add();

		$this->assertStaticContentCount( 1 );
		$this->assertMimeMapCount( '.webp', 1 );
	}

	public function testShouldCreateSystemWebServerAndStaticContentWhenAbsent() {
		$this->seed( '<configuration/>' );

		( new WebpIIS() )->add();

		$this->assertStaticContentCount( 1 );
		$this->assertMimeMapCount( '.webp', 1 );
	}

	public function testShouldMergeIntoForeignStaticContent() {
		$this->seed(
			'<configuration><system.webServer><staticContent><mimeMap fileExtension=".foo" mimeType="image/foo" /></staticContent></system.webServer></configuration>'
		);

		( new WebpIIS() )->add();

		$this->assertStaticContentCount( 1 );
		$this->assertMimeMapCount( '.webp', 1 );
		// Foreign mimeMap preserved.
		$this->assertMimeMapCount( '.foo', 1 );
	}

	public function testShouldKeepSingleStaticContentWithBothWebpAndAvif() {
		$this->seed( '<configuration><system.webServer/></configuration>' );

		( new WebpIIS() )->add();
		( new AvifIIS() )->add();

		$this->assertStaticContentCount( 1 );
		$this->assertMimeMapCount( '.webp', 1 );
		$this->assertMimeMapCount( '.avif', 1 );
	}

	public function testShouldBeIdempotentOnDoubleAdd() {
		$this->seed( '<configuration><system.webServer/></configuration>' );

		$webp = new WebpIIS();
		$webp->add();
		$webp->add();

		$this->assertStaticContentCount( 1 );
		$this->assertMimeMapCount( '.webp', 1 );
	}

	public function testShouldReplaceForeignMimeMapWithSameExtension() {
		// Foreign .webp mimeMap present (Option B accepted tradeoff): replaced, never duplicated.
		$this->seed(
			'<configuration><system.webServer><staticContent><mimeMap fileExtension=".webp" mimeType="image/foreign" /></staticContent></system.webServer></configuration>'
		);

		( new WebpIIS() )->add();

		$this->assertStaticContentCount( 1 );
		$this->assertMimeMapCount( '.webp', 1 );

		// The remaining mimeMap is Imagify's (correct image/webp mime type).
		$node = $this->xpath()->query( "//staticContent/mimeMap[@fileExtension='.webp']" )->item( 0 );
		$this->assertSame( 'image/webp', $node->getAttribute( 'mimeType' ) );
	}

	public function testRemoveAfterMergePreservesForeignBlock() {
		$this->seed(
			'<configuration><system.webServer><staticContent><mimeMap fileExtension=".foo" mimeType="image/foo" /></staticContent></system.webServer></configuration>'
		);

		$webp = new WebpIIS();
		$webp->add();
		$webp->remove();

		// Foreign staticContent + its mimeMap preserved; Imagify mimeMap gone.
		$this->assertStaticContentCount( 1 );
		$this->assertMimeMapCount( '.webp', 0 );
		$this->assertMimeMapCount( '.foo', 1 );
	}

	public function testShouldReturnWpErrorOnMalformedWebConfig() {
		$malformed = '<configuration><system.webServer></configuration>'; // Malformed (unclosed tag).
		$this->seed( $malformed );

		// Buffer libxml parse errors internally so the DOMDocument::load() failure surfaces
		// as a `false` return (the production path) instead of a converted PHPUnit warning.
		$previous = libxml_use_internal_errors( true );

		$result = ( new WebpIIS() )->add();

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$this->assertInstanceOf( \WP_Error::class, $result );
		// File left unchanged.
		$this->assertSame( $malformed, file_get_contents( $this->config_path ) );
	}
}
