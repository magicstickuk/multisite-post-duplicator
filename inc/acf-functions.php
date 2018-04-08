<?php
/**
 * Collect data on the Advance Custom Field Images from the sourse site to the destination
 *
 * This function hooks into the end of the core function. Checks all the meta attached to the source post
 * then identifies which ones are related to the ACF plugin and processes required info about them.
 * The data is then added to database for use later.
 *
 * @since 1.3
 * @param array $mdp_post Data used to set up the destination post
 * @param array $attached_images Data about the images in the main content of the source post
 * @param array $meta_values All meta attached to the source post
 * @param int $post_id_to_copy The ID of the source post
 * @param int $destination_blog_id The ID destination site
 * @return null
 *
*/
function mpd_do_acf_images_from_source($mdp_post, $attached_images, $meta_values, $post_id_to_copy, $destination_blog_id){
   
   $acf_collected           = array();
   $acf_gallery_collected   = array();
   $acf_file_data_collected = array();

   $current_blog_id = get_current_blog_id();

   //Is Advanced Custom Fields active and the source site is different from the destination
   if(class_exists('acf') && ($current_blog_id != $destination_blog_id)){

        if($meta_values){

            foreach ($meta_values as $key => $meta) {

                //Indicates it could be a ACF Value
                if(isset($meta_values["_" . $key])){

                     $acf_field_key  = $meta_values["_" . $key][0];

                     if(strlen($acf_field_key) > 20) {
                        $multi_keys = explode( 'field_', $acf_field_key );
                        $acf_field_key = "field_".end($multi_keys);
                     }

                     //Get the posssible ACF controller post for this image
                     $result = get_field_object($acf_field_key);
                    
                    if($result){

                        if(current_filter() == 'mpd_during_core_in_source'){
                            do_action('mpd_acf_field_found', $result, $meta, $acf_field_key, $destination_blog_id);
                        }

                        $acf_control    = $result;
                        $acf_type       = $acf_control['type'];

                        switch ($acf_type) {
                            case 'image':

                                $acf_image_data_source = array(

                                    'image_id'      => $meta[0],
                                    'field'         => $key,
                                    'post_id'       => $post_id_to_copy,
                                    'img_url'       => wp_get_attachment_url( $meta[0] ),
                                    'img_post_mime' => get_post_mime_type($meta[0])

                                );

                                array_push($acf_collected, $acf_image_data_source);

                                break;

                            case 'file':

                                $acf_file_data_collected_source = array(
                                    'url'           => wp_get_attachment_url( $meta[0] ),
                                    'post_mime'     => get_post_mime_type($meta[0]),
                                    'file_id'       => $meta[0],
                                    'field'         => $key,
                                    'post_id'       => $post_id_to_copy,

                                );

                                array_push($acf_file_data_collected, $acf_file_data_collected_source);

                                break;

                            case 'gallery':
                                
                                $source_ids     = maybe_unserialize($meta[0]);
                                $image_urls     = array();
                                $image_metas    = array();
                                $img_post_mimes = array();

                                if($source_ids && is_array($source_ids)){

                                    foreach ($source_ids as $source_id) {

                                        $image_url      = wp_get_attachment_url( $source_id);
                                        $img_post_mime  = get_post_mime_type($source_id);

                                        array_push($image_urls, $image_url);
                                        array_push($img_post_mimes, $img_post_mime);

                                    }

                                }

                                $acf_image_gallery_data_source = array(

                                    'image_ids'     => $source_ids,
                                    'field'         => $key,
                                    'post_id'       => $post_id_to_copy,
                                    'img_url'       => $image_urls,
                                    'img_post_mime' => $img_post_mimes

                                );

                                array_push($acf_gallery_collected, $acf_image_gallery_data_source);

                                break;

                            default:
                                
                                break;

                        }  
                        
                    }

                }

            }

        }

        update_site_option( 'source_acf_files', $acf_file_data_collected);
        update_site_option( 'source_acf_images', $acf_collected);
        update_site_option( 'source_acf_gallery_images', $acf_gallery_collected);   

    }  
   
}

