<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Returns the main instance of the Imagify class.
 *
 * @since  1.6.5
 * @author Grégory Viguier
 *
 * @return object The Imagify instance.
 */
function imagify() {
	return Imagify::get_instance();
}

/**
 * Create a new user on Imagify.
 *
 * @param  array $data All user data.
 * @return object
 */
function add_imagify_user( $data ) {
	return imagify()->create_user( $data );
}

/**
 * Update your Imagify account.
 *
 * @param  string $data All user data.
 * @return object
 */
function update_imagify_user( $data ) {
	return imagify()->update_user( $data );
}

/**
 * Get your Imagify account infos.
 *
 * @return object
 */
function get_imagify_user() {
	return imagify()->get_user();
}

/**
 * Get the Imagify API version.
 *
 * @return object
 */
function get_imagify_api_version() {
	return imagify()->get_api_version();
}

/**
 * Check your Imagify API key status.
 *
 * @param  string $data An API key.
 * @return bool
 */
function get_imagify_status( $data ) {
	return imagify()->get_status( $data );
}

/**
 * Optimize an image by uploading it on Imagify.
 *
 * @param  array $data All image data.
 * @return object
 */
function fetch_imagify_image( $data ) {
	return imagify()->fetch_image( $data );
}

/**
 * Optimize an image by sharing its URL on Imagify.
 *
 * @since 1.6.7 $data['image'] can contain the file path (prefered) or the result of `curl_file_create()`.
 *
 * @param  array $data All image data.
 * @return object
 */
function upload_imagify_image( $data ) {
	return imagify()->upload_image( $data );
}

/**
 * Get Imagify Plans Prices.
 *
 * @since  1.5
 * @author Geoffrey Crofte
 *
 * @return object
 */
function get_imagify_plans_prices() {
	return imagify()->get_plans_prices();
}

/**
 * Get Imagify Plans Prices.
 *
 * @since  1.5
 * @author Geoffrey Crofte
 *
 * @return object
 */
function get_imagify_packs_prices() {
	return imagify()->get_packs_prices();
}

/**
 * Get Imagify All Prices (plan & packs).
 *
 * @since  1.5.4
 * @author Geoffrey Crofte
 *
 * @return object
 */
function get_imagify_all_prices() {
	return imagify()->get_all_prices();
}

/**
 * Check if Coupon Code exists.
 *
 * @since  1.6
 * @author Geoffrey Crofte
 *
 * @param  string $coupon the coupon code to check.
 * @return object
 */
function check_imagify_coupon_code( $coupon ) {
	return imagify()->check_coupon_code( $coupon );
}

/**
 * Check if Discount/Promotion is available.
 *
 * @since  1.6.3
 * @author Geoffrey Crofte
 *
 * @return object
 */
function check_imagify_discount() {
	return imagify()->check_discount();
}

/**
 * Get Maximum image size for free plan.
 *
 * @since  1.5.6
 * @author Remy Perona
 *
 * @return string
 */
function get_imagify_max_image_size() {
	$max_image_size = get_transient( 'imagify_max_image_size' );

	if ( false === $max_image_size ) {
		$max_image_size = imagify()->get_public_info();

		if ( ! is_wp_error( $max_image_size ) ) {
			$max_image_size = $max_image_size->max_image_size;
			set_transient( 'imagify_max_image_size', $max_image_size, 6 * HOUR_IN_SECONDS );
		}
	}

	return $max_image_size;
}

/**
 * Translate a message from our servers.
 *
 * @since  1.6.10
 * @author Grégory Viguier
 * @see    Imagify::curl_http_call()
 * @see    Imagify::handle_response()
 *
 * @param  string $message The message from the server (in English).
 * @return string          If in our list, the translated message. The original message otherwise.
 */
