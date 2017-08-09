<?php
/**
 * MPD Addon: Batch Duplication
 * 
 * This MPD addon allows you to batch-duplicate your pages within the post list page in WordPress
 * 
 * @ignore
 * @since 0.6
 * @author Mario Jaconelli <mariojaconelli@gmail.com>
 * 
 */

/**
 * @ignore
 */
function mpd_bulk_add_default_option($mdp_default_options){

  $mdp_default_options['add_bulk_settings'] = 'allow-batch';

  return $mdp_default_options;

}
add_filter('mdp_default_options', 'mpd_bulk_add_default_option');

/**
 * @ignore
 */
function addon_mpd_bulk_setting_activation($options){

  if(version_compare(mpd_get_version(),'0.6', '<')){
      $options['add_bulk_settings']    = 'allow-batch';
  }

  return $options;

}
add_filter('mdp_activation_options', 'addon_mpd_bulk_setting_activation');

/**
 * @ignore
 */
function mpd_bulk_admin_script() {

    if( is_multisite() ){
        $defaultoptions = mdp_get_default_options();
        $sites          = mpd_wp_get_sites();
        $options        = get_option('mdp_settings');
        $post_status    = isset($_REQUEST["post_status"]) ? $_REQUEST["post_status"] : '';

        $active_mpd     = apply_filters( 'mpd_is_active', true );
      
        if(isset($options['add_bulk_settings']) || ($defaultoptions['add_bulk_settings'] == 'allow-batch' && !$options)){ ?>

          <?php if($post_status != 'trash' && $active_mpd): ?>

            <script type="text/javascript">

              jQuery(document).ready(function() {

                <?php foreach ($sites as $site) :?>

                  <?php $blog_details = get_blog_details($site->blog_id); ?> 

                    <?php if($site->blog_id != get_current_blog_id() && current_user_can_for_blog($site->blog_id, mpd_get_required_cap())):?> 

                      jQuery('<option>').val("dup-<?php echo $site->blog_id ?>").text('<?php _e('Duplicate to ')?><?php echo $blog_details->blogname; ?>').appendTo("select[name='action']");
                      jQuery('<option>').val("dup-<?php echo $site->blog_id ?>").text('<?php _e('Duplicate to ')?><?php echo $blog_details->blogname; ?>').appendTo("select[name='action2']");

                    <?php endif; ?>
                    
                  <?php endforeach; ?>

              });
              
            </script>

          <?php endif; ?>

        <?php
        }

    }
    
}

add_action('admin_footer-edit.php', 'mpd_bulk_admin_script');
add_action('admin_footer-upload.php', 'mpd_bulk_admin_script');
/**
 * @ignore
 */
function mpd_bulk_action() {
 
  $wp_list_table  = _get_list_table('WP_Posts_List_Table');
  $action         = $wp_list_table->current_action();

  if (0 === strpos($action, 'dup')) {
      
      preg_match("/(?<=dup-)\d+/", $action, $get_site);
      
      if(isset($_REQUEST['post'])) {
            $post_ids = array_map('intval', $_REQUEST['post']);
      }

      $results          = array();
      $map_family_tree  = array();

      if($post_ids){

          foreach($post_ids as $post_id){
          
            do_action('mpd_single_batch_before', $post_id, $get_site[0]);

            if(get_option('skip_standard_dup')){
                    delete_option('skip_standard_dup' );
                    continue;
            }

            $results[] = mpd_duplicate_over_multisite(
                
                $post_id, 
                $get_site[0],
                $_REQUEST['post_type'],
                get_current_user_id(),
                mpd_get_prefix(),
                mpd_get_status()

            );

            $highest_index = max(array_keys($results));

            // Collect the results data to be used in assigning parent/child data in the destination site
            $map_family_tree[] = array(

                'old_post_id'  => $post_id,
                'new_blog_id'  => $get_site[0],
                'old_parent_id'=> wp_get_post_parent_id($post_id),
                'new_post_id'  => $results[$highest_index]['id']

            );

            do_action('mpd_single_batch_after', $post_id);

          }

      

        //Assign any parent/child relationship that is available within the batch
        $family_tree          = mpd_map_new_family_tree($map_family_tree);
        
        $countBatch           = count($results);

        if($countBatch){
            $destination_name     = get_blog_details($get_site[0])->blogname;
            $destination_edit_url = get_admin_url( $get_site[0], 'edit.php?post_type='.$_REQUEST['post_type']);
            $the_ess              = $countBatch != 1 ? __('posts have', 'multisite-post-duplicator') : __('post has', 'multisite-post-duplicator');
            $notice               = '<div class="updated"><p>'.$countBatch. " " . $the_ess . " " . __('been duplicated to', 'multisite-post-duplicator' ) ." '<a href='".$destination_edit_url."'>". $destination_name ."'</a></p></div>";

            update_option('mpd_admin_bulk_notice', $notice );

        }
       
        do_action('mpd_batch_after', $results);

      }

  }
 
}

