<?php
/**
 *
 * This file is a collection all functions relating to informing the user of
 * possible considerations to make in the destination site after duplication
 * @since 1.4
 * @author Mario Jaconelli <mariojaconelli@gmail.com>
 *
 */

function mpd_add_considerations(){

    // If there are any considerations in the database display them.
    if($considerations = get_option('mpd_considerations')){

        echo $considerations;
        delete_option('mpd_considerations');

    }

}
add_action('mpd_after_notices', 'mpd_add_considerations');

function mpd_add_consideration_text($message){

    $message       .= get_option('mpd_considerations') ? " ". __('with the following considerations:', 'multisite-post-duplicator' ) : '';
    
    return $message;

}
add_filter('mpd_admin_notice_text','mpd_add_consideration_text');


function mpd_take_note_of_posttypes(){

    $wp_post_types  = get_post_types();
    $post_types     = get_option('mpd_noted_posttypes');

    if($wp_post_types !== $post_types ){

        update_option('mpd_noted_posttypes', $wp_post_types );

    }

}

add_action('admin_head', 'mpd_take_note_of_posttypes');

function mpd_take_note_of_taxonomies(){

    $wp_taxonomies  = get_taxonomies();
    $mpd_taxonomies = get_option('mpd_noted_taxonomies');

    if($wp_taxonomies !== $mpd_taxonomies ){

        update_option('mpd_noted_taxonomies', $wp_taxonomies );

    }

}

add_action('admin_head', 'mpd_take_note_of_taxonomies');

function mpd_post_type_considerations($mdp_post, $attached_images, $meta_values, $source_id, $destination_id){

    $current_considerations = get_option('mpd_considerations');    
    $considerations         = $current_considerations ? $current_considerations : '';

    $post_type              = $mdp_post['post_type'];
    $destination_post_types = get_blog_option($destination_id, 'mpd_noted_posttypes');
    
    if($destination_post_types){

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

            update_option('mpd_considerations', $considerations);
        }
        
    }
    
}

add_action('mpd_during_core_in_source', 'mpd_post_type_considerations', 20, 5);

function mpd_taxonomy_considerations($source_taxonomy_terms_object, $destination_id){
    
    if($destination_id){

        $current_considerations = get_option('mpd_considerations');
        
        $considerations = $current_considerations ? $current_considerations : '';

        if($source_taxonomy_terms_object){

            if($taxonomies = get_blog_option($destination_id , 'mpd_noted_taxonomies')){
                
                $destination_blog       = get_blog_details( $destination_id);
                $destination_blog_name  = $destination_blog->blogname;
                $destination_blog_url   = $destination_blog->siteurl;
               
                foreach ($source_taxonomy_terms_object as $taxonomy_object) {

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

                        update_option('mpd_considerations', $considerations);

                    }

                }

            }

        }
    
    }

    return $source_taxonomy_terms_object;
    
}
add_filter('mpd_post_taxonomy_terms', 'mpd_taxonomy_considerations', 20, 2);

function mpd_acf_considerations($acf_control_row, $meta, $acf_field_key, $destination_blog_id){

    $acf_field_source = get_field_object($acf_field_key);
 
    switch_to_blog($destination_blog_id);

        $acf_field_destination = get_field_object($acf_field_key);
        
    restore_current_blog();

    $current_considerations = get_option('mpd_considerations');
        
    $considerations = $current_considerations ? $current_considerations : '';

    if(!$acf_field_destination){
        //The acf field doesnt exist in the destination site.
        $considerations .= "<div class='notice notice-info notice-considerations'><p>";
        $considerations .= __("The Advanced Custom Field", 'multisite-post-duplicator'  );
        $considerations .= " '<em>" . $acf_field_source['label'] . "</em>' ";
        $considerations .= __("doesn't exsist in the destination site so will not appear unless you export this field from this site and import into the destination site.", 'multisite-post-duplicator' );
        $considerations .= ".</p></div>";

    }

    update_option('mpd_considerations', $considerations);

}

add_action('mpd_acf_field_found', 'mpd_acf_considerations', 10, 4);