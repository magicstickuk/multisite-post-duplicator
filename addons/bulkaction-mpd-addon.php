<?php

add_action('admin_footer-edit.php', 'mpd_bulk_admin_script');
 
function mpd_bulk_admin_script() {

    $args           = array('network_id' => null);

    if(is_multisite()){

        $sites          = wp_get_sites($args);

        ?>
        <script type="text/javascript">
          jQuery(document).ready(function() {
            <?php foreach ($sites as $site) :?>

              <?php $blog_details = get_blog_details($site['blog_id']); ?> 

                <?php if($site['blog_id'] != get_current_blog_id() && current_user_can_for_blog($site['blog_id'], 'publish_posts')):?> 

                  jQuery('<option>').val("dup-<?php echo $site['blog_id'] ?>").text('<?php _e('Duplicate to ')?><?php echo $blog_details->blogname; ?>').appendTo("select[name='action']");
                  jQuery('<option>').val("dup-<?php echo $site['blog_id'] ?>").text('<?php _e('Duplicate to ')?><?php echo $blog_details->blogname; ?>').appendTo("select[name='action2']");

                <?php endif; ?>
                
              <?php endforeach; ?>
          });
        </script>
        <?php 

    }
    
}

add_action('load-edit.php', 'mpd_bulk_action');
 
function mpd_bulk_action() {
 
  $wp_list_table = _get_list_table('WP_Posts_List_Table');
  $action = $wp_list_table->current_action();

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
  }


  // ..
  // 4. Redirect client
  //wp_redirect($sendback);
 
}

add_action( 'mdp_end_plugin_setting_page', 'add_bulk_settings');

function add_bulk_settings(){

     add_settings_field( 
      'add_bulk_settings', 
      __( 'Batch stuff?', MPD_DOMAIN ), 
      'mdp_default_batch_render', 
      MPD_SETTING_PAGE, 
      MPD_SETTING_SECTION

  );

}

function mdp_default_batch_render(){

  ?>Stuff<?php

}