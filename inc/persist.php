<?php
/**
 * Persist Duplication Functions
 * 
 * File is a collection of the functions used for the plugins persist functionality
 * 
 * @ignore
 * @since 1.0
 * @author Mario Jaconelli <mariojaconelli@gmail.com>
 * 
 */

function mpd_linked_list_metabox($page){

	$priority = apply_filters( 'mpd_list_metabox_priority', 'high' );

	if(mpd_get_persists_for_post()){
	 	add_meta_box( 'multisite_linked_list_metabox', "<i class='fa fa-link' aria-hidden='true'></i> " . __('Linked MPD Pages', MPD_DOMAIN ), 'mpd_linked_list_metabox_render', $page, 'side', $priority );
	}


}
add_action('mpd_meta_box', 'mpd_linked_list_metabox');

function mpd_linked_list_metabox_render(){
    
    $linked_posts = mpd_get_persists_for_post();
    $count = 1;
    ?>

    <p><small><?php _e('This post has other posts linked to it. If you update this post the it will also update the following posts in your network:', MPD_DOMAIN)?></small></p>
       
    <?php foreach ($linked_posts as $linked_post) :?>

    	<?php
    		$destination_post 		= get_blog_post($linked_post->destination_id, $linked_post->destination_post_id);
    		$destination_details 	= get_blog_details($linked_post->destination_id);
    	?>

    	<?php if($count == 1 || $linked_post->destination_id != $carryover):?>
    		
    		<span class="mpd-metabox-subtitle"><?php echo $destination_details->blogname ?></span>
    	
    	<?php endif;?>

    		<small class="mpd-metabox-list">

    			<a href="<?php echo mpd_get_edit_url($linked_post->destination_id, $linked_post->destination_post_id); ?>"><?php echo $destination_post->post_title; ?></a>

    		</small>

    	<?php $carryover = $linked_post->destination_id; $count++;?>

    <?php endforeach;?>

    <hr>

    <p class="bottom-para">

        <small>

        	<a class="no-dec" target="_blank" title="Multisite Post Duplicator Manage Links" href="<?php echo esc_url( get_admin_url(null, 'options-general.php?page=multisite_post_duplicator&tab=persists') ); ?>"> Manage Links <i class="fa fa-cog fa-lg" aria-hidden="true"></i></a>

        </small>
                
    </p>

    <?php
}

function mpd_create_persist_database(){

	$tableName = $wpdb->base_prefix . "mpd_log";

	$charset_collate = $wpdb->get_charset_collate();

	$sql ="CREATE TABLE $tableName (
	
			  id mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
			  source_id mediumint(9) DEFAULT NULL,
			  destination_id mediumint(9) DEFAULT NULL,
			  source_post_id mediumint(9) DEFAULT NULL,
			  destination_post_id mediumint(9) DEFAULT NULL,
			  persist_active mediumint(9) DEFAULT '0' NOT NULL,
			  persist_action_count mediumint(9) DEFAULT '0' NOT NULL,
			  dup_user_id mediumint(9) DEFAULT NULL,
			  dup_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  UNIQUE KEY id (id)
			
			) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
	dbDelta( $sql );

}

add_action('mpd_extend_activation', 'mpd_create_persist_database');

/**
 * Add the settings required for the persist setting 
 *
 * @since 1.0
 * @return null
 *
 */
function persist_addon_mpd_settings(){

	mpd_settings_field('persist_option_setting', __( 'Show logging tab?', MPD_DOMAIN ), 'persist_option_setting_render');
	mpd_settings_field('persist_functionality_setting', __( 'Show Post persist functionality?', MPD_DOMAIN ), 'persist_functionality_setting_render');

}
add_action( 'mdp_end_plugin_setting_page', 'persist_addon_mpd_settings');

/**
 * Function Used to render settings markup for logging question
 *
 * @since 1.0
 * @return null
 *
 */
function persist_option_setting_render(){
  
  $options = get_option( 'mdp_settings' ); ?>
  <script>
		jQuery(document).ready(function() {
				accordionClick('.sl-click', '.sl-content', 'fast');
		});
	</script>
  <input type='checkbox' name='mdp_settings[add_logging]' <?php mpd_checked_lookup($options, 'add_logging', 'allow-logging') ;?> value='allow-logging'> <i class="fa fa-info-circle sl-click accord" aria-hidden="true"></i>

  <p class="mpdtip sl-content" style="display:none"><?php _e('Having this option checked will allow you to see the log of duplications made over this network', MPD_DOMAIN)?></p>
 
  <?php
  
}

