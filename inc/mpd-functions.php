<?php
/**
 * 
 * This file is a collection all functions that are referred to in other files
 * @since 0.1
 * @author Mario Jaconelli <mariojaconelli@gmail.com>
 * 
 */

/**
  * Get the value of the post types that we don't want to use in Multisite Post Duplicator
  *
  * WordPress has some post types that are not applicable to MPD's behaviour. This is where
  * we have defined these post types for reference throughout MPD
  *
  * @since 0.5
  * @param none
  * @return array Containing all post types to be ignored.
  * 
  * Example : 
  * 
  *     ['revision', 'nav_menu_item', 'attachment']  
  * 
  */        
function mpd_get_post_types_to_ignore(){

    $post_types_to_ignore   = apply_filters('mpd_ignore_post_types', array(

            'revision',
            'nav_menu_item',
            'attachment'

        )
    );

    return $post_types_to_ignore;

}

/**
 * Get a list of post types the user has selected they want to show the MPD Metabox (if the 'Some Post Types' option was selected in settings)
 * 
 * This function checks the settings for MPD and returns all the values that are associated with post types
 * 
 * @since 0.4
 * @return array Containing post types for use with MPD Metabox
 * 
*/
function mpd_get_some_postypes_to_show_options(){
	
    $post_types             = array();
	$options  	            = get_option( 'mdp_settings' );
    $post_types_to_ignore   = mpd_get_post_types_to_ignore();

	foreach ($options as $key => $value) {
        //Find all the options in the options array that refer to post type display and assign them to a new key => value array
        if (substr($key, 0, 28) == "meta_box_post_type_selector_" && !in_array($value, $post_types_to_ignore)) {

            $post_types[] = $value;

        }

    }

    return $post_types;

}

/**
 * This function returns the post types that MPD has to show the metabox on based on the user desicion on settings
 * 
 * @since 0.4
 * @param none
 * @return array Containing post types that will show a MPD Metabox.
 * 
 * Example : 
 * 
 *      ['post', 'page']
 *  
*/
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
/**
 * This function returns the current default prefix for the duplication.
 * 
 * Returns either the core default value or the value of prefix saved by user in settings
 * 
 * @since 0.5
 * @param none
 * @return string
*/
function mpd_get_prefix(){

      if($options = get_option( 'mdp_settings' )){

            $prefix = $options['mdp_default_prefix'];

      }else{
            
            $defaultOptions   = mdp_get_default_options();
            $prefix           = $defaultOptions['mdp_default_prefix'];
            
      }

      return $prefix;
      
}

/**
 * Gets information on the featured image attached to a post
 * 
 * This function will get the meta data and other information on the posts featured image including the url
 * to the full size version of the image.
 * 
 * @since 0.5
 * @param int $post_id The ID of the post with that the featured image is attached to. 
 * @return array
 * 
 * Example 
 * 
 *          id => '23', 
 *          url => 'http://www.example.com/image/image.jpg',
 *          alt => 'Image Alt Tag', 
 *          description => 'Probably a big string of text here',
 *          caption => 'A nice caption for the image hopefully'
 * 
 */
function mpd_get_featured_image_from_source($post_id){

    $thumbnail_id   = get_post_thumbnail_id($post_id);
    $image          = wp_get_attachment_image_src($thumbnail_id, 'full' );

    if($image){

        $image_details = array(

            'url'           => $image[0],
            'alt'           => get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ),
            'post_title'    => get_post_field('post_title', $thumbnail_id),
            'description'   => get_post_field('post_content', $thumbnail_id),
            'caption'       => get_post_field('post_excerpt', $thumbnail_id),
            'post_name'     => get_post_field('post_name', $thumbnail_id)

        );

        $image_details = apply_filters( 'mpd_featured_image', $image_details );

        return $image_details;

    }
    
}
/**
 * This function performs the action of copying the featured image to the newly created post in 
 * the core function.
 * 
 * @since 0.5
 * @param int $destination_id The ID of the newly created post
 * @param array $image_details The details of the featured image to be copied. Linked to: mpd_get_featured_image_from_source()
 * which will generate the correct array structure for use here.
 * @return null
 * 
 */
