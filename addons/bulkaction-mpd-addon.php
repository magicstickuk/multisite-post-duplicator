<?php

add_action('admin_footer-edit.php', 'custom_bulk_admin_footer');
 
function custom_bulk_admin_footer() {
 
  global $post_type;
 
  if($post_type == 'post') {
    ?>
    <script type="text/javascript">
      jQuery(document).ready(function() {
        jQuery('<option>').val('dup-1').text('<?php _e('Duplicate to site 1')?>').appendTo("select[name='action']");
        jQuery('<option>').val('dup-1').text('<?php _e('Duplicate to site 1')?>').appendTo("select[name='action2']");
        jQuery('<option>').val('dup-2').text('<?php _e('Duplicate to site 2')?>').appendTo("select[name='action']");
        jQuery('<option>').val('dup-2').text('<?php _e('Duplicate to site 2')?>').appendTo("select[name='action2']");
      });
    </script>
    <?php
  }
}

add_action('load-edit.php', 'custom_bulk_action');
 
function custom_bulk_action() {
 
  // ...
 
  // 1. get the action
  $wp_list_table = _get_list_table('WP_Posts_List_Table');
  $action = $wp_list_table->current_action();
  if($action){
  	 // var_dump($action);
  	 //  var_dump($wp_list_table);
  	 //  if(isset($_REQUEST['post'])) {
    //         $post_ids = array_map('intval', $_REQUEST['post']);
    //     }
    //     var_dump($post_ids);
  }

  // ...

  
  //check_admin_referer('bulk-posts');
 
  // ...
 
  switch($action) {
    // 3. Perform the action
    case 'export-1':
      // if we set up user permissions/capabilities, the code might look like:
      //if ( !current_user_can($post_type_object->cap->export_post, $post_id) )
      //  pp_die( __('You are not allowed to export this post.') );
 
      $exported = 0;
 
      // foreach( $post_ids as $post_id ) {
      //   	var_dump($action);
      // }
 
      // build the redirect url
      //$sendback = add_query_arg( array('exported' => $exported, 'ids' => join(',', $post_ids) ), $sendback );
 
    break;
    default: return;
  }
 
  // ...
 
  // 4. Redirect client
  //wp_redirect($sendback);
 
  exit();
}
