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
        $options        = get_option( 'mdp_settings' );
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

      $results = array();

      foreach($post_ids as $post_id){
          
          $results[] = mpd_duplicate_over_multisite(
              
              $post_id, 
              $get_site[0],
              $_REQUEST['post_type'],
              get_current_user_id(),
              mpd_get_prefix(),
              'draft'

          );

      }
      
      $countBatch           = count($results);
      $destination_name     = get_blog_details($get_site[0])->blogname;
      $destination_edit_url = get_admin_url( $get_site[0], 'edit.php?post_type='.$_REQUEST['post_type']);
      $the_ess              = $countBatch != 1 ? 'posts have' : 'post has';
      $notice               = '<div class="updated"><p>'.$countBatch. " " . $the_ess . " " . __('been duplicated to', MPD_DOMAIN ) ." '<a href='".$destination_edit_url."'>". $destination_name ."'</a></p></div>";

      update_option('mpd_admin_bulk_notice', $notice );

  }
 
}

add_action('load-edit.php', 'mpd_bulk_action');

/**
 * @ignore
 */
function mpd_bulk_admin_notices() {
 
  global $pagenow;
  
  if($pagenow == 'edit.php'){
       
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

    mpd_settings_field('add_bulk_settings', '<i class="fa fa-files-o" aria-hidden="true"></i> ' . __( 'Allow batch duplication?', MPD_DOMAIN ), 'mdp_default_batch_render');
     
}

add_action( 'mdp_end_plugin_setting_page', 'add_bulk_settings');

/**
 * @ignore
 */
function mdp_default_batch_render(){

  $options = get_option( 'mdp_settings' );
  ?>
  <input type='checkbox' name='mdp_settings[add_bulk_settings]' <?php mpd_checked_lookup($options, 'add_bulk_settings', 'allow-batch') ;?> value='allow-batch'>

  <p class="mpdtip"><i class="fa fa-info-circle" aria-hidden="true"></i> <?php _e('Having this option checked will allow you to duplicate muliple pages at a time via the batch processing options on the WordPress post list page', MPD_DOMAIN)?></p>
  <?php

}