add_action('mpd_during_core_in_source', 'mpd_do_acf_images_from_source', 10, 5);
add_action('mpd_persist_during_core_in_source', 'mpd_do_acf_images_from_source', 10, 5);

/**
 * Copy the source ACF files to the destination site.
 *
 *
 * @since 1.6.5
 * @param int $post_id The ID of the destination post
 * @return null
 *
*/
function mpd_do_acf_files_to_destination($post_id, $mpd_post, $source_id){
  
    if(class_exists('acf')){
    
        $acf_files = get_site_option( 'source_acf_files' );

        if($acf_files){
          
            foreach ($acf_files as $acf_file) {
                
                $file       = $acf_file['url'];

                if($file){
                    
                    $info       = pathinfo($file);
                    $file_name  = basename($file,'.'.$info['extension']);

                    $attachment = array(
                         'post_mime_type' => $acf_file['post_mime'],
                         'post_title'     => $file_name,
                         'post_content'   => '',
                         'post_status'    => 'inherit'
                    );

                    $attach_id = mpd_copy_file_to_destination($attachment, $file, $post_id, $source_id, $acf_file['file_id']);
                    
                    update_field($acf_file['field'], $attach_id, $post_id);
                }
               
                
            }

            delete_site_option('source_acf_files');

        }
      
    }

}

add_action('mpd_end_of_core_before_return', 'mpd_do_acf_files_to_destination', 10, 3);
add_action('mpd_persist_end_of_core_before_return', 'mpd_do_acf_files_to_destination', 10, 3);

/**
 * Copy the source ACF images to the destination site.
 *
 *
 * @since 1.2.1
 * @param int $post_id The ID of the destination post
 * @return null
 *
*/
function mpd_do_acf_images_to_destination($post_id, $mpd_post, $source_id){
  
    if(class_exists('acf')){
    
        $acf_images = get_site_option( 'source_acf_images' );

        if($acf_images){
          
            foreach ($acf_images as $acf_image) {
                
                $file       = $acf_image['img_url'];

                if($file){
                    
                    $info       = pathinfo($file);
                    $file_name  = basename($file,'.'.$info['extension']);

                    $attachment = array(
                         'post_mime_type' => $acf_image['img_post_mime'],
                         'post_title'     => $file_name,
                         'post_content'   => '',
                         'post_status'    => 'inherit'
                    );

                    $attach_id = mpd_copy_file_to_destination($attachment, $file, $post_id, $source_id, $acf_image['image_id']);
                    
                    update_field($acf_image['field'], $attach_id, $post_id);
                }
               
                
            }

            delete_site_option('source_acf_images');

        }

        $acf_gallerys = get_site_option( 'source_acf_gallery_images' );

        if($acf_gallerys){

            foreach ($acf_gallerys as $gallery_key => $acf_gallery) {

                $attach_ids = array();

                if(isset($acf_gallery['image_ids']) && $acf_gallery['image_ids'] !=''){

                    foreach($acf_gallery['image_ids'] as $key => $acf_image){

                        $file       = $acf_gallerys[$gallery_key]['img_url'][$key];

                        if($file){

                            $info       = pathinfo($file);
                            $file_name  = basename($file,'.'.$info['extension']);

                            $attachment = array(

                                 'post_mime_type' => $acf_gallerys[$gallery_key]['img_post_mime'][$key],
                                 'post_title'     => $file_name,
                                 'post_content'   => '',
                                 'post_status'    => 'inherit'

                            );

                            $attach_id = mpd_copy_file_to_destination($attachment, $file, $post_id, $source_id, $acf_gallerys[$gallery_key]['image_ids'][$key]);

                            array_push($attach_ids,$attach_id);

                        }
                        
                    }
                   
                    update_field($acf_gallerys[$gallery_key]['field'], $attach_ids, $post_id);

                    update_field($acf_gallerys[$gallery_key]['field'], $attach_ids, $post_id);

                }
                

            }  

            delete_site_option('source_acf_gallery_images');

        }
      
    }

}

