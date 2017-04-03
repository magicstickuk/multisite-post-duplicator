<?php
/**
 * 
 * This file controls the generation and functionality  of the MPD Metabox.
 * 
 * @since 0.4
 * @author Mario Jaconelli <mariojaconelli@gmail.com>
 * @author Sergi Ambeln
 * 
 */

if ( ! defined( 'ABSPATH' ) ) exit('You\'re not allowed to see this page');

if ( is_multisite() ) {

    add_action( 'add_meta_boxes', 'mpd_metaboxes' );

}

/**
 * 
 * This function initialises the MPD Metabox on the WordPress Post
 * 
 * Before displaying this function will check the plugin settings option to make sure the use wants to
 * display the metabox or not depending on the post type.
 * 
 * @since 0.4
 * @return null
 * 
 */
function mpd_metaboxes(){
    
    $active_mpd = apply_filters( 'mpd_is_active', true );

    if($post_types = mpd_get_postype_decision_from_options()){

        foreach ($post_types as $page ){

            if ($active_mpd && current_user_can(mpd_get_required_cap()))  {

                $priority = apply_filters( 'mpd_metabox_priority', 'high' );

                add_meta_box( 'multisite_clone_metabox', "<i class='fa fa-clone' aria-hidden='true'></i> " . __('Multisite Post Duplicator', 'multisite-post-duplicator' ), 'mpd_publish_top_right', $page, 'side', $priority );

                do_action('mpd_meta_box', $page);
                   
            }

            do_action('mpd_meta_box_global', $page);

        } 

    }

    add_action('admin_notices', 'mpd_plugin_admin_notices');
    
}


/**
*
 * This function generates the markup for the MPD Metabox
*
 * @since 0.4
* @return null
*
 */
function mpd_publish_top_right(){

    add_thickbox();

    ?>

    <div id="clone_multisite_box">

        <div class="metabox">

            <?php do_action('mpd_before_metabox_content'); ?>

            <?php mpd_do_metabox_site_list();?>

            <?php do_action('mpd_after_metabox_content'); ?>

        </div>

    </div>

<?php

}
/**
*
* This function generates the markup for the default post status selection in the MPD Metabox
*
* @since 1.6
* @return null
*
*/
function mpd_metabox_post_status(){

    $post_statuses = mpd_get_post_statuses();
    $status = mpd_get_status();

    if(apply_filters('mpd_show_metabox_post_status', true)) : ?>

        <p><?php _e('Duplicated post status', 'multisite-post-duplicator' ); ?>:

            <select id="mpd-new-status" name="mpd-new-status" style="width:100%">

                <?php foreach ($post_statuses as $post_status_key => $post_status_value): ?>

                    <option value="<?php echo $post_status_key;?>" <?php echo $post_status_key == $status ? 'selected' : '' ?>><?php echo $post_status_value;?></option>

                <?php endforeach ?>

            </select>

        </p>

 <?php endif;

}
add_action('mpd_before_metabox_content', 'mpd_metabox_post_status', 5);

/**
*
* This function generates the markup for the default prefix selection in the MPD Metabox
*
* @since 1.6
* @return null
*
*/
function mpd_metabox_prefix(){

    if(apply_filters('mpd_show_metabox_prefix', true)) :?>

        <p><?php _e('Title prefix for new post', 'multisite-post-duplicator' ); ?>:

            <input type="text" style="width:100%" name="mpd-prefix" value="<?php echo mpd_get_prefix(); ?>"/>

        </p>

    <?php endif;

}
add_action('mpd_before_metabox_content', 'mpd_metabox_prefix', 10);

/**
*
* This function generates the markup for the site-list checkoxes in the MPD Metabox
*
* @since 1.6
* @return null
*
*/
function mpd_do_metabox_site_list(){

    $sites = mpd_wp_get_sites();

    ?>
    <script>

        jQuery(document).ready(function($) {
            accordionClick('.ps-link', '.ps-content', 'fast');
        });

    </script>

    <?php if(apply_filters('mpd_show_site_list', true)) :?>

        <p>

            <?php _e('Site(s) you want duplicate to', 'multisite-post-duplicator' ); ?> <i class="fa fa-info-circle ps-link accord" aria-hidden="true"></i> :

        </p>

        <p class="mpdtip ps-content" style="display:none">

            <?php _e('If you have checked any of the checkboxes below then this post will be duplicated on save.', 'multisite-post-duplicator' );?>

        </p>

        <ul id="mpd_blogschecklist" data-wp-lists="list:category" class="mpd_blogschecklist">

            <?php $current_blog_id = get_current_blog_id(); ?>

            <?php foreach ($sites as $site): ?>

                <?php if (current_user_can_for_blog($site->blog_id, mpd_get_required_cap()) && !in_array($site->blog_id, mpd_get_restrict_some_sites_options())) : ?>

                    <?php $blog_details = get_blog_details($site->blog_id); ?>

                    <li id="mpd_blog_<?php echo $site->blog_id; ?>" class="mpd-site-checkbox">

                        <label class="selectit">

                            <input

                                class="<?php echo $site->blog_id == $current_blog_id ? 'mpd-current-site' : '';?>"

                                value="<?php echo $site->blog_id; ?>"

                                type="checkbox"

                                name="mpd_blogs[]"

                                id="in_blog_<?php echo $site->blog_id; ?>">

                                <?php

                                    echo $site->blog_id == $current_blog_id ? '<em>' : '';
                                    echo $blog_details->blogname . " ";
                                    echo $site->blog_id == $current_blog_id ? ' <small>(' . __('Current Site', 'multisite-post-duplicator' ) . ')</small></em>' : '';

                                ?>

                        </label>

                    </li>

                <?php endif; ?>

            <?php endforeach; ?>

        </ul>

    <?php endif;

}

/**
 * 
 * This function sets up the MPD core function and calls it based on values added by the user in the MPD metabox
 * 
 * @since 0.4
 * @param int $post_id The post ID of the post currently being viewed.
 * @return int The post ID of the post currently being viewed.
 * 
 */

function mpd_clone_post($post_id){


    //$here = get_site_option('avoid_infinite');

   // if(!$here){
        if (!count($_POST)){

            return $post_id;

        }

        if($choice = apply_filters('mpd_enter_the_loop', false, $_POST, $post_id)){  

            $mpd_blogs = apply_filters('mpd_selected_blogs', $_POST['mpd_blogs'], $_POST['ID']);

            foreach( $mpd_blogs as $mpd_blog_id ){

                $createdPost = false;

                do_action('mpd_single_metabox_before', $_POST['ID'], $mpd_blog_id);   
                
                if(apply_filters('mpd_do_single_metabox_duplication', true)){

                    $createdPost = mpd_duplicate_over_multisite(

                        $_POST["ID"],
                        $mpd_blog_id, 
                        $_POST["post_type"],
                        get_current_user_id(),
                        $_POST["mpd-prefix"],
                        $_POST["mpd-new-status"]

                    );

                }
                if($createdPost){
                    do_action('mpd_single_metabox_after', $_POST['ID'], $mpd_blog_id, $createdPost );
                }
                     
                
            }

        }
        
        //update_site_option('avoid_infinite', 1 );
    
    //}
   
    return $post_id;

}
add_filter( 'save_post', 'mpd_clone_post', 100 );