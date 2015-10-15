<?php

function mpd_get_some_postypes_to_show_options(){
	
	$options  	= get_option( 'mdp_settings' ); 
	$post_types = array();

	foreach ($options as $key => $value) {
        if (substr($key, 0, 28) == "meta_box_post_type_selector_") {

            $post_types[] = $value;

        }

    }

    return $post_types;

}

function mpd_get_postype_decision_from_options(){

	$options      = get_option( 'mdp_settings' ); 
  	$post_types   = $options['meta_box_show_radio'] ? $options['meta_box_show_radio'] : get_post_types('','names');
    
    if($options['meta_box_show_radio']){

        if($options['meta_box_show_radio'] == 'all'){

            $post_types = get_post_types('','names');

        }elseif($options['meta_box_show_radio'] == 'none'){

            $post_types = null;

        }elseif($options['meta_box_show_radio'] == 'some'){

            $post_types = mpd_get_some_postypes_to_show_options();  

        }
        

    }else{

        $post_types  = get_post_types('','names');

    }

    return $post_types;
}

function mpd_get_prefix(){

      $options  = get_option( 'mdp_settings' ); 
      $prefix   = $options['mdp_default_prefix'] ? $options['mdp_default_prefix'] : 'Copy of';

      return $prefix;
      
}

function get_featured_image_from_source($post_id){

    $image_details  = array();

    $image                  = wp_get_attachment_image_src( get_post_thumbnail_id($post_id), 'full' );
    $image_details['url']   = $image[0];
    $image_details['alt']   = get_post_meta( get_post_thumbnail_id($post_id), '_wp_attachment_image_alt', true );

    return $image_details;

}

function set_featured_image_to_destination($destination_id, $image_url,  $image_alt_text){

    $upload_dir = wp_upload_dir();
    $image_data = file_get_contents($image_url);
    $filename   = basename($image_url);

    if( wp_mkdir_p( $upload_dir['path'] ) ) {
        $file = $upload_dir['path'] . '/' . $filename;
    } else {
        $file = $upload_dir['basedir'] . '/' . $filename;
    }

    file_put_contents( $file, $image_data );

    $wp_filetype = wp_check_filetype( $filename, null );

    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name( $filename ),
        'post_content'   => '', //Image Description
        'post_status'    => 'inherit',
        'post_excerpt'    => '' // Caption
    );

    // Create the attachment
    $attach_id = wp_insert_attachment( $attachment, $file, $destination_id );

    // Add any alt text;
    if($image_alt_text){

         $image_alt = update_post_meta($destination_id,'_wp_attachment_image_alt', $image_alt_text);

    }
   
    

    // Include image.php
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Define attachment metadata
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

    // Assign metadata to attachment
    wp_update_attachment_metadata( $attach_id, $attach_data );

    // And finally assign featured image to post
    set_post_thumbnail( $destination_id, $attach_id );
    
}
