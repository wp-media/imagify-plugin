<?php
namespace Imagify\ThirdParty\AS3CF;

use \Imagify\Optimization\File;
use \Imagify\ThirdParty\AS3CF\CDN\WP\AS3 as CDN;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Imagify WP Offload S3 class.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class Main extends \Imagify_AS3CF_Deprecated {
	use \Imagify\Traits\InstanceGetterTrait;

	/**
	 * AS3CF settings.
	 *
	 * @var    array
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $s3_settings;

	/**
	 * Filesystem object.
	 *
	 * @var    \Imagify_Filesystem
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $filesystem;

	/**
	 * The class constructor.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 */
	protected function __construct() {
		$this->filesystem = \Imagify_Filesystem::get_instance();
	}

	/**
	 * Launch the hooks.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 */
	public function init() {
		static $done = false;

		if ( $done ) {
			return;
		}
		$done = true;

		/**
		 * WebP images to display with a <picture> tag.
		 */
		add_action( 'as3cf_init',                         [ $this, 'store_s3_settings' ] );
		add_filter( 'imagify_webp_picture_process_image', [ $this, 'picture_tag_webp_image' ] );

		/**
		 * Register CDN.
		 */
		add_filter( 'imagify_cdn', [ $this, 'register_cdn' ], 8, 3 );

		/**
		 * Optimization process.
		 */
		add_filter( 'imagify_before_optimize_size', [ $this, 'maybe_copy_file_from_cdn_before_optimization' ], 8, 6 );
		add_action( 'imagify_after_optimize',       [ $this, 'maybe_send_media_to_cdn_after_optimization' ], 8, 2 );

		/**
		 * Restoration process.
		 */
		add_action( 'imagify_after_restore_media',  [ $this, 'maybe_send_media_to_cdn_after_restore' ], 8, 4 );

		/**
		 * WebP support.
		 */
		add_filter( 'as3cf_attachment_file_paths',  [ $this, 'add_webp_images_to_attachment' ], 8, 3 );
		add_filter( 'mime_types',                   [ $this, 'add_webp_support' ] );

		/**
		 * Redirections.
		 */
		add_filter( 'imagify_redirect_to', [ $this, 'redirect_referrer' ] );

		/**
		 * Stats.
		 */
		add_filter( 'imagify_total_attachment_filesize', [ $this, 'add_stats_for_s3_files' ], 8, 4 );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** OPTIMIZATION PROCESS ==================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * On AS3CF init, store its settings.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param \Amazon_S3_And_CloudFront $as3cf AS3CF’s main instance.
	 */
	public function store_s3_settings( $as3cf ) {
		if ( method_exists( $as3cf, 'get_settings' ) ) {
			$this->store_s3_settings = (array) $as3cf->get_settings();
		}
	}

	/**
	 * WebP images to display with a <picture> tag.
	 *
	 * @since  1.9
	 * @see    \Imagify\Webp\Picture\Display->process_image()
	 * @author Grégory Viguier
	 *
	 * @param  array $data An array of data for this image.
	 * @return array
	 */
	public function picture_tag_webp_image( $data ) {
		global $wpdb;

		if ( ! empty( $data['src']['webp_path'] ) ) {
			// The file is local.
			return $data;
		}

		$match = $this->is_s3_url( $data['src']['url'] );

		if ( ! $match ) {
			// The file is not on S3.
			return $data;
		}

		// Get the image ID.
		$post_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s",
				// We use only year/month + filename, we should not have any subdir between them for the main file.
				$match['year_month'] . $match['filename']
			)
		);

		if ( $post_id <= 0 ) {
			// Not in the database.
			return $data;
		}

		$s3_info      = get_post_meta( $post_id, 'amazonS3_info', true );
		$imagify_data = get_post_meta( $post_id, '_imagify_data', true );

		if ( ! $s3_info || ! $imagify_data ) {
			return $data;
		}

		$webp_size_suffix = constant( imagify_get_optimization_process_class_name( 'wp' ) . '::WEBP_SUFFIX' );
		$webp_size_name   = 'full' . $webp_size_suffix;

		if ( ! empty( $imagify_data['sizes'][ $webp_size_name ]['success'] ) ) {
			// We have a WebP image.
			$data['src']['webp_exists'] = true;
		}

		if ( empty( $data['srcset'] ) ) {
			return $data;
		}

		$meta_data = get_post_meta( $post_id, '_wp_attachment_metadata', true );

		if ( empty( $meta_data['sizes'] ) ) {
			return $data;
		}

		// Ease the search for corresponding file name.
		$size_files = [];

		foreach ( $meta_data['sizes'] as $size_name => $size_data ) {
			$size_files[ $size_data['file'] ] = $size_name;
		}

		// Look for a corresponding size name.
		foreach ( $data['srcset'] as $i => $srcset_data ) {
			if ( empty( $srcset_data['webp_url'] ) ) {
				// Not a supported image format.
				continue;
			}
			if ( ! empty( $srcset_data['webp_path'] ) ) {
				// The file is local.
				continue;
			}

			$match = $this->is_s3_url( $srcset_data['url'] );

			if ( ! $match ) {
				// Not on S3.
				continue;
			}

			// Try with no subdirs.
			$filename = $match['filename'];

			if ( isset( $size_files[ $filename ] ) ) {
				$size_name = $size_files[ $filename ];
			} else {
				// Try with subdirs.
				$filename = $match['subdirs'] . $match['filename'];

				if ( isset( $size_files[ $filename ] ) ) {
					$size_name = $size_files[ $filename ];
				} elseif ( preg_match( '@/\d+/$@', $match['subdirs'] ) ) {
					// Last try: the subdirs may contain the S3 versioning. If not the case, we can still build a pyramid with this code.
					$filename = preg_replace( '@/\d+/$@', '/', $match['subdirs'] ) . $match['filename'];

					if ( isset( $size_files[ $filename ] ) ) {
						$size_name = $size_files[ $filename ];
					} else {
						continue;
					}
				}
			}

			$webp_size_name = $size_name . $webp_size_suffix;

			if ( ! empty( $imagify_data['sizes'][ $webp_size_name ]['success'] ) ) {
				// We have a WebP image.
				$data['srcset'][ $i ]['webp_exists'] = true;
			}
		}

		return $data;
	}

	/**
	 * The CDN to use for this media.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param bool|PushCDNInterface $cdn      A PushCDNInterface instance. False if no CDN is used.
	 * @param int                   $media_id The media ID.
	 * @param ContextInterface      $context  The context object.
	 */
	public function register_cdn( $cdn, $media_id, $context ) {
		if ( 'wp' !== $context->get_name() ) {
			return $cdn;
		}
		if ( $cdn instanceof PushCDNInterface ) {
			return $cdn;
		}

		return new CDN( $media_id );
	}

	/**
	 * Before performing a file optimization, download the file from the CDN if it is missing.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  null|\WP_Error   $response           Null by default.
	 * @param  ProcessInterface $process            The optimization process instance.
	 * @param  File             $file               The file instance. If $webp is true, $file references the non-webp file.
	 * @param  string           $thumb_size         The media size.
	 * @param  int              $optimization_level The optimization level (0=normal, 1=aggressive, 2=ultra).
	 * @param  bool             $webp               The image will be converted to WebP.
	 * @return null|\WP_Error                       Null. A \WP_Error object on error.
	 */
	public function maybe_copy_file_from_cdn_before_optimization( $response, $process, $file, $thumb_size, $optimization_level, $webp ) {
		if ( is_wp_error( $response ) || 'wp' !== $process->get_media()->get_context() ) {
			return $response;
		}

		$media = $process->get_media();
		$cdn   = $media->get_cdn();

		if ( ! $cdn instanceof CDN ) {
			return $response;
		}

		if ( $this->filesystem->exists( $file->get_path() ) ) {
			return $response;
		}

		// Get files from the CDN.
		$result = $cdn->get_files_from_cdn( [ $file->get_path() ] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $response;
	}

	/**
	 * After performing a media optimization:
	 * - Save some data,
	 * - Upload the files to the CDN,
	 * - Maybe delete them from the server.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param ProcessInterface $process The optimization process.
	 * @param array            $item    The item being processed.
	 */
	public function maybe_send_media_to_cdn_after_optimization( $process, $item ) {
		if ( 'wp' !== $process->get_media()->get_context() ) {
			return;
		}

		$media = $process->get_media();
		$cdn   = $media->get_cdn();

		if ( ! $cdn instanceof CDN ) {
			return;
		}

		$cdn->send_to_cdn( ! empty( $item['data']['is_new_upload'] ) );
	}

	/**
	 * After restoring a media:
	 * - Save some data,
	 * - Upload the files to the CDN,
	 * - Maybe delete WebP files from the CDN.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param ProcessInterface $process  The optimization process.
	 * @param bool|WP_Error    $response The result of the operation: true on success, a WP_Error object on failure.
	 * @param array            $files    The list of files, before restoring them.
	 * @param array            $data     The optimization data, before deleting it.
	 */
	public function maybe_send_media_to_cdn_after_restore( $process, $response, $files, $data ) {
		if ( 'wp' !== $process->get_media()->get_context() ) {
			return;
		}

		$media = $process->get_media();
		$cdn   = $media->get_cdn();

		if ( ! $cdn instanceof CDN ) {
			return;
		}

		if ( is_wp_error( $response ) ) {
			$error_code = $response->get_error_code();

			if ( 'copy_failed' === $error_code ) {
				// No files have been restored.
				return;
			}

			// No thumbnails left?
		}

		$cdn->send_to_cdn( false );

		// Remove WebP files from CDN.
		$webp_files = [];

		if ( $files ) {
			// Get the paths to the WebP files.
			foreach ( $files as $size_name => $file ) {
				$webp_size_name = $size_name . $process::WEBP_SUFFIX;

				if ( empty( $data['sizes'][ $webp_size_name ]['success'] ) ) {
					// This size has no WebP version.
					continue;
				}

				if ( 0 === strpos( $file['mime-type'], 'image/' ) ) {
					$webp_file = new File( $file['path'] );

					if ( ! $webp_file->is_webp() ) {
						$webp_files[] = $webp_file->get_path_to_webp();
					}
				}
			}
		}

		if ( $webp_files ) {
			$cdn->remove_files_from_cdn( $webp_files );
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** OPTIMIZATION PROCESS ==================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Add the WebP files to the list of files that the CDN must handle.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $paths         A list of file paths, keyed by size name. 'file' for the full size. Includes a 'backup' size and a 'thumb' size.
	 * @param  int   $attachment_id The media ID.
	 * @param  array $metadata      The attachment meta data.
	 * @return array
	 */
	public function add_webp_images_to_attachment( $paths, $attachment_id, $metadata ) {
		if ( ! $paths ) {
			// ¯\(°_o)/¯.
			return $paths;
		}

		$process = imagify_get_optimization_process( $attachment_id, 'wp' );

		if ( ! $process->is_valid() ) {
			return $paths;
		}

		$media = $process->get_media();

		if ( ! $media->is_image() ) {
			return $paths;
		}

		// Use the optimization data (the files may not be on the server).
		$data = $process->get_data()->get_optimization_data();

		if ( empty( $data['sizes'] ) ) {
			return $paths;
		}

		foreach ( $paths as $size_name => $file_path ) {
			if ( 'thumb' === $size_name || 'backup' === $size_name || $process->is_size_webp( $size_name ) ) {
				continue;
			}

			if ( 'file' === $size_name ) {
				$size_name = 'full';
			}

			$webp_size_name = $size_name . $process::WEBP_SUFFIX;

			if ( empty( $data['sizes'][ $webp_size_name ]['success'] ) ) {
				// This size has no WebP version.
				continue;
			}

			$file = new File( $file_path );

			if ( ! $file->is_webp() ) {
				$paths[ $webp_size_name ] = $file->get_path_to_webp();
			}
		}

		return $paths;
	}

	/**
	 * Add WebP format to the list of allowed mime types.
	 *
	 * @since  1.9
	 * @access public
	 * @see    get_allowed_mime_types()
	 * @author Grégory Viguier
	 *
	 * @param  array $mime_types A list of mime types.
	 * @return array
	 */
	public function add_webp_support( $mime_types ) {
		$mime_types['webp'] = 'image/webp';
		return $mime_types;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** VARIOUS HOOKS =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * After a non-ajax optimization, remove some unnecessary arguments from the referrer used for the redirection.
	 * Those arguments don't break anything, they're just not relevant and display obsolete admin notices.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @param  string $redirect The URL to redirect to.
	 * @return string
	 */
	public function redirect_referrer( $redirect ) {
		return remove_query_arg( [ 'as3cfpro-action', 'as3cf_id', 'errors', 'count' ], $redirect );
	}

	/**
	 * Provide the file sizes and the number of thumbnails for files that are only on S3.
	 *
	 * @since  1.6.7
	 * @author Grégory Viguier
	 *
	 * @param  bool  $size_and_count False by default.
	 * @param  int   $image_id       The attachment ID.
	 * @param  array $files          An array of file paths with thumbnail sizes as keys.
	 * @param  array $image_ids      An array of all attachment IDs.
	 * @return bool|array            False by default. Provide an array with the keys 'filesize' (containing the total filesize) and 'thumbnails' (containing the number of thumbnails).
	 */
	public function add_stats_for_s3_files( $size_and_count, $image_id, $files, $image_ids ) {
		static $data;

		if ( is_array( $size_and_count ) ) {
			return $size_and_count;
		}

		if ( $this->filesystem->exists( $files['full'] ) ) {
			// If the full size is on the server, that probably means all files are on the server too.
			return $size_and_count;
		}

		if ( ! isset( $data ) ) {
			$data = \Imagify_DB::get_metas( [
				// Get the filesizes.
				's3_filesize' => 'wpos3_filesize_total',
			], $image_ids );

			$data = array_map( 'absint', $data['s3_filesize'] );
		}

		if ( empty( $data[ $image_id ] ) ) {
			// The file is not on S3.
			return $size_and_count;
		}

		// We can't take the disallowed sizes into account here.
		return [
			'filesize'   => (int) $data[ $image_id ],
			'thumbnails' => count( $files ) - 1,
		];
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if an URL is a S3 one.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $url The URL to test.
	 * @return array|bool  {
	 *     An array if an S3 URL. False otherwise.
	 *
	 *     @type string $key        Bucket key. Ex: subdir/wp-content/uploads/2019/02/13142432/foobar-480x510.jpg.
	 *     @type string $year_month The uploads year/month folders. Ex: 2019/02/.
	 *     @type string $subdirs    Sub-directories between year/month folders and the filename.
	 *                              It can be the S3 versioning folder, any folder added by a plugin, or both.
	 *                              There is no way to know which one it is. Ex: foo/13142432/.
	 *     @type string $filename   The file name. Ex: foobar-480x510.jpg.
	 * }
	 */
	public function is_s3_url( $url ) {
		static $uploads_dir;
		static $domain;

		/**
		 * Tell if an URL is a S3 one.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param null|array|bool $is  Null by default. Must return an array if an S3 URL, or false if not.
		 * @param string          $url The URL to test.
		 */
		$is = apply_filters( 'imagify_as3cf_is_s3_url', null, $url );

		if ( false === $is ) {
			return false;
		}

		if ( is_array( $is ) ) {
			return imagify_merge_intersect( $is, [
				'key'        => '',
				'year_month' => '',
				'subdirs'    => '',
				'filename'   => '',
			] );
		}

		if ( ! isset( $uploads_dir ) ) {
			$uploads_dir = wp_parse_url( $this->filesystem->get_upload_baseurl() );
			$uploads_dir = trim( $uploads_dir['path'], '/' ) . '/';
		}

		if ( ! isset( $domain ) ) {
			if ( ! empty( $this->store_s3_settings['cloudfront'] ) ) {
				$domain = sanitize_text_field( $this->store_s3_settings['cloudfront'] );
				$domain = preg_replace( '@^(?:https?:)?//@', '//', $domain );
				$domain = preg_quote( $domain, '@' );
			} else {
				$domain = 's3-.+\.amazonaws\.com/[^/]+/';
			}
		}

		$pattern = '@^(?:https?:)?//' . $domain . '/(?<key>' . $uploads_dir . '(?<year_month>\d{4}/\d{2}/)?(?<subdirs>.+/)?(?<filename>[^/]+))$@i';

		if ( ! preg_match( $pattern, $url, $match ) ) {
			return false;
		}

		unset( $match[0] );

		return array_merge( [
			'year_month' => '',
			'subdirs'    => '',
		], $match );
	}
}
