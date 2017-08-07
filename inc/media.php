<?php


function mpd_media_duplicate($post_id, $destination_id){

    $the_media = get_post($post_id);

    $the_media_url = wp_get_attachment_url( $post_id );

    $wp_filetype = wp_check_filetype( $the_media_url , null );

    $info       = pathinfo($the_media_url);
    $file_name  = basename($the_media_url,'.'.$info['extension']);

    $meta_values  = apply_filters('mpd_filter_media_meta', get_post_meta($post_id));

    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name( $file_name ),
        'post_content'   => $the_media->post_content,
        'post_status'    => 'inherit',
        'post_excerpt'   => $the_media->post_excerpt,
        'post_name'      => $the_media->post_name,
    );

    $image_alt = get_post_meta( $post_id, '_wp_attachment_image_alt', true);

    $source_id = get_current_blog_id();

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

function mpd_bulk_action_media(){

  $wp_list_table  = _get_list_table('WP_Media_List_Table');
  $action         = $wp_list_table->current_action();

  if (0 === strpos($action, 'dup')) {
      
      preg_match("/(?<=dup-)\d+/", $action, $get_site);
      
      if(isset($_REQUEST['media'])) {
            $post_ids = array_map('intval', $_REQUEST['media']);
      }

      $results          = array();

      foreach($post_ids as $post_id){
          
          do_action('mpd_single_batch_before', $post_id, $get_site[0]);

          if(get_option('skip_standard_dup')){
                  delete_option('skip_standard_dup' );
                  continue;
          }

        $results[] = mpd_media_duplicate($post_id, intval($get_site[0]));

        do_action('mpd_single_batch_after', $post_id);

      }
      
      $countBatch           = count($results);

      if($countBatch){
          $destination_name     = get_blog_details($get_site[0])->blogname;
          $destination_edit_url = get_admin_url( $get_site[0], 'upload.php');
          $the_ess              = $countBatch != 1 ? __('posts have', 'multisite-post-duplicator') : __('post has', 'multisite-post-duplicator');
          $notice               = '<div class="updated"><p>'.$countBatch. " " . $the_ess . " " . __('been duplicated to', 'multisite-post-duplicator' ) ." '<a href='".$destination_edit_url."'>". $destination_name ."'</a></p></div>";

          update_option('mpd_admin_bulk_notice', $notice );

      }
     
      do_action('mpd_batch_after', $results);

  }

}

add_action('load-edit.php', 'mpd_bulk_action_media');
add_action('load-upload.php', 'mpd_bulk_action_media');