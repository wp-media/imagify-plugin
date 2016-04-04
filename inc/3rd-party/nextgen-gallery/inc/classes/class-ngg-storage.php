<?php 
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

class Imagify_NGG_Storage extends Mixin {
    /**
     * Recover image from backup copy and reprocess it
     *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
     * @return void
     **/
    function recover_image( $image ) {
        // $image could be an object or an (int) image ID
        if ( is_numeric( $image ) ) {
            $image = $this->object->_image_mapper->find( $image );
        }
        
		// Remove Imagify data
		if ( isset( $image->pid ) ) {
			Imagify_NGG_DB()->delete( $image->pid );	
		}
		
		return $this->call_parent( 'recover_image', $image );
    }
}