<?php

/**
 * Setup and process duplication of a media file
 *
 *
 * @since 1.7
 * @param int $post_id The ID of the 'attachment' post.
 * @param int $destination_id The ID of site to duplicate this to attachment to.
 * @return int The ID of the newly created attachment
 *
*/
function mpd_media_duplicate($post_id, $destination_id){

    $the_media      = get_post($post_id);
    $the_media_url  = wp_get_attachment_url( $post_id );
    $wp_filetype    = wp_check_filetype( $the_media_url , null );
    $image_alt      = get_post_meta( $post_id, '_wp_attachment_image_alt', true);
    $source_id      = get_current_blog_id();

    $info           = pathinfo($the_media_url);
    $file_name      = basename($the_media_url,'.'.$info['extension']);

    $meta_values    = apply_filters('mpd_filter_media_meta', get_post_meta($post_id));

    $attachment     = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name( $file_name ),
        'post_content'   => $the_media->post_content,
        'post_status'    => 'inherit',
        'post_excerpt'   => $the_media->post_excerpt,
        'post_name'      => $the_media->post_name,
    );

    switch_to_blog($destination_id);

        $attach_id = mpd_copy_file_to_destination($attachment, $the_media_url, 0, $source_id, $the_media->ID);

        mpd_process_meta($attach_id, $meta_values);

         // Add alt text from the destination image
        if($image_alt){

             update_post_meta($attach_id,'_wp_attachment_image_alt', $image_alt);

        }

    restore_current_blog();

    return $attach_id;

}

/**
 * Gets the actions from the Media Post list table and determines what to batch duplicate
 *
 * @since 1.7
 * @return null
 *
*/
function mpd_bulk_action_media(){

    $wp_list_table  = _get_list_table('WP_Media_List_Table');
    $action         = $wp_list_table->current_action();

    if (0 === strpos($action, 'dup')) {
      
        preg_match("/(?<=dup-)\d+/", $action, $get_site);
          
        if(isset($_REQUEST['media'])) {
            $post_ids = array_map('intval', $_REQUEST['media']);
        }

        $results = array();

        foreach($post_ids as $post_id){
              
            do_action('mpd_single_batch_before', $post_id, $get_site[0]);

            if(get_option('skip_standard_dup')){
                delete_option('skip_standard_dup' );
                continue;
            }

            $results[] = mpd_media_duplicate($post_id, intval($get_site[0]));

            do_action('mpd_single_batch_after', $post_id);

        }
          
        $countBatch = count($results);

        if($countBatch){

            $destination_name     = get_blog_details($get_site[0])->blogname;
            $destination_edit_url = get_admin_url( $get_site[0], 'upload.php');
            $the_ess              = $countBatch != 1 ? __('media files have', 'multisite-post-duplicator') : __('media file has', 'multisite-post-duplicator');
            $notice               = '<div class="updated"><p>'.$countBatch. " " . $the_ess . " " . __('been duplicated to', 'multisite-post-duplicator' ) ." '<a href='".$destination_edit_url."'>". $destination_name ."'</a></p></div>";

            update_option('mpd_admin_bulk_notice', $notice );

        }
         
        do_action('mpd_batch_after', $results);

    }

}

add_action('load-upload.php', 'mpd_bulk_action_media');

/**
 * Hooks into the 'edit attachement' and duplictes the attachment to any sites that have been checked in the
 * MPD metabox UI
 *
 * @since 1.7
 * @return null
 *
*/
function mpd_media_metabox_duplicate(){

    if(isset($_POST['mpd_blogs'])){

        foreach ($_POST['mpd_blogs'] as $key => $site) {

            $attach_id = mpd_media_duplicate($_POST['post_ID'], intval($site));
            
            $blog_details  = get_blog_details(intval($site));
            $site_name     = $blog_details->blogname;
            $site_edit_url = $blog_details->siteurl . "/wp-admin/" . "post.php?post=".$attach_id."&action=edit";

            $notice = mdp_make_admin_notice($site_name, $site_edit_url, $blog_details);

        }

    }

}
add_action('edit_attachment', 'mpd_media_metabox_duplicate');

/**
 * Determines what UI elements to show on the MPD Metabox for Media Post type
 *
 * @since 1.7
 * @return null
 *
*/
function mpd_display_media_metabox_options(){
    
    global $post_type;

    if($post_type == 'attachment'){

        add_filter('mpd_show_metabox_prefix', '__return_false');
        add_filter('mpd_show_metabox_post_status', '__return_false');
        add_filter('mpd_show_metabox_persist', '__return_false');

    }
}

add_action('admin_head', 'mpd_display_media_metabox_options');