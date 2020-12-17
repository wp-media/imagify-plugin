<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Imagify.io API for WordPress.
 */
class Imagify {
	use \Imagify\Traits\InstanceGetterTrait;

	/**
	 * The Imagify API endpoint.
	 *
	 * @var string
	 */
	const API_ENDPOINT = 'https://app.imagify.io/api/';

	/**
	 * The Imagify API key.
	 *
	 * @var string
	 */
	private $api_key = '';

	/**
	 * Random key used to store the API key in the request args.
	 *
	 * @var string
	 */
	private $secure_key = '';

	/**
	 * HTTP headers. Each http call must fill it (even if it's with an empty array).
	 *
	 * @var array
	 */
	private $headers = [];

	/**
	 * All (default) HTTP headers. They must not be modified once the class is instanciated, or it will affect any following HTTP calls.
	 *
	 * @var array
	 */
	private $all_headers = [];

	/**
	 * Filesystem object.
	 *
	 * @var    object Imagify_Filesystem
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $filesystem;

	/**
	 * Use data fetched from the API.
	 *
	 * @var    \stdClass|\WP_Error
	 * @since  1.9.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected static $user;

	/**
	 * The constructor.
	 */
	protected function __construct() {
		if ( ! class_exists( 'Imagify_Filesystem' ) ) {
			// Dirty patch used when updating from 1.7.
			include_once IMAGIFY_PATH . 'inc/classes/class-imagify-filesystem.php';
		}

		$this->api_key    = get_imagify_option( 'api_key' );
		$this->secure_key = $this->generate_secure_key();
		$this->filesystem = Imagify_Filesystem::get_instance();

		$this->all_headers['Accept']        = 'Accept: application/json';
		$this->all_headers['Content-Type']  = 'Content-Type: application/json';
		$this->all_headers['Authorization'] = 'Authorization: token ' . $this->api_key;
	}

	/**
	 * Get your Imagify account infos.
	 *
	 * @access public
	 * @since  1.6.5
	 *
	 * @return object
	 */
	public function get_user() {
		global $wp_current_filter;

		if ( isset( static::$user ) ) {
			return static::$user;
		}

		if ( in_array( 'upgrader_post_install', (array) $wp_current_filter, true ) ) {
			// Dirty patch used when updating from 1.7.
			static::$user = new WP_Error();
			return static::$user;
		}

		$this->headers = $this->all_headers;
		static::$user  = $this->http_call( 'users/me/', [ 'timeout' => 10 ] );

		if ( is_wp_error( static::$user ) ) {
			return static::$user;
		}

		$maybe_missing = [
			'account_type'                 => 'free',
			'quota'                        => 0,
			'extra_quota'                  => 0,
			'extra_quota_consumed'         => 0,
			'consumed_current_month_quota' => 0,
		];

		foreach ( $maybe_missing as $name => $value ) {
			if ( ! isset( static::$user->$name ) ) {
				static::$user->$name = $value;
			}
		}

		return static::$user;
	}

	/**
	 * Create a user on your Imagify account.
	 *
	 * @access public
	 * @since  1.6.5
	 *
	 * @param  array $data All user data.
	 * @return object
	 */
	public function create_user( $data ) {
		$this->headers = [];
		$data          = array_merge(
			$data,
			[
				'from_plugin' => true,
				'partner'     => imagify_get_partner(),
			]
		);

		if ( ! $data['partner'] ) {
			unset( $data['partner'] );
		}

		$response = $this->http_call(
			'users/',
			[
				'method'    => 'POST',
				'post_data' => $data,
			]
		);

		if ( ! is_wp_error( $response ) ) {
			imagify_delete_partner();
		}

		return $response;
	}

	/**
	 * Update an existing user on your Imagify account.
	 *
	 * @access public
	 * @since  1.6.5
	 *
	 * @param  string $data All user data.
	 * @return object
	 */
	public function update_user( $data ) {
		$this->headers = $this->all_headers;

		return $this->http_call(
			'users/me/',
			[
				'method'    => 'PUT',
				'post_data' => $data,
				'timeout'   => 10,
			]
		);
	}

