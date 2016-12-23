<?php
/**
 * Collect data on the Advance Custom Field Images from the sourse site to the destination
 *
 * This function hooks into the end of the core function. Checks all the meta attached to the source post
 * then identifies which ones are related to the ACF plugin and processes required info about them.
 * The data is then added to database for use later.
 *
 * @since 1.2.1
 * @param array $mdp_post Data used to set up the destination post
 * @param array $attached_images Data about the images in the main content of the source post
 * @param array $meta_values All meta aatached to the source post
 * @param int $post_id_to_copy The ID of the source post
 * @return null
 *
*/
function mpd_do_acf_images_from_source($mdp_post, $attached_images, $meta_values, $post_id_to_copy){
   
   $acf_collected = array();

   //Is Advanced Custom Fields active?
   if(class_exists('acf')){

        foreach ($meta_values as $key => $meta) {

            //Indicates it could be a ACF Value
            if(isset($meta_values["_" . $key])){

                global $wpdb;

                $acf_field_key  = $meta_values["_" . $key][0];
                $siteid         = $wpdb->blogid != 1 ? $wpdb->blogid . "_" : ''; 
                
                //Get the posssible ACF controller post for this image
                $result         = $wpdb->get_row(
                                        "SELECT ID, post_content
                                         FROM $wpdb->prefix" . $siteid . "posts
                                         WHERE post_name = '". $acf_field_key ."'"
                                  );
                
               
                if($result){

                    $acf_control    = unserialize($result->post_content);
                    $acf_type       = $acf_control['type'];

                    if($acf_type == 'image'){

                        $acf_image_data_source = array(

                            'image_id'      => $meta[0],
                            'field'         => $key,
                            'field_key'     => $acf_field_key,
                            'post_id'       => $post_id_to_copy,
                            'img_url'       => wp_get_attachment_url( $meta[0] ),
                            'img_meta'      => wp_get_attachment_metadata($meta[0]),
                            'img_post_mime' => get_post_mime_type($meta[0])

                        );

                        array_push($acf_collected, $acf_image_data_source);

                    }
                    
                }

            }

        }
       
        update_site_option( 'source_acf_images', $acf_collected);   

    }  
   
}

add_action('mpd_during_core_in_source', 'mpd_do_acf_images_from_source', 10, 4);

/**
 * Copy the source ACF images to the destination site.
 *
 *
 * @since 1.2.1
 * @param int $post_id The ID of the destination post
 * @return null
 *
*/
function mpd_do_acf_images_to_destination($post_id){
  
    if(class_exists('acf')){
    
        $acf_images = get_site_option( 'source_acf_images' );

        if($acf_images){
          
            foreach ($acf_images as $acf_image) {
                
                $file       = $acf_image['img_url'];
                $info       = pathinfo($file);
                $file_name  = basename($file,'.'.$info['extension']);

                 // Get the upload directory for the current site
                $upload_dir = wp_upload_dir();
                // Make the path to the desired path to the new file we are about to create
                if( wp_mkdir_p( $upload_dir['path'] ) ) {

                    $file = $upload_dir['path'] . '/' . $file_name .'.'. $info['extension'];

                } else {

                    $file = $upload_dir['basedir'] . '/' . $file_name .'.'. $info['extension'];

                }

                $image_data = file_get_contents(mpd_fix_wordpress_urls($acf_image['img_url']));

                // Add the file contents to the new path with the new filename
                file_put_contents( $file, $image_data );

                $attachment = array(
                     'post_mime_type' => $acf_image['img_post_mime'],
                     'post_title'     => $file_name,
                     'post_content'   => '',
                     'post_status'    => 'inherit'
                );

                $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
                 // Include code to process functions below:
                require_once(ABSPATH . 'wp-admin/includes/image.php');

                // Define attachment metadata
                $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

                wp_update_attachment_metadata( $attach_id, $attach_data );
                
                update_field($acf_image['field'], $attach_id, $post_id);
                
            }

            delete_site_option('source_acf_images');

        }
        
    }

}

add_action('mpd_end_of_core_before_return', 'mpd_do_acf_images_to_destination', 10, 1);