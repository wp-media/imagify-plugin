<?php

namespace Imagify\Tests\Integration\inc\classes\AutoOptimization;

use Imagify_Auto_Optimization;
use Imagify\Tests\Integration\TestCase;
use WP_REST_Request;

/**
 * Fires the real WordPress hook chain for a WP 7.1 client side upload and checks when auto
 * optimization runs, and what it can see when it does.
 *
 * The unit tests call the methods directly, which proves they behave as written but not that
 * the sequence WordPress actually produces lands where it should. This drives the genuine
 * chain instead: `wp_generate_attachment_metadata` for the create phase, then the same filter
 * for the finalize phase, then `wp_update_attachment_metadata`, which writes the post meta and
 * therefore fires `added_post_meta` / `updated_post_meta`.
 *
 * Two things matter and neither can be checked from a unit test:
 *   - nothing is optimized on the create phase, when no sub size exists yet,
 *   - the optimization that does run happens after the metadata is stored, so the sizes it
 *     reads are the complete set rather than the value that was about to be replaced.
 *
 * @covers \Imagify_Auto_Optimization::maybe_store_generate_step
 * @covers \Imagify_Auto_Optimization::store_ids_to_optimize
 * @covers \Imagify_Auto_Optimization::do_auto_optimization_after_meta_update
 * @group  AutoOptimization
 */
class Test_ClientSideUploadSequence extends TestCase {
	/**
	 * This suite needs no Imagify API credentials.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Files written to disk by the test.
	 *
	 * @var array
	 */
	private $created_files = [];

	/**
	 * Auto optimization runs recorded during the test.
	 *
	 * @var array
	 */
	private $runs = [];

	/**
	 * Original value of the auto_optimize option.
	 *
	 * @var mixed
	 */
	private $original_auto_optimize;

	/**
	 * Prepares the test environment before each test.
	 */
	public function set_up() {
		parent::set_up();

		$this->original_auto_optimize = get_imagify_option( 'auto_optimize' );
		update_imagify_option( 'auto_optimize', 1 );

		$this->runs = [];

		/*
		 * Record every optimization the sequence triggers, along with the sizes readable from
		 * the stored metadata at that moment. What is under test is when the decision is taken
		 * and what it can see: the optimization itself only reaches a queue that nothing
		 * dispatches during the tests.
		 */
		add_action(
			'imagify_before_auto_optimization',
			[ $this, 'record_run' ],
			5,
			2
		);

		/*
		 * The plugin registers these on boot. Calling init() again is idempotent, since the
		 * callbacks and priorities are identical, and it keeps the test honest if the hooks
		 * were not registered for any reason. remove_hooks() is deliberately not called on
		 * tear down: it would leave auto optimization switched off for every later test.
		 */
		Imagify_Auto_Optimization::get_instance()->init();
	}

	/**
	 * Cleans up the test environment after each test.
	 */
	public function tear_down() {
		remove_action( 'imagify_before_auto_optimization', [ $this, 'record_run' ], 5 );

		update_imagify_option( 'auto_optimize', $this->original_auto_optimize );

		foreach ( $this->created_files as $file ) {
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
		}

		$this->created_files = [];

		parent::tear_down();
	}

	/**
	 * Records an auto optimization run and what the stored metadata holds at that point.
	 *
	 * @param int  $attachment_id Attachment ID.
	 * @param bool $is_new_upload Whether Imagify treats this as a new upload.
	 */
	public function record_run( $attachment_id, $is_new_upload ) {
		$metadata = wp_get_attachment_metadata( $attachment_id );

		$this->runs[] = [
			'is_new_upload' => $is_new_upload,
			'sizes'         => is_array( $metadata ) && ! empty( $metadata['sizes'] ) ? array_keys( $metadata['sizes'] ) : [],
		];
	}

	/**
	 * Creates an attachment with a real file behind it, as the upload would.
	 *
	 * @return int
	 */
	private function create_attachment() {
		$uploads   = wp_upload_dir();
		$filename  = 'imagify-client-side-' . uniqid() . '.jpg';
		$file_path = trailingslashit( $uploads['basedir'] ) . $filename;

		wp_mkdir_p( dirname( $file_path ) );
		file_put_contents( $file_path, 'not-a-real-jpeg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		$this->created_files[] = $file_path;

		$attachment_id = $this->factory()->attachment->create_object(
			[
				'file'           => $filename,
				'post_mime_type' => 'image/jpeg',
				'post_status'    => 'inherit',
			]
		);

		update_post_meta( $attachment_id, '_wp_attached_file', $filename );

		return $attachment_id;
	}

	/**
	 * Builds one entry of the metadata `sizes` array, as WordPress stores it.
	 *
	 * @param  string $file   File name.
	 * @param  int    $width  Width in pixels.
	 * @param  int    $height Height in pixels.
	 * @return array
	 */
	private function size_data( $file, $width, $height ) {
		return [
			'file'      => $file,
			'width'     => $width,
			'height'    => $height,
			'mime-type' => 'image/jpeg',
		];
	}

	/**
	 * Builds the REST request WordPress hands to `rest_after_insert_attachment` when the
	 * browser is going to send the sub sizes itself.
	 *
	 * @return WP_REST_Request
	 */
	private function client_side_request() {
		$request = new WP_REST_Request( 'POST', '/wp/v2/media' );
		$request->set_param( 'generate_sub_sizes', false );

		return $request;
	}

	/**
	 * Runs the create phase: WordPress stores metadata that carries no sub size yet.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	private function run_create_phase( $attachment_id ) {
		$metadata = [
			'file'   => get_post_meta( $attachment_id, '_wp_attached_file', true ),
			'width'  => 3800,
			'height' => 2500,
			'sizes'  => [],
		];

		/** This filter is documented in wp-admin/includes/image.php */
		$metadata = apply_filters( 'wp_generate_attachment_metadata', $metadata, $attachment_id, 'create' );

		wp_update_attachment_metadata( $attachment_id, $metadata );
	}