function mpd_set_featured_image_to_destination($destination_id, $image_details){

    // Get the upload directory for the current site
    $upload_dir = wp_upload_dir();
    // Get all the data inside a file and attach it to a variable
    $image_data = file_get_contents($image_details['url']);
    // Get the file name of the source file
    $filename   = apply_filters('mpd_featured_image_filename', basename($image_details['url']), $image_details);

    // Make the path to the desired path to the new file we are about to create
    if( wp_mkdir_p( $upload_dir['path'] ) ) {

        $file = $upload_dir['path'] . '/' . $filename;

    } else {

        $file = $upload_dir['basedir'] . '/' . $filename;

    }

    // Add the file contents to the new path with the new filename
    file_put_contents( $file, $image_data );

    // Get the mime type of the new file extension
    $wp_filetype    = wp_check_filetype( $filename, null );
    // Get the URL (not the URI) of the new file
    $new_file_url   = $upload_dir['url'] . '/' . $filename;

    // Create the database information for this new image
    $attachment = array(

        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => $image_details['post_title'],
        'post_content'   => $image_details['description'],
        'post_status'    => 'inherit',
        'post_excerpt'   => $image_details['caption'],
        'post_name'      => $image_details['post_name'],
        //'guid'           => $new_file_url

    );

    // Attach the new file and its information to the database
    $attach_id = wp_insert_attachment( $attachment, $file, $destination_id );

    // Add alt text from the destination image
    if($image_details['alt']){

         update_post_meta($attach_id,'_wp_attachment_image_alt', $image_details['alt']);

    }
   
    // Include code to process functions below:
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Define attachment metadata
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

    // Assign metadata to attachment
    wp_update_attachment_metadata( $attach_id, $attach_data );

    // And finally assign featured image to post
    set_post_thumbnail( $destination_id, $attach_id );
    
}

/**
 * This function looks at the post_content of a post attempts to return all the id's of images used in the content
 * 
 * When adding an image to your post content from WordPress it will give the image a class of wp-image-{image id}
 * This function anticipates this behaviour and searchs the content of any intaces of this class structure and grabs
 * the {image id} and collects these id's into an array.
 * 
 * @since 0.5
 * @param int $post_id The ID of the post to analise
 * @return array 
 * 
 * Example: 
 * 
 *         ['20', '30', '1', '456']
 * 
 */
function mpd_get_images_from_the_content($post_id){
    
    //Collect the sourse content
    $html   = get_post_field( 'post_content', $post_id);
    $doc    = new DOMDocument();
    
    @$doc->loadHTML($html);
    //Now just focus on the image(s) within that post content
    $tags   = $doc->getElementsByTagName('img');
    
    if($tags){

        $images_objects_from_post = array();

        foreach ($tags as $tag) {
            //For all the images in that content get the class attribute and get the specific class
            //that WordPress adds to an image that indicates its ID.
            preg_match("/(?<=wp-image-)\d+/", $tag->getAttribute('class'),$matches);
            //Get the post object for the collected ID
            $image_obj = get_post($matches[0]);
            //Push this object into an array.
            $images_objects_from_post[$matches[0]] = $image_obj;

        }
        //Deliver the array of attachement objects to the core
        return $images_objects_from_post;
    }
    
}


/**
 * This function performs the action of copying the attached media image to the newly created post in 
 * the core function.
 * 
 * @since 0.5
 * @param int $destination_id The ID of the post we are copying the media to
 * @param array $post_media_attachments array of media library ids to copy. Probably generated from mpd_get_images_from_the_content()
 * @param array $attached_images_alt_tags array of alt tags associated with the images in $post_media_attachments array. Mirrors the array order for association.
 * Probably generated from mpd_get_image_alt_tags()
 * @param int $source_id The id of the blog these images are being copied from.
 * @param int $new_blog_id The id of the blog these images are going to.
 * @return null
 * 
 */
