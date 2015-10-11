<?php

if ( ! defined( 'ABSPATH' ) ) exit('You\'re not allowed to see this page'); // Exit if accessed directly

if ( is_multisite() ) {
    add_action( 'add_meta_boxes', 'mpd_metaboxes' );
}

function mpd_metaboxes()
{
  
  $options = get_option( 'mdp_settings' ); 
  $post_types = $options['meta_box_show_radio'] ? $options['meta_box_show_radio'] : get_post_types('','names');
    
    if($options['meta_box_show_radio']){

        if($options['meta_box_show_radio'] == 'all'){

            $post_types = get_post_types('','names');

        }elseif($options['meta_box_show_radio'] == 'none'){

            $post_types = null;

        }elseif($options['meta_box_show_radio'] == 'some'){

            $post_types = array();

            foreach ($options as $key => $value) {
                if (substr($key, 0, 28) == "meta_box_post_type_selector_") {
                    $post_types[] = $value;
                }
            }

        }
        

    }else{

        $post_types  = get_post_types('','names');

    }

    if($post_types){

        foreach ($post_types as $page ){

            if ( current_user_can( 'publish_posts' ) )  {
                    add_meta_box( 'multisite_clone_metabox', 'Multisite Post Duplicator', 'mpd_publish_top_right', $page, 'side', 'high' );
            }

        } 

    }
    

}

function mpd_publish_top_right()
{
    $post_statuses = array('publish', 'future', 'draft', 'pending', 'private');
    $sites = wp_get_sites();

    ?>
    <div id="clone_multisite_box">

        <div class="metabox">

            <p>Duplicated post status:

                <select id="mpd-new-status" name="mpd-new-status">
             <?php foreach ($post_statuses as $post_status): ?>
                      <option value="<?php echo $post_status?>" <?php echo $post_status == 'draft' ? 'selected' : '' ?>><?php echo $post_status?></option>
               <?php endforeach ?>
            </select>
                </select>
            </p>

            <p>Title prefix for new post:
               <?php 
                     $options = get_option( 'mdp_settings' ); 
                     $default_prefix = $options['mdp_default_prefix'] ? $options['mdp_default_prefix'] : 'Copy of'   
               ?>
                <input type="text" name="mpd-prefix" value="<?php echo $default_prefix; ?>"/>
            </p>

            <p>Site(s) you want duplicate to:
                <ul id="mpd_blogschecklist" data-wp-lists="list:category" class="mpd_blogschecklist" style="padding-left: 5px;margin-top: -8px;">
                    
                    <?php foreach ($sites as $site): ?>

                        <?php if (current_user_can_for_blog($site['blog_id'], 'publish_posts') ) : ?>
                            <?php $blog_details = get_blog_details($site['blog_id']); ?>
                            
                                <li id="mpd_blog_<?php echo $site['blog_id']; ?>" class="popular-category">
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
                    If you have checked any of the checkboxes above then this post will be duplicated on save.
                </em>
            </p>
            <p style="font-size: 80%; text-align:right; font-style:italic">
                <a target="_blank" href="<?php echo esc_url( get_admin_url(null, 'options-general.php?page=multisite_post_duplicator') ); ?>">Settings</a>
            </p>
        </div>

    </div>

<?php
}

add_filter( 'save_post', 'mpd_clone_post' );

function mpd_clone_post($data )
{

    // Other "don't save" operations as remove or create new one:
    if (!count($_POST))
    {
        return $data;
    }

    if( ($_POST["post_status"] != "auto-draft")
        && ( isset($_POST['mpd_blogs'] ) )
        && ( count( $_POST['mpd_blogs'] ) )
        && ( $_POST["post_ID"] == $data ) //hack to avoid execution in cloning process
    )
    {
        $mpd_blogs = $_POST['mpd_blogs'];
        foreach( $mpd_blogs as $mpd_blog_id )
        {
            duplicate_over_multisite($_POST["ID"], $mpd_blog_id, $_POST["post_type"], $_POST["post_author"], $_POST["mpd-prefix"], $_POST["mpd-new-status"]);
        }
    }

    return $data;
}