	/**
	 * Check your Imagify API key status.
	 *
	 * @access public
	 * @since  1.6.5
	 *
	 * @param  string $data The license key.
	 * @return object
	 */
	public function get_status( $data ) {
		static $status = [];

		if ( isset( $status[ $data ] ) ) {
			return $status[ $data ];
		}

		$this->headers = [
			'Authorization' => 'Authorization: token ' . $data,
		];

		$uri     = 'status/';
		$partner = imagify_get_partner();

		if ( $partner ) {
			$uri .= '?partner=' . $partner;
		}

		$status[ $data ] = $this->http_call( $uri, [ 'timeout' => 10 ] );

		return $status[ $data ];
	}

	/**
	 * Get the Imagify API version.
	 *
	 * @access public
	 * @since  1.6.5
	 *
	 * @return object
	 */
	public function get_api_version() {
		static $api_version;

		if ( ! isset( $api_version ) ) {
			$this->headers = [
				'Authorization' => $this->all_headers['Authorization'],
			];

			$api_version = $this->http_call( 'version/', [ 'timeout' => 5 ] );
		}

		return $api_version;
	}

	/**
	 * Get Public Info.
	 *
	 * @access public
	 * @since  1.6.5
	 *
	 * @return object
	 */
	public function get_public_info() {
		$this->headers = $this->all_headers;

		return $this->http_call( 'public-info' );
	}

	/**
	 * Optimize an image from its binary content.
	 *
	 * @access public
	 * @since 1.6.5
	 * @since 1.6.7 $data['image'] can contain the file path (prefered) or the result of `curl_file_create()`.
	 *
	 * @param  string $data All options.
	 * @return object
	 */
	public function upload_image( $data ) {
		$this->headers = [
			'Authorization' => $this->all_headers['Authorization'],
		];

		return $this->http_call(
			'upload/',
			[
				'method'    => 'POST',
				'post_data' => $data,
			]
		);
	}

	/**
	 * Optimize an image from its URL.
	 *
	 * @access public
	 * @since  1.6.5
	 *
	 * @param  string $data All options. Details here: --.
	 * @return object
	 */
	public function fetch_image( $data ) {
		$this->headers = $this->all_headers;

		return $this->http_call(
			'fetch/',
			[
				'method'    => 'POST',
				'post_data' => wp_json_encode( $data ),
			]
		);
	}

	/**
	 * Get prices for plans.
	 *
	 * @access public
	 * @since  1.6.5
	 *
	 * @return object
	 */
	public function get_plans_prices() {
		$this->headers = $this->all_headers;

		return $this->http_call( 'pricing/plan/' );
	}

	/**
	 * Get prices for packs (One Time).
	 *
	 * @access public
	 * @since  1.6.5
	 *
	 * @return object
	 */
	public function get_packs_prices() {
		$this->headers = $this->all_headers;

		return $this->http_call( 'pricing/pack/' );
	}

	/**
	 * Get all prices (packs & plans included).
	 *
	 * @access public
	 * @since  1.6.5
	 *
	 * @return object
	 */
	public function get_all_prices() {
		$this->headers = $this->all_headers;

		return $this->http_call( 'pricing/all/' );
	}

	/**
	 * Get all prices (packs & plans included).
	 *
	 * @access public
	 * @since  1.6.5
	 *
	 * @param  string $coupon A coupon code.
	 * @return object
	 */
	public function check_coupon_code( $coupon ) {
		$this->headers = $this->all_headers;

		return $this->http_call( 'coupons/' . $coupon . '/' );
	}

	/**
	 * Get information about current discount.
	 *
	 * @access public
	 * @since  1.6.5
	 *
	 * @return object
	 */
	public function check_discount() {
		$this->headers = $this->all_headers;

		return $this->http_call( 'pricing/discount/' );
	}

