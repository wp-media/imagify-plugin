<?php

declare( strict_types=1 );

namespace Imagify\Tests\Unit\Inc\ThirdParty\PerfectImages\Classes;

use Imagify\Media\WP;
use Imagify\ThirdParty\PerfectImages\PerfectImages;
use Mockery;
use PHPUnit\Framework\TestCase;

class PerfectImagesTest extends TestCase {

	/**
	 * @test
	 * @group 3rd-party
	 * @group PerfectImages
	 */
	public function shouldAddRetinaSizesToMediaFilesArray()
	{
		$perfectImages = PerfectImages::get_instance();
		$media = Mockery::Mock( WP::class );

		$this->assertEquals([], $perfectImages->add_2x_images_to_sizes_for_optimization( [], $media ) );
	}
}
