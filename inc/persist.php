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
 * Create Metaboxs related to the 'Linked Duplication' functionality on a post type as determined my users options ($page)
 *
 * @since 1.0
 * @param string|array|WP_Screen $page The screen or screens on which to show the box. Generated from mpd_get_postype_decision_from_options();
 * @return null
 *
 */
function mpd_side_metaboxs($page){

	// Filter to allow developers to determine thier own metabox priority.
	$priority 		= apply_filters( 'mpd_metabox_priority', 'high' );
	$options 		= get_option( 'mdp_settings' );

	
	// If no linkage exists add a metabox to allow a link to be created to an existing post
	if((isset($options['allow_persist']) || !$options)){

		add_meta_box( 'multisite_create_link', "<i class='fa fa-link' aria-hidden='true'></i> " . __('MPD Create Link', 'multisite-post-duplicator' ), 'mpd_create_link_render', $page, 'side', $priority );

	}


}
add_action('mpd_meta_box', 'mpd_side_metaboxs');

/**
 * Create Metaboxs related to the 'Linked Duplication' functionality on a post type as determined my users options ($page).
 * These metaboxes are not restricted by the user capabilities.
 *
 * @since 1.6.4
 * @param string|array|WP_Screen $page The screen or screens on which to show the box. Generated from mpd_get_postype_decision_from_options();
 * @return null
 *
 */
function mpd_side_metaboxs_global($page){

	// Filter to allow developers to determine thier own metabox priority.
	$priority 		= apply_filters( 'mpd_metabox_priority', 'high' );
	$options 		= get_option( 'mdp_settings' );
	$persists 		= mpd_get_persists_for_post();
	$source 		= mpd_get_posts_source_post();

	// If the current post is a source post, the add a metabox to list the linked posts.
	if($persists){
	 	add_meta_box( 'multisite_linked_list_metabox', "<i class='fa fa-link' aria-hidden='true'></i> " . __('Linked MPD Pages', 'multisite-post-duplicator' ), 'mpd_linked_list_metabox_render', $page, 'side', $priority );
	 	
	}
	// If the current post has any souce posts add appropriate metabox
	if($source){
		add_meta_box( 'multisite_source_list_metabox', "<i class='fa fa-university' aria-hidden='true'></i> " . __('MPD Source Post', 'multisite-post-duplicator' ), 'mpd_source_list_metabox_render', $page, 'side', $priority );
	 	
	}



}

add_action('mpd_meta_box_global', 'mpd_side_metaboxs_global');

/**
 * Create the markup for 'add link to exsisting post'
 *
 * @since 1.1
 * @return null
 *
 */
function mpd_create_link_render(){
	?>
	<p><?php _e('Do you want to link this page to an existing post?', 'multisite-post-duplicator') ?></p>

	<a class="button-secondary" id="createLink"><?php _e('Create', 'multisite-post-duplicator'); ?></a>

	<div class="create-link-ui">
			
			<p><?php _e('Select the site where the post is that you want to link to', 'multisite-post-duplicator'); ?>:</p>
			
			<?php $sites = mpd_wp_get_sites();?>
			
			<select id="create-link-site-select">

				<option value="-1">
				-- <?php _e('Select Site', 'multisite-post-duplicator'); ?> --
				</option>

				<?php foreach ($sites as $site) :?>

					<?php 	$siteid 		= $site->blog_id;
							$blog_details 	= get_blog_details($siteid); ?>

					<option value="<?php echo $siteid ?>">

						<?php echo $blog_details->blogname; ?>

					</option>

				<?php endforeach; ?>

			</select>

			<p class="create-link-site-spin mpd-spinner-container"><img src="<?php echo plugins_url('../css/select2-spinner.gif',__FILE__); ?>"/></p>

			<p class="link-created-confirm"><img src="<?php echo plugins_url('../images/tick.png',__FILE__); ?>" alt=""> <?php _e('Link Created','multisite-post-duplicator'); ?></p>

			<p class="link-created-create-another"><a>Create Another Link</a></p>

	</div>

	<?php 
}

/**
 * Create the markup for 'select post' select box. This function is used in ajax
 *
 * @since 1.1
 * @return null
 *
 */
