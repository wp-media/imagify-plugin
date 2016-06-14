<?php 
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

class Imagify_NGG {
    /**
     * The constructor
     *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
     * @return void
     **/
    public function __construct() {
        add_action( 'init', array( $this, 'add_mixin' ) );
    }
    
    /**
     * Add custom NGG mixin to override its functions
     *
	 * @since 1.5
	 * @author Jonathan Buttigieg
	 *
     * @return void
     **/
    function add_mixin() {
        include_once( 'class-ngg-storage.php' );
        $storage = C_Gallery_Storage::get_instance();
        $storage->get_wrapped_instance()->add_mixin( 'Imagify_NGG_Storage' );
    }
}
 
$Imagify_NGG = new Imagify_NGG();