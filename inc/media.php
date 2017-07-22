<?php


function mpd_set_media_post_type($source){

    $post_type = get_post_type($source['source_post_id']);

    if($post_type == 'attachment'){
           
        $source['requested_post_status'] = 'inherit';

    }

    return $source;

}
//add_filter('mpd_source_data','mpd_set_media_post_type');

function mpd_media_no_functions($choice){

    $post_type = get_post_type($source['source_post_id']);

    if($post_type == 'attachment'){
           
        return false;

    }

    return $choice;
    
}
//add_filter('mdp_copy_content_images', 'mpd_media_no_functions');
//add_filter('mdp_default_featured_image', 'mpd_media_no_functions');