function mpd_create_link_post_list(){

	$site 			= $_POST['site'];
	$post_id 		= $_POST['post_id'];
	$post_type 		= get_post_type( $post_id );
	$postStatuses 	= array_keys(mpd_get_post_statuses());

	switch_to_blog( $site );
	$args = array(
			'posts_per_page'   => -1,
			'post_type'        => $post_type,
			'post_status'      => $postStatuses,
	);

	$posts = get_posts($args);?>

	<select id="create-link-post-select">
		
		<?php if($posts):?>
			
			<option value="-1">
				-- <?php _e('Select a post to link to', 'multisite-post-duplicator');?> --
			</option>
			
			<?php foreach ($posts as $site_post): ?>
				
				<option value="<?php echo $site_post->ID; ?>">
					<?php echo $site_post->post_title; ?>
				</option>

			<?php endforeach ?>
			
			<?php else: ?>
				<option value="-1">
					-- <?php _e('No posts available to link to', 'multisite-post-duplicator');?> --
				</option>
			<?php endif; restore_current_blog();?>

		</select>
	
	<?php if($posts):?>
	
		<a class="button button-primary button-large" id="create-link-submit"><?php _e('Create Link', 'multisite-post-duplicator') ?></a>
	
	<?php endif?>
	
	<p class="create-link-submit-spin mpd-spinner-container"><img src="<?php echo plugins_url('../css/select2-spinner.gif',__FILE__); ?>"/></p>
	
	<?php

	die();

}
add_action('wp_ajax_mpd_create_link_post_list', 'mpd_create_link_post_list');

/**
 * Ajax fiunction that links desired posts in our custom database
 *
 * @since 1.1
 * @return null
 *
 */
function mpd_create_link_submit(){

	global $wpdb;

	$site 		= $_POST['site'];
	$post_to_link 	= $_POST['post_to_link'];
	$post_id 		= $_POST['post_id'];

	$wpdb->delete(
		$wpdb->base_prefix . "mpd_log", 
		array(
			'source_id' 			=> get_current_blog_id(), 
			'destination_id' 		=> $site,
			'source_post_id'		=> $post_id,
			'destination_post_id'	=> $post_to_link,
		),
		array( 
			'%d','%d','%d','%d'
		)
	);

	$result = $wpdb->insert( 
		$wpdb->base_prefix . "mpd_log", 
		array( 
			'source_id' 		=> get_current_blog_id(), 
			'destination_id' 		=> $site,
			'source_post_id'		=> $post_id,
			'destination_post_id'	=> $post_to_link,
			'persist_active'		=> 1,
			'persist_action_count'	=> 0,
			'dup_user_id'		=> get_current_user_id(),
			'dup_time'			=> date("Y-m-d H:i:s")
		), 
		array( 
			'%d','%d','%d','%d','%d','%d','%d', '%s'
		) 
	);

	if($result){
		echo '1';
	}

	die();
}

add_action('wp_ajax_mpd_create_link_submit', 'mpd_create_link_submit');

/**
 * Create the markup for information on the posts source post
 *
 * @since 1.0
 * @return null
 *
 */
function mpd_source_list_metabox_render(){
	
	// Get the source post information from our custom database
	$source_link_info 	= mpd_get_posts_source_post();

	// Relate this info to WordPress
	if($source_link_info){
		$source_post 		= get_blog_post($source_link_info->source_id, $source_link_info->source_post_id);
		$source_details 	= get_blog_details($source_link_info->source_id);
	}
	
	?>
	<script>
		// Add alert warning information to the 'update' button on the post's page about this functionality
    	jQuery(document).ready(function($) {
    		
    		jQuery('#publish').click(function(e) {
    			e.preventDefault();
        		if (window.confirm("Remember, this post is linked to source post, so any changes made here maybe overwritten if the source post is updated")) {
            		jQuery(this).unbind('click').click();
        		}
    		});

    	});
    </script>
	
	<p class="notice notice-warning"><small><?php _e('CAUTION: This post is linked to the following post:', 'multisite-post-duplicator')?></small></p>
	
	<span class="mpd-metabox-subtitle"><?php echo $source_details->blogname ?></span>	
	
	<small class="mpd-metabox-list">

    	<a href="<?php echo mpd_get_edit_url($source_link_info->source_id, $source_link_info->source_post_id); ?>"><?php echo $source_post->post_title; ?></a>

    </small>
    		
	<p><small><?php _e('This means that if the source post above is updated it will overwrite any changes made here.', 'multisite-post-duplicator')?></small></p>
	
	<?php
	
	mpd_do_manage_links();
}

