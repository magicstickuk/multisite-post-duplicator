<?php

/**
 *
 * This main core function on Multisite Post Duplicator that processes the duplication of a post on a network from one
 * site to another
 * 
 * @param int $post_id_to_copy The ID of the source post to copy
 * @param int $new_blog_id The ID of the destination blog to copy to.
 * @param string $post_type The destination post type.
 * @param int $post_author The id of the requested post author from the destination site.
 * @param string $prefix Optional prefix to be used on the destination post.
 * @param string $post_status a post status for the destination id. Has to be one of the values returned from WordPress's get_post_statuses() function
 * 
 * @return array An array containing information about the newly created post
 * 
 * Example [
 * 
 *      'id'           => 20,
 *      'edit_url'     => 'http://www.example.com/site1/wp-admin/post.php?post=20&action=edit',
 *      'site_name'    => 'Another Site'
 *  
 * ];
 */
function mpd_duplicate_over_multisite($post_id_to_copy, $new_blog_id, $post_type, $post_author, $prefix, $post_status) {

    $mpd_process_info = array(

        'source_id'             => $post_id_to_copy,
        'destination_id'        => $new_blog_id,
        'post_type'             => $post_type,
        'post_author'           => $post_author,
        'prefix'                => $prefix,
        'requested_post_status' => $post_status

    );

    $options    = get_option( 'mdp_settings' );
    $mdp_post   = get_post($mpd_process_info['source_id']);
    $title      = get_the_title($mdp_post);
    $sourcetags = wp_get_post_tags( $mpd_process_info['source_id'], array( 'fields' => 'names' ) );
    $source_id  = get_current_blog_id();

    if($mpd_process_info['prefix'] != ''){

        $mpd_process_info['prefix'] = trim($mpd_process_info['prefix']) . ' ';

    }

    $mdp_post = apply_filters('mpd_setup_destination_data', array(

            'post_title'    => $mpd_process_info['prefix'] . $title,
            'post_status'   => $mpd_process_info['requested_post_status'],
            'post_type'     => $mpd_process_info['post_type'],
            'post_author'   => $mpd_process_info['post_author'],
 			      'post_content'  => $mdp_post->post_content

    ), $mpd_process_info);

    $data                       = get_post_custom($mdp_post);
    $meta_values                = get_post_meta($mpd_process_info['source_id']);
    $featured_image             = mpd_get_featured_image_from_source($mpd_process_info['source_id']);

    if($mpd_process_info['destination_id'] != get_current_blog_id()){

        $attached_images = mpd_get_images_from_the_content($mpd_process_info['source_id']);

        if($attached_images){

            $attached_images_alt_tags   = mpd_get_image_alt_tags($attached_images);
            
        }

    }else{
        
        $attached_images = false;

    }

    switch_to_blog($mpd_process_info['destination_id']);

    $post_id = wp_insert_post($mdp_post);

       foreach ( $data as $key => $values) {

           foreach ($values as $value) {

               add_post_meta( $post_id, $key, $value );

            }

        }

  	 foreach ($meta_values as $key => $values) {

           foreach ($values as $value) {
 
                if(is_serialized($value)){
                 
                    add_post_meta( $post_id, $key, unserialize($value));

                }else{

                    add_post_meta( $post_id, $key, $value );

                }
               
            }

    }

    if($attached_images){
        
        if(isset($options['mdp_copy_content_images']) || !$options ){
            
            mpd_process_post_media_attachements($post_id, $attached_images, $attached_images_alt_tags, $source_id);

        }

    }

    if($featured_image){
        
        if(isset($options['mdp_default_featured_image']) || !$options ){

            mpd_set_featured_image_to_destination( $post_id, $featured_image ); 

        }

    }

    if($sourcetags){

        if(isset($options['mdp_default_tags_copy']) || !$options ){

            wp_set_post_tags( $post_id, $sourcetags );

        }
        
    }
     
     $site_edit_url = get_edit_post_link( $post_id );
     $blog_details  = get_blog_details($mpd_process_info['destination_id']);
     $site_name     = $blog_details->blogname;

     restore_current_blog();

     $notice = mdp_make_admin_notice($site_name, $site_edit_url, $blog_details);

     update_option('mpd_admin_notice', $notice );

     $createdPostObject = array(

         'id'           => $post_id,
         'edit_url'     => $site_edit_url,
         'site_name'    => $site_name

    );
     
     return $createdPostObject;
 
}