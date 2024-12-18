<?php
declare(strict_types=1);

namespace Imagify\Optimization\Process;

use Imagify\Optimization\Data\DataInterface;
use Imagify\Media\MediaInterface;
use Imagify\Optimization\File;
use WP_Error;

/**
 * Fallback class to optimize medias.
 */
class Noop implements ProcessInterface {
	/**
	 * The suffix used in the thumbnail size name.
	 *
	 * @var string
	 * @since 1.9
	 */
	const WEBP_SUFFIX = '@imagify-webp';

	/**
	 * The suffix used in the thumbnail size name.
	 *
	 * @var string
	 * @since 2.2
	 */
	const AVIF_SUFFIX = '@imagify-avif';

	/**
	 * The suffix used in file name to create a temporary copy of the full size.
	 *
	 * @var string
	 * @since 1.9
	 */
	const TMP_SUFFIX = '@imagify-tmp';

	/**
	 * Used for the name of the transient telling if a media is locked.
	 * %1$s is the context, %2$s is the media ID.
	 *
	 * @var string
	 * @since 1.9
	 */
	const LOCK_NAME = 'imagify_%1$s_%2$s_process_locked';

	/**
	 * Tell if the given entry can be accepted in the constructor.
	 * For example it can include `is_numeric( $id )` if the constructor accepts integers.
	 *
	 * @since 1.9
	 *
	 * @param mixed $id Whatever.
	 *
	 * @return bool
	 */
	public static function constructor_accepts( $id ) {
		return false;
	}

	/**
	 * Get the data instance.
	 *
	 * @since 1.9
	 *
	 * @return DataInterface|false
	 */
	public function get_data() {
		return false;
	}

	/**
	 * Get the media instance.
	 *
	 * @since 1.9
	 *
	 * @return MediaInterface|false
	 */
	public function get_media() {
		return false;
	}

	/**
	 * Get the File instance of the original file.
	 *
	 * @since 1.9.8
	 *
	 * @return File|false
	 */
	public function get_original_file() {
		return false;
	}

	/**
	 * Get the File instance of the full size file.
	 *
	 * @since 1.9.8
	 *
	 * @return File|false
	 */
	public function get_fullsize_file() {
		return false;
	}

	/**
	 * Get the File instance.
	 *
	 * @since 1.9
	 *
	 * @return File|false
	 */
	public function get_file() {
		return false;
	}

	/**
	 * Tell if the current media is valid.
	 *
	 * @since 1.9
	 *
	 * @return bool
	 */
	public function is_valid() {
		return false;
	}

	/**
	 * Tell if the current user is allowed to operate Imagify in this context.
	 *
	 * @since 1.9
	 *
	 * @param string $describer Capacity describer. See \Imagify\Context\ContextInterface->get_capacity() for possible values. Can also be a "real" user capacity.
	 *
	 * @return bool
	 */
	public function current_user_can( $describer ) {
		return false;
	}

	/**
	 * Optimize a media files by pushing tasks into the queue.
	 *
	 * @since 1.9
	 *
	 * @param int $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
	 *
	 * @return bool|WP_Error True if successfully launched. A WP_Error instance on failure.
	 */
	public function optimize( $optimization_level = null ) {
		return new WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
	}

	/**
	 * Re-optimize a media files with a different level.
	 *
	 * @since 1.9
	 *
	 * @param int $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
	 *
	 * @return bool|WP_Error True if successfully launched. A WP_Error instance on failure.
	 */
	public function reoptimize( $optimization_level = null ) {
		return new WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
	}

	/**
	 * Optimize several file sizes by pushing tasks into the queue.
	 *
	 * @since 1.9
	 *
	 * @param array $sizes              An array of media sizes (strings). Use "full" for the size of the main file.
	 * @param int   $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
	 *
	 * @return bool|WP_Error True if successfully launched. A WP_Error instance on failure.
	 */
	public function optimize_sizes( $sizes, $optimization_level = null ) {
		return new WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
	}

	/**
	 * Optimize one file with Imagify directly.
	 *
	 * @since 1.9
	 *
	 * @param string $size               The media size.
	 * @param int    $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
	 *
	 * @return array|WP_Error The optimization data. A WP_Error instance on failure.
	 */
	public function optimize_size( $size, $optimization_level = null ) {
		return new WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
	}

	/**
	 * Restore the media files from the backup file.
	 *
	 * @since 1.9
	 *
	 * @return bool|WP_Error True on success. A WP_Error instance on failure.
	 */
	public function restore() {
		return new WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
	}

