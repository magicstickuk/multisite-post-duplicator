<?php
/**
 *
 * This file is a collection all non-WordPress functions that used throughout the plugin
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
  * @return array One dimentional array containing all post types to be ignored.
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
            //'attachment',
            

        )
    );

    return $post_types_to_ignore;

}

/**
 * Generate markup for a help icon with content within this plugin
 *
 * This function checks the settings for MPD and returns all the option values that are associated with post types
 *
 * @since 1.1.1
 * @param string $c The content of the help text for the icon
 * @return null
 *
*/
function mpd_information_icon($c){

    $u = uniqid(); ?>

    <script>
    
        jQuery(document).ready(function() {

            accordionClick('.<?php echo $u; ?>-click', '.<?php echo $u; ?>-content', 'fast');

        });

    </script>

    <i class="fa fa-info-circle <?php echo $u; ?>-click accord" aria-hidden="true"></i>

    <p class="mpdtip <?php echo $u; ?>-content" style="display:none"><?php _e($c, 'multisite-post-duplicator' )?></p>
    
    <?php
}

/**
 * Get a list of post types the user wants to show the MPD Metabox (if the 'Some Post Types' option was selected in settings)
 *
 * This function checks the settings for MPD and returns all the option values that are associated with post types
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
 * This function returns the all post types that the user has selected they want to display the MDP metabox on
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

    $default_opps = mdp_get_default_options();

      if($options = get_option( 'mdp_settings' )){

            

            $prefix   = isset($options['mdp_default_prefix']) ? $options['mdp_default_prefix'] : $default_opps['mdp_default_prefix'];

      }else{

            $prefix   = $default_opps['mdp_default_prefix'];

      }

      return $prefix;

}
/**
 * This function returns the current default status for the duplication.
 *
 * Returns either the core default value or the value of status saved by user in settings
 *
 * @since 1.2.1
 * @param none
 * @return string
*/
function mpd_get_status(){

    $default_opps = mdp_get_default_options();

      if($options = get_option( 'mdp_settings' )){

            $status    = isset($options['mdp_default_status']) ? $options['mdp_default_status'] : $default_opps['mdp_default_status'];

      }else{

            $status    = $default_opps['mdp_default_status'];

      }

      return $status;

}
/**
 * This function returns the value of custom meta keys to ignore upon duplication.
 *
 * @since 0.9
 * @param none
 * @return string
*/
function mpd_get_ignore_keys(){

    $default_opps = mdp_get_default_options();

      if($options = get_option( 'mdp_settings' )){

            $ignore_keys = isset($options['mdp_ignore_custom_meta']) ? $options['mdp_ignore_custom_meta'] : $default_opps['mdp_ignore_custom_meta'];

      }else{

            $ignore_keys = $default_opps['mdp_ignore_custom_meta'];

      }

      return $ignore_keys;

}
/**
 * Gets information on the featured image attached to a post
 *
 * This function will get the meta data and other information on the posts featured image; including the url
 * to the full size version of the image.
 *
 * @since 0.5
 * @param int $post_id The ID of the post that the featured image is attached to.
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

            'id'            => $thumbnail_id,
            'url'           => get_attached_file($thumbnail_id),
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
function mpd_set_featured_image_to_destination($destination_id, $image_details, $source_blog_id){
    
    // Get the upload directory for the current site
    $upload_dir = wp_upload_dir();
    // Get all the data inside a file and attach it to a variable

    // Get the file name of the source file
    $filename   = apply_filters('mpd_featured_image_filename', basename($image_details['url']), $image_details);

    // Make the path to the desired path to the new file we are about to create
    if( wp_mkdir_p( $upload_dir['path'] ) ) {

        $file = $upload_dir['path'] . '/' . $filename;

    } else {

        $file = $upload_dir['basedir'] . '/' . $filename;

    }

    // Add the file contents to the new path with the new filename
    

    if($the_original_id = mpd_does_file_exist($image_details['id'], $source_blog_id, get_current_blog_id())){

         // Get the mime type of the new file extension
        $wp_filetype = wp_check_filetype( $filename, null );

        $attachment = array(
            'ID' => $the_original_id,
            'post_parent' => $destination_id,
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => $image_details['post_title'],
            'post_content'   => $image_details['description'],
            'post_status'    => 'inherit',
            'post_excerpt'   => $image_details['caption'],
            'post_name'      => $image_details['post_name']
        );

        $attach_id = wp_insert_attachment( $attachment );

        // Include code to process functions below:
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Define attachment metadata
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

        // Assign metadata to attachment
        wp_update_attachment_metadata( $attach_id, $attach_data );

        // And finally assign featured image to post
        set_post_thumbnail( $destination_id, $attach_id );

    }else{

        if($image_details['url'] && $file){

            copy($image_details['url'], $file);

        }
        
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
            'post_name'      => $image_details['post_name']

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

        do_action('mpd_media_image_added', $attach_id, $source_blog_id, $image_details['id'] );

        // And finally assign featured image to post
        set_post_thumbnail( $destination_id, $attach_id );

    }  

}

/**
 * This function looks at the post_content of a post and attempts to return all the id's of images that are used in the content
 *
 * When adding an image to your post content in WordPress, WordPress it will give the image a class of wp-image-{image id}
 * This function anticipates this behaviour and searchs the content of any instances of this class structure and grabs
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
            // Save all elements needed to the duplication process
            $images_objects_from_post[ $matches[0] ] = array(
                'attached_file_path' => get_attached_file( $matches[0] ),
                'object'             => $image_obj
             );

        }
        //Deliver the array of attachment objects to the core
        return $images_objects_from_post;
    }

}


/**
 * This function performs the action of copying the attached media image(s) to the newly created post in
 * the core function.
 *
 * @since 0.5
 * @param int $destination_post_id The ID of the post we are copying the media to
 * @param array $post_media_attachments An array of media library IDs to copy. Probably generated from mpd_get_images_from_the_content()
 * @param array $attached_images_alt_tags An array of alt tags associated with the images in $post_media_attachments array. Mirrors the array order of this for association. Probably generated from mpd_get_image_alt_tags()
 * @param int $source_id The ID of the blog these images are being copied from.
 * @param int $new_blog_id The ID of the blog these images are going to.
 * @return null
 *
 */
