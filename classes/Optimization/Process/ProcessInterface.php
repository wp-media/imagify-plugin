<?php
namespace Imagify\Optimization\Process;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Interface to use to optimize medias.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
interface ProcessInterface {

	/**
	 * Tell if the given entry can be accepted in the constructor.
	 * For example it can include `is_numeric( $id )` if the constructor accepts integers.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  mixed $id Whatever.
	 * @return bool
	 */
	public static function constructor_accepts( $id );

	/**
	 * Get the data instance.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return DataInterface|false
	 */
	public function get_data();

	/**
	 * Get the media instance.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return MediaInterface|false
	 */
	public function get_media();

	/**
	 * Get the File instance of the original file.
	 *
	 * @since  1.9.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return File|false
	 */
	public function get_original_file();

	/**
	 * Get the File instance of the full size file.
	 *
	 * @since  1.9.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return File|false
	 */
	public function get_fullsize_file();

	/**
	 * Tell if the current media is valid.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_valid();

	/**
	 * Tell if the current user is allowed to operate Imagify in this context.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $describer Capacity describer. See \Imagify\Context\ContextInterface->get_capacity() for possible values. Can also be a "real" user capacity.
	 * @return bool
	 */
	public function current_user_can( $describer );


	/** ----------------------------------------------------------------------------------------- */
	/** OPTIMIZATION ============================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Optimize a media files by pushing tasks into the queue.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
	 * @return bool|WP_Error           True if successfully launched. A \WP_Error instance on failure.
	 */
	public function optimize( $optimization_level = null );

	/**
	 * Re-optimize a media files with a different level.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
	 * @return bool|WP_Error           True if successfully launched. A \WP_Error instance on failure.
	 */
	public function reoptimize( $optimization_level = null );

	/**
	 * Optimize several file sizes by pushing tasks into the queue.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $sizes              An array of media sizes (strings). Use "full" for the size of the main file.
	 * @param  int   $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
	 * @return bool|WP_Error             True if successfully launched. A \WP_Error instance on failure.
	 */
	public function optimize_sizes( $sizes, $optimization_level = null );

	/**
	 * Optimize one file with Imagify directly.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $size               The media size.
	 * @param  int    $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
	 * @return array|WP_Error             The optimization data. A \WP_Error instance on failure.
	 */
	public function optimize_size( $size, $optimization_level = null );

	/**
	 * Restore the media files from the backup file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|WP_Error True on success. A \WP_Error instance on failure.
	 */
	public function restore();


	/** ----------------------------------------------------------------------------------------- */
	/** MISSING THUMBNAILS ====================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the sizes for this media that have not get through optimization.
	 * No sizes are returned if the file is not optimized, has no backup, or is not an image.
	 * The 'full' size os never returned.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
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
	public function get_missing_sizes();

	/**
	 * Optimize missing thumbnail sizes.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|WP_Error True if successfully launched. A \WP_Error instance on failure.
	 */
	public function optimize_missing_thumbnails();


	/** ----------------------------------------------------------------------------------------- */
	/** BACKUP FILE ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Delete the backup file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function delete_backup();


	/** ----------------------------------------------------------------------------------------- */
	/** RESIZE FILE ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Maybe resize an image.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $size   The size name.
	 * @param  File   $file   A File instance.
	 * @return array|WP_Error A \WP_Error instance on failure, an array on success as follow: {
	 *     @type bool $resized   True when the image has been resized.
	 *     @type bool $backuped  True when the image has been backuped.
	 *     @type int  $file_size The file size in bytes.
	 * }
	 */
	public function maybe_resize( $size, $file );


	/** ----------------------------------------------------------------------------------------- */
	/** WEBP ==================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Generate webp images if they are missing.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|WP_Error True if successfully launched. A \WP_Error instance on failure.
	 */
	public function generate_webp_versions();

	/**
	 * Delete the webp images.
	 * This doesn't delete the related optimization data.
	 *
	 * @since  1.9
	 * @since  1.9.6 Return WP_Error or true.
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $keep_full Set to true to keep the full size.
	 * @return bool|\WP_Error  True on success. A \WP_Error object on failure.
	 */
	public function delete_webp_files( $keep_full = false );

	/**
	 * Tell if a thumbnail size is an "Imagify webp" size.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $size_name The size name.
	 * @return string|bool       The unsuffixed name of the size if webp. False if not webp.
	 */
	public function is_size_webp( $size_name );

	/**
	 * Tell if the media has webp versions.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function has_webp();


	/** ----------------------------------------------------------------------------------------- */
	/** PROCESS STATUS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if a process is running for this media.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_locked();

	/**
	 * Set the running status to "running" for a period of time.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function lock();

	/**
	 * Delete the running status.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function unlock();


	/** ----------------------------------------------------------------------------------------- */
	/** DATA ==================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if a size already has optimization data.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $size The size name.
	 * @return bool
	 */
	public function size_has_optimization_data( $size );

	/**
	 * Update the optimization data for a size.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $response The API response.
	 * @param  string $size     The size name.
	 * @param  int    $level    The optimization level (0=normal, 1=aggressive, 2=ultra).
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
	public function update_size_optimization_data( $response, $size, $level );
}