/**
 * Create the markup for information on the source posts linked pages
 *
 * @since 1.0
 * @return null
 *
 */
function mpd_linked_list_metabox_render(){
    
    $linked_posts = mpd_get_persists_for_post();
    $count = 1;
    ?>
    
    <p><small><?php _e('This post it linked to other posts. If you update this post the it will also update the following posts in your network:', 'multisite-post-duplicator')?></small></p>
       
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

    <?php endforeach;
    
    mpd_do_manage_links();
    
}


/**
 * Create the markup for a link to manage linked posts.
 *
 * @since 1.0
 * @return null
 *
 */
function mpd_do_manage_links(){
	?>
	<hr>

    <p class="bottom-para">

        <small>

        	<a class="no-dec" target="_blank" title="Multisite Post Duplicator Manage Links" href="<?php echo esc_url( get_admin_url(null, 'options-general.php?page=multisite_post_duplicator&tab=persists') ); ?>"> Manage Links <i class="fa fa-cog fa-lg" aria-hidden="true"></i></a>

        </small>
                
    </p>
	<?php
	
}

/**
 * Add the settings required for the persist setting 
 *
 * @since 1.0
 * @return null
 *
 */
function persist_addon_mpd_settings(){

	mpd_settings_field('persist_option_setting', '<i class="fa fa-list-ul" aria-hidden="true"></i> ' . __( 'Show logging tab?', 'multisite-post-duplicator' ), 'persist_option_setting_render');

	mpd_settings_field('persist_functionality_setting', '<i class="fa fa-link" aria-hidden="true"></i> ' . __( 'Allow linked duplication functionality?', 'multisite-post-duplicator' ), 'persist_functionality_setting_render');

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
 	<div class="checkbox checkbox-slider--b-flat">
		
		<label>
  			<input type='checkbox' name='mdp_settings[add_logging]' <?php mpd_checked_lookup($options, 'add_logging', 'allow-logging') ;?> value='allow-logging'>

  			<span><?php mpd_information_icon('Having this option checked will allow you to see the log of duplications made over this network'); ?></span>
  			</label>
  			</div>
 
  <?php
  
}

/**
 * Render settings markup for logging question
 *
 * @since 1.0
 * @return null
 *
 */
