<?php
namespace Imagify\CDN;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Interface to use for Push CDNs.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
interface PushCDNInterface {

	/**
	 * Tell if the CDN is ready (not necessarily reachable).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_ready();

	/**
	 * Tell if the media is on the CDN.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function media_is_on_cdn();

	/**
	 * Get files from the CDN.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $file_paths A list of file paths.
	 * @return bool|\WP_Error    True on success. A \WP_error object on failure.
	 */
	public function get_files_from_cdn( $file_paths );

	/**
	 * Remove files from the CDN.
	 * Don't use this to empty a folder.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $file_paths A list of file paths. Those paths are not necessary absolute, and can be also file names.
	 * @return bool|\WP_Error    True on success. A \WP_error object on failure.
	 */
	public function remove_files_from_cdn( $file_paths );

	/**
	 * Send all files from a media to the CDN.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $is_new_upload Tell if the current media is a new upload. If not, it means it's a media being regenerated, restored, etc.
	 * @return bool|\WP_Error      True/False if sent or not. A \WP_error object on failure.
	 */
	public function send_to_cdn( $is_new_upload );

	/**
	 * Get a file URL.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_name Name of the file. Leave empty for the full size file.
	 * @return string            URL to the file.
	 */
	public function get_file_url( $file_name = false );

	/**
	 * Get a file path.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_name Name of the file. Leave empty for the full size file. Use 'original' to get the path to the original file.
	 * @return string            Path to the file.
	 */
	public function get_file_path( $file_name = false );
}