	/**
	 * Get the sizes for this media that have not get through optimization.
	 * No sizes are returned if the file is not optimized, has no backup, or is not an image.
	 * The 'full' size os never returned.
	 *
	 * @since 1.9
	 *
	 * @return array|WP_Error {
	 *     A WP_Error object on failure.
	 *     An array of data for the thumbnail sizes on success.
	 *     Size names are used as array keys.
	 *
	 *     @type int    $width  The image width.
	 *     @type int    $height The image height.
	 *     @type bool   $crop   True to crop, false to resize.
	 *     @type string $name   The size name.
	 *     @type string $file   The name the thumbnail "should" have.
	 * }
	 */
	public function get_missing_sizes() {
		return [];
	}

	/**
	 * Optimize missing thumbnail sizes.
	 *
	 * @since 1.9
	 *
	 * @return bool|WP_Error True if successfully launched. A WP_Error instance on failure.
	 */
	public function optimize_missing_thumbnails() {
		return new WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
	}

	/**
	 * Delete the backup file.
	 *
	 * @since 1.9
	 */
	public function delete_backup() {}

	/**
	 * Maybe resize an image.
	 *
	 * @since 1.9
	 *
	 * @param string $size   The size name.
	 * @param File   $file   A File instance.
	 *
	 * @return array|WP_Error A WP_Error instance on failure, an array on success as follow: {
	 *     @type bool $resized   True when the image has been resized.
	 *     @type bool $backuped  True when the image has been backuped.
	 *     @type int  $file_size The file size in bytes.
	 * }
	 */
	public function maybe_resize( $size, $file ) {
		return [
			'resized'   => false,
			'backuped'  => false,
			'file_size' => 0,
		];
	}

	/**
	 * Generate Nextgen images if they are missing.
	 *
	 * @since 1.9
	 *
	 * @return bool|WP_Error True if successfully launched. A WP_Error instance on failure.
	 */
	public function generate_nextgen_versions() {
		return new WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
	}

	/**
	 * Delete the next gen format images.
	 * This doesn't delete the related optimization data.
	 *
	 * @since 2.2
	 *
	 * @param  bool $keep_full Set to true to keep the full size.
	 * @return bool|WP_Error  True on success. A WP_Error object on failure.
	 */
	public function delete_nextgen_files( $keep_full = false ) {
		return false;
	}

	/**
	 * Tell if a thumbnail size is an "Imagify Next-Gen" size.
	 *
	 * @since  2.2
	 *
	 * @param  string $size_name The size name.
	 *
	 * @return string|bool The unsuffixed name of the size if Next-Gen. False if not a Next-Gen.
	 */
	public function is_size_next_gen( $size_name ) {
		return false;
	}

	/**
	 * Tell if the media has all next-gen versions.
	 *
	 * @return bool
	 */
	public function is_full_next_gen() {
		return false;
	}

	/**
	 * Tell if the media has a next-gen format.
	 *
	 * @since 2.2
	 *
	 * @return bool
	 */
	public function has_next_gen() {
		return false;
	}

	/**
	 * Tell if a process is running for this media.
	 *
	 * @since 1.9
	 *
	 * @return bool
	 */
	public function is_locked() {
		return false;
	}

	/**
	 * Set the running status to "running" for a period of time.
	 *
	 * @since 1.9
	 */
	public function lock() {}

	/**
	 * Delete the running status.
	 *
	 * @since 1.9
	 */
	public function unlock() {}

	/**
	 * Tell if a size already has optimization data.
	 *
	 * @since 1.9
	 *
	 * @param string $size The size name.
	 *
	 * @return bool
	 */
	public function size_has_optimization_data( $size ) {
		return false;
	}

	/**
	 * Update the optimization data for a size.
	 *
	 * @since 1.9
	 *
	 * @param object $response The API response.
	 * @param string $size     The size name.
	 * @param int    $level    The optimization level (0=normal, 1=aggressive, 2=ultra).
	 *
	 * @return array            {
	 *     The optimization data.
	 *
	 *     @type string $size           The size name.
	 *     @type int    $level          The optimization level.
	 *     @type string $status         The status: 'success', 'already_optimized', 'error'.
	 *     @type bool   $success        True if successfully optimized. False on error or if already optimized.
	 *     @type string $error          An error message.
	 *     @type int    $original_size  The weight of the file, before optimization.
	 *     @type int    $optimized_size The weight of the file, once optimized.
	 * }
	 */
	public function update_size_optimization_data( $response, $size, $level ) {
		return [
			'size'           => 'noop',
			'level'          => false,
			'status'         => '',
			'success'        => false,
			'error'          => '',
			'original_size'  => 0,
			'optimized_size' => 0,
		];
	}
}