function persist_functionality_setting_render(){
  
  $options = get_option( 'mdp_settings' ); ?>

 	<div class="checkbox checkbox-slider--b-flat">
		
		<label>
  			<input type='checkbox' name='mdp_settings[allow_persist]' <?php mpd_checked_lookup($options, 'allow_persist', 'allow_persist') ;?> value='allow_persist'>
			<span>
  				<?php mpd_information_icon('Having this option checked will allow you to link a source post to a destination post. If the source is then updated the destination post will always be updated. This link can be added via the MPD Box on the posts page'); ?>	
  			</span>
  		</label>

  	</div>
 
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
 
  if(version_compare(mpd_get_version(),'1.0.2', '<=')){
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
			
	global $wpdb;

	$desitnation_post_id = isset($mpd_process_info['destination_post_id']) ? $mpd_process_info['destination_post_id'] : $createdPostObject['id'];

	$tableName 			= $wpdb->base_prefix . "mpd_log";
	$current_blog_id 	= get_current_blog_id();

	//Check if the log already exsists (avoiding duplication through save_post actions and filters)
	$query = $wpdb->prepare("SELECT *
				FROM $tableName
				WHERE 
				source_id = %d 
				AND destination_id = %d
				AND source_post_id = %d
				AND destination_post_id = %d", 
				
				$current_blog_id,
				$mpd_process_info['destination_id'],
				$mpd_process_info['source_post_id'],
				$desitnation_post_id
			);

	$result = $wpdb->get_results($query);

	//If the log doesn't exist then add to the database
	if(!$result){

		$resultSubmitted = $wpdb->insert( 
		$tableName, 
			array( 
				'source_id' 			=> $current_blog_id, 
				'destination_id' 		=> $mpd_process_info['destination_id'],
				'source_post_id'		=> $mpd_process_info['source_post_id'],
				'destination_post_id'	=> $desitnation_post_id,
				'persist_action_count'	=> 0,
				'dup_user_id'			=> get_current_user_id(),
				'dup_time'				=> date("Y-m-d H:i:s")
			), 
			array( 
				'%d','%d','%d','%d','%d','%d', '%s'
			) 
		);

		return $resultSubmitted;
		
	}

	
	
	
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
	
	return apply_filters('mpd_get_log', $results);

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

	$query = $wpdb->prepare("SELECT persist_active
				FROM $tableName
				WHERE 
				source_id = %d 
				AND destination_id = %d
				AND source_post_id = %d", 
				
				$args['source_id'],
				$args['destination_id'],
				$args['source_post_id']  );

	$result = $wpdb->get_var($query);
	
	if($result != null && $result != 0){
		return true;
	}else{
		return false;
	}

}
/**
 * Check to see if a persist request is made on a set of arguments
 *
 * @since 1.6
 * @param $args Array 
 * 		Required Params
 * 		'source_id' : The ID of the source site 
 *		'destination_id' : The ID of the destination site  
 *		'source_post_id' : The ID of the source post that was copied
 * 		'destination_post_id' : The ID of the source post that was copied
 * @return bool True if persist request exsists on arguments
 */
function mpd_is_there_a_persist_exact($args){
	
	global $wpdb;
	
	$tableName = $wpdb->base_prefix . "mpd_log";

	$query = $wpdb->prepare("SELECT persist_active
				FROM $tableName
				WHERE 
				source_id = %d 
				AND destination_id = %d
				AND source_post_id = %d
				AND destination_post_id = %d",  
				
				$args['source_id'],
				$args['destination_id'],
				$args['source_post_id'],
				$args['destination_post_id']   );

	$result = $wpdb->get_var($query);
	
	if($result != null && $result != 0){
		return true;
	}else{
		return false;
	}

}

/**
 * Get the source post, if any, for a given post
 *
 * @since 1.0
 * @param $blog_id The id of the blog the post is on. If blank then use current blog.
 * @param $blog_id The id of post to look at. If blank then use current post.
 * @return object An object with information on the post relationship with the following parameters:
 * 
 * 'id' => The unique id of the duplication event
 * 'source_id' => The id of the souce blog when duplicating
 * 'destination_id' => The id of the destination blog when duplicating
 * 'source_post_id' => The id of the souce post when duplicating
 * 'destination_post_id' => The id of the post that was created for the duplication
 * 'persist_active' => Confirmation that there is an active link on this post
 * 'persist_active_count' => How many times the source has maintained the link and update the destination
 */
function mpd_get_posts_source_post($blog_id = null, $post_id = null){
	
	global $wpdb;
	global $post;

	$postobject = $post ? $post->ID : false;
	$blog_id 	= $blog_id ? $blog_id : get_current_blog_id();
	$post_id 	= $post_id ? $post_id : $postobject;
	
	if($post_id){
		
		$tableName = $wpdb->base_prefix . "mpd_log";
	
		$query = $wpdb->prepare("SELECT *
			  FROM $tableName
			  WHERE 
			  destination_id = %d 
			  AND destination_post_id = %d
			  AND persist_active = 1
			  order by destination_id",

			  $blog_id,
			  $post_id);

	
		$result = $wpdb->get_row($query);	
		
	}
	
	// Check if the result is for a post that exists.

	if($result){
		
		$result_post 			= get_blog_post($result->source_id, $result->source_post_id);
		$wanted_post_statuses 	= array_keys(mpd_get_post_statuses());

		if($result_post && in_array($result_post->post_status, $wanted_post_statuses)){
			return $result;
		}

	}

}

/**
 * Get the linked posts, if any, for a given post
 *
 * @since 1.0
 * @param $blog_id The id of the blog the post is on. If blank then use current blog.
 * @param $blog_id The id of post to look at. If blank then use current post.
 * @return object An object with information on the post relationship with the following parameters:
 *  
 * 'id' => The unique id of the duplication event
 * 'source_id' => The id of the souce blog when duplicating
 * 'destination_id' => The id of the destination blog when duplicating
 * 'source_post_id' => The id of the souce post when duplicating
 * 'destination_post_id' => The id of the post that was created for the duplication
 * 'persist_active' => Confirmation that there is an active link on this post
 * 'persist_active_count' => How many times the source has maintained the link and update the destination
 */
function mpd_get_persists_for_post($blog_id = null, $post_id = null){
	
	global $wpdb;
	global $post;

	$postobject = $post ? $post->ID : false;
	$blog_id 	= $blog_id ? $blog_id : get_current_blog_id();
	$post_id 	= $post_id ? $post_id : $postobject;
	
	if($post_id){
		
		$tableName = $wpdb->base_prefix . "mpd_log";

		$query = $wpdb->prepare("SELECT *
			  FROM $tableName
			  WHERE 
			  source_id = %d
			  AND source_post_id = %d
			  AND persist_active = 1
			  order by destination_id",

			  $blog_id,
			  $post_id);

	
		$results = $wpdb->get_results($query);	
	}
	
	
	// Now we need to check the post statuses and remove any that are in the bin or dont exsist

	if($results){

		$wanted_post_statuses 	= array_keys(mpd_get_post_statuses());
		$keys_to_remove 		= array();

		foreach ($results as $key => $result) {

			$the_post = get_blog_post($result->destination_id, $result->destination_post_id);

			if(!$the_post || !in_array($the_post->post_status, $wanted_post_statuses)){

				array_push($keys_to_remove, $key);

			}

		}
	
		foreach ($keys_to_remove as $value) {
			
			unset($results[$value]);

		}

	}
	
	return $results;
	
}
/**
 * Get all data on requested persists across the network
 *
 * @since 1.0
 * @return Object All data relating to requested persists
 */
function mpd_get_the_persists(){
	
	global $wpdb;
	
	$tableName = $wpdb->base_prefix . "mpd_log";

	$query = "SELECT *
				FROM $tableName
				WHERE persist_active = 1";
	
	$results = $wpdb->get_results($query);
	
	return apply_filters('mpd_get_the_persists', $results);

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
 * @param int $dataValue The value to update the 'persist_active' value to. 1 denotes 'active', 0 denotes 'inactive'
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
		'%d','%d','%d','%d'
	);
	
	$result = $wpdb->update( $table, $data, $where, $format, $where_format);
	
	return $result;

}