add_action('mpd_end_of_core_before_return', 'mpd_do_acf_images_to_destination', 10, 3);
add_action('mpd_persist_end_of_core_before_return', 'mpd_do_acf_images_to_destination', 10, 3);

/**
 * Copy the ACF Field Groups (using the bulk action method) to the destination site.
 *
 *
 * @since 1.5
 * @param int $post_id The ID of the ACF field Group to copy
 * @param int $destination_id The ID of site to copy the ACF Frield froup to
 * @return null
 *
*/
function mpd_copy_acf_field_group($post_id, $destination_id){
       
    $post_type = get_post_type($post_id);
   
    if($post_type == 'acf-field-group'){
        
        //flush trash
        mpd_flush_acf_trash($destination_id);

        //Tell the bulk action plugin to skip the normal duplication process and do this instead.
        update_option('skip_standard_dup', 1);

        global $wpdb;
        
        //Get the multisite table names for our queries.
        $source_blog_id         = get_current_blog_id();
        $source_tablename       = mpd_get_tablename($source_blog_id);
        $destination_tablename  = mpd_get_tablename($destination_id);

        // Get the full post object for the source post
        $source_post    = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $source_tablename WHERE ID = %d AND post_type='acf-field-group'",
                $post_id
            )
        );

        //Check the destination site if there is an acf field group with a matching field_key
        $matching_existing_post = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $destination_tablename WHERE post_name = %s AND post_type='acf-field-group' AND post_status = 'publish'",
                $source_post->post_name
            )
        );

        //If it does have a matching field_key update post and update all of its children
        if($matching_existing_post){

            $args = array(
                'source_id'           => $source_blog_id,
                'destination_id'      => $destination_id,
                'source_post_id'      => $post_id,
                'destination_post_id' => $matching_existing_post->ID
            );

            mpd_log_duplication(false, $args);

            if(isset($_POST['persist'])){

                mpd_add_persist($args);

            }

            switch_to_blog($destination_id);

            // Update the acf field group
            wp_update_post(array(
                
                'ID'           => $matching_existing_post->ID,
                'post_content' => $source_post->post_content,
                'post_title'   => $source_post->post_title,
                'post_excerpt' => $source_post->post_excerpt,

            ));

            //Get all source child posts
            $source_child_posts      = mpd_acf_decendant_fields($post_id, $source_blog_id);

            //Get all destination child posts (this is for comparisson purposes further down the line)
            $destination_child_posts = mpd_acf_decendant_fields($matching_existing_post->ID, $destination_id);

            //We need an array of the source field key so we can compare with current destination keys at the end so
            //we can delete any from the destination that are no longer in the source (sync).
            $source_child_field_keys = array();
            $new_acf_fields = array();

            foreach ($source_child_posts as $key => $source_child_post) {
                //Collect the field key
                array_push($source_child_field_keys, $source_child_post->post_name);

                //Check that the child exists in the destination
                $matching_child_post = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $destination_tablename WHERE post_name = %s AND post_status = 'publish'",
                        $source_child_post->post_name
                    )
                );
                // Get the post object of the source post that has the same field_key
                $matching_source_post = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $source_tablename WHERE post_name = %s AND post_status = 'publish'",
                        $source_child_post->post_name
                    )
                );

                //if there is a matching child update the post
                if($matching_child_post){
                    
                    wp_update_post(array(
                    
                        'ID'           => $matching_child_post->ID,

                        'post_content' => $matching_source_post->post_content,
                        'post_title'   => $matching_source_post->post_title,
                        'post_excerpt' => $matching_source_post->post_excerpt,
                        'post_parent'  => $matching_child_post->post_parent
    
                    ));

                }else{


                    $new_acf_field_id = wp_insert_post(array(

                        'post_parent'    => $matching_existing_post->ID,

                        'post_content'   => $matching_source_post->post_content,
                        'post_title'     => $matching_source_post->post_title,
                        'post_excerpt'   => $matching_source_post->post_excerpt,
                        'post_status'    => $matching_source_post->post_status,
                        'post_type'      => $matching_source_post->post_type,
                        'post_name'      => $matching_source_post->post_name,
                        'comment_status' => $matching_source_post->comment_status,
                        'ping_status'    => $matching_source_post->ping_status,
                        'menu_order'     => $matching_source_post->menu_order

                    ));

                    array_push($new_acf_fields, array('field_key' => $matching_source_post->post_name, 'id' => $new_acf_field_id));
                    
                 }

            }

            foreach ($new_acf_fields as $new_acf_field) {
                     
                     $new_id   = $new_acf_field['id'];
                     $its_key  = $new_acf_field['field_key'];

                     mpd_set_acf_destination_parent_id_by_id($new_id, $its_key, $source_blog_id, $destination_id);

                     
            }

            // Delete any child posts (acf fields) from the field group that exists in the destination but is not in the source.
             foreach ($destination_child_posts as $destination_child_post) {
                    
                $field_key = $destination_child_post->post_name;

                if(!in_array($field_key, $source_child_field_keys)){

                    $wpdb->query( 
                        $wpdb->prepare( 
                            "DELETE FROM $destination_tablename WHERE post_name = %s",
                            $field_key
                        )
                    ); 

                }

            }

            //Collect information about the new post 
            $site_edit_url = get_edit_post_link($matching_existing_post->ID);
            $blog_details  = get_blog_details($destination_id);
            $site_name     = $blog_details->blogname;

            restore_current_blog();

            mdp_make_admin_notice($site_name, $site_edit_url, $blog_details);

            


        }else{
            //Insert new post into destination. get children insert the children and assign children's parent as the new posts id.
            switch_to_blog($destination_id);

            $new_group_id = wp_insert_post(array(

                'post_content'      => $source_post->post_content,
                'post_title'        => $source_post->post_title,
                'post_excerpt'      => $source_post->post_excerpt,
                'post_status'       => $source_post->post_status,
                'post_type'         => $source_post->post_type,
                'post_name'         => $source_post->post_name,
                'comment_status'    => $source_post->comment_status,
                'ping_status'       => $source_post->ping_status,
                'menu_order'        => $source_post->menu_order
    
            ));

            $source_child_posts = mpd_acf_decendant_fields($post_id, $source_blog_id);

            $destination_post_ids = array();

            foreach ($source_child_posts as $source_child_post) {

                $destination_post = wp_insert_post(array(

                    'post_content'      => $source_child_post->post_content,
                    'post_title'        => $source_child_post->post_title,
                    'post_excerpt'      => $source_child_post->post_excerpt,
                    'post_status'       => $source_child_post->post_status,
                    'post_type'         => $source_child_post->post_type,
                    'post_name'         => $source_child_post->post_name,
                    'comment_status'    => $source_child_post->comment_status,
                    'ping_status'       => $source_child_post->ping_status,
                    'menu_order'        => $source_child_post->menu_order
    
                ));

                $destination_post_ids[] = $destination_post;

            }

            mpd_acf_setup_destination_parents($source_child_posts, $destination_post_ids, $new_group_id);

            //Collect information about the new post 
            $site_edit_url = get_edit_post_link($new_group_id);
            $blog_details  = get_blog_details($destination_id);
            $site_name     = $blog_details->blogname;

            restore_current_blog();

            $args = array(
                'source_id'           => $source_blog_id,
                'destination_id'      => $destination_id,
                'source_post_id'      => $post_id,
                'destination_post_id' => $new_group_id
            );

            mpd_log_duplication(false, $args);
            
            if(isset($_POST['persist'])){
               
                mpd_add_persist($args);

            }

            mdp_make_admin_notice($site_name, $site_edit_url, $blog_details);

        }

    }

    return;

}

