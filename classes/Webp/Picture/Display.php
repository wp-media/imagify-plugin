<?php
namespace Imagify\Webp\Picture;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Display webp images on the site with <picture> tags.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class Display {
	use \Imagify\Traits\FakeSingletonTrait;

	/**
	 * Option value.
	 *
	 * @var    string
	 * @since  1.9
	 * @author Grégory Viguier
	 */
	const OPTION_VALUE = 'picture';

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
	 * Constructor.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function __construct() {
		$this->filesystem = \Imagify_Filesystem::get_instance();
	}

	/**
	 * Init.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		add_action( 'template_redirect', [ $this, 'start_content_process' ], 6 );
	}

	/** ----------------------------------------------------------------------------------------- */
	/** ADD <PICTURE> TAGS TO THE PAGE ========================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Start buffering the page content.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function start_content_process() {
		if ( ! get_imagify_option( 'display_webp' ) ) {
			return;
		}

		if ( self::OPTION_VALUE !== get_imagify_option( 'display_webp_method' ) ) {
			return;
		}

		/**
		 * Prevent the replacement of <img> tags into <picture> tags.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param bool $allow True to allow the use of <picture> tags (default). False to prevent their use.
		 */
		$allow = apply_filters( 'imagify_allow_picture_tags_for_webp', true );

		if ( ! $allow ) {
			return;
		}

