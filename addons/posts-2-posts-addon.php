<?php

// Load the posts-to-posts addon only if its network activated
if (!function_exists('is_plugin_active_for_network')) {
    require_once(ABSPATH . '/wp-admin/includes/plugin.php');
}

if (is_plugin_active_for_network('posts-to-posts/posts-to-posts.php')) {

    $mpd_p2p_source_data = null;

    function mpd_p2p_p2p_enrich(&$p2p_link) {
        $tmp = (array) $p2p_link;
        $tmp['meta'] = get_metadata('p2p', $p2p_link->p2p_id);
        if($tmp['meta']){
           foreach ($tmp['meta'] as $key => &$val) {
                // everything is singular
                $val = $val[0];
            } 
        }
        
        unset($val);
        $p2p_link = (object) $tmp;
    }

    function mpd_p2p_prepare_source_data($source_post_id, $destination_blog_id) {
        global $wpdb;
        global $mpd_p2p_source_data;

        $p2p_links = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $wpdb->p2p WHERE p2p_from = %d OR p2p_to = %d",
                $source_post_id, $source_post_id));
        if($p2p_links){
           foreach ($p2p_links as &$p2p_link) {
                mpd_p2p_p2p_enrich($p2p_link);
            } 
        }
        
        unset($p2p_link);

        $mpd_p2p_source_data = array(
            'source_post_id' => $source_post_id,
            'source_p2p_links' => $p2p_links,
            'destination_blog_id' => $destination_blog_id,
        );
    }

    function mpd_p2p_by_dest($mpd_links) {
        $res = array();
        if($mpd_links){
            foreach ($mpd_links as $mpd_link) {
                if (!array_key_exists($mpd_link->destination_id, $res)) {
                    $res[$mpd_link->destination_id] = array();
                }
                array_push($res[$mpd_link->destination_id], $mpd_link->destination_post_id);
            }
        }
        

        return $res;
    }

    add_action("mpd_during_core_in_source", "mpd_p2p_pre_copy_persist", 10, 5);
    add_action("mpd_persist_during_core_in_source", "mpd_p2p_pre_copy_persist", 10, 5);
    function mpd_p2p_pre_copy_persist($mpd_post, $attached_image, $meta_values, $source_post_id, $destination_blog_id) {
        if (!function_exists('p2p_register_connection_type')) {
            return;
        }

        mpd_p2p_prepare_source_data($source_post_id, $destination_blog_id);
    }

    add_action("mpd_end_of_core_before_return", "mpd_p2p_after_create_update", 10, 3);
    add_action("mpd_persist_end_of_core_before_return", "mpd_p2p_after_create_update", 10, 3);
    function mpd_p2p_after_create_update($dest_post_id, $mpd_post, $source_blog_id) {
        if (!function_exists('p2p_register_connection_type')) {
            return;
        }

        global $mpd_p2p_source_data;

        $source_post_id = $mpd_p2p_source_data['source_post_id'];
        $destination_blog_id = $mpd_p2p_source_data['destination_blog_id'];
        if($mpd_p2p_source_data['source_p2p_links']){
            foreach ($mpd_p2p_source_data['source_p2p_links'] as $source_p2p_link) {
                $source_p2p_other = ($source_post_id == $source_p2p_link->p2p_from ?
                    $source_p2p_link->p2p_to : $source_p2p_link->p2p_from);
                $mpd_links_of_p2p_link = mpd_get_persists_for_post($source_blog_id, $source_p2p_other);

                if($mpd_links_of_p2p_link){
                   foreach ($mpd_links_of_p2p_link as $mpd_link_of_p2p_link) {
                        if ($mpd_link_of_p2p_link->destination_id != $destination_blog_id) {
                            continue;
                        }

                        _mpd_p2p_copy_link_raw(
                            true /* link */,
                            $source_p2p_link /* meta and dir */,
                            $source_post_id /* for dir */,
                            $dest_post_id, /* the two new ids */
                            $mpd_link_of_p2p_link->destination_post_id);
                    } 
                }
                
            }
        }
        
    }

    add_action('p2p_created_connection', 'mpd_p2p_created_connection', 10, 1);
    function mpd_p2p_created_connection($p2p_id) {
        _mpd_p2p_copy_link_by_id(true, $p2p_id);
    }

    add_action('p2p_delete_connections', 'mpd_p2p_delete_connections', 10, 1);
    function mpd_p2p_delete_connections($p2p_ids) {
        if($p2p_ids){
            foreach ($p2p_ids as $p2p_id) {
                _mpd_p2p_copy_link_by_id(false, $p2p_id);
            }  
        }
        
    }

    function _mpd_p2p_copy_link_by_id($is_create, $p2p_id) {
        $p2p = p2p_get_connection($p2p_id);
        mpd_p2p_p2p_enrich($p2p);
        $from_post_id = $p2p->p2p_from;
        $to_post_id = $p2p->p2p_to;

        foreach (mpd_get_persists_for_post(null, $from_post_id) as $from_mpd_link) {
            foreach (mpd_get_persists_for_post(null, $to_post_id) as $to_mpd_link) {
                if ($from_mpd_link->destination_id != $to_mpd_link->destination_id) {
                    continue;
                }

                switch_to_blog($from_mpd_link->destination_id);

                _mpd_p2p_copy_link_raw(
                    $is_create,
                    $p2p,
                    $from_post_id,
                    $from_mpd_link->destination_post_id,
                    $to_mpd_link->destination_post_id);

                restore_current_blog();
            }
        }
    }

    function _mpd_p2p_copy_link_raw($is_create, $p2p, $old_post_id, $new_post_id, $other_post_id) {
        global $wpdb;

        $from = $p2p->p2p_from == $old_post_id ? $new_post_id : $other_post_id;
        $to = $p2p->p2p_from == $old_post_id ? $other_post_id : $new_post_id;

        // NOTE: This whole thing does not handle multiple p2p connections for
        // a pair of posts
        $existing_p2p_link = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $wpdb->p2p WHERE p2p_from = %d AND p2p_to = %d AND p2p_type = %s",
                $from, $to, $p2p->p2p_type));


        if (!$is_create) {
            if (!is_null($existing_p2p_link)) {
                p2p_delete_connection($existing_p2p_link->p2p_id);
            }
            return;
        }

        if (is_null($existing_p2p_link)) {
            p2p_create_connection($p2p->p2p_type, array(
                'from' => $from,
                'to' => $to,
                'meta' => $p2p->meta));
        } else {
            // exists, most likely the meta is good
            $p2p_meta = get_metadata('p2p', $existing_p2p_link->p2p_id);
            $expected_p2p_meta = $p2p->meta;

            $are_same = true;
            if (count($p2p_meta) == count($expected_p2p_meta)) {
                foreach ($expected_p2p_meta as $key => $val) {
                    if (!(array_key_exists($key, $p2p_meta) && $p2p_meta[$key] == $val)) {
                        $are_same = false;
                        break;
                    }
                }
            } else {
                $are_same = false;
            }

            if (!$are_same) {
                // well, meta differs, wipe all and re-add
                $wpdb->delete($wpdb->p2pmeta, array('p2p_id' => $existing_p2p_link->p2p_id), array('%d'));
                foreach ($expected_p2p_meta as $key => $val) {
                    p2p_add_meta($existing_p2p_link->p2p_id, $key, $val);
                }
            }
        }
    }
}
