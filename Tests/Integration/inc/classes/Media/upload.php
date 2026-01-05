<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\inc\classes\Media;

use Imagify\Tests\Integration\TestCase;
use Brain\Monkey\Functions;
use ReflectionClass;

/**
 * @covers \Imagify\Media\Upload\Upload::add_imagify_filter_to_attachments_dropdown
 * @group  Upload
 */
class Upload extends TestCase {
	protected $view_instance;

	public function tear_down() {
		remove_all_filters( 'imagify_display_library_stats' );
		remove_all_filters( 'imagify_count_optimized_attachments' );
		remove_all_filters( 'imagify_count_unoptimized_attachments' );
		remove_all_filters( 'imagify_count_error_attachments' );

		if ($this->view_instance) {

		}
		parent::tear_down();
	}

	public function set_up() {

		parent::set_up();
	}

	public function testShouldReturnExpected() {
		$upload = new \Imagify\Media\Upload\Upload();

		add_filter( 'imagify_display_library_stats', '__return_true' );

		$this->mock_imagify_count_functions();
		ob_start();
		$upload->add_imagify_filter_to_attachments_dropdown();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Filter by status', $output );
		$this->assertStringContainsString( '<select id="filter-by-optimization-status"', $output );
		$this->assertStringContainsString( 'selected="selected"', $output );
		$this->assertStringContainsString( 'Errors (2)', $output );
		$this->assertSame( 1, 1 );
	}

	/**
	 * Mock the return values of imagify count functions.
	 *
	 * @return void
	 */
	public function mock_imagify_count_functions() {
		add_filter( 'imagify_count_optimized_attachments', function() {
			return 10;
		} );
		add_filter( 'imagify_count_unoptimized_attachments', function() {
			return 5;
		} );
		add_filter( 'imagify_count_error_attachments', function() {
			return 2;
		} );
	}
}
