<?php

/**
 * Create a new user on Imagify.
 *
 * @param  array $data All user data.
 * @return object
 **/
function add_imagify_user( $data ) {
	return Imagify()->createUser( $data );
}

/**
 * Update your Imagify account.
 *
 * @param  string $data All user data.
 * @return object
 **/
function update_imagify_user( $data ) {
	return Imagify()->updateUser( $data );
}

/**
 * Get your Imagify account infos.
 *
 * @return object
 **/
function get_imagify_user() {
	return Imagify()->getUser();
}

/**
 * Get the Imagify API version.
 *
 * @return object
 **/
function get_imagify_api_version() {
	return Imagify()->getApiVersion();
}

/**
 * Check your Imagify API key status.
 *
 * @return bool
 **/
function get_imagify_status( $data ) {
	return Imagify()->getStatus( $data );
}

/**
 * Optimize an image by uploading it on Imagify.
 *
 * @param  array $data All image data.
 * @return object
 **/
function fetch_imagify_image( $data ) {
	return Imagify()->fetchImage( $data );
}

/**
 * Optimize an image by sharing its URL on Imagify.
 *
 * @param  array $data All image data.
 * @return object
 **/
function upload_imagify_image( $data ) {
	return Imagify()->uploadImage( $data );
}

/**
 * Get Imagify Plans Prices
 *
 * @return object
 * @since 1.5
 * @author Geoffrey Crofte
 **/
function get_imagify_plans_prices() {
	return Imagify()->getPlansPrices();
}

/**
 * Get Imagify Plans Prices
 *
 * @return object
 * @since 1.5
 * @author Geoffrey Crofte
 **/
function get_imagify_packs_prices() {
	return Imagify()->getPacksPrices();
}

/**
 * Imagify.io API for WordPress
 */
class Imagify {
    /**
     * The Imagify API endpoint
     */
    const API_ENDPOINT = 'https://app.imagify.io/api/';

    /**
     * The Imagify API key
     */
    private $apiKey = '';

	/**
     * HTTP headers
     */
    private $headers = array();

    /**
	 * @var The single instance of the class
	 */
	protected static $_instance = null;

    /**
     * The constructor
     *
     * @return void
     **/
    public function __construct()
    {
		// check if the WordPress plugin is activated and the API key is stored in the options
		if ( defined( 'IMAGIFY_VERSION' ) && function_exists( 'get_imagify_option' ) ) {
	        $apiKey 	  = get_imagify_option( 'api_key', false );
	        $this->apiKey = ( $apiKey ) ? $apiKey : $this->apiKey;
        }

		// check if the API key is defined with the PHP constant (it's ovveride the WordPress plugin option
        if ( defined( 'IMAGIFY_API_KEY' ) && IMAGIFY_API_KEY ) {
	        $this->apiKey = IMAGIFY_API_KEY;
        }

        $this->headers['Accept']        = 'Accept: application/json';
        $this->headers['Content-Type']  = 'Content-Type: application/json';
        $this->headers['Authorization'] = 'Authorization: token ' . $this->apiKey;
    }

    /**
	 * Main Imagify Instance
	 *
	 * Ensures only one instance of class is loaded or can be loaded.
	 *
	 * @static
	 * @return Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

    /**
     * Create a user on your Imagify account.
     *
     * @param  array $data All user data. Details here: --
     * @return object
     **/
    public function createUser( $data ) {
	    unset( $this->headers['Authorization'], $this->headers['Accept'], $this->headers['Content-Type'] );

		$data['from_plugin'] = true;
		$args = array(
			'method'    => 'POST',
			'post_data' => $data
		);
		
        return $this->httpCall( 'users/', $args );
    }

	/**
     * Get your Imagify account infos.
     *
     * @return object
     **/
    public function getUser() {
		static $user;

        if ( ! isset( $user ) ) {
            $user = $this->httpCall( 'users/me/', array( 'timeout' => 10 ) );
        }

        return $user;
    }

