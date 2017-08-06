<?php


function mpd_media_duplicate($post_id, $destination_id){

    $the_media = get_post($post_id);

    $the_media_url = wp_get_attachment_url( $post_id );

    $wp_filetype = wp_check_filetype( $the_media_url , null );

    $info       = pathinfo($the_media_url);
    $file_name  = basename($the_media_url,'.'.$info['extension']);

    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name( $file_name ),
        'post_content'   => $the_media->post_content,
        'post_status'    => 'inherit',
        'post_excerpt'   => $the_media->post_excerpt,
        'post_name'      => $the_media->post_name,
    );

    $source_id = get_current_blog_id();

    switch_to_blog($destination_id);

    remove_action('add_attachment', 'mpd_media_duplicate');

    $attach_id = mpd_copy_file_to_destination($attachment, $the_media_url, 0, $source_id, $the_media->ID);

    restore_current_blog();

    add_action('add_attachment', 'mpd_media_duplicate');

}

add_action( 'add_attachment', 'mpd_media_duplicate', 10,  1);