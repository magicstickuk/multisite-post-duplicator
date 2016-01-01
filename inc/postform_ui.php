<?php
/**
 * 
 * This file controls the generation and functionality  of the MPD Metabox.
 * 
 * @since 0.4
 * @author Mario Jaconelli <mariojaconelli@gmail.com>
 * @author Sergi Ambel
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
    $active_mpd_role = apply_filters( 'mpd_active_role', true );

    $post_types = mpd_get_postype_decision_from_options();

    if($post_types){

        foreach ($post_types as $page ){

            if ($active_mpd && $active_mpd_role)  {
                    add_meta_box( 'multisite_clone_metabox', __('Multisite Post Duplicator', MPD_DOMAIN ), 'mpd_publish_top_right', $page, 'side', 'high' );
            }

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

    $post_statuses  = get_post_statuses();
    $sites          = mpd_wp_get_sites()

    ?>


    <div id="clone_multisite_box">

        <div class="metabox">

            <?php do_action('mpd_before_metabox_content'); ?>

            <p><?php _e('Duplicated post status', MPD_DOMAIN ); ?>:

            <select id="mpd-new-status" name="mpd-new-status">
             <?php foreach ($post_statuses as $post_status_key => $post_status_value): ?>
                      <option value="<?php echo $post_status_key;?>" <?php echo $post_status_key == 'draft' ? 'selected' : '' ?>><?php echo $post_status_value;?></option>
               <?php endforeach ?>
            </select>
               
            </p>

            <p><?php _e('Title prefix for new post', MPD_DOMAIN ); ?>:
            
                <input type="text" name="mpd-prefix" value="<?php echo mpd_get_prefix(); ?>"/>
                
            </p>

            <p><?php _e('Site(s) you want duplicate to', MPD_DOMAIN ); ?>:

                <ul id="mpd_blogschecklist" data-wp-lists="list:category" class="mpd_blogschecklist" style="padding-left: 5px;margin-top: -8px;">
                    
                    <?php foreach ($sites as $site): ?>

                        <?php if (current_user_can_for_blog($site['blog_id'], 'publish_posts') ) : ?>

                            <?php $blog_details = get_blog_details($site['blog_id']); ?>
                            
                                <li id="mpd_blog_<?php echo $site['blog_id']; ?>" class="mpd-site-checkbox">

                                    <label class="selectit">
                                        <input value="<?php echo $site['blog_id']; ?>" type="checkbox" name="mpd_blogs[]" id="in_blog_<?php echo $site['blog_id']; ?>"> <?php echo $blog_details->blogname; ?>

                                    </label>

                                </li>
                            
                        <?php endif; ?>

                    <?php endforeach; ?>

                </ul>
            </p>

            <p>
                <em>
                    <?php _e('If you have checked any of the checkboxes above then this post will be duplicated on save.', MPD_DOMAIN );?>
                </em>
            </p>

            <p style="font-size: 80%; text-align:right; font-style:italic">

                <a target="_blank" href="<?php echo esc_url( get_admin_url(null, 'options-general.php?page=multisite_post_duplicator') ); ?>"><?php _e('Settings', MPD_DOMAIN ); ?></a>
                
            </p>

            <?php do_action('mpd_after_metabox_content'); ?>
            
        </div>

    </div>

<?php
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

    if (!count($_POST)){

        return $post_id;
        
    }

    if(    ( isset($_POST["post_status"] ) )
        && ( $_POST["post_status"] != "auto-draft" )
        && ( isset($_POST['mpd_blogs'] ) )
        && ( count( $_POST['mpd_blogs'] ) )
        && ( $_POST["post_ID"] == $post_id ) //hack to avoid execution in cloning process
    ){

    $mpd_blogs = $_POST['mpd_blogs'];

        foreach( $mpd_blogs as $mpd_blog_id ){

            mpd_duplicate_over_multisite($_POST["ID"], $mpd_blog_id, $_POST["post_type"], get_current_user_id(), $_POST["mpd-prefix"], $_POST["mpd-new-status"]);

        }

    }

    return $post_id;

}

add_filter( 'save_post', 'mpd_clone_post' );