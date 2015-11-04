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
      
    if ( (bool) ! $number[1] ) {
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