add_action('load-edit.php', 'mpd_bulk_action');

/**
 * @ignore
 */
function mpd_map_new_family_tree($map_family_tree){

  foreach ($map_family_tree as $key => $family_tree) {

    // Does the source have a parent
    if($old_parent_id = $family_tree['old_parent_id']){
      
      //Is the parent ID in the result set of the source IDS?
      //If so, return that ID's new post ID and update its parent
      $search_for = mpd_search($map_family_tree, 'old_post_id', $old_parent_id);
      
      if($search_for){

          global $wpdb;

          $new_parent = $search_for[0]['new_post_id'];

          $blogText = $family_tree['new_blog_id'] != 1 ? $family_tree['new_blog_id'] . "_" : '';

          $wpdb->update( 
            
            $wpdb->base_prefix . $blogText  . "posts", 
            
            array( 
              'post_parent' =>  $new_parent
            ), 

            array(
              'ID' => $family_tree['new_post_id']
            ), 

            array( 
              '%d' 
            ), 
            array( '%d' ) 

          );
      } 

    } 

  }

  do_action( 'mpd_map_destination_family', $map_family_tree);

  return $map_family_tree;

}
/**
 * @ignore
 */
function mpd_bulk_admin_notices() {
 
  global $pagenow;
  
  if($pagenow == 'edit.php' || $pagenow == 'upload.php'){
       
        if($notices = get_option('mpd_admin_bulk_notice')){

              echo $notices;

              delete_option('mpd_admin_bulk_notice');
              delete_option('mpd_admin_notice');

        }
  }

}

add_action('admin_notices', 'mpd_bulk_admin_notices');

/**
 * @ignore
 */
function add_bulk_settings(){


    mpd_settings_field('add_bulk_settings', '<i class="fa fa-files-o" aria-hidden="true"></i> ' . __( 'Allow batch duplication?', 'multisite-post-duplicator' ), 'mdp_default_batch_render');

     
}

add_action( 'mdp_end_plugin_setting_page', 'add_bulk_settings');

/**
 * @ignore
 */
function mdp_default_batch_render(){
  
  $options = get_option('mdp_settings');
  ?>
  <div class="checkbox checkbox-slider--b-flat">
    
    <label>
      <input type='checkbox' name='mdp_settings[add_bulk_settings]' <?php mpd_checked_lookup($options, 'add_bulk_settings', 'allow-batch') ;?> value='allow-batch'>
        <span>
          <?php mpd_information_icon('Having this option checked will allow you to duplicate muliple pages at a time via the batch processing options on the WordPress post list page'); ?>
        </span>
    </label>
</div>
  <?php

}
/**
 * @ignore
 */
function mpd_clean_batch_admin_notices(){

    delete_option('mpd_admin_notice');

}
add_action('mpd_batch_after', 'mpd_clean_batch_admin_notices');