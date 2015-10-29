<?php

add_action('admin_footer-edit.php', 'mpd_bulk_admin_script');
 
function mpd_bulk_admin_script() {

    $args           = array('network_id' => null);
    $sites          = wp_get_sites($args);

    ?>
    <script type="text/javascript">
      jQuery(document).ready(function() {
        <?php foreach ($sites as $site) :?>
          <?php $blog_details = get_blog_details($site['blog_id']); ?> 
            <?php if($site['blog_id'] != get_current_blog_id()):?> 
              jQuery('<option>').val("dup-<?php echo $site['blog_id'] ?>").text('<?php _e('Duplicate to ')?><?php echo $blog_details->blogname; ?>').appendTo("select[name='action']");
              jQuery('<option>').val("dup-<?php echo $site['blog_id'] ?>").text('<?php _e('Duplicate to ')?><?php echo $blog_details->blogname; ?>').appendTo("select[name='action2']");
            <?php endif; ?>
          <?php endforeach; ?>
      });
    </script>
    <?php 
}

add_action('load-edit.php', 'mpd_bulk_action');
 
function mpd_bulk_action() {
 
  $wp_list_table = _get_list_table('WP_Posts_List_Table');
  $action = $wp_list_table->current_action();
  if($action){

      preg_match("/(?<=dup-)\d+/", $action, $get_site);
      //echo $get_site[0];
  	 //var_dump($action);
  	 //var_dump($wp_list_table);
  	  if(isset($_REQUEST['post'])) {
            $post_ids = array_map('intval', $_REQUEST['post']);
           // var_dump($post_ids);
      }

      $results = array();
      foreach($post_ids as $post_id){
          
          $results[] = mpd_duplicate_over_multisite(
            $post_id, 
            $get_site[0],
            $_REQUEST['post_type'],
            1,
            mpd_get_prefix(),
            'draft'
          );

      } 
  }

 
  // ...
 
  // 4. Redirect client
  //wp_redirect($sendback);
 
}