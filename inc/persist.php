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

/**
 * Add the settings required for the persist setting 
 *
 * @since 1.0
 * @return null
 *
 */
function persist_addon_mpd_settings(){

	mpd_settings_field('persist_option_setting', __( 'Show Logging Tab?', MPD_DOMAIN ), 'persist_option_setting_render');

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

  <input type='checkbox' name='mdp_settings[add_logging]' <?php mpd_checked_lookup($options, 'add_logging', 'allow-logging') ;?> value='allow-logging'>

  <p class="mpdtip"><?php _e('Having this option checked will allow you to see the log of duplications made over this network', MPD_DOMAIN)?></p>
 
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
 
  $options['add_logging'] = 'allow-logging';

  return $options;

}
add_filter('mpd_activation_options', 'addon_mpd_logging_setting_activation');

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
	<h2>Linked Duplication Control</h2>
		<table id="mpdLinkedTable" class="display" cellspacing="0" width="100%" style="display:none;">

	        <thead>
	            <tr>
	                <th>Source Site</th>
	                <th>Destination Site</th>
	                <th>Source Post</th>
	                <th>Destination Post</th>
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

		        	<?php if($destination_post && $destination_post->post_status != 'trash'):?>

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
		
		<h2>Multisite Post Duplicator Log</h2>

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
	        			$nice_date_time			= date($date_format ." ". $time_format ,strtotime($row->dup_time));

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