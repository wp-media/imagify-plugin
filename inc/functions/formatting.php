<?php 
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Round UP to nearest half integer
 *
 * @since 1.0
 * @source http://stackoverflow.com/a/13526408
 *
 * @param  int $number  The number to round up
 * @return int $number  The formatted number
 */
function imagify_round_half_five( $number ) {
    $number = strval( $number );
    $number = explode( '.', $number );
      
    if ( ! isset( $number[1] ) ) {
	    return $number[0];
    }
    
    $decimal = floatval( '0.' . substr( $number[1], 0 , 2 ) ); // cut only 2 number
    if ( $decimal > 0 ) {
        if( $decimal <= 0.5 ) {
            return floatval( $number[0] ) + 0.5;
        } elseif ( $decimal > 0.5 && $decimal <= 0.99 ) {
            return floatval( $number[0]) + 1;
        }
    } else {
        return floatval( $number );
    }
}

/**
 * Get the Imagify attachment class name depending to a context
 *
 * @since 1.5
 * @source Jonathan Buttigieg
 *
 * @param  string $context     The context to determine the class name
 * @return string $class_name  The Imagify attachment class name
 */
function get_imagify_attachment_class_name( $context = 'wp' ) {
	$class_name  = 'Imagify_';
	$class_name .= 'wp' !== $context ? $context . '_Attachment' : 'Attachment';
	
	return $class_name;
}