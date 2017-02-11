<?php
/**
 *
 * This file is a collection all functions relating to informing the user of
 * possible considerations to make in the destination site after duplication
 * @since 1.4
 * @author Mario Jaconelli <mariojaconelli@gmail.com>
 *
 */

/**
 * Echo out any considerations stored in the options table after the main notice has been displayed
 * using 'mpd_after_notices' hook.
 *
 * @since 1.4
 * @return null
 *
*/
function mpd_add_considerations(){

    // If there are any considerations in the database display them.
    if($considerations = get_option('mpd_considerations')){

        echo $considerations;
        delete_option('mpd_considerations');

    }

}
//add_action('mpd_after_notices', 'mpd_add_considerations');

/**
 * If there are any considerations in the database adust the main notice text using
 * 'mpd_admin_notice_text' filter
 *
 * @since 1.4
 * @return null
 *
*/
function mpd_add_consideration_text($message){

    $message       .= get_option('mpd_considerations') ? " ". __('with the following considerations:', 'multisite-post-duplicator' ) : '';
    
    return $message;

}
//add_filter('mpd_admin_notice_text','mpd_add_consideration_text');

/**
 * This is us logging the post types on a site into a site option. On multisite switch_to_blog doesn't
 * give us access to the $wp_post_types global, so we are having to use this method of storing the post types on the site
 * so we can refer to them later.
 *
 * @since 1.4
 * @return null
 *
*/
function mpd_take_note_of_posttypes(){

    $wp_post_types  = get_post_types();
    $post_types     = get_option('mpd_noted_posttypes');

    if($wp_post_types !== $post_types ){

        update_option('mpd_noted_posttypes', $wp_post_types );

    }

}

add_action('admin_head', 'mpd_take_note_of_posttypes');
add_action('wp_head', 'mpd_take_note_of_posttypes');

/**
 * This is us logging the taxonomies on a site into a site option. On multisite switch_to_blog doesn't
 * give us access to the $wp_taxonomy_terms global, so we are having to use this method of storing the taxonomies on the site
 * so we can refer to them later.
 *
 * @since 1.4
 * @return null
 *
*/
function mpd_take_note_of_taxonomies(){

    $wp_taxonomies  = get_taxonomies();
    $mpd_taxonomies = get_option('mpd_noted_taxonomies');

    if($wp_taxonomies !== $mpd_taxonomies ){

        update_option('mpd_noted_taxonomies', $wp_taxonomies );

    }

}

add_action('admin_head', 'mpd_take_note_of_taxonomies');
add_action('wp_head', 'mpd_take_note_of_taxonomies');

/**
 * Here we hook into the core data of the duplication. We look at the source post types and the destination site's
 * post types. If there is a difference we update a site option in the database so it can be echoed out during the 'admin_notcies'
 * action.
 *
 * @since 1.4
 * @param Array $mdp_post. Data about the soucrce post. See core function for data-structure
 * @param Array $attached_images. Array of images attached to the post. See core function for data-structure
 * @param int $source_id. The id of the source site
 * @param int $destination_id. The id of the destination site
 * @return null
 *
*/
function mpd_post_type_considerations($mdp_post, $attached_images, $meta_values, $source_id, $destination_id){

    // Get any considerations currently in database
    $current_considerations = get_option('mpd_considerations');    
    $considerations         = $current_considerations ? $current_considerations : '';

    // Assign the post type of the source post
    $post_type              = $mdp_post['post_type'];
    // Get the list of all the post types we have currently noted in the destination site
    $destination_post_types = get_blog_option($destination_id, 'mpd_noted_posttypes');
    
    if($destination_post_types){
        // If the source post type is not in the list of destination post types then build a WordPress admin notice.
        if(!in_array($post_type, $destination_post_types)){

            $destination_blog       = get_blog_details( $destination_id);
            $destination_blog_name  = $destination_blog->blogname;
            $destination_blog_url   = $destination_blog->siteurl;

            $considerations .= "<div class='notice notice-info notice-considerations'><p>";
            $considerations .= __("The post type of this post", 'multisite-post-duplicator'  );
            $considerations .= " '<em>" . $post_type . "</em>' ";
            $considerations .= __("doesn't exist in the destination site. In order for you to see the post you just created would will have to register a post-type called", 'multisite-post-duplicator' );
            $considerations .= " '<em>" . $post_type . "</em>' ";
            $considerations .= __("in site",  'multisite-post-duplicator' );
            $considerations .= " " . "<a target='_blank' href='" . $destination_blog_url.  "/wp-admin'>";
            $considerations .= $destination_blog_name;
            $considerations .= "</a>";
            $considerations .= ".</p></div>";
            //Store the markup in a database for use at 'admin_notice' action
            update_option('mpd_considerations', $considerations);
        }
        
    }
    
}

//add_action('mpd_during_core_in_source', 'mpd_post_type_considerations', 20, 5);