/**
 * Function Used to render settings markup for logging question
 *
 * @since 1.0
 * @return null
 *
 */
function persist_functionality_setting_render(){
  
  $options = get_option( 'mdp_settings' ); ?>
 <script>
		jQuery(document).ready(function() {
				accordionClick('.ap-click', '.ap-content', 'fast');
		});
	</script>
  <input type='checkbox' name='mdp_settings[allow_persist]' <?php mpd_checked_lookup($options, 'allow_persist', 'allow_persist') ;?> value='allow_persist'> <i class="fa fa-info-circle ap-click accord" aria-hidden="true"></i>

  <p class="mpdtip ap-content" style="display:none"><?php _e('Having this option checked will allow you to link a source post to a destination post. If the source is then updated the destination post will always be updated. This link can be added via the MPD Box on the posts page', MPD_DOMAIN)?></p>
 
  <?php
  
}

/**
 * Add plugin activation option for logging on activation of plugin
 *
 * @since 1.0
 * @param Array $options Default options to add on activation if options don't already exsist
 * @return Array Updated Options
 *
 */
function addon_mpd_logging_setting_activation($options){
 
  if(version_compare(mpd_get_version(),'0.9.1', '<=')){
  		$options['add_logging'] = 'allow-logging';
  		$options['allow_persist'] = 'allow_persist';
  }

  return $options;

}
add_filter('mdp_activation_options', 'addon_mpd_logging_setting_activation');

/**
 * Add default option for logging on activation of plugin
 *
 * @since 1.0
 * @param Array $options Default options to add on activation if options don't already exsist
 * @return Array Updated Options
 *
 */
function mpd_logging_add_default_option($mdp_default_options){

  $mdp_default_options['add_logging'] = 'allow-logging';
   $mdp_default_options['allow_persist'] = 'allow_persist';

  return $mdp_default_options;

}
add_filter('mdp_default_options', 'mpd_logging_add_default_option');

/**
 * Add this function processes a log of the duplication into our custom database
 *
 * @since 1.0
 * @param Array $createdPostObject Info about created post.
 * @param Array $mpd_process_info Original arguments used by core to process the duplication
 * @return bool True on success, false on failure
 *
 */
function mpd_log_duplication($createdPostObject, $mpd_process_info){
	
	$options = get_option( 'mdp_settings' );
		
	global $wpdb;

	$result = $wpdb->insert( 
		$wpdb->base_prefix . "mpd_log", 
		array( 
			'source_id' 			=> get_current_blog_id(), 
			'destination_id' 		=> $mpd_process_info['destination_id'],
			'source_post_id'		=> $mpd_process_info['source_id'],
			'destination_post_id'	=> $createdPostObject['id'],
			'persist_action_count'	=> 0,
			'dup_user_id'			=> get_current_user_id(),
			'dup_time'				=> date("Y-m-d H:i:s")
		), 
		array( 
			'%d','%d','%d','%d','%d','%d', '%s'
		) 
	);
	
	return $result;
	
	
}

add_action('mpd_log', 'mpd_log_duplication', 10, 2);

/**
 * Get all the log entries from our custom log database
 *
 * @since 1.0
 * @return Object Log entries
 *
 */
function mpd_get_log(){

	global $wpdb;
	
	$tableName = $wpdb->base_prefix . "mpd_log";

	$query = "SELECT * FROM $tableName";
	
	$results = $wpdb->get_results($query);
	
	return $results;

}

/**
 * Check to see if a persist request is made on a set of arguments
 *
 * @since 1.0
 * @param $args Array 
 * 		Required Params
 * 		'source_id' : The ID of the source site 
 *		'destination_id' : The ID of the destination site  
 *		'source_post_id' : The ID of the source post that was copied
 * @return bool True if persist request exsists on arguments
 */