	/**
	 * Runs the finalize phase: every sideloaded sub size is stored in one go.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	private function run_finalize_phase( $attachment_id ) {
		$metadata = wp_get_attachment_metadata( $attachment_id );

		$metadata['sizes'] = [
			'thumbnail'    => $this->size_data( 'thumb.jpg', 150, 150 ),
			'medium'       => $this->size_data( 'medium.jpg', 300, 197 ),
			'medium_large' => $this->size_data( 'medium_large.jpg', 768, 505 ),
			'large'        => $this->size_data( 'large.jpg', 1024, 674 ),
		];

		/** This filter is documented in wp-admin/includes/image.php */
		$metadata = apply_filters( 'wp_generate_attachment_metadata', $metadata, $attachment_id, 'update' );

		wp_update_attachment_metadata( $attachment_id, $metadata );
	}

	/**
	 * Test: the create phase optimizes nothing, and the finalize phase optimizes once, as a new
	 * upload, with every sub size readable from the stored metadata.
	 */
	public function testOptimizesOnceTheSubsizesAreStored() {
		$attachment_id = $this->create_attachment();

		Imagify_Auto_Optimization::get_instance()->flag_awaiting_client_side_subsizes(
			get_post( $attachment_id ),
			$this->client_side_request(),
			true
		);

		$this->run_create_phase( $attachment_id );

		$this->assertSame( [], $this->runs, 'Nothing should be optimized while the browser still owes its sub sizes.' );

		$this->run_finalize_phase( $attachment_id );

		$this->assertCount( 1, $this->runs, 'The media should be optimized exactly once.' );
		$this->assertTrue( $this->runs[0]['is_new_upload'], 'The finalize pass is still the new upload.' );
		$this->assertSame(
			[ 'thumbnail', 'medium', 'medium_large', 'large' ],
			$this->runs[0]['sizes'],
			'The optimization must run after the metadata is stored, so every sub size is visible.'
		);
	}

	/**
	 * Test: an ordinary upload, where WordPress builds the sub sizes itself, is optimized on
	 * the create phase exactly as before, so the deferral is limited to the client side flow.
	 */
	public function testOptimizesImmediatelyOnAnOrdinaryUpload() {
		$attachment_id = $this->create_attachment();

		// No flag: WordPress is building the sub sizes itself.
		$metadata = [
			'file'   => get_post_meta( $attachment_id, '_wp_attached_file', true ),
			'width'  => 1200,
			'height' => 900,
			'sizes'  => [
				'thumbnail' => $this->size_data( 'thumb.jpg', 150, 150 ),
				'medium'    => $this->size_data( 'medium.jpg', 300, 225 ),
			],
		];

		/** This filter is documented in wp-admin/includes/image.php */
		$metadata = apply_filters( 'wp_generate_attachment_metadata', $metadata, $attachment_id, 'create' );

		wp_update_attachment_metadata( $attachment_id, $metadata );

		$this->assertCount( 1, $this->runs, 'An ordinary upload should still be optimized once, on the create phase.' );
		$this->assertTrue( $this->runs[0]['is_new_upload'] );
	}

	/**
	 * Test: the flag is not left behind once the sub sizes have arrived.
	 */
	public function testClearsTheFlagOnceTheSubsizesArrive() {
		$attachment_id = $this->create_attachment();
		$auto          = Imagify_Auto_Optimization::get_instance();

		$auto->flag_awaiting_client_side_subsizes( get_post( $attachment_id ), $this->client_side_request(), true );

		$this->assertTrue( $auto->is_awaiting_client_side_subsizes( $attachment_id ) );

		$this->run_create_phase( $attachment_id );

		$this->assertTrue( $auto->is_awaiting_client_side_subsizes( $attachment_id ), 'The flag survives the create phase.' );

		$this->run_finalize_phase( $attachment_id );

		$this->assertFalse( $auto->is_awaiting_client_side_subsizes( $attachment_id ), 'The flag is cleared once the sub sizes are in.' );
	}
}