/**
 * Here we are filtering into the source taxonomy terms as collected at source in the core.
 * We look at the source taxonomy terms and the destination site's taxonomy terms.
 * If there is a difference we update a site option in the database so it can be echoed out during the 'admin_notcies' action.
 *
 * @since 1.4
 * @param Array $source_taxonomy_terms_object. An array of term objects as built (at source) by function mpd_get_post_taxonomy_terms()
 * @param int $destination_id. The id of the destination site
 * @return Array $source_taxonomy_terms_object (filtered?)
 *
*/
function mpd_taxonomy_considerations($source_taxonomy_terms_object, $destination_id){
   
    if($destination_id){

        // Get any considerations currently in database
        $current_considerations = get_option('mpd_considerations');
        $considerations         = $current_considerations ? $current_considerations : '';

        if($source_taxonomy_terms_object){

            if($taxonomies = get_blog_option($destination_id , 'mpd_noted_taxonomies')){
                
                // Get destination blog details so we can use in admin notice
                $destination_blog       = get_blog_details( $destination_id);
                $destination_blog_name  = $destination_blog->blogname;
                $destination_blog_url   = $destination_blog->siteurl;
                
                // For each taxonomey object on the source site we are looking to see if the taxonomy has been
                // noted in the destination site. If it hasn't create a notice to be displayed at 'admin_notices' action
                foreach ($source_taxonomy_terms_object as $taxonomy_object) {

                    if(isset( $taxonomy_object[0])){

                        $looking_for    = $taxonomy_object[0]->taxonomy;

                        if(!in_array($looking_for, $taxonomies)){

                            $considerations .= "<div class='notice notice-info notice-considerations'><p>";
                            $considerations .= __("The taxonomy from this post", 'multisite-post-duplicator'  );
                            $considerations .= " '<em>" . $looking_for . "</em>' ";
                            $considerations .= __("doesn't exist in the destination site. In order for you to see and use the taxonomy's terms you just copied would will have to register a taxonomy called", 'multisite-post-duplicator' );
                            $considerations .= " '<em>" . $looking_for . "</em>' ";
                            $considerations .= __("in site",  'multisite-post-duplicator' );
                            $considerations .= " " . "<a target='_blank' href='" . $destination_blog_url.  "/wp-admin'>";
                            $considerations .= $destination_blog_name;
                            $considerations .= "</a>";
                            $considerations .= ".</p></div>";
                            //Store the markup in a database for use at 'admin_notice' action
                            update_option('mpd_considerations', $considerations);
                            
                        }
                    
                    }
                   
                }

            }

        }
    
    }

    return $source_taxonomy_terms_object;
    
}
//add_filter('mpd_post_taxonomy_terms', 'mpd_taxonomy_considerations', 20, 2);

/**
 * Here we are hooking into the data of an meta key that has been idenified as an ACF field during mpd_do_acf_images_from_source()
 * We look at the source ACF field from the source and check for its existance in the destination site
 * If it doesnt exist we create a notification for the user to alert them of the inconsistancy.
 *
 * @since 1.4
 * @param Object $acf_control_row. An object of data used by ACF to manage the fields configuation. See mpd_do_acf_images_from_source() for data structure
 * @param int $meta. All the meta attached to the source post
 * @param string $acf_field_key. The ACF key identifed.
 * @param int $destination_id. The id of the destination site
 * @return null
 *
*/
function mpd_acf_considerations($acf_control_row, $meta, $acf_field_key, $destination_blog_id){

    // Get data on the ACF field in the source site. See https://www.advancedcustomfields.com/resources/get_field_object/
    $acf_field_source = get_field_object($acf_field_key);
 
    switch_to_blog($destination_blog_id);
        // Get data on the ACF field in the destination site.
        $acf_field_destination = get_field_object($acf_field_key);
        
    restore_current_blog();

    $current_considerations = get_option('mpd_considerations');
        
    $considerations = $current_considerations ? $current_considerations : '';
    // If the field object doesn't exsist in the destination site then create a consideration
    if(!$acf_field_destination){
        //The acf field doesnt exist in the destination site.
        $considerations .= "<div class='notice notice-info notice-considerations'><p>";
        $considerations .= __("The Advanced Custom Field", 'multisite-post-duplicator'  );
        $considerations .= " '<em>" . $acf_field_source['label'] . "</em>' ";
        $considerations .= __("doesn't exsist in the destination site. Why not use MPD to duplicate <a href='". get_admin_url( $blog_id, 'edit.php?post_type=acf-field-group') ."'>your acf group</a>?", 'multisite-post-duplicator' );
        $considerations .= ".</p></div>";

    }
    //Store the markup in a database for use at 'admin_notice' action
    update_option('mpd_considerations', $considerations);

}

//add_action('mpd_acf_field_found', 'mpd_acf_considerations', 10, 4);