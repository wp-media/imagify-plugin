<?php 
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/*
 * Add "Imagify" column in the Media Uploader
 *
 * @since 1.1.7
 */
add_filter( 'attachment_fields_to_edit', '_imagify_attachment_fields_to_edit', PHP_INT_MAX, 2 );
function _imagify_attachment_fields_to_edit( $form_fields, $post ) {
    $form_fields['imagify'] = array(
        'label'         => 'Imagify',
        'input'         => 'html',
        'html'          => get_imagify_media_column_content( $post->ID ),
        'show_in_edit'  => true,
        'show_in_modal' => true,
    );
    
    return $form_fields;
}