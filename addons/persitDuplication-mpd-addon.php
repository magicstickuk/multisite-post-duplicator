<?php
/**
 * MPD Addon: Persist Duplication
 * 
 * This addon allows for a link to be created between the source and destination site
 * so if one if the source is ever updated the it will update the original destination also
 * 
 * @ignore
 * @since 1.0
 * @author Mario Jaconelli <mariojaconelli@gmail.com>
 * 
 */

/**
 * @ignore
 */
function persist_addon_mpd_settings(){

	mpd_settings_field('persist_option_setting', __( 'Restrict MPD to certain sites', MPD_DOMAIN ), 'persist_option_setting_render');    
     
}

add_action( 'mdp_end_plugin_setting_page', 'persist_addon_mpd_settings');

/**
 * @ignore
 */
function persist_option_setting_render(){
  
  
}

// Need a post destination and source link table
// Possible columns
// duplication id (p key auto-increment) int, source_id int, destination_id int, source_post_id int, destination_post_id int, persist_active bool, persist_action_count int

function mpd_log_duplication($createdPostObject, $mpd_process_info){
	
	global $wpdb;
	
	$result = $wpdb->insert( 
		$wpdb->base_prefix . "mpd_log", 
		array( 
			'source_id' 			=> get_current_blog_id(), 
			'destination_id' 		=> $mpd_process_info['destination_id'],
			'source_post_id'		=> $mpd_process_info['source_id'],
			'destination_post_id'	=> $createdPostObject['id'],
			'persist_action_count'	=> 0,
			'dup_user_id'			=> get_current_user_id()
		), 
		array( 
			'%d','%d','%d','%d','%d','%d'
		) 
	);
	
	return $result;
}

add_action('mpd_log', 'mpd_log_duplication', 10, 2);

function testingtesting(){
	$args = array(

			'source_id'			=> 1,
			'destination_id'	=> 2,
			'source_post_id'	=> 302,
			'destination_post_id'=> 148

			); 
	
	$result = mpd_add_persit($args);
	var_dump($result);
	return $result;
}
add_action('admin_notices', 'testingtesting');

function mpd_is_there_a_persist($args){
	
	global $wpdb;
	
	$tableName = $wpdb->base_prefix . "mpd_log";

	$query = "SELECT persist_active
				FROM $tableName
				WHERE 
				source_id = ". $args['source_id'] . " 
				AND destination_id = ". $args['destination_id']. "
				AND source_post_id = ". $args['source_post_id']. "
				AND destination_post_id = ". $args['destination_post_id'];
	
	$result = $wpdb->get_var($query);
	
	if($result != null && $result != 0){
		return true;
	}else{
		return false;
	}

}
function mpd_add_persit($args){
	
	global $wpdb;
	
	$table = $wpdb->base_prefix . "mpd_log";
	$data = array(
		'persist_active' => 1
	);
	$where = array( 
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

function mpd_do_persit(){
	
	
}

function mpd_log_persist_save(){
	
}

function mpd_get_persist_status(){
	
	
}