	/**
	 * Make an HTTP call using curl.
	 *
	 * @access private
	 * @since  1.6.5
	 * @since  1.6.7 Use `wp_remote_request()` when possible (when we don't need to send an image).
	 *
	 * @param  string $url  The URL to call.
	 * @param  array  $args The request args.
	 * @return object
	 */
	private function http_call( $url, $args = [] ) {
		$args = array_merge(
			[
				'method'    => 'GET',
				'post_data' => null,
				'timeout'   => 45,
			],
			$args
		);

		$endpoint = trim( $url, '/' );
		/**
		 * Filter the timeout value for any request to the API.
		 *
		 * @since  1.6.7
		 * @author Grégory Viguier
		 *
		 * @param int    $timeout  Timeout value in seconds.
		 * @param string $endpoint The targetted endpoint. It's basically URI without heading nor trailing slash.
		 */
		$args['timeout'] = apply_filters( 'imagify_api_http_request_timeout', $args['timeout'], $endpoint );

		// We need to send an image: we must use cURL directly.
		if ( isset( $args['post_data']['image'] ) ) {
			return $this->curl_http_call( $url, $args );
		}

		$args = array_merge(
			[
				'headers'   => [],
				'body'      => $args['post_data'],
				'sslverify' => apply_filters( 'https_ssl_verify', false ),
			],
			$args
		);

		unset( $args['post_data'] );

		if ( $this->headers ) {
			foreach ( $this->headers as $name => $value ) {
				$value = explode( ':', $value, 2 );
				$value = end( $value );

				$args['headers'][ $name ] = trim( $value );
			}
		}

		if ( ! empty( $args['headers']['Authorization'] ) ) {
			// Make sure our API has not overwritten by some other plugin.
			$args[ $this->secure_key ] = preg_replace( '/^token /', '', $args['headers']['Authorization'] );

			if ( ! has_filter( 'http_request_args', [ $this, 'force_api_key_header' ] ) ) {
				add_filter( 'http_request_args', [ $this, 'force_api_key_header' ], IMAGIFY_INT_MAX + 25, 2 );
			}
		}

		$response = wp_remote_request( self::API_ENDPOINT . $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$response  = wp_remote_retrieve_body( $response );

		return $this->handle_response( $response, $http_code );
	}

	/**
	 * Make an HTTP call using curl.
	 *
	 * @access private
	 * @since  1.6.7
	 * @throws Exception When curl_init() fails.
	 * @author Grégory Viguier
	 *
	 * @param  string $url  The URL to call.
	 * @param  array  $args The request arguments.
	 * @return object
	 */
	private function curl_http_call( $url, $args = [] ) {
		// Check if curl is available.
		if ( ! Imagify_Requirements::supports_curl() ) {
			return new WP_Error( 'curl', 'cURL isn\'t installed on the server.' );
		}

		try {
			$url = self::API_ENDPOINT . $url;
			$ch  = curl_init();

			if ( false === $ch ) {
				throw new Exception( 'Could not initialize a new cURL handle' );
			}

			if ( isset( $args['post_data']['image'] ) && is_string( $args['post_data']['image'] ) && $this->filesystem->exists( $args['post_data']['image'] ) ) {
				$args['post_data']['image'] = curl_file_create( $args['post_data']['image'] );
			}

			// Handle proxies.
			$proxy = new WP_HTTP_Proxy();

			if ( $proxy->is_enabled() && $proxy->send_through_proxy( $url ) ) {
				curl_setopt( $ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP );
				curl_setopt( $ch, CURLOPT_PROXY, $proxy->host() );
				curl_setopt( $ch, CURLOPT_PROXYPORT, $proxy->port() );

				if ( $proxy->use_authentication() ) {
					curl_setopt( $ch, CURLOPT_PROXYAUTH, CURLAUTH_ANY );
					curl_setopt( $ch, CURLOPT_PROXYUSERPWD, $proxy->authentication() );
				}
			}

			if ( 'POST' === $args['method'] ) {
				curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $args['post_data'] );
			} elseif ( 'PUT' === $args['method'] ) {
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT' );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $args['post_data'] );
			}

			if ( defined( 'CURLOPT_PROTOCOLS' ) ) {
				curl_setopt( $ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS );
			}

			$user_agent = apply_filters( 'http_headers_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ) );

			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_HEADER, false );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->headers );
			curl_setopt( $ch, CURLOPT_TIMEOUT, $args['timeout'] );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $args['timeout'] );
			curl_setopt( $ch, CURLOPT_USERAGENT, $user_agent );
			@curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

