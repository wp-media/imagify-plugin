<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Round UP to nearest half integer.
 *
 * @since 1.0
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
	/**
	 * Filter the context used for the optimization.
	 *
	 * @since 1.6.6
	 * @author Grégory Viguier
	 *
	 * @param string $context       The context.
	 * @param int    $attachment_id The attachment ID.
	 * @param string $identifier    An identifier.
	 */
	$context = apply_filters( 'imagify_optimize_attachment_context', $context, $attachment_id, $identifier );

	return 'Imagify_' . ( 'wp' !== $context ? $context . '_' : '' ) . 'Attachment';
}