function mpd_process_post_media_attachements($destination_post_id, $post_media_attachments, $attached_images_alt_tags, $source_id, $new_blog_id ){

   // Variable to return the count of images we have processed and also to patch the source keys with the destination keys
   $image_count = 0;
   // Get array of the IDs of the source images pulled from the source content
   $old_image_ids = array_keys($post_media_attachments);

   //Do stuff with each image from the source post content
   foreach ($post_media_attachments as $post_media_img_data) {

        // Get all the data inside a file and attach it to a variable
        $post_media_attachment  = $post_media_img_data['object'];
        $file_fullpath          = $post_media_img_data['attached_file_path'];

        if($file_fullpath && file_exists($file_fullpath)){

            // Break up the source URL into targetable sections
            $image_URL_info         = pathinfo(mpd_wp_get_attachment_url($post_media_attachment->ID, $source_id));
            //Just get the url without the filename extension...we are doing this because this will be the standard URL
            //for all the thumbnails attached to this image and we can therefore 'find and replace' all the possible
            //intermediate image sizes later down the line. See: https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
            $image_URL_without_EXT  = $image_URL_info['dirname'] ."/". $image_URL_info['filename'];
            //Do the find and replace for the site path
            // ie   http://www.somesite.com/source_blog_path/uploads/10/10/file... will become
            //      http://www.somesite.com/destination_blog_path/uploads/10/10/file...
            $image_URL_without_EXT  = str_replace(get_blog_details($new_blog_id)->siteurl, get_blog_details($source_id)->siteurl, $image_URL_without_EXT);

            $filename = basename($file_fullpath);

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
            $new_file_url = str_replace(get_blog_details($source_id)->siteurl, get_blog_details($new_blog_id)->siteurl, $new_file_url);

            if($the_original_id = mpd_does_file_exist($post_media_attachment->ID, $source_id, $new_blog_id)){
                
                // Get the mime type of the new file extension
                $wp_filetype = wp_check_filetype( $filename, null );

                $attachment = array(
                    'ID' => $the_original_id,
                    'post_parent' => $destination_post_id,
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title'     => sanitize_file_name( $filename ),
                    'post_content'   => $post_media_attachment->post_content,
                    'post_status'    => 'inherit',
                    'post_excerpt'   => $post_media_attachment->post_excerpt,
                    'post_name'      => $post_media_attachment->post_name,
                    'guid'           => $new_file_url
                );

                $attach_id = wp_insert_attachment( $attachment );

                // Include code to process functions below:
                require_once(ABSPATH . 'wp-admin/includes/image.php');

                // Define attachment metadata
                $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

                // Assign metadata to attachment
                wp_update_attachment_metadata( $attach_id, $attach_data );

            }else{

                // Add the file contents to the new path with the new filename
                copy($file_fullpath, $file);
                // Get the mime type of the new file extension
                $wp_filetype = wp_check_filetype( $filename, null );

                $attachment = apply_filters('mpd_post_media_attachments', array(

                    'post_mime_type' => $wp_filetype['type'],
                    'post_title'     => sanitize_file_name( $filename ),
                    'post_content'   => $post_media_attachment->post_content,
                    'post_status'    => 'inherit',
                    'post_excerpt'   => $post_media_attachment->post_excerpt,
                    'post_name'      => $post_media_attachment->post_name,
                    'guid'           => $new_file_url


                ), $post_media_attachment);

                // Attach the new file and its information to the database
                $attach_id = wp_insert_attachment( $attachment, $file, $destination_post_id );

                // Add alt text to the destination image
                if($attached_images_alt_tags){

                      update_post_meta($attach_id,'_wp_attachment_image_alt', $attached_images_alt_tags[$image_count]);

                }

                // Include code to process functions below:
                require_once(ABSPATH . 'wp-admin/includes/image.php');

                // Define attachment metadata
                $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

                // Assign metadata to attachment
                wp_update_attachment_metadata( $attach_id, $attach_data );

                do_action('mpd_media_image_added', $attach_id, $source_id, $post_media_attachment->ID);


            }

             // Now that we have all the data for the newly created file and its post we need to manipulate the old content so that
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

}


/**
 * This function is to generate the image URL from the newly created media library object for use in the core
 * functions 'find and replace' action
 *
 * @since 0.5
 * @param int $attach_id The ID of the new image
 * @param int $source_id The ID of the blog the image has come from
 * @param int $new_blog_id The ID of the blog the image is going to
 * @param string $new_file_url The previously generated URL for the new image
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
        // This step is not required if the source site is the network main site
        if(network_site_url() != get_blog_details($source_id)->siteurl . "/"){
            $new_image_URL_without_EXT  = str_replace(get_blog_details($source_id)->siteurl, get_blog_details($new_blog_id)->siteurl, $new_image_URL_without_EXT);
        }

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

            $alt_tags_to_be_copied[$attachement_count] = $alt_tag;

            $attachement_count++;


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
function mpd_checked_lookup($options, $option_key, $option_value, $type = null){

    if(isset($options[$option_key])){
        if($type=='select'){

            $checkedLookup = selected( $options[$option_key], $option_value, false);

        }

        $checkedLookup = checked( $options[$option_key], $option_value, false);

    }elseif(!$options){
        if($type=='select'){
            $checkedLookup = 'selected="selected"';
        }
        $checkedLookup = 'checked="checked"';

    }else{

        $checkedLookup = '';

    };

    echo $checkedLookup;


}
/**
 * Generates markup for the 'Success Notice' once the MPD core function has been run.
 *
 * Once the markup has been generated it is then saved as an option in wp_options database for use when the page loads.
 *
 * @since 0.5
 * @param string $site_name The site name of the destination blog
 * @param string $site_url The edit URL link of the destination blog
 * @param array $destination_blog_details An array of information about the destination blog. Passed over so the details can be used in
 * filter
 * @return string The markup to be added to the success notice
 *
 */
function mdp_make_admin_notice($site_name, $site_url, $destination_blog_details){

    $option_value = get_option('mpd_admin_notice');

    global $post;

    $parts = parse_url($site_url);

    if(isset($parts['query'])){
        parse_str($parts['query'], $query);
    }
    
    $args= array(

        'source_id'             => get_current_blog_id(),
        'destination_id'        => $destination_blog_details->blog_id,
        'source_post_id'        => $post->ID,
        'destination_post_id'   => isset($query['post']) ? $query['post'] : 0

    );

    if(mpd_is_there_a_persist_exact($args)){

        $filter     = 'mpd_admin_persist_notice_text';
        $editText   = 'You updated your linked post on ';
        $editLink   = 'Edit updated post';

    }else{

        $filter     = 'mpd_admin_notice_text';
        $editText   = 'You succesfully duplicated this post to ';
        $editLink   = 'Edit duplicated post';

    }

    $message        = '<div class="updated"><p>';
    
    $message       .= apply_filters(
                        $filter,
                        __($editText, 'multisite-post-duplicator'),
                        $site_name,
                        $site_url,
                        $destination_blog_details
                      );

    $message       .=   $site_name.
                        ' <a href="'.$site_url.'">'.
                        __($editLink, 'multisite-post-duplicator' ).
                        '</a>';
    
    $message       .= '</p></div>';

    if(!$option_value){

        $notice_data    = array(
            'name'        => $site_name,
            'url'         => $site_url,
            'blog_object' =>  $destination_blog_details->blogname
        );

        $option_value   = array('message' => $message, 'data' => array($notice_data) );  
        
    }else{

        $notice_data_new = array(
            'name'        => $site_name,
            'url'         => $site_url,
            'blog_object' => $destination_blog_details->blogname
        );

        // Prevent Duplicate notices going on screen.
        foreach ($option_value['data'] as $key => $value) {

            if($value == $notice_data_new){
                return;
            }

        }

        $option_value['message'] = $option_value['message'] . $message;

        array_push($option_value['data'], $notice_data_new);

    }
    //Add this collected notice to the database because the new page needs a method of getting this data
    //when the page refreshes
    update_option('mpd_admin_notice', $option_value );

    return $message;

}

/**
 * Displays the admin notice.
 *
 * Once the notice has been displayed on the screen it is then deleted form the database
 *
 * @since 0.5
 * @param none
 * @return none
 *
 */
function mpd_plugin_admin_notices(){

    // If there is a notice in the database display it.
    if($notices = get_option('mpd_admin_notice')){

        echo $notices['message'];
        // Now that we know the notice has been displayed we can delete its database entry
        delete_option('mpd_admin_notice');

    }

    do_action('mpd_after_notices');
    
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
 * @param $args string Any arguments you want to pass to the function
 *
 * @return none
 *
 */
function mpd_settings_field($tag, $settings_title, $callback_function_to_markup, $args = null){

  add_settings_field(
      $tag,
      __( $settings_title, 'multisite-post-duplicator' ),
      $callback_function_to_markup,
      MPD_SETTING_PAGE,
      MPD_SETTING_SECTION,
      $args
  );

}

/**
 * This function allows for hooking into the sites returned in the WP core wp_get_sites() function.
 *
 * Uses 'add settings field'. See https://codex.wordpress.org/Function_Reference/add_settings_field
 *
 * @since 0.6
 *
 * @return array A (filtered?) array of all the sites on the network,
 *
 */
function mpd_wp_get_sites(){

    if(is_multisite()){
      global $wp_version;
      $args           = array('network_id' => null);
      $is_pre_4_6     = version_compare( $wp_version, '4.6-RC1', '<' );

      if($is_pre_4_6){

            $new_sites  = array();
            $sites      = wp_get_sites($args);
            $object     = new stdClass();

            foreach (wp_get_sites($args) as $site){

                $object = (object) $site;
                array_push($new_sites, $object);

            }

            $sites = $new_sites;

      }else{
        
            $args['number'] = null;
            $args = apply_filters('mpd_get_sites_args', $args);
            $sites = get_sites($args);

      }

      $filtered_sites = apply_filters('mpd_global_filter_sites', $sites);

      return $filtered_sites;

    }

}

/**
 * [mpd_fix_wordpress_urls this function fix URLs that are missing the HTTP protocol. It support HTTP and HTTPS]
 * @param  [string] $url_input [URL that may not have a protocol]
 * @return [string]            [URL with protocol]
 *
 * @since 0.7
 */
function mpd_fix_wordpress_urls($url_input) {
	// Wordpress can have URLs missing the protocol (ssl website), so we have to check if the protocol is set
	$protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
	$url = preg_replace("/(^\/\/)/", $protocol, $url_input);
	return $url;
}

/**
 * This function alerts the user if they have installed this plugin on a non multisite installation
 * or if thier confiuration is not supported.
 *
 * @since 0.7.3
 *
 * @return string
 *
 */
function mpd_non_multisite_admin_notice() {
    
    if (!is_multisite()) {

        ?>

        <div class='error'><p>
            <?php _e('You have activated Multisite Post Duplicator on this WordPress Installation but this is not a Multisite Network. In the interest of your websites efficiency we would advise you deactivate the plugin until you are using a Multisite Network','multisite-post-duplicator');?>
            </p>
        </div>

        <?php
        
    }

    if(mpd_is_subdomain_install() && !get_site_option('mpd_has_dismissed_subdomain_error')){
            
        ?>
            
        <div class='not-subdomain error notice is-dismissible'><p>
            <?php _e('You have activated Multisite Post Duplicator on this WordPress Installation however this network has the subdomain configuration enabled. This plugin is untested on subdomain configurations. While it should work fine for most functions you may notice issues with images being copied over to destination sites. We are working to bring full subdmain support as soon as possible.', 'multisite-post-duplicator' ); ?>
            </p>
        </div>
        
        <?php

    }

}

add_action('admin_notices', 'mpd_non_multisite_admin_notice');

/**
 * Adds ajax to subdomain error message dismiss button so we can control if we want to display the message in the future
 *
 * @since 0.9.5
 *
 * @return null
 *
 */
function mpd_notices_javascript(){

    if(mpd_is_subdomain_install()){
    ?>
    <script>
        jQuery(document).on('ready', function() {

            jQuery('.not-subdomain .notice-dismiss').click(function(){

                jQuery.ajax({
                    url : ajaxurl,
                    type : 'post',
                    data : {
                        action : 'mpd_dismiss_subdomain_notice'
                    }
                });

             });
          
        });
    </script>
    <?php

    }

}

add_action('admin_head', "mpd_notices_javascript");

/**
 * This function is called when a user clicks to dismiss the subdamin error message. It creates an option
 * in the network options table that tells us not to display the message again as it has been dismissed
 *
 * @since 0.9.5
 *
 * @return null
 *
 */
function mpd_dismiss_subdomain_notice(){

    update_site_option('mpd_has_dismissed_subdomain_error', 1);

    die();

}

add_action('wp_ajax_mpd_dismiss_subdomain_notice','mpd_dismiss_subdomain_notice');

/**
 * This function allows for user control of the available statuses the can be used in the duplicated post
 *
 * @since 0.7.4
 *
 * @return array of post status available to duplicated post
 *
 */
function mpd_get_post_statuses(){

    $available_statuses = apply_filters('mpd_available_post_statuses', get_post_statuses());
    
    return $available_statuses;

}

/**
 * This function gets all the categories that a post is assigned to
 *
 * @since 0.8
 * @param $post_id The id of the post that we want to get the categories for
 * @param $post_type The post type of the post that we want to get the categories for
 *
 * @return array An array of the category objects.
 *
 */
function mpd_get_objects_of_post_categories($post_id, $post_type){

    return mpd_get_post_taxonomy_terms($post_id, true, false);

}

/**
 * 
 * This function gets all the categories currently available on the site
 *
 *
 * @since 0.8
 * @param $post_type The post type of the post that we want to get the categories for
 *
 * @return array An array of the category objects.
 *
 */
function mpd_get_objects_of_site_categories($post_type){

    $args = array(
        'type' => $post_type,
        "hide_empty" => 0,
    );

    $all_categories = get_terms( 'category', $args );

    return $all_categories;

}

/**
*
 * This function performs the action of taking the categories of the sourse post
* and assigning the categories to the new destination post. If the category doesn't
* exist in the destination site it will create the category
*
* @since 0.8
* @param $post_id The the ID of the newly created destination post
* @param $source_categories An array of category objects from the source post
* @param $post_type The post type of the post that we assign the categories to
*
 * @return NULL
*
*/
function mpd_set_destination_categories($post_id, $source_categories, $post_type){
    mpd_set_post_taxonomy_terms($post_id, $source_categories);
    return;
}


function mpd_has_parent_terms($terms) {
    foreach ($terms as $term) {
        if ($term->parent != 0) {
            return true;
        }
    }
    return false;
}

/**
 * 
 * This function performs the action of taking the taxonomies of the source post and
 * collecting them into an array of objects for use when duplicating to the destination.
 * Works with mpd_set_post_taxonomy_terms();
 *
 * @since 0.9
 * @param $post_id The the ID of post being copied
 * @param $category_only if true, only categories are handled, if false -- all
 * other taxonomies (excluding tags)
 * @param $destination_id The the ID destination site (for validations)
 * @return array An array of term objects used in the post
 *
 */
function mpd_get_post_taxonomy_terms($post_id, $category_only, $destination_id) {

    $source_taxonomy_terms_object = array();

    $post_taxonomies = get_object_taxonomies( get_post_type($post_id), 'names' );

    foreach ($post_taxonomies as $post_taxonomy) {

        if ($post_taxonomy == 'post_tag') {
            continue;
        }
        if (($post_taxonomy == 'category' && $category_only) || ($post_taxonomy != 'category' && !$category_only)) {

            $post_terms = wp_get_post_terms($post_id, $post_taxonomy);

            if (mpd_has_parent_terms($post_terms)) {
                $all_terms = get_terms($post_taxonomy, array(
                    'type' => get_post_type($post_id),
                    'hide_empty' => 0
                ));
            } else {
                $all_terms = null;
            }

            $source_taxonomy_terms_object[$post_taxonomy] = array($post_terms, $all_terms);
        }
    }

    return apply_filters('mpd_post_taxonomy_terms', $source_taxonomy_terms_object, $destination_id);
}

function &mpd_hash_obj_by($obj_array = false, $key) {

    $res = array();

    if($obj_array){

        foreach ($obj_array as &$obj) {

            $res[$obj->$key] = $obj;

        }

    }
    
    unset($obj);

    return $res;

}

function mpd_add_term_recursively($post_term, &$orig_all_terms_by_id, &$all_terms_by_slug) {

    if (array_key_exists($post_term->slug, $all_terms_by_slug)) {

        return $all_terms_by_slug[$post_term->slug]->term_id;

    }
    // does not exist

    if ($post_term->parent != 0) {

        $parent_id = mpd_add_term_recursively($orig_all_terms_by_id[$post_term->parent], $orig_all_terms_by_id, $all_terms_by_slug);

    } else {

        $parent_id = 0;

    }

    $new_term = wp_insert_term($post_term->name, $post_term->taxonomy, array(
        'description' => $post_term->description,
        'slug' => $post_term->slug,
        'parent' => $parent_id
    ));

    $all_terms_by_slug[$post_term->slug] = (object) $new_term;
    
    return $new_term['term_id'];
}

/**
 * 
 * This function performs the action of setting the taxonomies of the source post and
 * to the destination post.
 * Works with mpd_get_post_taxonomy_terms();
 *
 * @since 0.9
 * @param $post_id The ID of the newly created post
 * @param $source_taxonomy_terms_object An array of term objects used in the source post
 * 
 * @return array An array of term objects used in the post
 *
 */
function mpd_set_post_taxonomy_terms($post_id, $source_taxonomy_terms_object) {

    foreach ($source_taxonomy_terms_object as $tax => &$tax_data) {

        $orig_post_terms = $tax_data[0];

        $orig_all_terms = array_key_exists(1, $tax_data) ? $tax_data[1] : array();

        $all_terms = get_terms($tax, array(
            'type' => get_post_type($post_id),
            'hide_empty' => 0
        ));

        $orig_all_terms_by_id   = &mpd_hash_obj_by($orig_all_terms, 'term_id');
        $all_terms_by_slug      = &mpd_hash_obj_by($all_terms, 'slug');

        $dest_post_term_ids = array();

        foreach ($orig_post_terms as &$post_term) {

            array_push($dest_post_term_ids, mpd_add_term_recursively($post_term, $orig_all_terms_by_id, $all_terms_by_slug));

        }

        unset($post_term);

        wp_set_object_terms($post_id, $dest_post_term_ids, $tax);

    }

    unset($tax_data);
}

/**
 * 
 * This function filters out post meta keys from the post meta as requested in the 'keys to ignore' mpd settings page.
 *
 * @since 0.9
 * @param $post_meta_array The source post post_meta
 * 
 * @return array A filtered array without the keys as specified in 'keys to ignore' in mpd settings
 *
 */
function mpd_ignore_custom_meta_keys($post_meta_array){
	
	$options = get_option( 'mdp_settings' );
	
	$meta_to_ignore_raw = str_replace(' ', '', $options['mdp_ignore_custom_meta']);
	$meta_to_ignore 	= explode(',', $meta_to_ignore_raw);

	$new_post_meta 	= array();
	
	foreach($post_meta_array as $meta_key => $meta_value){
		
		if(!in_array($meta_key, $meta_to_ignore)){

			$new_post_meta[$meta_key] = $meta_value;
            		
		}
		
	}
	
	return $new_post_meta;
	
}

add_filter('mpd_filter_post_meta', 'mpd_ignore_custom_meta_keys');
add_filter('mpd_filter_persist_post_meta', 'mpd_ignore_custom_meta_keys');

/**
 * 
 * This function gets An 'edit url' for a post.
 *
 * @since 1.0
 * @param $blog_id The blog_id where the post is
 * @param $post_id The post_id you want to link to
 * 
 * @return string An edit url for a post
 *
 */
function mpd_get_edit_url($blog_id, $post_id){

    $url = get_admin_url($blog_id, 'post.php?post='. $post_id. '&action=edit');

    return $url;

}

function mpd_get_version(){

    $version_number = get_option( 'mdp_version' );

    return $version_number;
}

/**
 * 
 * If the user chooses to, this function will collect the post date to of the source post to be used later
 * in mpd_set_published_date() to assign the published date to be the same as the destination.
 *
 * @since 0.9.4
 * @param $mpd_process_info Array of the source post information
 * 
 * @return array A filtered array of source post information
 *
 */
function mpd_get_published_date($mpd_process_info){

 $options = get_option( 'mdp_settings' );

 if(isset($options['mdp_retain_published_date'])){
    $mpd_process_info['post_date'] = get_post_field('post_date', $mpd_process_info['source_post_id']);
 }
 
 return $mpd_process_info;

}

add_filter('mpd_source_data', 'mpd_get_published_date');

/**
 * 
 * If the user chooses to, this function will set the post date to of the source post to the destination post. Note we have to set the post_status to publish for this activity as no published date would be assigned otherwise.
 *
 * @since 0.9.4
 * @param $mdp_post Array of the destination post info prior to post being created in database
 * @param $mpd_process_info Array of the source post information
 * 
 * @return array A filtered array of destination post information prior to post being saved in database
 *
 */
function mpd_set_published_date($mdp_post, $mpd_process_info){

  $options = get_option( 'mdp_settings' );

  if(isset($options['mdp_retain_published_date'])){

    $mdp_post['post_date'] = $mpd_process_info['post_date'];
    $mdp_post['post_status'] = 'publish';

  }
 
  return $mdp_post;

}

add_filter('mpd_setup_destination_data', 'mpd_set_published_date', 10,2);

/**
 *
 * If the user chooses to, this function will copy the publish date to the
 * copied post.
 *
 * @param $mdp_post Array of the destination post info prior to post being created in database
 * @param $persist_post Object of source post being duplicated
 *
 * @return array Modified $mpd_post that would be used for duplicate post creation
 */
function mpd_update_published_date($mpd_post, $persist_post) {

  $options = get_option( 'mdp_settings' );

  if(isset($options['mdp_retain_published_date'])){
      $mpd_post['post_date'] = get_post_field('post_date', $persist_post->source_post_id);
  }

  return $mpd_post;
  
}

add_filter('mpd_setup_persist_destination_data', 'mpd_update_published_date', 10, 2);


/**
 * 
 * Do the markup for a link to MDP setting page
 *
 * @since 1.0
 *
 */
function mpd_do_settings_link(){

    ?> <p class="bottom-para">

            <small>
                <a class="no-dec" target="_blank" title="Multisite Post Duplicator Settings" href="<?php echo esc_url( get_admin_url(null, 'options-general.php?page=multisite_post_duplicator') ); ?>"> Settings <i class="fa fa-sliders fa-lg" aria-hidden="true"></i></a>
            </small>
                
        </p>

    <?php

}
add_action('mpd_after_metabox_content', 'mpd_do_settings_link');

/**
 * 
 * Custom wraper for WordPress' core is_subdomain_install. Need to wrap this so we don't get errors if the plugin is installed
 * on a non-multisite istallation
 *
 * @since 1.1
 * 
 * @return boolean True if this is a multisite network.
 *
 */
function mpd_is_subdomain_install(){

    if(function_exists('is_subdomain_install')){
        
        return is_subdomain_install();
        
    }else{

        return false;

    }

}
/**
 * 
 * Helper function to search a multidimentional array and return results for a matching key/value pair.
 * Got it from: http://stackoverflow.com/questions/1019076/how-to-search-by-key-value-in-a-multidimensional-array-in-php
 *
 * @since 1.2
 * @param $array Array to be searched
 * @param $key The key we are looking at
 * @param $value The value we want to return results for
 * 
 * @return array An array of reults matching the criteria
 *
 */
function mpd_search($array, $key, $value){
    $results = array();

    if (is_array($array)) {
        if (isset($array[$key]) && $array[$key] == $value) {
            $results[] = $array;
        }

        foreach ($array as $subarray) {
            $results = array_merge($results, mpd_search($subarray, $key, $value));
        }
    }

    return $results;
}

/**
 * 
 * Helper function process a duplication of an image file from the source to the destination. Has to be run while 'in' the destination site
 *
 * @since 1.3
 * @param $attachment Array of data about the attachment that will be written into the wp_posts table of the database.
 * @param $img_url The URL of the image to be copied
 * @param $post_id The new post ID that the image has to be assigned to.
 * 
 * @return int The id of the newly created image
 *
 */
function mpd_copy_file_to_destination($attachment, $img_url, $post_id = 0, $source_id, $file_id){

    $info       = pathinfo($img_url);
    $file_name  = basename($img_url,'.'.$info['extension']);

     // Get the upload directory for the current site
    $upload_dir = wp_upload_dir();
    // Make the path to the desired path to the new file we are about to create
    if( wp_mkdir_p( $upload_dir['path'] ) ) {

        $file = $upload_dir['path'] . '/' . $file_name .'.'. $info['extension'];

    } else {

        $file = $upload_dir['basedir'] . '/' . $file_name .'.'. $info['extension'];

    }
    
    if($the_original_id = mpd_does_file_exist($file_id, $source_id, get_current_blog_id())){
        
        return $the_original_id;

    }
    
    $filtered_url = mpd_fix_wordpress_urls($img_url);

    if($filtered_url && $filtered_url != ''){

        copy($filtered_url, $file);

        $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
        // Include code to process functions below:
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Define attachment metadata
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

        wp_update_attachment_metadata( $attach_id, $attach_data );

        do_action('mpd_media_image_added', $attach_id, $source_id, $file_id);
        
    }
   

    return $attach_id;

}
/**
 * 
 * Helper function to get the table name of a perticular table on a specific site
 *
 * @since 1.5
 * @param int $blogid The id of the blog to want the table name from
 * @param string $table The standard table name without prefix. Default is posts
 * 
 * @return string The rtable name with appropriate prefix
 *
 */
function mpd_get_tablename($blogid, $table = 'posts'){
    
    global $wpdb;

    $siteid         = $blogid != 1 ? $blogid . "_" : ''; 
    $tablename      = $wpdb->base_prefix . $siteid . $table;

    return $tablename;
}

/**
 * 
 * UI Function which provides a button to select all checkboxes in the MPD post metabox
 *
 * @since 1.5
 * 
 * @return null
 *
 */
function  mpd_select_all_checkboxes(){

    if(apply_filters('mpd_show_select_all_checkboxes', true)) :?>
        
        <?php
            $first_text     = __('Select all except current', 'multisite-post-duplicator');
            $second_text    = __('Select none', 'multisite-post-duplicator');
        ?>
        <p><small><a id="mpd-select-all" href="#"><?php echo $first_text; ?></a></small></p>

         <script>
            jQuery(document).ready(function() {

                jQuery("#mpd-select-all").click(function(){
                    
                    if(jQuery(this).html() == '<?php echo $first_text; ?>'){ 
                        jQuery('#mpd_blogschecklist input:checkbox:not(.mpd-current-site)').prop('checked', 'checked');
                        jQuery(this).html('<?php echo $second_text; ?>');
                    }else{
                        jQuery(this).html('<?php echo $first_text; ?>');
                        jQuery('#mpd_blogschecklist input:checkbox').removeProp('checked');
                    }
                    
                    jQuery('#mpd_blogschecklist .mpd-site-checkbox input').trigger('change');  
                });

            });
        </script>

    <?php endif; 

}

add_action('mpd_after_metabox_content', 'mpd_select_all_checkboxes', 5);

/**
 * 
 * Function to control the possibility of infinite loops when duplicating.
 *
 * @since 1.5
 * 
 * @return null
 *
 */
function mpd_weve_seen_the_page(){

    delete_site_option('avoid_infinite');
    delete_site_option('avoid_infinite_persist');

}

add_action('shutdown', 'mpd_weve_seen_the_page');

function mpd_process_persist( $post_id, $destination_id, $created_post = false){

    if(isset($_POST['persist'])){
                    
        $args = array(

            'source_id'      => get_current_blog_id(),
            'destination_id' => $destination_id,
            'source_post_id' => $_POST['ID'],
            'destination_post_id' => $created_post['id']

        );
                    
        mpd_add_persist($args);

    }

}
add_action('mpd_single_metabox_after', 'mpd_process_persist', 10, 3);

function mpd_skip_standard_duplication($choice){

    if(get_option('skip_standard_dup')){
                
        delete_option('skip_standard_dup' );
        
        return $choice = false;

    }

    return $choice;

}

add_filter('mpd_do_single_metabox_duplication', 'mpd_skip_standard_duplication', 20); //Note priority higher than mpd_copy_acf_field_group()

/**
 * 
 * Hooks into mpd_enter_the_loop funciton and set the conditions in which the multisite
 * post duplication processes can be accessed
 *
 * @since 1.5.5
 * @param boolean $choice The initial state if we are allowed into the loop
 * @param array $post_global the global post object on the form submit
 * @param int $post_id the ID of the post being saved
 * 
 * @return boolean The desision to continue or not
 *
 */
function mpd_enter_the_loop($choice, $post_global, $post_id){

    if(( isset($post_global["post_status"] ) ) 
            && ( $post_global["post_status"] != "auto-draft" )
            && ( isset($post_global['mpd_blogs'] ) )
            && ( count( $post_global['mpd_blogs'] ) )
            && ( $post_global["post_ID"] == $post_id )
            ){
        return true;
    }

    return false;

}
add_filter('mpd_enter_the_loop', 'mpd_enter_the_loop', 10, 3);

/**
 * 
 * Get media URL on any sub site by ID
 *
 * @since 1.6.5
 * @param int $ID The ID of the media we want the URL of
 * @param int $source_blog The blog id this media file resides.
 * 
 * @return string The URL of the media file
 *
 */
function mpd_wp_get_attachment_url($ID, $source_blog){

    switch_to_blog($source_blog);
        $attachment_url = wp_get_attachment_url($ID);
    restore_current_blog();

    return $attachment_url;

}

/**
 * 
 * Log the duplication of a media file by creating two meta files
 *
 * @since 1.6.6
 * @param int $attach_id The ID of the media we want to log
 * @param int $source_id The blog id this media file resides.
 * @param int $source_attachment_id The original ID of the image on the source site
 * 
 * @return null
 *
 */
function mpd_log_media_file($attach_id, $source_id, $source_attachment_id){

    $meta_id = update_post_meta($attach_id, 'mpd_media_source_' . $source_id, $source_attachment_id);

    switch_to_blog($source_id);
        $post_modified = get_post_field('post_modified', $source_attachment_id, 'raw');
    restore_current_blog();

    update_post_meta($attach_id, 'mpd_meta_id_' . $meta_id, $post_modified);
}


add_action('mpd_media_image_added', 'mpd_log_media_file', 10, 3);

/**
 * 
 * Check if a copy of a media file exists on another blog
 * Uses the logging structure as set in mpd_log_media_file();
 *
 * @since 1.6.6
 * @param int $source_file_id The ID of the media we want to check
 * @param int $source_id The blog id this media file resides.
 * @param int $destination_id The blog id to check if there is a copy on
 * 
 * @return booleon True if it does exist and false if it doesn't
 *
 */
function mpd_does_file_exist($source_file_id, $source_id, $destination_id){

    //If source file id exists for source site on destination site
    global $wpdb;

    $destination_tablename = mpd_get_tablename($destination_id, 'postmeta');

    $meta_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT meta_id FROM $destination_tablename  WHERE meta_key = %s AND meta_value = %d",
            'mpd_media_source_' .$source_id, $source_file_id
        )
    );

    //If there is a catch check if the current mod time matches the logged
    if(null !== $meta_id){

        $source_tablename = mpd_get_tablename($source_id);

        $current_mod_time = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_modified FROM $source_tablename WHERE ID = %d",
                $source_file_id
            )
        );

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $destination_tablename  WHERE meta_key = 'mpd_meta_id_%d'",
                $meta_id
            )
        );

        if(null !== $row){
           $past_mod_time = $row->meta_value;

            if($past_mod_time == $current_mod_time){
                return $row->post_id;

            }else{
                return false;
            } 
        }
        
    }

    return false;

}

function mpd_process_meta($post_id, $meta_values){

    if($meta_values){

        foreach ($meta_values as $key => $values) {
        
           if(substr( $key, 0, 3 ) !== "mpd_"){

                foreach ($values as $value) {

                    //If the data is serialised we need to unserialise it before adding or WordPress will serialise the serialised data
                    //...which is bad

                    if(is_serialized($value)){
                     
                        update_post_meta( $post_id, $key, unserialize($value));

                    }else{

                        update_post_meta( $post_id, $key, $value );

                    }
               
                }

           }

        }
        
    }
    
}