add_action('mpd_single_batch_before', 'mpd_copy_acf_field_group', 10, 2);
add_action('mpd_single_metabox_before', 'mpd_copy_acf_field_group', 10, 2);

/**
 * Get of all children post of a given parent
 *
 *
 * @since 1.5
 * @param int $post_id The ID of the post
 * @param int $blog_id The ID of site to where the parent exists
 * @param string $status 'publish' to get all published posts or any other string to get all post statues
 * @return array An array of post objects
 *
*/
function mpd_acf_child_fields($post_id, $blog_id, $status = 'publish'){

    global $wpdb;

    $and_clause = $status == 'publish' ? "post_status = 'publish'" : "1=1";

    $tablename  = mpd_get_tablename($blog_id);

    $results    = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $tablename WHERE post_parent = %d AND " . $and_clause,
            $post_id
        )
    );

    return $results;

}

/**
 * Get of all decentants of a given post. Functions for a maximum of 6 generations
 *
 * @since 1.5
 * @param int $post_id The ID of the post
 * @param int $blog_id The ID of site to where the parent exists
 * @param string $status 'publish' to get all published posts or any other string to get all post statues
 * @return array An array of post objects
 *
*/
function mpd_acf_decendant_fields($post_id, $blog_id, $status = 'publish'){

    $children    = mpd_acf_child_fields($post_id, $blog_id, $status);

    $totalchildren = $children;

    for ($depth = 0; $depth <= 5; $depth++) {

        $parents  = $children;
        $children = array();

        foreach ($parents as $parent) {
            
            $innerchildren = mpd_acf_child_fields($parent->ID, $blog_id, $status);

            if($innerchildren){

                foreach ($innerchildren as $innerchild) {
                    
                    array_push($children, $innerchild);
                    array_push($totalchildren, $innerchild);

                }
                
            }
            
        }

        if(count($children) == 0){
            break;
        }

    }

    return $totalchildren;

}