			/**
			 * Tell which http version to use with cURL during image optimization.
			 *
			 * @since  1.8.4.1
			 * @since  1.9.9 Default value is `false`.
			 * @author Grégory Viguier
			 *
			 * @param $use_version_1_0 bool True to use version 1.0. False for 1.1. Default is false.
			 */
			if ( apply_filters( 'imagify_curl_http_version_1_0', false ) ) {
				curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0 );
			} else {
				curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
			}

			$response  = curl_exec( $ch );
			$error     = curl_error( $ch );
			$http_code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );

			if ( is_resource( $ch ) ) {
				curl_close( $ch );
			} else {
				unset( $ch );
			}
		} catch ( Exception $e ) {
			$args['headers'] = $this->headers;
			/**
			 * Fires after a failed curl request.
			 *
			 * @since  1.6.9
			 * @author Grégory Viguier
			 *
			 * @param string $url  The requested URL.
			 * @param array  $args The request arguments.
			 * @param object $e    The raised Exception.
			 */
			do_action( 'imagify_curl_http_response', $url, $args, $e );

			return new WP_Error( 'curl', 'An error occurred (' . $e->getMessage() . ')' );
		} // End try().

		$args['headers'] = $this->headers;

		/**
		 * Fires after a successful curl request.
		 *
		 * @since  1.6.9
		 * @author Grégory Viguier
		 *
		 * @param string $url       The requested URL.
		 * @param array  $args      The request arguments.
		 * @param string $response  The request response.
		 * @param int    $http_code The request HTTP code.
		 * @param string $error     An error message.
		 */
		do_action( 'imagify_curl_http_response', $url, $args, $response, $http_code, $error );

		return $this->handle_response( $response, $http_code, $error );
	}

	/**
	 * Handle the request response and maybe trigger an error.
	 *
	 * @access private
	 * @since  1.6.7
	 * @author Grégory Viguier
	 *
	 * @param  string $response  The request response.
	 * @param  int    $http_code The request HTTP code.
	 * @param  string $error     An error message.
	 * @return object
	 */
	private function handle_response( $response, $http_code, $error = '' ) {
		$response = json_decode( $response );

		if ( 401 === $http_code ) {
			// Reset the API validity cache if the API key is not valid.
			Imagify_Requirements::reset_cache( 'api_key_valid' );
		}

		if ( 200 !== $http_code && ! empty( $response->code ) ) {
			if ( ! empty( $response->detail ) ) {
				return new WP_Error( 'error ' . $http_code, $response->detail );
			}
			if ( ! empty( $response->image ) ) {
				$error = (array) $response->image;
				$error = reset( $error );
				return new WP_Error( 'error ' . $http_code, $error );
			}
		}

		if ( 413 === $http_code ) {
			return new WP_Error( 'error ' . $http_code, 'Your image is too big to be uploaded on our server.' );
		}

		if ( 200 !== $http_code ) {
			$error = trim( (string) $error );
			$error = '' !== $error ? ' - ' . htmlentities( $error ) : '';
			return new WP_Error( 'error ' . $http_code, "Our server returned an error ({$http_code}{$error})" );
		}

		if ( ! is_object( $response ) ) {
			return new WP_Error( 'invalid response', 'Our server returned an invalid response.', $response );
		}

		return $response;
	}

	/**
	 * Generate a random key.
	 * Similar to wp_generate_password() but without filter.
	 *
	 * @access private
	 * @since  1.8.4
	 * @see    wp_generate_password()
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	private function generate_secure_key() {
		$length   = wp_rand( 12, 20 );
		$chars    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';
		$password = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$password .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
		}

		return $password;
	}

	/**
	 * Filter the arguments used in an HTTP request, to make sure our API key has not been overwritten by some other plugin.
	 *
	 * @access public
	 * @since  1.8.4
	 * @author Grégory Viguier
	 *
	 * @param  array  $args An array of HTTP request arguments.
	 * @param  string $url  The request URL.
	 * @return array
	 */
	public function force_api_key_header( $args, $url ) {
		if ( strpos( $url, self::API_ENDPOINT ) === false ) {
			return $args;
		}

		if ( ! empty( $args['headers']['Authorization'] ) || ! empty( $args[ $this->secure_key ] ) ) {
			if ( ! empty( $args[ $this->secure_key ] ) ) {
				$args['headers']['Authorization'] = 'token ' . $args[ $this->secure_key ];
			} else {
				$args['headers']['Authorization'] = 'token ' . $this->api_key;
			}
		}

		return $args;
	}
}
