<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\inc\functions;

use Imagify\Tests\Integration\TestCase;

/**
 * Tests for imagify_get_external_url().
 *
 * @covers ::imagify_get_external_url
 *
 * @group  Functions
 */
class Test_ImagifyGetExternalUrl extends TestCase {
	/**
	 * Whether to use the Imagify API in these tests.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * The next-gen delivery documentation target resolves to the broken-images article.
	 *
	 * That article is what the settings page links to when explaining the layout
	 * risk of the <picture> tag method, so the slug must not drift silently.
	 */
	public function testShouldReturnTheNextGenDeliveryDocumentationUrl() {
		$this->assertSame(
			IMAGIFY_SITE_DOMAIN . '/documentation/my-images-are-broken/',
			imagify_get_external_url( 'documentation-nextgen-delivery' )
		);
	}

	/**
	 * An unknown target still returns an empty string.
	 */
	public function testShouldReturnAnEmptyStringForAnUnknownTarget() {
		$this->assertSame( '', imagify_get_external_url( 'no-such-target' ) );
	}
}
