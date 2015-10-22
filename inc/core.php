<?php

function mpd_duplicate_over_multisite($post_id_to_copy, $new_blog_id, $post_type, $post_author, $prefix, $post_status) {

    $options    = get_option( 'mdp_settings' );
    $mdp_post   = get_post($post_id_to_copy);
    $title      = get_the_title($mdp_post);
    $sourcetags = wp_get_post_tags( $post_id_to_copy, array( 'fields' => 'names' ) );
    $source_id  = get_current_blog_id();

    if($prefix != ''){

        $prefix = trim($prefix) . ' ';

    }

    $mdp_post = array(

            'post_title'    => $prefix . $title,
            'post_status'   => $post_status,
            'post_type'     => $post_type,
            'post_author'   => $post_author,
 			'post_content'  => $mdp_post->post_content

    );

    $data                       = get_post_custom($mdp_post);
    $meta_values                = get_post_meta($post_id_to_copy);
    $featured_image             = mpd_get_featured_image_from_source($post_id_to_copy);

    if($new_blog_id != get_current_blog_id()){

        $attached_images = mpd_get_images_from_the_content($post_id_to_copy);

        if($attached_images){

            $attached_images_alt_tags   = mpd_get_image_alt_tags($attached_images);
            
        }

    }else{
        
        $attached_images = false;

    }

    switch_to_blog($new_blog_id);

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
     $blog_details  = get_blog_details($new_blog_id);
     $site_name     = $blog_details->blogname;

     restore_current_blog();

     $notice = mdp_make_admin_notice($site_name, $site_edit_url);

     update_option('mpd_admin_notice', $notice );

     $createdPostObject = array(

         'id'           => $post_id,
         'edit_url'     => $site_edit_url,
         'site_name'    => $site_name

    );
     
     return $createdPostObject;
 
}