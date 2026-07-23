<?php
declare( strict_types=1 );

namespace Imagify\Tests\Integration\classes\WriteFile\AbstractIISDirConfFile;

use Imagify\Avif\RewriteRules\IIS as AvifRewriteIIS;
use Imagify\Tests\Integration\TestCase;
use Imagify\Webp\RewriteRules\IIS as WebpRewriteIIS;

/**
 * Integration tests for the <preConditions> singleton handling in IIS rewrite rules.
 *
 * IIS allows exactly one <preConditions> collection under
 * /configuration/system.webServer/rewrite/outboundRules. Both the WebP and AVIF
 * rewrite-rules writers emit their own <preCondition> (IsWebp / IsAvif); they must
 * land in a single shared container and a remove() of one format must not wipe the
 * other (issue #1180).
 *
 * @covers \Imagify\WriteFile\AbstractIISDirConfFile::insert_contents
 * @covers \Imagify\Webp\RewriteRules\IIS::get_raw_new_contents
 * @covers \Imagify\Avif\RewriteRules\IIS::get_raw_new_contents
 * @group  WriteFile
 * @group  IIS
 */
class RewriteRulesPreConditionsTest extends TestCase {
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
	 * Assert the number of <preConditions> collections under outboundRules.
	 *
	 * @param int $expected Expected count.
	 */
	private function assertPreConditionsCount( int $expected ) {
		$this->assertSame(
			$expected,
			$this->xpath()->query( '/configuration/system.webServer/rewrite/outboundRules/preConditions' )->length,
			'Unexpected number of <preConditions> collections.'
		);
	}

	/**
	 * Assert the number of <preCondition> entries for a given name.
	 *
	 * @param string $name     preCondition name (IsWebp / IsAvif).
	 * @param int    $expected Expected count.
	 */
	private function assertPreConditionCount( string $name, int $expected ) {
		$this->assertSame(
			$expected,
			$this->xpath()->query( "/configuration/system.webServer/rewrite/outboundRules/preConditions/preCondition[@name='" . $name . "']" )->length,
			"Unexpected number of {$name} preCondition entries."
		);
	}

	/**
	 * Both formats added must share a single <preConditions> with one entry each.
	 */
	public function testBothFormatsShareSinglePreConditionsContainer() {
		$this->seed( '<configuration><system.webServer><rewrite><rules/><outboundRules/></rewrite></system.webServer></configuration>' );

		( new WebpRewriteIIS() )->add();
		( new AvifRewriteIIS() )->add();

		$this->assertPreConditionsCount( 1 );
		$this->assertPreConditionCount( 'IsWebp', 1 );
		$this->assertPreConditionCount( 'IsAvif', 1 );
	}

	/**
	 * Adding the same format twice must stay idempotent (one entry, one container).
	 */
	public function testDoubleAddIsIdempotent() {
		$this->seed( '<configuration><system.webServer><rewrite><rules/><outboundRules/></rewrite></system.webServer></configuration>' );

		$webp = new WebpRewriteIIS();
		$webp->add();
		$webp->add();

		$this->assertPreConditionsCount( 1 );
		$this->assertPreConditionCount( 'IsWebp', 1 );
	}

	/**
	 * Removing WebP must keep the AVIF preCondition and its container.
	 */
	public function testRemoveWebpKeepsAvifPreCondition() {
		$this->seed( '<configuration><system.webServer><rewrite><rules/><outboundRules/></rewrite></system.webServer></configuration>' );

		( new WebpRewriteIIS() )->add();
		( new AvifRewriteIIS() )->add();
		( new WebpRewriteIIS() )->remove();

		$this->assertPreConditionsCount( 1 );
		$this->assertPreConditionCount( 'IsWebp', 0 );
		$this->assertPreConditionCount( 'IsAvif', 1 );
	}

	/**
	 * Removing AVIF must keep the WebP preCondition and its container.
	 */
	public function testRemoveAvifKeepsWebpPreCondition() {
		$this->seed( '<configuration><system.webServer><rewrite><rules/><outboundRules/></rewrite></system.webServer></configuration>' );

		( new WebpRewriteIIS() )->add();
		( new AvifRewriteIIS() )->add();
		( new AvifRewriteIIS() )->remove();

		$this->assertPreConditionsCount( 1 );
		$this->assertPreConditionCount( 'IsAvif', 0 );
		$this->assertPreConditionCount( 'IsWebp', 1 );
	}

	/**
	 * Removing the last preCondition must drop the now-empty container entirely.
	 */
	public function testRemoveLastPreConditionDropsEmptyContainer() {
		$this->seed( '<configuration><system.webServer><rewrite><rules/><outboundRules/></rewrite></system.webServer></configuration>' );

		( new WebpRewriteIIS() )->add();
		( new AvifRewriteIIS() )->add();
		( new WebpRewriteIIS() )->remove();
		( new AvifRewriteIIS() )->remove();

		$this->assertPreConditionsCount( 0 );
		$this->assertPreConditionCount( 'IsWebp', 0 );
		$this->assertPreConditionCount( 'IsAvif', 0 );
	}
}