function mpd_is_there_a_persist($args){
	
	global $wpdb;
	
	$tableName = $wpdb->base_prefix . "mpd_log";

	$query = "SELECT persist_active
				FROM $tableName
				WHERE 
				source_id = ". $args['source_id'] . " 
				AND destination_id = ". $args['destination_id']. "
				AND source_post_id = ". $args['source_post_id'];

	
	$result = $wpdb->get_var($query);
	
	if($result != null && $result != 0){
		return true;
	}else{
		return false;
	}

}
function mpd_get_persists_for_post($blog_id = null, $post_id = null){
	
	global $wpdb;
	global $post;

	$postobject = $post ? $post->ID : 0;
	$blog_id 	= $blog_id ? $blog_id : get_current_blog_id();
	$post_id 	= $post_id ? $post_id : $postobject;
	
	$tableName = $wpdb->base_prefix . "mpd_log";

	$query = "SELECT *
			  FROM $tableName
			  WHERE 
			  source_id = ". $blog_id . " 
			  AND source_post_id = ". $post_id ."
			  
			  order by destination_id";

	
	$result = $wpdb->get_results($query);
	// AND persist_active = 1
	return $result;
	
}
/**
 * Get all data on requested persists across the network
 *
 * @since 1.0
 * @return Object All data relating to requested presists
 */
function mpd_get_the_persists(){
	
	global $wpdb;
	
	$tableName = $wpdb->base_prefix . "mpd_log";

	$query = "SELECT *
				FROM $tableName
				WHERE persist_active = 1";
	
	$results = $wpdb->get_results($query);
	
	return $results;

}

/**
 * Add persist request to a set of arguments
 *
 * @since 1.0
 * @param  Array $args 
 * 		Required Params
 * 		'source_id' : The ID of the source site 
 *		'destination_id' : The ID of the destination site  
 *		'source_post_id' : The ID of the source post that was copied
 * 		'destination_post_id' : The ID of the source post that was copied
 * @return bool True on success
 */
function mpd_add_persist($args){
	
	$dataValue = 1;
	
	$result = mpd_update_persist($args, $dataValue);
	
	return $result;

}
/**
 * Remove persist request to a set of arguments
 *
 * @since 1.0
 * @param  Array $args 
 * 		Required Params
 * 		'source_id' : The ID of the source site 
 *		'destination_id' : The ID of the destination site  
 *		'source_post_id' : The ID of the source post that was copied
 * 		'destination_post_id' : The ID of the source post that was copied
 * @return bool True on success
 */
function mpd_remove_persist($args){

	$dataValue = 0;

	$result = mpd_update_persist($args, $dataValue);

	return $result;
		
}
/**
 * Update persist request to a set of arguments
 *
 * @since 1.0
 * @param  Array $args 
 * 		Required Params
 * 		'source_id' : The ID of the source site 
 *		'destination_id' : The ID of the destination site  
 *		'source_post_id' : The ID of the source post that was copied
 * 		'destination_post_id' : The ID of the source post that was copied
 * @return bool True on success
 */
function mpd_update_persist($args, $dataValue){

	global $wpdb;
	
	$table 	= $wpdb->base_prefix . "mpd_log";
	$data 	= array(
		'persist_active' => $dataValue
	);
	$where 	= array(

		'source_id' 			=> $args['source_id'], 
		'destination_id' 		=> $args['destination_id'],
		'source_post_id'		=> $args['source_post_id'],
		'destination_post_id'	=> $args['destination_post_id']

	);	
	$format = array('%d');	
	$where_format = array( 
		'%d','%d','%d','%d','%d'
	);
	
	$result = $wpdb->update( $table, $data, $where, $format, $where_format);
	
	return $result;

}
function mpd_get_persist_count($args){

	global $wpdb;
	
	$table 	= $wpdb->base_prefix . "mpd_log";

	$query = "SELECT persist_action_count
				FROM $table
				WHERE 	source_id 			= " . $args['source_id'] . "
				AND
						destination_id		= " . $args['destination_id'] . "
				AND
						source_post_id		= " . $args['source_post_id'] . "
				AND
						destination_post_id	= " . $args['destination_post_id'];
	
	$result = $wpdb->get_var($query);

	return $result;

}

function mpd_set_persist_count($args){

	global $wpdb;
	
	$table 	= $wpdb->base_prefix . "mpd_log";

	$oldData = mpd_get_persist_count($args);

	$newData 	= array(
		'persist_action_count' => intval($oldData) + 1
	);

	$where 	= array(

		'source_id' 			=> $args['source_id'], 
		'destination_id' 		=> $args['destination_id'],
		'source_post_id'		=> $args['source_post_id'],
		'destination_post_id'	=> $args['destination_post_id'],
		'persist_active'		=> 1

	);	
	$format = array('%d');	

	$where_format = array( 
		'%d','%d','%d','%d','%d'
	);

	$result = $wpdb->update( $table, $newData, $where, $format, $where_format);

	return $newData['persist_action_count'];

}

