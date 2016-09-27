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
            'attachment'

        )
    );

    return $post_types_to_ignore;

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

      if($options = get_option( 'mdp_settings' )){

            $prefix = $options['mdp_default_prefix'];

      }else{

            $defaultOptions   = mdp_get_default_options();
            $prefix           = $defaultOptions['mdp_default_prefix'];

      }

      return $prefix;

}
/**
 * This function returns the value of keys to ignore.
 *
 * @since 0.9
 * @param none
 * @return string
*/
function mpd_get_ignore_keys(){

      if($options = get_option( 'mdp_settings' )){

            $ignore_keys = $options['mdp_ignore_custom_meta'];

      }else{

            $defaultOptions	= mdp_get_default_options();
            $ignore_keys    = $defaultOptions['mdp_ignore_custom_meta'];

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
function mpd_set_featured_image_to_destination($destination_id, $image_details){

    // Get the upload directory for the current site
    $upload_dir = wp_upload_dir();
    // Get all the data inside a file and attach it to a variable
    $image_data = file_get_contents(mpd_fix_wordpress_urls($image_details['url']));
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

    // And finally assign featured image to post
    set_post_thumbnail( $destination_id, $attach_id );

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
            $images_objects_from_post[$matches[0]] = $image_obj;

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
   foreach ($post_media_attachments as $post_media_attachment) {

        // Get all the data inside a file and attach it to a variable
        $image_data             = file_get_contents(mpd_fix_wordpress_urls($post_media_attachment->guid));
        // Break up the source URL into targetable sections
        $image_URL_info         = pathinfo($post_media_attachment->guid);
        //Just get the url without the filename extension...we are doing this because this will be the standard URL
        //for all the thumbnails attached to this image and we can therefore 'find and replace' all the possible
        //intermediate image sizes later down the line. See: https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
        $image_URL_without_EXT  = $image_URL_info['dirname'] ."/". $image_URL_info['filename'];
        //Do the find and replace for the site path
        // ie   http://www.somesite.com/source_blog_path/uploads/10/10/file... will become
        //      http://www.somesite.com/destination_blog_path/uploads/10/10/file...

        $image_URL_without_EXT  = str_replace(get_blog_details($new_blog_id)->siteurl, get_blog_details($source_id)->siteurl, $image_URL_without_EXT);

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
        $new_file_url = str_replace(get_blog_details($source_id)->siteurl, get_blog_details($new_blog_id)->siteurl, $new_file_url);

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

        // Now that we have all the data for the newly created file and its post we need to manipulate the old content so that
        // it now reflects the destination post
        $new_image_URL_without_EXT  = mpd_get_image_new_url_without_extension($attach_id, $source_id, $new_blog_id, $new_file_url);
        $old_content                = get_post_field('post_content', $destination_post_id);
        $middle_content             = str_replace($image_URL_info['dirname'] ."/". $image_URL_info['filename'], $new_image_URL_without_EXT, $old_content);
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
 * Once the notice has been displayed on the screen it is then deleted form the database
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
 * @param $args string Any arguments you want to pass to the function
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
        echo "<div class='error'><p>You have activated <a href='https://en-gb.wordpress.org/plugins/multisite-post-duplicator/' target='_blank'>Multisite Post Duplicator</a> on this WordPress Installation but this is not a <a target='_blank' href='http://codex.wordpress.org/Create_A_Network'>Multisite Network</a>. In the interest of your websites efficiency we would advise you deactivate the plugin until you are using a <a target='_blank' href='http://codex.wordpress.org/Create_A_Network'>Multisite Network</a></p></div>";
    }

    if(is_subdomain_install()){
            echo "<div class='error'><p>You have activated <a href='https://en-gb.wordpress.org/plugins/multisite-post-duplicator/' target='_blank'>Multisite Post Duplicator</a> on this WordPress Installation however this network has the subdomain configuration enabled. Unfortunately this plugin doesn't support Subdomain configurations at this time. Please accept our apologies and check back as we hope to support it soon.</div>";
    }
}

add_action('admin_notices', 'mpd_non_multisite_admin_notice');

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

    $args = array(
        'type' => $post_type,
    );
    $categories = wp_get_post_categories($post_id, $args);
   
    $array_of_category_objects = array();

    foreach ($categories as $category) {
        array_push($array_of_category_objects, get_category($category));
    }

    return $array_of_category_objects;

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
 
    $all_destination_categories = mpd_get_objects_of_site_categories($post_type);
 
    $destination_post_categories = array();
 
    foreach ($source_categories as $source_category) {
 
        $source_slug = $source_category->slug;
 
        if($source_slug != 'uncategorised'){
 
             foreach ($all_destination_categories as $destination_category) {
 
                if($destination_category->slug == $source_slug){
 
                    $category = get_category_by_slug( $destination_category->slug  );
 
                    array_push($destination_post_categories, $category->term_id);
 
                }else{
 
                    $catarr = array(
                        'cat_name'              => esc_attr($source_category->name),
                        'category_description'  => esc_attr($source_category->description),
                        'category_nicename'     => $source_slug,
                        'category_parent'       => ''
                    );
 
                    $new_cat_id = wp_insert_category($catarr);
 
                  
 
                    array_push($destination_post_categories, $new_cat_id);
 
                }
 
            }
 
        }
 
    }
 
    wp_set_post_categories( $post_id, $destination_post_categories, false );
 
    return;
 
}

/**
 * 
 * This function performs the action of taking the taxonomies of the source post and
 * collecting them into an array of objects for use when duplicating to the destination.
 * Works with mpd_set_post_taxonomy_terms();
 *
 * @since 0.9
 * @param $post_id The the ID of post being copied
 * 
 * @return array An array of term objects used in the post
 *
 */
function mpd_get_post_taxonomy_terms($post_id){

    $source_taxonomy_terms_object = array();

    $post_taxonomies = get_object_taxonomies( get_post_type($post_id), 'names' );

    foreach ($post_taxonomies as $post_taxonomy) {

        if($post_taxonomy != 'category' && $post_taxonomy != 'post_tag'){

            array_push($source_taxonomy_terms_object, wp_get_post_terms($post_id, $post_taxonomy));

        }

    }

    return $source_taxonomy_terms_object;

}

/**
 * 
 * This function performs the action of setting the taxonomies of the source post and
 * to the destination post.
 * Works with mpd_get_post_taxonomy_terms();
 *
 * @since 0.9
 * @param $source_taxonomy_terms_object An array of term objects used in the source post
 * @param $post_id The ID of the newly created post
 * 
 * @return array An array of term objects used in the post
 *
 */
function mpd_set_post_taxonomy_terms($source_taxonomy_terms_object, $post_id){

    foreach ($source_taxonomy_terms_object as $source_taxonomy_terms) {

        foreach ($source_taxonomy_terms as $term) {

            $args = array(
                'description'=> esc_attr($term->description),
                'slug' => $term->slug,
            );

            wp_insert_term( $term->name, $term->taxonomy, $args);

            wp_set_object_terms( $post_id, $term->slug, $term->taxonomy, true);
            
        }
        
    }

    return; 

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
add_filter('mpd_filter_post_custom', 'mpd_ignore_custom_meta_keys');
add_filter('mpd_filter_post_meta', 'mpd_ignore_custom_meta_keys');

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
    $mpd_process_info['post_date'] = get_post_field('post_date', $mpd_process_info['source_id']);
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