/**
 * Get the amount of time the source post has updated a destination post.
 *
 * @since 1.0
 * @param  Array $args 
 * 		Required Params
 * 		'source_id' : The ID of the source site 
 *		'destination_id' : The ID of the destination site  
 *		'source_post_id' : The ID of the source post that was copied
 * 		'destination_post_id' : The ID of the source post that was copied
 * @return int The number count of succesfully duplicated linked posts
 */
function mpd_get_persist_count($args){

	global $wpdb;
	
	$table 	= $wpdb->base_prefix . "mpd_log";

	$query = $wpdb->prepare("SELECT persist_action_count
				FROM $table
				WHERE source_id 		= %d
				AND destination_id		= %d
				AND source_post_id		= %d
				AND destination_post_id	= %d",

				$args['source_id'],
				$args['destination_id'],
				$args['source_post_id'],
				$args['destination_post_id']);
	
	$result = $wpdb->get_var($query);

	return $result;

}

/**
 * Increment the count of duplication between source and destination posts on a set of given arguments
 *
 * @since 1.0
 * @param  Array $args 
 * 		Required Params
 * 		'source_id' : The ID of the source site 
 *		'destination_id' : The ID of the destination site  
 *		'source_post_id' : The ID of the source post that was copied
 * 		'destination_post_id' : The ID of the source post that was copied
 * @return int The new number count of succesfully duplicated linked posts
 */
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

