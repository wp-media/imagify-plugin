<?php

namespace functions\Attachments;

use Imagify\Tests\Unit\TestCase;
use Brain\Monkey\{Filters, Functions};
use Imagify_DB;
use Mockery;
use wpdb;

class Test_magifyAttachmentMetadata extends TestCase {
	private $attachment_id;

	public function setUp(): void {
		parent::setUp();
		global $wpdb;

		$this->wpdb = $this->getMockBuilder('wpdb')
			->disableOriginalConstructor()
			->getMock();
		$this->wpdb->posts = 'wp_posts';
		$wpdb = $this->wpdb;

		$imagify_db   = Mockery::mock( Imagify_DB::class );

		$this->mime_types = "'image/jpeg', 'image/png'";
		$this->statuses = "'inherit', 'private'";
		$this->exist_clause = "";

		$imagify_db->shouldReceive('get_mime_types')
			->andReturn($this->mime_types);
		$imagify_db->shouldReceive('get_post_statuses')
			->andReturn($this->statuses);
		$imagify_db->shouldReceive('get_required_wp_metadata_exist_clause')
			->andReturn($this->exist_clause);

		require_once IMAGIFY_PLUGIN_ROOT . 'inc/functions/attachments.php';
	}



	public function testShouldReturnSanitizedKey()
	{
		$this->wpdb->expects($this->once())
			->method('get_var')
			->willReturn(1);

		$result = imagify_has_attachments_without_required_metadata();
		$this->assertTrue($result);
	}
}