/**
 * Process the destination posts so that their hierarchy persists into the destination site
 *
 * @since 1.5
 * @param array $source_decendants All the decendants of a specific 'acf-field-group' $group_id
 * @param array $destination_post_ids An array of the destination post ids. The order of which matches the array of the source $source_decendants
 * @param int $group_id The id of the parent acf field group
 * @return null
 *
*/
function mpd_acf_setup_destination_parents($source_decendants, $destination_post_ids, $group_id){

    $source_parent_key = false;

    foreach ($destination_post_ids as $key => $destination_post_id) {
        
        $source_parent_id = $source_decendants[$key]->post_parent;

        //Find source posts key whos id = $source_parent_id 
        foreach ($source_decendants as $innerkey => $source_child_post) {
                    
            if($source_child_post->ID == $source_parent_id){

                $source_parent_key = $innerkey;
                        
            }
        
            $destination_parent_id = $source_parent_key !== false ? $destination_post_ids[$source_parent_key] : $group_id;
                
            wp_update_post(array(
                'ID'           => $destination_post_id,
                'post_parent'  => $destination_parent_id
            ));

        }
                
    }

}

/**
 * Process a destination posts so that its hierarchy persists from the source site
 *
 * @since 1.5
 * @param int $new_id The id of the post you wish to set its corresponding parent
 * @param string $its_key The acf key of the new acf field in the destination
 * @param int $source_blog_id The id of the source blog
 * @param int $destination_blog_id The id of the destination blog
 * @return null
 *
*/
function mpd_set_acf_destination_parent_id_by_id($new_id, $its_key, $source_blog_id, $destination_blog_id){

    global $wpdb;

    $source_tablename       = mpd_get_tablename($source_blog_id);
    $destination_tablename  = mpd_get_tablename($destination_blog_id);

    //Get field key of parent_id
     $matching_source_post = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $source_tablename WHERE post_name = %s AND post_status = 'publish'",
            $its_key
        )
    );

    $matching_source_post_parent_id = $matching_source_post->post_parent;

     //Get the id of the source Parent
    $matching_source_post_parent = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $source_tablename WHERE ID = %s AND post_status = 'publish'",
            $matching_source_post->post_parent
        )
    );
    $destination_parent_id = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $destination_tablename WHERE post_name = %s AND post_status = 'publish'",
            $matching_source_post_parent->post_name
        )
    );

     wp_update_post(array(
        'ID'           => $new_id,
        'post_parent'  => $destination_parent_id->ID
    ));

}

