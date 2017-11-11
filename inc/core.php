<?php
/**
 * 
 * This file contains the main function that processes any requested duplication
 * @since 0.1
 * @author Mario Jaconelli <mariojaconelli@gmail.com>
 */

/**
 *
 * This is the main core function on Multisite Post Duplicator that processes the duplication of a post on a network from one
 * site to another
 * 
 * @param int $post_id_to_copy The ID of the source post to copy
 * @param int $new_blog_id The ID of the destination blog to copy to.
 * @param string $post_type The destination post type.
 * @param int $post_author The ID of the requested post author from the destination site.
 * @param string $prefix Optional prefix to be used on the destination post.
 * @param string $post_status The post status for the destination ID. Has to be one of the values returned from the mpd_get_post_statuses() function
 * 
 * @return array An array containing information about the newly created post
 * 
 * Example:
 * 
 *          id           => 20,
 *          edit_url     => 'http://[...]/site1/wp-admin/post.php?post=20&action=edit',
 *          site_name    => 'Another Site'
 * 
 */
function mpd_duplicate_over_multisite($post_id_to_copy, $new_blog_id, $post_type, $post_author, $prefix, $post_status) {

    //Collect function arguments into a single variable
    $mpd_process_info = apply_filters('mpd_source_data', array(

        'source_post_id'        => $post_id_to_copy,
        'destination_id'        => $new_blog_id,
        'post_type'             => $post_type,
        'post_author'           => $post_author,
        'prefix'                => $prefix,
        'requested_post_status' => $post_status

    ));

    do_action('mpd_before_core', $mpd_process_info);

    //Get plugin options
    $options    = get_option( 'mdp_settings' );
    //Get the object of the post we are copying
    $mdp_post   = get_post($mpd_process_info['source_post_id']);
    //Get the title of the post we are copying
    $title      = get_the_title($mdp_post);
    //Get the tags from the post we are copying
    $sourcetags = wp_get_post_tags( $mpd_process_info['source_post_id'], array( 'fields' => 'names' ) );
    //Get the ID of the sourse blog
    $source_blog_id  = get_current_blog_id();
    //Get the categories for the post
    $source_categories = mpd_get_objects_of_post_categories($mpd_process_info['source_post_id'], $mpd_process_info['post_type']);
    //Get the taxonomy terms for the post
    $source_taxonomies = mpd_get_post_taxonomy_terms($mpd_process_info['source_post_id'], false, $mpd_process_info['destination_id']);

    //Format the prefix into the correct format if the user adds their own whitespace
    if($mpd_process_info['prefix'] != ''){

        $mpd_process_info['prefix'] = trim($mpd_process_info['prefix']) . ' ';

    }

    //Permalink Setup
    $post_name = $mpd_process_info['destination_id'] == $source_blog_id ? null : $mdp_post->post_name;

    //Using the orgininal post object we now want to insert our any new data based on user settings for use
    //in the post object that we will be adding to the destination site
    $mdp_post = apply_filters('mpd_setup_destination_data', array(

            'post_title'    => $mpd_process_info['prefix'] . $title,
            'post_status'   => $mpd_process_info['requested_post_status'],
            'post_type'     => $mpd_process_info['post_type'],
            'post_author'   => $mpd_process_info['post_author'],
 			'post_content'  => $mdp_post->post_content,
            'post_excerpt'  => $mdp_post->post_excerpt,
            'post_content_filtered' => $mdp_post->post_content_filtered,
            'post_name'     => $post_name

    ), $mpd_process_info);

    //Get all the meta data associated with the source post
    $meta_values       = apply_filters('mpd_filter_post_meta', get_post_meta($mpd_process_info['source_post_id']));
    //Get array of data associated with the featured image for this post
    $featured_image    = mpd_get_featured_image_from_source($mpd_process_info['source_post_id']);

    //If we are copying the source post to another site on the network we will collect data about those 
    //images.
    if($mpd_process_info['destination_id'] != $source_blog_id){

        $attached_images = mpd_get_images_from_the_content($mpd_process_info['source_post_id']);

        if($attached_images){

            $attached_images_alt_tags   = mpd_get_image_alt_tags($attached_images);
            
        }

    }else{
        
        $attached_images = false;

    }

    //Hook for actions just before we switch to the destination blog to start processing our collected data
    do_action('mpd_during_core_in_source', $mdp_post, $attached_images, $meta_values, $mpd_process_info['source_post_id'], $mpd_process_info['destination_id']);
    


    ////////////////////////////////////////////////
    //Tell WordPress to work in the destination site
    switch_to_blog($mpd_process_info['destination_id']);
    ////////////////////////////////////////////////



    //Make the new post
    $post_id = wp_insert_post($mdp_post);
    

    //Copy the meta data collected from the sourse post to the new post
    mpd_process_meta($post_id, $meta_values);
 
    //If there were media attached to the source post content then copy that over
    if($attached_images){
        //Check that the users plugin settings actually want this process to happen
        if((isset($options['mdp_copy_content_images']) || !$options) && apply_filters('mdp_copy_content_images', true) ){
            
            mpd_process_post_media_attachements($post_id, $attached_images, $attached_images_alt_tags, $source_blog_id, $new_blog_id);

        }

    }
    //If there was a featured image in the source post then copy it over
    if($featured_image){
        //Check that the users plugin settings actually want this process to happen
        if((isset($options['mdp_default_featured_image']) || !$options) && apply_filters('mdp_default_featured_image', true) ){

            mpd_set_featured_image_to_destination( $post_id, $featured_image, $source_blog_id ); 

        }

    }
    //If there were tags in the source post then copy them over
    if($sourcetags){
        //Check that the users plugin settings actually want this process to happen
        if((isset($options['mdp_default_tags_copy']) || !$options) && apply_filters('mdp_default_tags_copy', true)  ){

            wp_set_post_tags( $post_id, $sourcetags );

        }
        
    }

    //If there were categories in the source post then copy them over
    if($source_categories){

        if((isset($options['mdp_copy_post_categories']) || !$options) && apply_filters('mdp_copy_post_categories', true) ){

            mpd_set_destination_categories($post_id, $source_categories, $mdp_post['post_type']);

        }

    }
    //If there were taxonomies in the source post then copy them over
    if($source_taxonomies){

        if((isset($options['mdp_copy_post_taxonomies']) || !$options) && apply_filters('mdp_copy_post_taxonomies', true) ){

            mpd_set_post_taxonomy_terms($post_id, $source_taxonomies);

        }

    }
    
    //Collect information about the new post 
    $site_edit_url = get_edit_post_link( $post_id );
    $blog_details  = get_blog_details($mpd_process_info['destination_id']);
    $site_name     = $blog_details->blogname;

    do_action('mpd_end_of_core_before_return', $post_id, $mdp_post, $source_blog_id);

    //////////////////////////////////////
    //Go back to the current blog so we can update information about the action that just took place
    restore_current_blog();
    //////////////////////////////////////

    //Use the collected information about the new post to generate a status notice and a link for the user
    $notice = mdp_make_admin_notice($site_name, $site_edit_url, $blog_details);

    //Lets also create an array to return to function call incase it is required (extensibility)
    $created_post_details = apply_filters('mpd_returned_information', array(

        'id'           => $post_id,
        'edit_url'     => $site_edit_url,
        'site_name'    => $site_name

    ));

    do_action('mpd_end_of_core', $created_post_details);

    do_action('mpd_log', $created_post_details, $mpd_process_info);
     
    return $created_post_details;
 
}


