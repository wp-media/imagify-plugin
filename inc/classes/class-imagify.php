<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Imagify.io API for WordPress.
 */
class Imagify extends Imagify_Deprecated {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.6';
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
	 * HTTP headers. Each http call must fill it (even if it's with an empty array).
	 *
	 * @var array
	 */
	private $headers = array();

	/**
	 * All (default) HTTP headers. They must not be modified once the class is instanciated, or it will affect any following HTTP calls.
	 *
	 * @var array
	 */
	private $all_headers = array();

	/**
	 * The single instance of the class.
	 *
	 * @access  protected
	 *
	 * @var object
	 */
	protected static $_instance;

	/**
	 * The constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		// Check if the WordPress plugin is activated and the API key is stored in the options.
		if ( defined( 'IMAGIFY_VERSION' ) && function_exists( 'get_imagify_option' ) ) {
			$api_key       = get_imagify_option( 'api_key', false );
			$this->api_key = $api_key ? $api_key : $this->api_key;
		}

		// Check if the API key is defined with the PHP constant (it's ovveride the WordPress plugin option.
		if ( defined( 'IMAGIFY_API_KEY' ) && IMAGIFY_API_KEY ) {
			$this->api_key = IMAGIFY_API_KEY;
		}

		$this->all_headers['Accept']        = 'Accept: application/json';
		$this->all_headers['Content-Type']  = 'Content-Type: application/json';
		$this->all_headers['Authorization'] = 'Authorization: token ' . $this->api_key;
	}

	/**
	 * Get the main Instance.
	 *
	 * @access  public
	 * @since   1.6.5
	 * @author  Grégory Viguier
	 *
	 * @return object Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get your Imagify account infos.
	 *
	 * @access  public
	 * @since   1.6.5
	 *
	 * @return object
	 */
	public function get_user() {
		static $user;

		if ( ! isset( $user ) ) {
			$this->headers = $this->all_headers;

			$user = $this->http_call( 'users/me/', array(
				'timeout' => 10,
			) );
		}

		return $user;
	}

	/**
	 * Create a user on your Imagify account.
	 *
	 * @access  public
	 * @since   1.6.5
	 *
	 * @param  array $data All user data. Details here: --.
	 * @return object
	 */
	public function create_user( $data ) {
		$this->headers       = array();
		$data['from_plugin'] = true;

		return $this->http_call( 'users/', array(
			'method'    => 'POST',
			'post_data' => $data,
		) );
	}

	/**
	 * Update an existing user on your Imagify account.
	 *
	 * @access  public
	 * @since   1.6.5
	 *
	 * @param  string $data All user data. Details here: --.
	 * @return object
	 */
	public function update_user( $data ) {
		$this->headers = $this->all_headers;

		return $this->http_call( 'users/me/', array(
			'method'    => 'PUT',
			'post_data' => $data,
			'timeout'   => 10,
		) );
	}

	/**
	 * Check your Imagify API key status.
	 *
	 * @access  public
	 * @since   1.6.5
	 *
	 * @param  string $data The license key.
	 * @return object
	 */
	public function get_status( $data ) {
		static $status = array();

		if ( ! isset( $status[ $data ] ) ) {
			$this->headers = array(
				'Authorization' => 'Authorization: token ' . $data,
			);

			$status[ $data ] = $this->http_call( 'status/', array(
				'timeout' => 10,
			) );
		}

		return $status[ $data ];
	}

	/**
	 * Get the Imagify API version.
	 *
	 * @access  public
	 * @since   1.6.5
	 *
	 * @return object
	 */
	public function get_api_version() {
		static $api_version;

		if ( ! isset( $api_version ) ) {
			$this->headers = array(
				'Authorization' => $this->all_headers['Authorization'],
			);

			$api_version = $this->http_call( 'version/', array(
				'timeout' => 5,
			) );
		}

		return $api_version;
	}

	/**
	 * Get Public Info.
	 *
	 * @access  public
	 * @since   1.6.5
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
	 * @since  1.6.5
	 * @since  1.6.7 $data['image'] can contain the file path (prefered) or the result of `curl_file_create()`.
	 *
	 * @param  string $data All options.
	 * @return object
	 */
	public function upload_image( $data ) {
		$this->headers = array(
			'Authorization' => $this->all_headers['Authorization'],
		);

		return $this->http_call( 'upload/', array(
			'method'    => 'POST',
			'post_data' => $data,
		) );
	}

	/**
	 * Optimize an image from its URL.
	 *
	 * @access  public
	 * @since   1.6.5
	 *
	 * @param  string $data All options. Details here: --.
	 * @return object
	 */
	public function fetch_image( $data ) {
		$this->headers = $this->all_headers;

		return $this->http_call( 'fetch/', array(
			'method'    => 'POST',
			'post_data' => wp_json_encode( $data ),
		) );
	}

	/**
	 * Get prices for plans.
	 *
	 * @access  public
	 * @since   1.6.5
	 *
	 * @return object
	 */
	public function get_plans_prices() {
		$this->headers = $this->all_headers;

		return $this->http_call( 'pricing/plan/' );
	}

	/**
	 * Get prices for packs (one time).
	 *
	 * @access  public
	 * @since   1.6.5
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
	 * @access  public
	 * @since   1.6.5
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
	 * @access  public
	 * @since   1.6.5
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
	 * @access  public
	 * @since   1.6.5
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
	private function http_call( $url, $args = array() ) {
		$args = array_merge( array(
			'method'    => 'GET',
			'post_data' => null,
			'timeout'   => 45,
		), $args );

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

		$args = array_merge( array(
			'headers'   => array(),
			'body'      => $args['post_data'],
			'sslverify' => apply_filters( 'https_ssl_verify', false ),
		), $args );

		unset( $args['post_data'] );

		if ( $this->headers ) {
			foreach ( $this->headers as $name => $value ) {
				$value = explode( ':', $value, 2 );
				$value = end( $value );

				$args['headers'][ $name ] = trim( $value );
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
	 * @author Grégory Viguier
	 *
	 * @param  string $url  The URL to call.
	 * @param  array  $args The request args.
	 * @return object
	 */
	private function curl_http_call( $url, $args = array() ) {
		// Check if php-curl is enabled.
		if ( ! function_exists( 'curl_init' ) || ! function_exists( 'curl_exec' ) ) {
			return new WP_Error( 'curl', 'cURL isn\'t installed on the server.' );
		}

		try {
			$ch = curl_init();

			if ( isset( $args['post_data']['image'] ) && is_string( $args['post_data']['image'] ) && file_exists( $args['post_data']['image'] ) ) {
				$args['post_data']['image'] = curl_file_create( $args['post_data']['image'] );
			}

			if ( 'POST' === $args['method'] ) {
				curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $args['post_data'] );
			} elseif ( 'PUT' === $args['method'] ) {
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT' );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $args['post_data'] );
			}

			curl_setopt( $ch, CURLOPT_URL, self::API_ENDPOINT . $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->headers );
			curl_setopt( $ch, CURLOPT_TIMEOUT, $args['timeout'] );
			@curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );

			$response  = curl_exec( $ch );
			$error     = curl_error( $ch );
			$http_code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );

			curl_close( $ch );
		} catch ( Exception $e ) {
			return new WP_Error( 'curl', 'Unknown error occurred' );
		}

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

		if ( 200 !== $http_code && isset( $response->code, $response->detail ) ) {
			return new WP_Error( $http_code, $response->detail );
		}

		if ( 413 === $http_code ) {
			return new WP_Error( $http_code, 'Your image is too big to be uploaded on our server.' );
		}

		if ( 200 !== $http_code ) {
			$error = '' !== $error ? ' - ' . htmlentities( $error ) : '';
			return new WP_Error( $http_code, "Unknown error occurred ({$http_code} {$error})" );
		}

		return $response;
	}
}

/**
 * Returns the main instance of the Imagify class.
 *
 * @since 1.6.5
 * @author Grégory Viguier
 *
 * @return object The Imagify instance.
 */
function imagify() {
	return Imagify::get_instance();
}