function mpd_process_post_media_attachements($destination_post_id, $post_media_attachments, $attached_images_alt_tags, $source_id, $new_blog_id ){

   // Variable to return the count of images we have process and also to patch the source keys with the desitination keys
   $image_count = 0;
   // Get array of the ids of the sourse images pulled from the sourse content
   $old_image_ids = array_keys($post_media_attachments);

   //Do stuff with each image from the sourse post content
   foreach ($post_media_attachments as $post_media_attachment) {

        // Get all the data inside a file and attach it to a variable
        $image_data             = file_get_contents($post_media_attachment->guid);
        // Break up the sourse URL into targetable sections
        $image_URL_info         = pathinfo($post_media_attachment->guid);
        //Just get the url without the filename extension...we are doing this because this will be the standard URL
        //for all the thumbnails attached to this image and we can therefore 'find and replace' all the possible
        //intermediate image sizes later down the line. See: https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
        $image_URL_without_EXT  = $image_URL_info['dirname'] ."/". $image_URL_info['filename'];
        //Do the find and replace for the site path
        // ie   http://www.somesite.com/source_blog_path/uploads/10/10/file... will become
        //      http://www.somesite.com/destination_blog_path/uploads/10/10/file...
        $image_URL_without_EXT  = str_replace(get_blog_details($new_blog_id)->path, get_blog_details($source_id)->path, $image_URL_without_EXT);

        $filename               = basename($post_media_attachment->guid);

        // Get the upload directory for the current site
        $upload_dir = wp_upload_dir();
        // Make the path to the desired path to the new file we are about to create
        if( wp_mkdir_p( $upload_dir['path'] ) ) {

            $file = $upload_dir['path'] . '/' . $filename;

        } else {

            $file = $upload_dir['basedir'] . '/' . $filename;

        }

        // Get the URL (not the URI) of the new file
        $new_file_url = $upload_dir['url'] . '/' . $filename;
        // Add the file contents to the new path with the new filename
        file_put_contents( $file, $image_data );
        // Get the mime type of the new file extension
        $wp_filetype = wp_check_filetype( $filename, null );

        $attachment = apply_filters('mpd_post_media_attachments', array(

            'post_mime_type' => 'image/jpeg',
            'post_title'     => sanitize_file_name( $filename ),
            'post_content'   => $post_media_attachment->post_content,
            'post_status'    => 'inherit',
            'post_excerpt'   => $post_media_attachment->post_excerpt,
            'post_name'      => $post_media_attachment->post_name,
            'guid'           => $new_file_url


        ), $post_media_attachment);

        // Attach the new file and its information to the database
        $attach_id = wp_insert_attachment( $attachment, $file, $destination_post_id );

        // Add alt text from the destination image
        if($attached_images_alt_tags){

              update_post_meta($attach_id,'_wp_attachment_image_alt', $attached_images_alt_tags[$image_count]);

        }
       
        // Include code to process functions below:
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Define attachment metadata
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

        // Assign metadata to attachment
        wp_update_attachment_metadata( $attach_id, $attach_data );

        // Now that we have all the data for the newly created file and its post we need to manimulate the old content so that
        // it now reflects the destination post
        $new_image_URL_without_EXT  = mpd_get_image_new_url_without_extension($attach_id, $source_id, $new_blog_id, $new_file_url);
        $old_content                = get_post_field('post_content', $destination_post_id);
        $middle_content             = str_replace($image_URL_without_EXT, $new_image_URL_without_EXT, $old_content);
        $update_content             = str_replace('wp-image-'. $old_image_ids[$image_count], 'wp-image-' . $attach_id, $middle_content);

        $post_update = array(
            'ID'           => $destination_post_id,
            'post_content' => $update_content       
        );

        wp_update_post( $post_update );

        $image_count++;
   }

}


/**
 * This function is to generate the image URL from the newly created media libray object for use in the core 
 * functions 'find and replace' action
 * 
 * @since 0.5
 * @param int $attach_id The id or the new image
 * @param int $source_id The id of the blog the image has come from
 * @param int $new_blog_id The id of the blog the image is going to
 * @param string $new_file_url The previosly generated URL for the new image
 * @return string
 * 
 */
function mpd_get_image_new_url_without_extension($attach_id, $source_id, $new_blog_id, $new_file_url){

        //Break the of the new image into segments
        $new_image_URL_with_EXT     = pathinfo($new_file_url);
        //Just get the url without the filename extension...we are doing this because this will be the standard URL
        //for all the thumbnails attached to this image and we can therefore 'find and replace' all the possible
        //intermediate image sizes later down the line. See: https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
        $new_image_URL_without_EXT  = $new_image_URL_with_EXT['dirname'] ."/". $new_image_URL_with_EXT['filename'];
        //Do the find and replace for the site path
        // ie   http://www.somesite.com/source_blog_path/uploads/10/10/file... will become
        //      http://www.somesite.com/destination_blog_path/uploads/10/10/file...
        $new_image_URL_without_EXT  = str_replace(get_blog_details($source_id)->path, get_blog_details($new_blog_id)->path, $new_image_URL_without_EXT);

        return $new_image_URL_without_EXT;
        
}