/**
 * When a user updates a post we check if there is a link and if so do the duplication
 *
 * @since 1.0
 * @param  Int $post_id The post id of the current post being saved 
 * @return null
 */
function mpd_persist_post($post_id){
	
	$options = get_option( 'mdp_settings' );

	if((isset($options['allow_persist']) || !$options)){

		if( ! ( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) ) {

		    global $post;

			$blog_id = get_current_blog_id();
		    
			// Check if there is a link
			$persist_posts = mpd_get_persists_for_post($blog_id, $post_id);
			
			// Do the duplications if there are any links
		    if($persist_posts){
		        
		        foreach($persist_posts as $persist_post){
		            
		            $args = apply_filters('mpd_persist_post_args', array(
		                'source_id' 			=> intval($persist_post->source_id),
		                'destination_id' 		=> intval($persist_post->destination_id),
		                'source_post_id' 		=> intval($persist_post->source_post_id),
		                'destination_post_id' 	=> intval($persist_post->destination_post_id)
		            ));

		            if(!array_key_exists('skip_normal_persist', $args)){

		            	mpd_persist_over_multisite($persist_post);

		            	// Increment the count
		            	mpd_set_persist_count($args);

		           }

		            do_action('mpd_after_persist', $args);

		        }
		        
		    }

		}

	}
 
	return;
	
}
add_action('save_post', 'mpd_persist_post', 100);


/**
 * Get the nessesary assets to run datatables
 * https://datatables.net/
 *
 * @since 1.0
 * @return null
 */
function mpd_enqueue_datatables(){

	wp_enqueue_script(
		'mpd-admin-datatables-scripts',
		'https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js',
		array( 'jquery'),
		'1.0'
	);

	wp_enqueue_script(
		'mpd-admin-datatables-init',
		plugins_url( '../js/admin-datatable-init.js', __FILE__ ),
		array( 'mpd-admin-datatables-scripts' ),
		'1.0'
	);

	wp_localize_script('mpd-admin-datatables-init', 'mpd_dt_vars', array(
		'no_dups' => __('No multisite duplications.', 'multisite-post-duplicator'),
		'no_linked_dups' => __('There are no linked duplications yet.', 'multisite-post-duplicator'),
		'delete_link_warning' => __('Are you sure you want to delete the link between the source and destination post?', 'multisite-post-duplicator'),
		'search' => __('Search:', 'multisite-post-duplicator'),
		'first' => __('First', 'multisite-post-duplicator'),
		'last' => __('Last', 'multisite-post-duplicator'),
		'next' => __('Next', 'multisite-post-duplicator'),
		'previous' => __('Previous', 'multisite-post-duplicator'),
		'show' => __('Show', 'multisite-post-duplicator'),
		'entries' => __('entries', 'multisite-post-duplicator'),
		)
	);
	
	wp_register_style(
		'mpd-datatables-styles',
		'https://cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css',
		false,
		'1.0.0'
	);

	wp_enqueue_style('mpd-datatables-styles');

}

/**
 * Do the markup for a nice table showing all the currently active linked pages
 *
 * @since 1.0
 * @return null
 */