		ob_start( [ $this, 'maybe_process_buffer' ] );
	}

	/**
	 * Maybe process the page content.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $buffer The buffer content.
	 * @return string
	 */
	public function maybe_process_buffer( $buffer ) {
		if ( ! $this->is_html( $buffer ) ) {
			return $buffer;
		}

		if ( strlen( $buffer ) <= 255 ) {
			// Buffer length must be > 255 (IE does not read pages under 255 c).
			return $buffer;
		}

		$buffer = $this->process_content( $buffer );

		/**
		 * Filter the page content after Imagify.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param string $buffer The page content.
		 */
		$buffer = (string) apply_filters( 'imagify_buffer', $buffer );

		return $buffer;
	}

	/**
	 * Process the content.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $content The content.
	 * @return string
	 */
	public function process_content( $content ) {
		$images = $this->get_images( $content );

		if ( ! $images ) {
			return $content;
		}

		foreach ( $images as $image ) {
			$tag     = $this->build_picture_tag( $image );
			$content = str_replace( $image['tag'], $tag, $content );
		}

		return $content;
	}

	/** ----------------------------------------------------------------------------------------- */
	/** BUILD HTML TAGS AND ATTRIBUTES ========================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Build the <picture> tag to insert.
	 *
	 * @since  1.9
	 * @see    $this->process_image()
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  array $image An array of data.
	 * @return string       A <picture> tag.
	 */
	protected function build_picture_tag( $image ) {
		$to_remove = [
			'alt'           => '',
			'data-lazy-src' => '',
			'data-src'      => '',
			'sizes'         => '',
		];

		$attributes = array_diff_key( $image['attributes'], $to_remove );

		/**
		 * Filter the attributes to be added to the <picture> tag.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param array $attributes A list of attributes to be added to the <picture> tag.
		 * @param array $data       Data built from the originale <img> tag. See $this->process_image().
		 */
		$attributes = apply_filters( 'imagify_picture_attributes', $attributes, $image );

		$output = '<picture' . $this->build_attributes( $attributes ) . ">\n";
		/**
		 * Allow to add more <source> tags to the <picture> tag.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param string $more_source_tags Additional <source> tags.
		 * @param array  $data             Data built from the originale <img> tag. See $this->process_image().
		 */
		$output .= apply_filters( 'imagify_additional_source_tags', '', $image );
		$output .= $this->build_source_tag( $image );
		$output .= $this->build_img_tag( $image );
		$output .= "</picture>\n";

		return $output;
	}

	/**
	 * Build the <source> tag to insert in the <picture>.
	 *
	 * @since  1.9
	 * @see    $this->process_image()
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  array $image An array of data.
	 * @return string       A <source> tag.
	 */
	protected function build_source_tag( $image ) {
		$attributes = [
			'type'   => 'image/webp',
			'srcset' => [
				$image['src']['webp_url'],
			],
		];

		if ( ! empty( $image['srcset'] ) ) {
			foreach ( $image['srcset'] as $srcset ) {
				if ( empty( $srcset['webp_url'] ) ) {
					continue;
				}
				if ( $srcset['webp_url'] === $image['src']['webp_url'] ) {
					continue;
				}

				$attributes['srcset'][] = $srcset['webp_url'] . ' ' . $srcset['descriptor'];
			}
		}

		$attributes['srcset'] = implode( ', ', $attributes['srcset'] );

		if ( ! empty( $image['attributes']['sizes'] ) ) {
			$attributes['sizes'] = $image['attributes']['sizes'];
		}

		/**
		 * Filter the attributes to be added to the <source> tag.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param array $attributes A list of attributes to be added to the <source> tag.
		 * @param array $data       Data built from the original <img> tag. See $this->process_image().
		 */
		$attributes = apply_filters( 'imagify_picture_source_attributes', $attributes, $image );

		return '<source' . $this->build_attributes( $attributes ) . "/>\n";
	}

	/**
	 * Build the <img> tag to insert in the <picture>.
	 *
	 * @since  1.9
	 * @see    $this->process_image()
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  array $image An array of data.
	 * @return string       A <img> tag.
	 */
	protected function build_img_tag( $image ) {
		$attributes        = $image['attributes'];
		$attributes['src'] = $image['src']['url'];

		if ( ! empty( $image['srcset'] ) ) {
			$attributes['srcset'] = [];

			foreach ( $image['srcset'] as $srcset ) {
				$attributes['srcset'][] = $srcset['url'] . ' ' . $srcset['descriptor'];
			}

			$attributes['srcset'] = implode( ', ', $attributes['srcset'] );
		}

		$to_remove = [
			'class'  => '',
			'height' => '',
			'id'     => '',
			'style'  => '',
			'title'  => '',
			'width'  => '',
		];

		$attributes = array_diff_key( $attributes, $to_remove );

		/**
		 * Filter the attributes to be added to the <img> tag.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param array $attributes A list of attributes to be added to the <img> tag.
		 * @param array $data       Data built from the originale <img> tag. See $this->process_image().
		 */
		$attributes = apply_filters( 'imagify_picture_img_attributes', $attributes, $image );

		return '<img' . $this->build_attributes( $attributes ) . "/>\n";
	}

	/**
	 * Create HTML attributes from an array.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  array $attributes A list of attribute pairs.
	 * @return string            HTML attributes.
	 */
	protected function build_attributes( $attributes ) {
		if ( ! $attributes || ! is_array( $attributes ) ) {
			return '';
		}

		$out = '';

		foreach ( $attributes as $attribute => $value ) {
			$out .= ' ' . $attribute . '="' . esc_attr( $value ) . '"';
		}

		return $out;
	}

	/** ----------------------------------------------------------------------------------------- */
	/** VARIOUS TOOLS =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get a list of images in a content.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $content The content.
	 * @return array
	 */
	protected function get_images( $content ) {
		// Remove comments.
		$content = preg_replace( '/<!--(.*)-->/Uis', '', $content );

		if ( ! preg_match_all( '/<img\s.*>/isU', $content, $matches ) ) {
			return [];
		}

		$images = array_map( [ $this, 'process_image' ], $matches[0] );
		$images = array_filter( $images );

		/**
		 * Filter the images to display with a <picture> tag.
		 *
		 * @since  1.9
		 * @see    $this->process_image()
		 * @author Grégory Viguier
		 *
		 * @param array  $images A list of arrays.
		 * @param string $content The page content.
		 */
		$images = apply_filters( 'imagify_webp_picture_images_to_display', $images, $content );

		if ( ! $images || ! is_array( $images ) ) {
			return [];
		}

		foreach ( $images as $i => $image ) {
			if ( empty( $image['src']['webp_exists'] ) || empty( $image['src']['webp_url'] ) ) {
				unset( $images[ $i ] );
				continue;
			}

			unset( $images[ $i ]['src']['webp_path'], $images[ $i ]['src']['webp_exists'] );

			if ( empty( $image['srcset'] ) || ! is_array( $image['srcset'] ) ) {
				unset( $images[ $i ]['srcset'] );
				continue;
			}

			foreach ( $image['srcset'] as $j => $srcset ) {
				if ( empty( $srcset['webp_exists'] ) || empty( $srcset['webp_url'] ) ) {
					unset( $images[ $i ]['srcset'][ $j ]['webp_url'] );
				}

				unset( $images[ $i ]['srcset'][ $j ]['webp_path'], $images[ $i ]['srcset'][ $j ]['webp_exists'] );
			}
		}

		return $images;
	}

	/**
	 * Process an image tag and get an array containing some data.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $image An image html tag.
	 * @return array|false {
	 *     An array of data if the image has a webp version. False otherwise.
	 *
	 *     @type string $tag        The image tag.
	 *     @type array  $attributes The image attributes (minus src and srcset).
	 *     @type array  $src        {
	 *         @type string $url      URL to the original image.
	 *         @type string $webp_url URL to the webp version.
	 *     }
	 *     @type array  $srcset     {
	 *         An array or arrays. Not set if not applicable.
	 *
	 *         @type string $url        URL to the original image.
	 *         @type string $webp_url   URL to the webp version. Not set if not applicable.
	 *         @type string $descriptor A src descriptor.
	 *     }
	 * }
	 */
	protected function process_image( $image ) {
		static $extensions;

		$atts_pattern = '/(?<name>[^\s"\']+)\s*=\s*(["\'])\s*(?<value>.*?)\s*\2/';

		if ( ! preg_match_all( $atts_pattern, $image, $tmp_attributes, PREG_SET_ORDER ) ) {
			// No attributes?
			return false;
		}

		$attributes = [];

		foreach ( $tmp_attributes as $attribute ) {
			$attributes[ $attribute['name'] ] = $attribute['value'];
		}

		if ( ! empty( $attributes['class'] ) && strpos( $attributes['class'], 'imagify-no-webp' ) !== false ) {
			// Has the 'imagify-no-webp' class.
			return false;
		}

		// Deal with the src attribute.
		if ( empty( $attributes['src'] ) ) {
			// No src attribute.
			return false;
		}

		if ( ! isset( $extensions ) ) {
			$extensions = imagify_get_mime_types( 'image' );
			$extensions = array_keys( $extensions );
			$extensions = implode( '|', $extensions );
		}

		if ( ! preg_match( '@^(?<src>(?:https?:)?//.+\.(?<extension>' . $extensions . '))(?<query>\?.*)?$@i', $attributes['src'], $src ) ) {
			// Not a supported image format.
			return false;
		}

		$webp_url  = imagify_path_to_webp( $src['src'] );
		$webp_path = $this->url_to_path( $webp_url );
		$webp_url .= ! empty( $src['query'] ) ? $src['query'] : '';

		$data = [
			'tag'        => $image,
			'attributes' => $attributes,
			'src'        => [
				'url'         => $attributes['src'],
				'webp_url'    => $webp_url,
				'webp_path'   => $webp_path,
				'webp_exists' => $webp_path && $this->filesystem->exists( $webp_path ),
			],
			'srcset'     => [],
		];

		unset( $data['attributes']['src'], $data['attributes']['srcset'] );

		// Deal with the srcset attribute.
		if ( ! empty( $attributes['srcset'] ) ) {
			$srcset = explode( ',', $attributes['srcset'] );

			foreach ( $srcset as $srcs ) {
				$srcs = preg_split( '/\s+/', trim( $srcs ) );

				if ( count( $srcs ) > 2 ) {
					// Not a good idea to have space characters in file name.
					$descriptor = array_pop( $srcs );
					$srcs       = [ implode( ' ', $srcs ), $descriptor ];
				}

				if ( empty( $srcs[1] ) ) {
					$srcs[1] = '1x';
				}

				if ( ! preg_match( '@^(?<src>(?:https?:)?//.+\.(?<extension>' . $extensions . '))(?<query>\?.*)?$@i', $srcs[0], $src ) ) {
					// Not a supported image format.
					$data['srcset'][] = [
						'url'        => $srcs[0],
						'descriptor' => $srcs[1],
					];
					continue;
				}

				$webp_url  = imagify_path_to_webp( $src['src'] );
				$webp_path = $this->url_to_path( $webp_url );
				$webp_url .= ! empty( $src['query'] ) ? $src['query'] : '';

				$data['srcset'][] = [
					'url'         => $srcs[0],
					'descriptor'  => $srcs[1],
					'webp_url'    => $webp_url,
					'webp_path'   => $webp_path,
					'webp_exists' => $webp_path && $this->filesystem->exists( $webp_path ),
				];
			}
		}

		/**
		 * Filter a processed image tag.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param array  $data  An array of data for this image.
		 * @param string $image An image html tag.
		 */
		$data = apply_filters( 'imagify_webp_picture_process_image', $data, $image );

		if ( ! $data || ! is_array( $data ) ) {
			return false;
		}

		if ( ! isset( $data['tag'], $data['attributes'], $data['src'], $data['srcset'] ) ) {
			return false;
		}

		return $data;
	}

	/**
	 * Tell if a content is HTML.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $content The content.
	 * @return bool
	 */
	protected function is_html( $content ) {
		return preg_match( '/<\/html>/i', $content );
	}

	/**
	 * Convert a file URL to an absolute path.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $url A file URL.
	 * @return string|bool The file path. False on failure.
	 */
	protected function url_to_path( $url ) {
		static $scheme;
		static $uploads_url;
		static $uploads_dir;
		static $root_url;
		static $root_dir;

		if ( ! isset( $scheme ) ) {
			$scheme      = is_ssl() ? 'https' : 'http';
			$uploads_url = set_url_scheme( $this->filesystem->get_upload_baseurl(), $scheme );
			$uploads_dir = $this->filesystem->get_upload_basedir( true );
			$root_url    = set_url_scheme( $this->filesystem->get_site_root_url(), $scheme );
			$root_dir    = $this->filesystem->get_site_root();
		}

		$url = set_url_scheme( $url, $scheme );

		if ( stripos( $url, $uploads_url ) === 0 ) {
			return str_ireplace( $uploads_url, $uploads_dir, $url );
		}

		if ( stripos( $url, $root_url ) === 0 ) {
			return str_ireplace( $root_url, $root_dir, $url );
		}

		return false;
	}
}
