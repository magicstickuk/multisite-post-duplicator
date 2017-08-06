<?php


function mpd_media_post_type_check($post_id){

    $the_media = get_post($post_id);

    update_site_option('mpd_the_post', $the_media);

}

add_action( 'add_attachment', 'mpd_media_post_type_check', 10,  1);