function mpd_persist_over_multisite($persist_post) {

    
    $persist_post = apply_filters('mpd_persist_source_data', $persist_post );

    //Get plugin options
    $options    = get_option( 'mdp_settings' );
    //Get the object of the post we are copying
    $mdp_post   = get_post($persist_post->source_post_id);

    //Get the title of the post we are copying
    $title      = get_the_title($mdp_post);
    //Get the tags from the post we are copying
    $sourcetags = wp_get_post_tags( $persist_post->source_post_id, array( 'fields' => 'names' ) );
    //Get the ID of the sourse blog
    $source_blog_id  = get_current_blog_id();
    //Get the categories for the post
    $source_categories = mpd_get_objects_of_post_categories($persist_post->source_post_id, get_post_type($persist_post->source_post_id));
    //Get the taxonomy terms for the post
    $source_taxonomies = mpd_get_post_taxonomy_terms($persist_post->source_post_id, false, false);

    //Permalink Setup
    $post_name = $persist_post->destination_id == $source_blog_id ? null : $mdp_post->post_name;

    //Using the orgininal post object we now want to insert our any new data based on user settings for use
    //in the post object that we will be adding to the destination site
    $mdp_post = apply_filters('mpd_setup_persist_destination_data', array(
            'ID'            => $persist_post->destination_post_id,
            'post_title'    => $title,
            'post_name'     => sanitize_title_with_dashes($title),
            'post_type'     => get_post_type($persist_post->source_post_id),
            'post_author'   => get_post_field( 'post_author', $persist_post->source_post_id ),
            'post_content'  => $mdp_post->post_content,
            'post_excerpt'  => $mdp_post->post_excerpt,
            'post_content_filtered' => $mdp_post->post_content_filtered,
            'post_name' => $post_name,
            'post_status' => $mdp_post->post_status

    ), $persist_post);

    //Get all the meta data associated with the sourse post
    $meta_values       = apply_filters('mpd_filter_persist_post_meta', get_post_meta($persist_post->source_post_id)) ;
    //Get array of data associated with the featured image for this post
    $featured_image    = mpd_get_featured_image_from_source($persist_post->source_post_id);

    //If we are copying the sourse post to another site on the network we will collect data about those 
    //images.
    if($persist_post->destination_id != $persist_post->source_id){

        $attached_images = mpd_get_images_from_the_content($persist_post->source_post_id);

        if($attached_images){

            $attached_images_alt_tags   = mpd_get_image_alt_tags($attached_images);
            
        }

    }else{
        
        $attached_images = false;

    }

    //Hook for actions just before we switch to the destination blog to start processing our collected data
    do_action('mpd_persist_during_core_in_source', $mdp_post, $attached_images, $meta_values, $persist_post->source_post_id, $persist_post->destination_id);

    ////////////////////////////////////////////////
    //Tell WordPress to work in the destination site
    switch_to_blog($persist_post->destination_id);
    ////////////////////////////////////////////////

    global $wpdb;

    //Make the new post
    $post_id = wp_update_post($mdp_post);

    $post_meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d ", $post_id ));

    foreach ( $post_meta_ids as $mid ){
        delete_metadata_by_mid( 'post', $mid );
    }
    
    //Copy the new meta data collected from the sourse post to existing post
    mpd_process_meta($post_id, $meta_values);

    //If there were media attached to the sourse post content then copy that over
    if($attached_images){
        //Check that the users plugin settings actually want this process to happen
        if((isset($options['mdp_copy_content_images']) || !$options) && apply_filters('mdp_copy_content_images', true) ){

            mpd_process_post_media_attachements($post_id, $attached_images, $attached_images_alt_tags, $persist_post->source_id, $persist_post->destination_id);

        }
        
    }

    //If there was a featured image in the sourse post then copy it over
    if($featured_image){
        //Check that the users plugin settings actually want this process to happen
        if((isset($options['mdp_default_featured_image']) || !$options) && apply_filters('mdp_default_featured_image', true) ){

            mpd_set_featured_image_to_destination( $post_id, $featured_image, $source_blog_id ); 

        }

    }
    //If there were tags in the sourse post then copy them over
    if($sourcetags){
        //Check that the users plugin settings actually want this process to happen
        if((isset($options['mdp_default_tags_copy']) || !$options) && apply_filters('mdp_default_tags_copy', true)  ){

            wp_set_post_tags( $post_id, $sourcetags );

        }
        
    }

    //If there were categories in the sourse post then copy them over
    if($source_categories){

        if((isset($options['mdp_copy_post_categories']) || !$options) && apply_filters('mdp_copy_post_categories', true)  ){

            mpd_set_destination_categories($post_id, $source_categories, $mdp_post['post_type']);

        }

    }
    //If there were taxonomies in the source post then copy them over
    if($source_taxonomies){

        if((isset($options['mdp_copy_post_taxonomies']) || !$options) && apply_filters('mdp_copy_post_taxonomies', true) ){

            mpd_set_post_taxonomy_terms($post_id, $source_taxonomies);

        }

    }
    
    //Collect information about the new post 
    $site_edit_url = get_edit_post_link( $post_id );
    $blog_details  = get_blog_details($persist_post->destination_id);
    $site_name     = $blog_details->blogname;

    do_action('mpd_persist_end_of_core_before_return', $post_id, $mdp_post, $source_blog_id);

    //////////////////////////////////////
    //Go back to the current blog so we can update information about the action that just took place
    restore_current_blog();
    //////////////////////////////////////

    //Use the collected information about the new post to generate a status notice and a link for the user
    $notice = mdp_make_admin_notice($site_name, $site_edit_url, $blog_details);

    //Lets also create an array to return to function call incase it is required (extensibility)
    $created_post_details = apply_filters('mpd_persist_returned_information', array(

        'id'           => $post_id,
        'edit_url'     => $site_edit_url,
        'site_name'    => $site_name

    ));
     
    return $created_post_details;
 
}
