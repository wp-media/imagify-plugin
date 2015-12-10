<?php 
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/*
 * Add "Imagify" column in the Media Uploader
 *
 * @since 1.2
 */
add_filter( 'attachment_fields_to_edit', '_imagify_attachment_fields_to_edit', PHP_INT_MAX, 2 );
function _imagify_attachment_fields_to_edit( $form_fields, $post ) {    
    $current_screen = get_current_screen();
    if ( (bool) $current_screen && 'post' === $current_screen->base ) {
	    return $form_fields;
    }
    
    $form_fields['imagify'] = array(
        'label'         => 'Imagify',
        'input'         => 'html',
        'html'          => get_imagify_media_column_content( $post->ID ),
        'show_in_edit'  => true,
        'show_in_modal' => true,
    );
    
    return $form_fields;
}