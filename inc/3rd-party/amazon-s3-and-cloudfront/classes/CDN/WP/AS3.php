<?php
namespace Imagify\ThirdParty\AS3CF\CDN\WP;

use Imagify\CDN\PushCDNInterface;
use Imagify\Context\ContextInterface;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * AS3 CDN for WP context.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class AS3 implements PushCDNInterface {

	/**
	 * The media ID.
	 *
	 * @var    int
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $id;

	/**
	 * Filesystem object.
	 *
	 * @var    object Imagify_Filesystem
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $filesystem;

	/**
	 * Tell if we’re playing in WP 5.3’s garden.
	 *
	 * @var    bool
	 * @since  1.9.8
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $is_wp53;

	/**
	 * Constructor.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param int $media_id The media ID.
	 */
	public function __construct( $media_id ) {
		$this->id         = (int) $media_id;
		$this->filesystem = \Imagify_Filesystem::get_instance();
	}

	/**
	 * Tell if the CDN is ready (not necessarily reachable).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_ready() {
		global $as3cf;
		static $is;

		if ( ! isset( $is ) ) {
			$is = $as3cf && $as3cf->is_plugin_setup();
		}

		return $is;
	}

	/**
	 * Tell if the media is on the CDN.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function media_is_on_cdn() {
		return (bool) $this->get_cdn_info();
	}

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
	public function get_files_from_cdn( $file_paths ) {
		global $as3cf;

		if ( ! $this->is_ready() ) {
			return new \WP_Error( 'not_ready', __( 'CDN is not set up.', 'imagify' ) );
		}

		$cdn_info = $this->get_cdn_info();

		if ( ! $cdn_info ) {
			// The media is not on the CDN.
			return new \WP_Error( 'not_on_cdn', __( 'This media could not be found on the CDN.', 'imagify' ) );
		}

		$directory  = $this->filesystem->dir_path( $cdn_info['key'] );
		$directory  = $this->filesystem->is_root( $directory ) ? '' : $directory;
		$new_method = method_exists( $as3cf->plugin_compat, 'copy_s3_file_to_server' );
		$errors     = [];

		foreach ( $file_paths as $file_path ) {
			$cdn_info['key'] = $directory . $this->filesystem->file_name( $file_path );

			// Retrieve file from the CDN.
			if ( $new_method ) {
				$as3cf->plugin_compat->copy_s3_file_to_server( $cdn_info, $file_path );
			} else {
				$as3cf->plugin_compat->copy_provider_file_to_server( $cdn_info, $file_path );
			}

			if ( ! $this->filesystem->exists( $file_path ) ) {
				$errors[] = $file_path;
			}
		}

		if ( $errors ) {
			$nbr_errors = count( $errors );
			$errors_txt = array_map( [ $this->filesystem, 'make_path_relative' ], $errors );
			$errors_txt = wp_sprintf_l( '%l', $errors_txt );

			return new \WP_Error(
				'not_retrieved',
				sprintf(
					/* translators: %s is a list of file paths. */
					_n( 'The following file could not be retrieved from the CDN: %s.', 'The following files could not be retrieved from the CDN: %s.', $nbr_errors, 'imagify' ),
					$errors_txt
				),
				$errors
			);
		}

		return true;
	}

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
	public function remove_files_from_cdn( $file_paths ) {
		global $as3cf;

		if ( ! $this->is_ready() ) {
			return new \WP_Error( 'not_ready', __( 'CDN is not set up.', 'imagify' ) );
		}

		$cdn_info = $this->get_cdn_info();

		if ( ! $cdn_info ) {
			// The media is not on the CDN.
			return new \WP_Error( 'not_on_cdn', __( 'This media could not be found on the CDN.', 'imagify' ) );
		}

		$directory = $this->filesystem->dir_path( $cdn_info['key'] );
		$directory = $this->filesystem->is_root( $directory ) ? '' : $directory;

		if ( method_exists( $as3cf, 'get_s3object_region' ) ) {
			$region = $as3cf->get_s3object_region( $cdn_info );
		} else {
			$region = $as3cf->get_provider_object_region( $cdn_info );
		}

		if ( is_wp_error( $region ) ) {
			$region = '';
		}

		$to_remove = [];

		foreach ( $file_paths as $file_path ) {
			$to_remove[] = [
				'Key' => $directory . $this->filesystem->file_name( $file_path ),
			];
		}

		if ( method_exists( $as3cf, 'delete_s3_objects' ) ) {
			$result = $as3cf->delete_s3_objects( $region, $cdn_info['bucket'], $to_remove, false, false, false );
		} else {
			$result = $as3cf->delete_objects( $region, $cdn_info['bucket'], $to_remove, false, false, false );
		}

		if ( is_wp_error( $result ) ) {
			return new \WP_Error( 'deletion_failed', __( 'File(s) could not be removed from the CDN.', 'imagify' ) );
		}

		return true;
	}

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
	public function send_to_cdn( $is_new_upload ) {
		global $as3cf;

		if ( ! $this->is_ready() ) {
			return new \WP_Error( 'not_ready', __( 'CDN is not set up.', 'imagify' ) );
		}

		if ( ! $this->can_send_to_cdn( $is_new_upload ) ) {
			return false;
		}

		// Retrieve the missing files from the CDN: we must send all of them at once, even those that have not been modified.
		$file_paths = \AS3CF_Utils::get_attachment_file_paths( $this->id, false );

		if ( $file_paths ) {
			foreach ( $file_paths as $size => $file_path ) {
				if ( $this->filesystem->exists( $file_path ) ) {
					// Keep only the files that don't exist.
					unset( $file_paths[ $size ] );
				}
			}
		}

		if ( $file_paths ) {
			$result = $this->get_files_from_cdn( $file_paths );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$remove_local_files = $this->should_delete_files( $is_new_upload );

		if ( ! $is_new_upload ) {
			if ( $remove_local_files ) {
				// Force files deletion when not a new media.
				add_filter( 'as3cf_get_setting', [ $this, 'force_local_file_removal_setting' ], 100, 2 );
				add_filter( 'as3cf_setting_remove-local-file', 'imagify_return_true', 100 );
			} else {
				// Force to keep the files when not a new media.
				add_filter( 'as3cf_get_setting', [ $this, 'force_local_file_keep_setting' ], 100, 2 );
				add_filter( 'as3cf_setting_remove-local-file', 'imagify_return_false', 100 );
			}
		}

		if ( method_exists( $as3cf, 'upload_attachment_to_s3' ) ) {
			$result = $as3cf->upload_attachment_to_s3( $this->id, null, null, false, $remove_local_files );
		} else {
			$result = $as3cf->upload_attachment( $this->id, null, null, false, $remove_local_files );
		}

		if ( ! $is_new_upload ) {
			if ( $remove_local_files ) {
				remove_filter( 'as3cf_get_setting', [ $this, 'force_local_file_removal_setting' ], 100 );
				remove_filter( 'as3cf_setting_remove-local-file', 'imagify_return_true', 100 );
			} else {
				remove_filter( 'as3cf_get_setting', [ $this, 'force_local_file_keep_setting' ], 100 );
				remove_filter( 'as3cf_setting_remove-local-file', 'imagify_return_false', 100 );
			}
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

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
	public function get_file_url( $file_name = false ) {
		$file_url = wp_get_attachment_url( $this->id );

		if ( $file_name ) {
			// It's not the full size.
			$file_url = $this->filesystem->dir_path( $file_url ) . $file_name;
		}

		return $file_url;
	}

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
	public function get_file_path( $file_name = false ) {
		if ( ! $file_name ) {
			// Full size.
			return get_attached_file( $this->id, true );
		}

		if ( 'original' === $file_name ) {
			// Original file.
			if ( $this->is_wp_53() ) {
				// `wp_get_original_image_path()` may return false.
				$file_path = wp_get_original_image_path( $this->id );
			} else {
				$file_path = false;
			}

			if ( ! $file_path ) {
				$file_path = get_attached_file( $this->id, true );
			}

			return $file_path;
		}

		// Thumbnail.
		$file_path = get_attached_file( $this->id, true );
		$file_path = $this->filesystem->dir_path( $file_path ) . $file_name;

		return $file_path;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** INTERNAL TOOLS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Filter the CDN setting 'remove-local-file': this is used to force deletion when the media is not a new one.
	 * Caution to not use $this->should_delete_files() when this filter is used!
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool   $setting The setting value.
	 * @param  string $key     The setting name.
	 * @return bool
	 */
	public function force_local_file_removal_setting( $setting, $key ) {
		if ( 'remove-local-file' === $key ) {
			return true;
		}

		return $setting;
	}

	/**
	 * Filter the CDN setting 'remove-local-file': this is used to force not-deletion when the media is not a new one.
	 * Caution to not use $this->should_delete_files() when this filter is used!
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool   $setting The setting value.
	 * @param  string $key     The setting name.
	 * @return bool
	 */
	public function force_local_file_keep_setting( $setting, $key ) {
		if ( 'remove-local-file' === $key ) {
			return false;
		}

		return $setting;
	}

	/**
	 * Tell if a media is stored on the CDN.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return array|bool The CDN info on success. False if the media is not on the CDN.
	 */
	protected function get_cdn_info() {
		global $as3cf;

		if ( ! $as3cf ) {
			return false;
		}

		if ( method_exists( $as3cf, 'get_attachment_s3_info' ) ) {
			return $as3cf->get_attachment_s3_info( $this->id );
		}

		return $as3cf->get_attachment_provider_info( $this->id );
	}

	/**
	 * Tell if a media can (and should) be sent to the CDN.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  bool $is_new_upload Tell if the current media is a new upload. If not, it means it's a media being regenerated, restored, etc.
	 * @return bool
	 */
	protected function can_send_to_cdn( $is_new_upload ) {
		global $as3cf;
		static $can = [];
		static $cdn_setting;

		if ( isset( $can[ $this->id ] ) ) {
			return $can[ $this->id ];
		}

		if ( ! $this->is_ready() ) {
			$can[ $this->id ] = false;
			return $can[ $this->id ];
		}

		if ( ! isset( $cdn_setting ) ) {
			$cdn_setting = $as3cf && $as3cf->get_setting( 'copy-to-s3' );
		}

		// The CDN is set up: test if the media is on it.
		$can[ $this->id ] = $this->media_is_on_cdn();

		if ( $can[ $this->id ] && $is_new_upload ) {
			// Use the CDN setting to tell if we're allowed to send the files (should be true since it's a new upload and it's already there).
			$can[ $this->id ] = $cdn_setting;
		}

		/**
		 * Tell if a media can (and should) be sent to the CDN.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param bool             $can           True if the media can be sent. False otherwize.
		 * @param PushCDNInterface $cdn           The CDN instance.
		 * @param bool             $cdn_setting   CDN setting that tells if a new media can be sent to the CDN.
		 * @param bool             $is_new_upload Tell if the current media is a new upload. If not, it means it's a media being regenerated, restored, etc.
		 */
		$can[ $this->id ] = (bool) apply_filters( 'imagify_can_send_to_cdn', $can[ $this->id ], $this, $cdn_setting, $is_new_upload );

		return $can[ $this->id ];
	}

	/**
	 * Tell if the files should be deleted after optimization.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  bool $is_new_upload Tell if the current media is a new upload. If not, it means it's a media being regenerated, restored, etc.
	 * @return bool
	 */
	protected function should_delete_files( $is_new_upload ) {
		global $as3cf;

		if ( $is_new_upload ) {
			return (bool) $as3cf->get_setting( 'remove-local-file' );
		}

		// If the attachment has a 'filesize' metadata, that means the local files are meant to be deleted.
		return (bool) get_post_meta( $this->id, 'wpos3_filesize_total', true );
	}

	/**
	 * Tell if we’re playing in WP 5.3’s garden.
	 *
	 * @since  1.9.8
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	protected function is_wp_53() {
		if ( isset( $this->is_wp53 ) ) {
			return $this->is_wp53;
		}

		$this->is_wp53 = function_exists( 'wp_get_original_image_path' );

		return $this->is_wp53;
	}
}