/**
 * This function works with mpd_get_images_from_the_content() and produces alt tags associated with a matching
 * array of image objects
 * 
 * @since 0.5
 * @param object $post_media_attachments Probably generated from mpd_get_images_from_the_content()
 * @return array List of alt tags to be copied in core matching the array order of mpd_get_images_from_the_content()
 * 
 */
function mpd_get_image_alt_tags($post_media_attachments){

    if($post_media_attachments){

        $alt_tags_to_be_copied = array();

        $attachement_count = 0;

        foreach ($post_media_attachments as $post_media_attachment) {

            $alt_tag = get_post_meta($post_media_attachment->ID, '_wp_attachment_image_alt', true);

            if($alt_tag){
                $alt_tags_to_be_copied[$attachement_count] = $alt_tag;
                $attachement_count++;
            }

        }

        $alt_tags_to_be_copied = apply_filters('mpd_alt_tag_array_from_post_content', $alt_tags_to_be_copied, $post_media_attachments);

        return $alt_tags_to_be_copied;

    }

}
/**
 * A helper function to help display the default state of a settings page checkbox
 * 
 * @since 0.4
 * @param array $options The option from the database
 * @param string $option_key The key from the options array you are checking
 * @param string $option_value The value you are checking against
 * @return string The markup to be added to the checkbox
 * 
 */
function mpd_checked_lookup($options, $option_key, $option_value){

    if(isset($options[$option_key])){

        $checkedLookup = checked( $options[$option_key], $option_value, false);

    }elseif(!$options){

        $checkedLookup = 'checked="checked"';

    }else{

        $checkedLookup = '';

    };

    echo $checkedLookup;


}
/**
 * Generates markup for the 'Success Notice' once the MDP core function has been run.
 * 
 * Once the markup has been generated it is then saved as an option in wp_options database for use once the page updates.
 * 
 * @since 0.5
 * @param string $site_name The site name of the destination blog
 * @param string $site_url The edit URL link of the destination blog
 * @param array $destination_blog_details An array of information about the detination blog. Passed over so the details can be used in
 * filter
 * @return string The markup to be added to the success notice
 * 
 */
function mdp_make_admin_notice($site_name, $site_url, $destination_blog_details){

    $message = apply_filters('mpd_admin_notice_text', '<div class="updated"><p>'. __('You succesfully duplicated this post to', MPD_DOMAIN ) ." ". $site_name.'. <a href="'.$site_url.'">'.__('Edit duplicated post', MPD_DOMAIN ).'</a></p></div>', $site_name, $site_url, $destination_blog_details);

    $option_value = get_option('mpd_admin_notice');

    if($option_value){

        $message = $option_value . $message;

    }      

    return $message;
    
}
/**
 * Displays the admin notice.
 * 
 * Once the notice has been displayed on the screen it is then delted form the database
 * 
 * @since 0.5
 * @param none
 * @return none
 * 
 */
function mpd_plugin_admin_notices(){

    // If there is a notice in the database display it.
    if($notices= get_option('mpd_admin_notice')){

         echo $notices;

    }
    // Now that we know the notice has been displayed we can delete its database entry
    delete_option('mpd_admin_notice');

}

/**
 * Helper function to create setting field in mpd.
 * 
 * Uses 'add settings field'. See https://codex.wordpress.org/Function_Reference/add_settings_field
 * 
 * @since 0.6
 * @param $tag string Unique name for settings field
 * @param $settings_title string Title for the settings field in on the settings page. (accepts markup)
 * @param $callback_function_to_markup string The name of the function to render setting markup
 * 
 * @return none
 * 
 */
function mpd_settings_field($tag, $settings_title, $callback_function_to_markup, $args = null){

  add_settings_field( 
      $tag, 
      __( $settings_title, MPD_DOMAIN ), 
      $callback_function_to_markup,
      MPD_SETTING_PAGE, 
      MPD_SETTING_SECTION,
      $args
  );

}