function mpd_persist_post($post_id){
	
    global $post;

	$blog_id = get_current_blog_id();
    $post_id = $post->ID;
	
	$persist_posts = mpd_get_persists_for_post($blog_id, $post_id);
	
    if($persist_posts){
        
        foreach($persist_posts as $persist_post){
            
            $args = array(
                'source_id' 			=> intval($persist_post->source_id),
                'destination_id' 		=> intval($persist_post->destination_id),
                'source_post_id' 		=> intval($persist_post->source_post_id),
                'destination_post_id' 	=> intval($persist_post->destination_post_id)
            );
            
            mpd_persist_over_multisite($persist_post);

            mpd_set_persist_count($args);
        }
    }
 
	return;
	
}
add_action('save_post', 'mpd_persist_post');




function mpd_enqueue_datatables(){

	wp_enqueue_script(
		'mdp-admin-datatables-scripts',
		'https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js',
		array( 'jquery' ),
		'1.0'
	);

	wp_enqueue_script(
		'mdp-admin-datatables-init',
		plugins_url( '../js/admin-datatable-init.js', __FILE__ ),
		array( 'mdp-admin-datatables-scripts' ),
		'1.0'
	);
	
	wp_register_style(
		'mdp-datatables-styles',
		'https://cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css',
		false,
		'1.0.0'
	);

	wp_enqueue_style('mdp-datatables-styles');

}

function mpd_persist_page(){

	if(isset($_GET['remove'])){

		$args = array(
			'source_id' 			=> $_GET['s'], 
			'destination_id' 		=> $_GET['d'],
			'source_post_id'		=> $_GET['sp'],
			'destination_post_id'	=> $_GET['dp']
		);

		mpd_remove_persist($args);
	}

	mpd_enqueue_datatables();

	$rows = mpd_get_the_persists();
	?>
	<div class="wrap">
	<h2><i class="fa fa-link" aria-hidden="true"></i> Linked Duplication Control</h2>
		<div class="mpd-loading">
				<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
				<span class="sr-only">Loading...</span>
		</div>	
		<table id="mpdLinkedTable" class="display" cellspacing="0" width="100%" style="display:none;">

	        <thead>
	            <tr>
	                <th>Source Site</th>
	                <th>Destination Site</th>
	                <th>Source Post</th>
	                <th>Destination Post</th>
	                <th>Update Count</th>
	                <th>Post Type</th>
	                <th>User</th>
	                <th>Action</th>
	                
	            </tr>
	        </thead>
	       
	        <tbody>

	        	<?php foreach($rows as $row):?>
		        	
		        	<?php

	        			$source_details 		= get_blog_details($row->source_id);
	        			$destination_details 	= get_blog_details($row->destination_id);
	        			$source_post 			= get_blog_post($row->source_id, $row->source_post_id);
	        			$destination_post 		= get_blog_post($row->destination_id, $row->destination_post_id);
	        			$user_info 				= get_userdata($row->dup_user_id);
	        			$remove_url = "options-general.php?page=multisite_post_duplicator&tab=persists&remove=1&s=". $row->source_id . "&d=" . $row->destination_id . "&sp=" . $row->source_post_id  . "&dp=" . $row->destination_post_id;

		        	?>

		        	<?php if(($bool = ($source_post && $source_post->post_status != 'trash')) && $destination_post && $destination_post->post_status != 'trash'):?>

			       		 <tr>
			                <td><?php echo $source_details->blogname; ?></td>
			                <td><?php echo $destination_details->blogname; ?></td>
			                <td>
			                	<?php if($bool):?>
			                		
			                		<a href="<?php echo mpd_get_edit_url($row->source_id, $row->source_post_id); ?>">
			                			
				                <?php endif; ?>

				                <?php if($source_post):?>

				                	<?php echo $source_post->post_title; ?>
				                
				                <?php else:?>
				                	
				                	<em>This post no longer exists</em>
				                
				                <?php endif;?>

				                <?php if($bool):?>

				                	</a>

			                	<?php endif; ?>

			                </td>
			                <td>
			                	<a href="<?php echo mpd_get_edit_url($row->destination_id, $row->destination_post_id); ?>">
			                		<?php echo $destination_post->post_title; ?>
			                	</a>
			                </td>
			                <td><?php echo $row->persist_action_count; ?></td>
			                <td><?php echo $destination_post->post_type; ?></td>
			                <td><?php echo $user_info->user_login; ?></td>
			                <td>
			                	<a class="removeURL button-secondary" href="<?php echo $remove_url; ?>">Delete Persist</a>
			                </td>
			                <td><?php echo $row->dup_time; ?></td>
			            </tr>

		            <?php endif; ?>

	            <?php endforeach; ?>

	        </tbody>

	    </table>

	</div>

	<?php
}
/**
 * @ignore
 */
