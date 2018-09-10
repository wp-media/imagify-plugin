<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Round UP to nearest half integer.
 *
 * @since  1.0
 * @source http://stackoverflow.com/a/13526408
 *
 * @param  int|float|string $number  The number to round up.
 * @return float The formatted number.
 */
function imagify_round_half_five( $number ) {
	$number = strval( $number );
	$number = explode( '.', $number );

	if ( ! isset( $number[1] ) ) {
		return $number[0];
	}

	$decimal = floatval( '0.' . substr( $number[1], 0 , 2 ) ); // Cut only 2 numbers.

	if ( $decimal > 0 ) {
		if ( $decimal <= 0.5 ) {
			return floatval( $number[0] ) + 0.5;
		}
		if ( $decimal <= 0.99 ) {
			return floatval( $number[0] ) + 1;
		}
		return 1;
	}

	return floatval( $number );
}

/**
 * Convert number of bytes largest unit bytes will fit into.
 * This is a clone of size_format(), but with a non-breaking space.
 *
 * @since  1.7
 * @since  1.8.1 Automatic $decimals.
 * @author Grégory Viguier
 *
 * @param  int|string $bytes    Number of bytes. Note max integer size for integers.
 * @param  int        $decimals Optional. Precision of number of decimal places.
 *                              If negative or not an integer, $decimals value is "automatic": 0 if $bytes <= 1GB, or 1 if > 1GB.
 * @return string|false         False on failure. Number string on success.
 */
function imagify_size_format( $bytes, $decimals = -1 ) {

	if ( $decimals < 0 || ! is_int( $decimals ) ) {
		$decimals = $bytes > pow( 1024, 3 ) ? 1 : 0;
	}

	$bytes = @size_format( $bytes, $decimals );
	return str_replace( ' ', ' ', $bytes );
}

/**
 * Get the Imagify attachment class name depending to a context.
 *
 * @since  1.5
 * @since  1.6.6 $attachment_id and $identifier have been added.
 * @author Jonathan Buttigieg
 *
 * @param  string $context       The context to determine the class name.
 * @param  int    $attachment_id The attachment ID.
 * @param  string $identifier    An identifier.
 * @return string                The Imagify attachment class name.
 */
function get_imagify_attachment_class_name( $context, $attachment_id, $identifier ) {
	$context = $context ? $context : 'wp';

	if ( 'wp' !== $context && 'wp' === strtolower( $context ) ) {
		$context = 'wp';
	}

	/**
	 * Filter the context used for the optimization.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param string $context       The context.
	 * @param int    $attachment_id The attachment ID.
	 * @param string $identifier    An identifier.
	 */
	$context = apply_filters( 'imagify_optimize_attachment_context', $context, $attachment_id, $identifier );

	return 'Imagify_' . ( 'wp' !== $context ? $context . '_' : '' ) . 'Attachment';
}

/**
 * Get the Imagify attachment instance depending to a context.
 *
 * @since  1.6.13
 * @author Grégory Viguier
 *
 * @param  string $context       The context to determine the class name.
 * @param  int    $attachment_id The attachment ID.
 * @param  string $identifier    An identifier.
 * @return object                The Imagify attachment instance.
 */
function get_imagify_attachment( $context, $attachment_id, $identifier ) {
	$class_name = get_imagify_attachment_class_name( $context, $attachment_id, $identifier );
	return new $class_name( $attachment_id );
}