    /**
     * Check your Imagify API key status.
     *
     * @return object
     **/
    public function getStatus( $data ) {
	    static $status;

	    if ( ! isset( $status ) ) {
			unset( $this->headers['Accept'], $this->headers['Content-Type'] );
	        $this->headers['Authorization'] = 'Authorization: token ' . $data;

	        $status = $this->httpCall( 'status/', array( 'timeout' => 10 ) );
	    }

	    return $status;
    }

    /**
     * Get the Imagify API version.
     *
     * @return object
     **/
    public function getApiVersion() {
	    static $api_version;

	    if ( ! isset( $api_version ) ) {
            unset( $this->headers['Accept'], $this->headers['Content-Type'] );

            $api_version = $this->httpCall( 'version/', array( 'timeout' => 5 ) );
        }

	    return $api_version;
    }

    /**
     * Update an existing user on your Imagify account.
     *
     * @param  string $data All user data. Details here: --
     * @return object
     **/
    public function updateUser( $data ) {
        $args = array(
	    	'method'    => 'PUT',
	    	'post_data' => $data,
	    	'timeout'   => 10  
        );
        
        return $this->httpCall( 'users/me/', $args );
    }

    /**
     * Optimize an image from its binary content.
     *
     * @param  string $data All options. Details here: --
     * @return object
     **/
    public function uploadImage( $data ) {
		if ( isset( $this->headers['Accept'], $this->headers['Content-Type'] ) ) {
	        unset( $this->headers['Accept'], $this->headers['Content-Type'] );
        }
		
		$args = array(
			'method'    => 'POST',
			'post_data' => $data
		);
		
		return $this->httpCall( 'upload/', $args );
    }

    /**
     * Optimize an image from its URL.
     *
     * @param  string $data All options. Details here: --
     * @return object
     **/
    public function fetchImage( $data ) {
		$args = array(
			'method'    => 'POST',
			'post_data' => json_encode( $data )
		);
		return $this->httpCall( 'fetch/', $args );
    }

    /**
     * Get prices for plans
     *
     * @return object
     */
    public function getPlansPrices() {
        return $this->httpCall( 'pricing/plan/' );
    }

    /**
     * Get prices for packs (one time)
     *
     * @return object
     */
    public function getPacksPrices() {
        return $this->httpCall( 'pricing/pack/' );
    }

	/**
     * Make an HTTP call using curl.
     *
     * @param  string $url  The URL to call
     * @param  array $args  The request args
     * @return object
     **/
    private function httpCall( $url, $args = array() ) {
        $default = array( 
        	'method'    => 'GET', 
        	'post_data' => null, 
        	'timeout'   => 45 
        );
		$args = array_merge( $default, $args );

        // Check if php-curl is enabled
		if ( ! function_exists( 'curl_init' ) || ! function_exists( 'curl_exec' ) ) {
			return new WP_Error( 'curl', 'cURL isn\'t installed on the server.' );
		}

        try {
	    	$ch = curl_init();

	        if ( 'POST' == $args['method'] ) {
		        curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $args['post_data'] );
	        }

			curl_setopt( $ch, CURLOPT_URL, self::API_ENDPOINT . $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->headers );
			curl_setopt( $ch, CURLOPT_TIMEOUT, $args['timeout'] );
			@curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );

			$response  = json_decode( curl_exec( $ch ) );
	        $error     = curl_error( $ch );
	        $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

			curl_close( $ch );
        } catch( Exception $e ) {
	        return new WP_Error( 'curl', 'Unknown error occurred' );
        }

		if ( 200 != $http_code && isset( $response->code, $response->detail ) ) {
			return new WP_Error( $http_code, $response->detail );
		} elseif ( 200 != $http_code ) {
            $http_code = (int) $http_code;
            $error     = '' != $error ? ' - ' . htmlentities( $error ) : '';
			return new WP_Error( $http_code, "Unknown error occurred ({$http_code}{$error}) " );
		} else {
			return $response;
        }

		return $response;
    }
}

/**
 * Returns the main instance of Imagify to prevent the need to use globals.
 */
function Imagify() {
	return Imagify::instance();
}
$GLOBALS['imagify'] = Imagify();