function mpd_persist_page(){

	// Process romoval of link if user has clicked on the 'remove link' button
	if(isset($_GET['remove'])){

		$args = array(
			'source_id' 			=> $_GET['s'], 
			'destination_id' 		=> $_GET['d'],
			'source_post_id'		=> $_GET['sp'],
			'destination_post_id'	=> $_GET['dp']
		);

		mpd_remove_persist($args);
	}

	// Load assests for nice datatable markup and functionality
	mpd_enqueue_datatables();

	// Get all the linked duplications
	$rows = mpd_get_the_persists();
	?>
	<div class="wrap">
	<h2><i class="fa fa-link" aria-hidden="true"></i> <?php _e('Linked Duplication Control', 'multisite-post-duplicator'); ?></h2>
		<div class="mpd-loading">
				<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
				<span class="sr-only"><?php _e('Loading', 'multisite-post-duplicator') ?>...</span>
		</div>	
		<table id="mpdLinkedTable" class="display" cellspacing="0" width="100%" style="display:none;">

	        <thead>
	            <tr>
	                <th><?php _e('Source Site', 'multisite-post-duplicator'); ?></th>
	                <th><?php _e('Destination Site', 'multisite-post-duplicator'); ?></th>
	                <th><?php _e('Source Post', 'multisite-post-duplicator'); ?></th>
	                <th><?php _e('Destination Post', 'multisite-post-duplicator'); ?></th>
	                <th><?php _e('Update Count', 'multisite-post-duplicator'); ?></th>
	                <th><?php _e('Post Type', 'multisite-post-duplicator'); ?></th>
	                <th><?php _e('User', 'multisite-post-duplicator'); ?></th>
	                <th><?php _e('Action', 'multisite-post-duplicator'); ?></th>
	                
	            </tr>
	        </thead>
	       
	        <tbody>

	        	<?php foreach($rows as $row):?>
		        	
		        	<?php
		        		//TODO!! Bring these variables in via mySQL query for increased performance.
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
			                	<a class="removeURL button-secondary" href="<?php echo $remove_url; ?>"><i class="fa fa-chain-broken" aria-hidden="true"></i>  <?php _e('Remove Link', 'multisite-post-duplicator');?></a>
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
 * Do the markup for a nice table showing all duplication performed by this plugin since 1.0 install
 *
 * @since 1.0
 * @return null
 */
function mdp_log_page(){

	// Load assests for nice datatable markup and functionality
	mpd_enqueue_datatables();
	
	// Get all mpd duplcations
	$rows = mpd_get_log();

	?>
	<div class="wrap">
		
		<h2><i class="fa fa-list-ul" aria-hidden="true"></i> <?php _e('Multisite Post Duplicator Log', 'multisite-post-duplicator');?></h2>

		<div class="mpd-loading">
				<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
				<span class="sr-only">L<?php _e('Loading', 'multisite-post-duplicator') ?>...</span>
		</div>	

		<table id="mpdLogTable" class="display" cellspacing="0" width="100%" style="display:none;">
	        
	        <thead>
	            <tr>
	                <th><?php _e('Source Site', 'multisite-post-duplicator') ?></th>
	                <th><?php _e('Destination Site', 'multisite-post-duplicator') ?></th>
	                <th><?php _e('Source Post', 'multisite-post-duplicator') ?></th>
	                <th><?php _e('Destination Post', 'multisite-post-duplicator') ?></th>
	                <th><?php _e('Post Type', 'multisite-post-duplicator') ?></th>
	                <th><?php _e('User', 'multisite-post-duplicator') ?></th>
	                <th><?php _e('Time', 'multisite-post-duplicator') ?></th>
	                <th><?php _e('Time Raw', 'multisite-post-duplicator') ?></th>
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

/**
 * Function Used to render checkbox to assign duplication link in metabox
 *
 * @since 1.0
 * @return null
 *
 */
function mpd_persist_checkbox(){

	$options = get_option( 'mdp_settings' );

	if((isset($options['allow_persist']) || !$options) && apply_filters('mpd_show_metabox_persist', true) ): ?>     
        
        <hr>
            
            <label class="selectit">
                
                <script>
                	jQuery(document).ready(function($) { 
                		accordionClick('.pl-link', '.pl-content', 'fast');
                     });
                </script>

                <ul>
                	<li class="cdl disabled">
                		<input type="checkbox" disabled="disabled" name="persist">Create Duplication Link? <i class="fa fa-info-circle pl-link" aria-hidden="true"></i>
                	</li>

                </ul>
				
                <p class="mpdtip pl-content" style="display:none"><?php _e('Checking this option will create a link between this post and the resulting copied post. After the link is created if you ever update this post in the future the changes will automatically be copied over to the linked posts also. If you want to delete the link you can do so <a href="'. esc_url( get_admin_url(null, 'options-general.php?page=multisite_post_duplicator&tab=persists') ) .'">here</a>', 'multisite-post-duplicator' ) ?></p>

            </label>

        <hr>

    <?php endif;

}

add_action('mpd_after_metabox_content', 'mpd_persist_checkbox', 9);