/**
 * Hook to prevent destination post status being selected for acf posts. It doesnt apply to this post type
 *
 * @since 1.5
 * @param boolean $show Show the post status control?
 * @return boolean
 *
*/
function mpd_dont_show_acf_post_status($show){

    global $post;

    $post_type = get_post_type($post->ID);

    if($post_type == 'acf-field-group'){

        return false;

    }

    return $show;

}
add_filter('mpd_show_metabox_post_status', 'mpd_dont_show_acf_post_status');

/**
 * Hook to prevent destination prefix being added for acf posts. It doesnt apply to this post type
 *
 * @since 1.5
 * @param boolean $show Show the prefix control?
 * @return boolean
 *
*/
function mpd_dont_show_acf_prefix($show){

    global $post;
    
    $post_type = get_post_type($post->ID);

    if($post_type == 'acf-field-group'){

        return false;

    }

    return $show;

}
add_filter('mpd_show_metabox_prefix', 'mpd_dont_show_acf_prefix');

/**
 * Hook to ensure that ACF posts dont go down the normal route of duplication
 *
 * @since 1.5
 * @param array $args The args past in to process the 'linked post'
 * @return array filtered $args
 *
*/
function mpd_set_for_acf_group_persist($args){

    $the_post_type = get_post_type($args['source_post_id']);

    if($the_post_type == 'acf-field-group'){

        $args['skip_normal_persist'] = 1;

    }

    return $args;

}
add_filter('mpd_persist_post_args', 'mpd_set_for_acf_group_persist');

/**
 * Hook to ensure that ACF posts are copied to the destination site via function mpd_copy_acf_field_group();
 *
 * @since 1.5
 * @param array $args The args past in to process the 'linked post'
 * @return null
 *
*/
function mpd_do_acf_group_persist($args){

    mpd_copy_acf_field_group($args['source_post_id'], $args['destination_id']);
    
    delete_option('skip_standard_dup');
 
}
add_action('mpd_after_persist','mpd_do_acf_group_persist');

/**
 * Flush all the acf posts that are in the trash
 *
 * @since 1.5
 * @param int $destination_id The blog id of the site you wish to empty the trash
 * @return null
 *
*/
function mpd_flush_acf_trash($destination_id){

    global $wpdb;
    
    $tablename = mpd_get_tablename($destination_id);

    $binned_acf_groups = $wpdb->get_results(
        "SELECT * FROM $tablename WHERE post_type='acf-field-group' AND post_status = 'trash'"
    );

    if($binned_acf_groups){

        foreach ($binned_acf_groups as $binned_acf_group) {

           $decendants = mpd_acf_decendant_fields($binned_acf_group->ID, $destination_id, 'any');

           if($decendants){

                foreach ($decendants as $decendant) {
                    $wpdb->query( 

                        $wpdb->prepare( 
                            "DELETE FROM $tablename WHERE ID = %s",
                            $decendant->ID
                        )

                    ); 

                }

            }
            
            $wpdb->query(

                $wpdb->prepare( 
                    "DELETE FROM $tablename WHERE ID = %s",
                    $binned_acf_group ->ID
                )

            );

        }

    }

}