function imagify_translate_api_message( $message ) {
	if ( ! $message ) {
		return imagify_translate_api_message( 'Unknown error occurred' );
	}

	if ( is_wp_error( $message ) ) {
		if ( $message->errors ) {
			foreach ( (array) $message->errors as $code => $messages ) {
				if ( $messages ) {
					$message->errors[ $code ] = array_map( 'imagify_translate_api_message', (array) $messages );
				}
			}
		}

		return $message;
	}

	if ( is_object( $message ) && ! empty( $message->detail ) ) {
		$message->detail = imagify_translate_api_message( $message->detail );
	}

	if ( ! is_string( $message ) ) {
		return $message;
	}

	$trim_message = trim( $message, '. ' );

	$messages = array(
		// Local messages from Imagify::curl_http_call() and Imagify::handle_response().
		'Could not initialize a new cURL handle'                                                   => __( 'Could not initialize a new cURL handle.', 'imagify' ),
		'Unknown error occurred'                                                                   => __( 'Unknown error occurred.', 'imagify' ),
		'Your image is too big to be uploaded on our server'                                       => __( 'Your file is too big to be uploaded on our server.', 'imagify' ),
		'cURL isn\'t installed on the server'                                                      => __( 'cURL is not available on the server.', 'imagify' ),
		// API messages.
		'Authentification not provided'                                                            => __( 'Authentication not provided.', 'imagify' ),
		'Cannot create client token'                                                               => __( 'Cannot create client token.', 'imagify' ),
		'Confirm your account to continue optimizing image'                                        => __( 'Confirm your account to continue optimizing files.', 'imagify' ),
		'Coupon doesn\'t exist'                                                                    => __( 'Coupon does not exist.', 'imagify' ),
		'Email field shouldn\'t be empty'                                                          => __( 'Email field should not be empty.', 'imagify' ),
		'Email or Password field shouldn\'t be empty'                                              => __( 'This account already exists.', 'imagify' ),
		'Error uploading to data Storage'                                                          => __( 'Error uploading to Data Storage.', 'imagify' ),
		'Not able to connect to Data Storage API to get the token'                                 => __( 'Unable to connect to Data Storage API to get the token.', 'imagify' ),
		'Not able to connect to Data Storage API'                                                  => __( 'Unable to connect to Data Storage API.', 'imagify' ),
		'Not able to retrieve the token from DataStorage API'                                      => __( 'Unable to retrieve the token from Data Storage API.', 'imagify' ),
		'This email is already registered, you should try another email'                           => __( 'This email is already registered, you should try another email.', 'imagify' ),
		'This user doesn\'t exit'                                                                  => __( 'This user does not exist.', 'imagify' ),
		'Too many request, be patient'                                                             => __( 'Too many requests, please be patient.', 'imagify' ),
		'Unable to regenerate access token'                                                        => __( 'Unable to regenerate access token.', 'imagify' ),
		'User not valid'                                                                           => __( 'User not valid.', 'imagify' ),
		'WELL DONE. This image is already compressed, no further compression required'             => __( 'WELL DONE. This media file is already optimized, no further optimization is required.', 'imagify' ),
		'You are not authorized to perform this action'                                            => __( 'You are not authorized to perform this action.', 'imagify' ),
		'You\'ve consumed all your data. You have to upgrade your account to continue'             => __( 'You have consumed all your data. You have to upgrade your account to continue.', 'imagify' ),
		'Invalid token'                                                                            => __( 'Invalid API key', 'imagify' ),
		'Upload a valid image. The file you uploaded was either not an image or a corrupted image' => __( 'Invalid or corrupted file.', 'imagify' ),
	);

	if ( isset( $messages[ $trim_message ] ) ) {
		return $messages[ $trim_message ];
	}

	// Local message.
	if ( preg_match( '@^(?:Unknown|An) error occurred \((.+)\)$@', $trim_message, $matches ) ) {
		/* translators: %s is an error message. */
		return sprintf( __( 'An error occurred (%s).', 'imagify' ), esc_html( strip_tags( $matches[1] ) ) );
	}

	// API message.
	if ( preg_match( '@^Custom one time plan starts from (\d+) MB$@', $trim_message, $matches ) ) {
		/* translators: %s is a formatted number, dont use %d. */
		return sprintf( __( 'Custom One Time plan starts from %s MB.', 'imagify' ), number_format_i18n( (int) $matches[1] ) );
	}

	// API message.
	if ( preg_match( '@^(.*) is not a valid extension$@', $trim_message, $matches ) ) {
		/* translators: %s is a file extension. */
		return sprintf( __( '%s is not a valid extension.', 'imagify' ), sanitize_text_field( $matches[1] ) );
	}

	return $message;
}
