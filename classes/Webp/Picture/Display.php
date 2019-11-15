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
	use \Imagify\Traits\InstanceGetterTrait;

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
		add_action( 'template_redirect', [ $this, 'start_content_process' ], -1000 );
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
			'alt'              => '',
			'height'           => '',
			'width'            => '',
			'data-lazy-src'    => '',
			'data-src'         => '',
			'src'              => '',
			'data-lazy-srcset' => '',
			'data-srcset'      => '',
			'srcset'           => '',
			'data-lazy-sizes'  => '',
			'data-sizes'       => '',
			'sizes'            => '',
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
		$srcset_source = ! empty( $image['srcset_attribute'] ) ? $image['srcset_attribute'] : $image['src_attribute'] . 'set';
		$attributes    = [
			'type'         => 'image/webp',
			$srcset_source => [],
		];

		if ( ! empty( $image['srcset'] ) ) {
			foreach ( $image['srcset'] as $srcset ) {
				if ( empty( $srcset['webp_url'] ) ) {
					continue;
				}

				$attributes[ $srcset_source ][] = $srcset['webp_url'] . ' ' . $srcset['descriptor'];
			}
		}

		if ( empty( $attributes[ $srcset_source ] ) ) {
			$attributes[ $srcset_source ][] = $image['src']['webp_url'];
		}

		$attributes[ $srcset_source ] = implode( ', ', $attributes[ $srcset_source ] );

		foreach ( [ 'data-lazy-srcset', 'data-srcset', 'srcset' ] as $srcset_attr ) {
			if ( ! empty( $image['attributes'][ $srcset_attr ] ) && $srcset_attr !== $srcset_source ) {
				$attributes[ $srcset_attr ] = $image['attributes'][ $srcset_attr ];
			}
		}

		if ( 'srcset' !== $srcset_source && empty( $attributes['srcset'] ) && ! empty( $image['attributes']['src'] ) ) {
			// Lazyload: the "src" attr should contain a placeholder (a data image or a blank.gif ).
			$attributes['srcset'] = $image['attributes']['src'];
		}

		foreach ( [ 'data-lazy-sizes', 'data-sizes', 'sizes' ] as $sizes_attr ) {
			if ( ! empty( $image['attributes'][ $sizes_attr ] ) ) {
				$attributes[ $sizes_attr ] = $image['attributes'][ $sizes_attr ];
			}
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
		$to_remove = [
			'class'  => '',
			'id'     => '',
			'style'  => '',
			'title'  => '',
		];

		$attributes = array_diff_key( $image['attributes'], $to_remove );

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
				if ( ! is_array( $srcset ) ) {
					continue;
				}

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

		$atts_pattern = '/(?<name>[^\s"\']+)\s*=\s*(["\'])\s*(?<value>.*?)\s*\2/s';

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
		$src_source = false;

		foreach ( [ 'data-lazy-src', 'data-src', 'src' ] as $src_attr ) {
			if ( ! empty( $attributes[ $src_attr ] ) ) {
				$src_source = $src_attr;
				break;
			}
		}

		if ( ! $src_source ) {
			// No src attribute.
			return false;
		}

		if ( ! isset( $extensions ) ) {
			$extensions = imagify_get_mime_types( 'image' );
			$extensions = array_keys( $extensions );
			$extensions = implode( '|', $extensions );
		}

		if ( ! preg_match( '@^(?<src>(?:(?:https?:)?//|/).+\.(?<extension>' . $extensions . '))(?<query>\?.*)?$@i', $attributes[ $src_source ], $src ) ) {
			// Not a supported image format.
			return false;
		}

		$webp_url  = imagify_path_to_webp( $src['src'] );
		$webp_path = $this->url_to_path( $webp_url );
		$webp_url .= ! empty( $src['query'] ) ? $src['query'] : '';

		$data = [
			'tag'              => $image,
			'attributes'       => $attributes,
			'src_attribute'    => $src_source,
			'src'              => [
				'url'         => $attributes[ $src_source ],
				'webp_url'    => $webp_url,
				'webp_path'   => $webp_path,
				'webp_exists' => $webp_path && $this->filesystem->exists( $webp_path ),
			],
			'srcset_attribute' => false,
			'srcset'           => [],
		];

		// Deal with the srcset attribute.
		$srcset_source = false;

		foreach ( [ 'data-lazy-srcset', 'data-srcset', 'srcset' ] as $srcset_attr ) {
			if ( ! empty( $attributes[ $srcset_attr ] ) ) {
				$srcset_source = $srcset_attr;
				break;
			}
		}

		if ( $srcset_source ) {
			$data['srcset_attribute'] = $srcset_source;

			$srcset = explode( ',', $attributes[ $srcset_source ] );

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

		if ( ! isset( $data['tag'], $data['attributes'], $data['src_attribute'], $data['src'], $data['srcset_attribute'], $data['srcset'] ) ) {
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
		static $uploads_url;
		static $uploads_dir;
		static $root_url;
		static $root_dir;
		static $cdn_url;
		static $domain_url;

		/**
		 * $url, $uploads_url, $root_url, and $cdn_url are passed through `set_url_scheme()` only to make sure `stripos()` doesn't fail over a stupid http/https difference.
		 */
		if ( ! isset( $uploads_url ) ) {
			$uploads_url = set_url_scheme( $this->filesystem->get_upload_baseurl() );
			$uploads_dir = $this->filesystem->get_upload_basedir( true );
			$root_url    = set_url_scheme( $this->filesystem->get_site_root_url() );
			$root_dir    = $this->filesystem->get_site_root();
			$cdn_url     = $this->get_cdn_source();
			$cdn_url     = $cdn_url['url'] ? set_url_scheme( $cdn_url['url'] ) : false;
			$domain_url  = wp_parse_url( $root_url );

			if ( ! empty( $domain_url['scheme'] ) && ! empty( $domain_url['host'] ) ) {
				$domain_url = $domain_url['scheme'] . '://' . $domain_url['host'] . '/';
			} else {
				$domain_url = false;
			}
		}

		// Get the right URL format.
		if ( $domain_url && strpos( $url, '/' ) === 0 ) {
			// URL like `/path/to/image.jpg.webp`.
			$url = $domain_url . ltrim( $url, '/' );
		}

		$url = set_url_scheme( $url );

		if ( $cdn_url && $domain_url && stripos( $url, $cdn_url ) === 0 ) {
			// CDN.
			$url = str_ireplace( $cdn_url, $domain_url, $url );
		}

		// Return the path.
		if ( stripos( $url, $uploads_url ) === 0 ) {
			return str_ireplace( $uploads_url, $uploads_dir, $url );
		}

		if ( stripos( $url, $root_url ) === 0 ) {
			return str_ireplace( $root_url, $root_dir, $url );
		}

		return false;
	}

	/**
	 * Get the CDN "source".
	 *
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $option_url An URL to use instead of the one stored in the option. It is used only if no constant/filter.
	 * @return array  {
	 *     @type string $source Where does it come from? Possible values are 'constant', 'filter', or 'option'.
	 *     @type string $name   Who? Can be a constant name, a plugin name, or an empty string.
	 *     @type string $url    The CDN URL, with a trailing slash. An empty string if no URL is set.
	 * }
	 */
	public function get_cdn_source( $option_url = '' ) {
		if ( defined( 'IMAGIFY_CDN_URL' ) && IMAGIFY_CDN_URL && is_string( IMAGIFY_CDN_URL ) ) {
			// Use a constant.
			$source = [
				'source' => 'constant',
				'name'   => 'IMAGIFY_CDN_URL',
				'url'    => IMAGIFY_CDN_URL,
			];
		} else {
			// Maybe use a filter.
			$filter_source = [
				'name' => null,
				'url'  => null,
			];

			/**
			 * Provide a custom CDN source.
			 *
			 * @since  1.9.3
			 * @author Grégory Viguier
			 *
			 * @param array $filter_source {
			 *     @type $name string The name of which provides the URL (plugin name, etc).
			 *     @type $url  string The CDN URL.
			 * }
			 */
			$filter_source = apply_filters( 'imagify_cdn_source', $filter_source );

			if ( ! empty( $filter_source['url'] ) ) {
				$source = [
					'source' => 'filter',
					'name'   => ! empty( $filter_source['name'] ) ? $filter_source['name'] : '',
					'url'    => $filter_source['url'],
				];
			}
		}

		if ( empty( $source['url'] ) ) {
			// No constant, no filter: use the option.
			$source = [
				'source' => 'option',
				'name'   => '',
				'url'    => $option_url && is_string( $option_url ) ? $option_url : get_imagify_option( 'cdn_url' ),
			];
		}

		if ( empty( $source['url'] ) ) {
			// Nothing set.
			return [
				'source' => 'option',
				'name'   => '',
				'url'    => '',
			];
		}

		$source['url'] = $this->sanitize_cdn_url( $source['url'] );

		if ( empty( $source['url'] ) ) {
			// Not an URL.
			return [
				'source' => 'option',
				'name'   => '',
				'url'    => '',
			];
		}

		return $source;
	}

	/**
	 * Sanitize the CDN URL value.
	 *
	 * @since  1.9.3
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $url The URL to sanitize.
	 * @return string
	 */
	public function sanitize_cdn_url( $url ) {
		$url = sanitize_text_field( $url );

		if ( ! $url || ! preg_match( '@^https?://.+\.[^.]+@i', $url ) ) {
			// Not an URL.
			return '';
		}

		return trailingslashit( $url );
	}
}