function mdp_log_page(){

	mpd_enqueue_datatables();
	
	$rows = mpd_get_log();

	?>
	<div class="wrap">
		
		<h2><i class="fa fa-list-ul" aria-hidden="true"></i> Multisite Post Duplicator Log</h2>

		<div class="mpd-loading">
				<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
				<span class="sr-only">Loading...</span>
		</div>	

		<table id="mpdLogTable" class="display" cellspacing="0" width="100%" style="display:none;">
	        
	        <thead>
	            <tr>
	                <th>Source Site</th>
	                <th>Destination Site</th>
	                <th>Source Post</th>
	                <th>Destination Post</th>
	                <th>Post Type</th>
	                <th>User</th>
	                <th>Time</th>
	                <th>Time Raw</th>
	            </tr>
	        </thead>
	       
	        <tbody>
	        	<?php 
	        	
	        		$date_format = get_option('date_format');
	        		$time_format = get_option('time_format');
	        		
	        		foreach($rows as $row):?>
		        	
		        	<?php
	        			$source_details 		= get_blog_details($row->source_id);
	        			$destination_details 	= get_blog_details($row->destination_id);
	        			$source_post 			= get_blog_post($row->source_id, $row->source_post_id);
	        			$destination_post 		= get_blog_post($row->destination_id, $row->destination_post_id);
	        			$user_info 				= get_userdata($row->dup_user_id);
	        			$nice_date_time			= date($date_format .", ". $time_format ,strtotime($row->dup_time));

		        	 if($destination_post && $destination_post->post_status != 'trash'):?>

			       		 <tr>
			                <td><?php echo $source_details->blogname; ?></td>
			                <td><?php echo $destination_details->blogname; ?></td>
			                <td>
			                	<?php if($bool = ($source_post && $source_post->post_status != 'trash')):?>
			                		
			                		<a href="<?php echo mpd_get_edit_url($row->source_id, $row->source_post_id); ?>">
			                			
				                	<?php endif; ?>

				                	<?php if($source_post):?>

				                		<?php echo $source_post->post_title; ?>
				                	
				                	<?php else:?>
				                		
				                		<em>This post no longer exists</em>
				                	
				                	<?php endif;?>

				                	<?php if($bool):?>

				                		</a>

			                		<?php endif; ?>

			                </td>
			                <td>
			                	<a href="<?php echo mpd_get_edit_url($row->destination_id, $row->destination_post_id); ?>">
			                		<?php echo $destination_post->post_title; ?>
			                	</a>
			                </td>
			                <td><?php echo $destination_post->post_type; ?></td>
			                <td><?php echo $user_info->user_login; ?></td>
			                <td><?php echo $nice_date_time; ?></td>
			                <td><?php echo $row->dup_time; ?></td>
			            </tr>

		            <?php endif?>

	            <?php endforeach; ?>

	        </tbody>

	    </table>

	</div>
	<?php
}

function mpd_persist_checkbox(){

	$options = get_option( 'mdp_settings' );

	if(isset($options['allow_persist']) || !$options ): ?>     
            <hr>
                    <label class="selectit">
                        <script>
                            jQuery(document).ready(function($) { 
                                accordionClick('.pl-link', '.pl-content', 'fast');
                            });

                        </script>
                        <ul>
                            <li><input type="checkbox" name="persist">Create Duplication Link? <i class="fa fa-info-circle pl-link" aria-hidden="true"></i></li>
                        </ul>
                        
                        <p class="mpdtip pl-content" style="display:none"><?php _e('Checking this option will create a link between this post and the resulting copied post. After the link is created if you ever update this post in the future the changes will automatically be copied over to the linked posts also. If you want to delete the link you can do so <a href="'. esc_url( get_admin_url(null, 'options-general.php?page=multisite_post_duplicator&tab=persists') ) .'">here</a>', MPD_DOMAIN ) ?></p>

                    </label>

            <hr>

    <?php endif;

}

add_action('mpd_after_metabox_content', 'mpd_persist_checkbox', 9);