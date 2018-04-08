<?php

/*
Plugin Name: 	Multisite Post Duplicator
Plugin URI: 	http://www.wpmaz.uk
Description:    Duplicate/Copy/Clone any individual page, post or custom post type from one site on your multisite network to another. 
Version: 		1.7.6
Author: 		Mario Jaconelli
Author URI:  	http://www.wpmaz.uk
Text Domain: 	multisite-post-duplicator
Domain Path: 	/lang
*/

define('MPD_PLUGIN', true);
define('MPD_SETTING_PAGE', 'mpd_sp');
define('MPD_SETTING_SECTION', 'mpd_sps');
define('MPD_PLUGIN_URL', plugin_dir_path(__FILE__ ) );

$fs = array(
	'inc/mpd-functions',
	'inc/load-scripts',
	'inc/postform_ui',
	'inc/admin-ui',
	'inc/settings-ui',
	'inc/core',
	'inc/persist',
	'inc/acf-functions',
	'inc/considerations',
	'inc/media',
	'addons/bulkaction-mpd-addon',
	'addons/restrictSites-mpd-addon',
	'addons/roleAccess-mpd-addon',
);

foreach ($fs as $f) {
	include($f.'.php');
}

/**
 * 
 * Setup Internationalisation
 * 
 * @since 1.0
 * 
 */
function mpd_load_textdomain() {

	load_plugin_textdomain( 'multisite-post-duplicator', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );
	
}

add_action('plugins_loaded', 'mpd_load_textdomain');

/**
 * On activation of this plugin create a custom table in the database to sort information on linked pages.
 *
 * @since 1.0
 * @return null
 *
 */
function mpd_create_persist_database(){

	global $wpdb;
	
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

/**
 * 
 * Set the default options in WordPress on activation of the plugin
 * 
 * @since 0.5
 * 
 */
function mdp_plugin_activate() {

	mpd_create_persist_database();

   $mdp_default_options = mdp_get_default_options();
   $sites          		= mpd_wp_get_sites();

   foreach ($sites as $site) {
   		
   		$siteid = $site->blog_id;

   		switch_to_blog($siteid);

   			$options = get_option( 'mdp_settings' );
   			
	   		if(!$options){

		   		$options = array();

		   		foreach ($mdp_default_options as $mdp_default_option => $option_value) {

		   			$options[$mdp_default_option] = $option_value;

		   		}

		   		update_option( 'mdp_settings', $options);

		   }else{
		   		
		   		//Add default option for existing users with new checkboxes
		   		$options = get_option( 'mdp_settings' );

		   		$options = apply_filters('mdp_activation_options', $options);

		   		update_option( 'mdp_settings', $options);

		   }

		 mpd_do_version_log();

		 restore_current_blog();
		 
   	}

   	do_action('mpd_extend_activation', $mdp_default_options, $sites);
	   
}

register_activation_hook( __FILE__, 'mdp_plugin_activate' );

/**
 * 
 * Static function to allow MDP default options to be referenced globally
 * 
 * @since 0.5
 * @return array
 * 
 */
function mdp_get_default_options(){

	$mdp_default_options = array(

		'mdp_default_prefix' 			=> __('Copy of', 'multisite-post-duplicator' ),
		'mdp_default_tags_copy' 		=> 'tags',
		'mdp_copy_post_categories'		=> 'category',
		'mdp_copy_post_taxonomies'		=> 'taxonomy',
		'mdp_default_featured_image' 	=> 'feat',
		'mdp_copy_content_images' 		=> 'content-image',
		'meta_box_show_radio' 			=> 'all',
		'mdp_ignore_custom_meta'		=> '',
		'mdp_allow_dev_info'			=> 'allow-dev',
		'mdp_default_status'			=> 'draft'
	);

	$mpd_post_types 		= get_post_types();
	$post_types_to_ignore  	= mpd_get_post_types_to_ignore();

	foreach ($mpd_post_types as $mpd_post_type){

		if( !in_array( $mpd_post_type, $post_types_to_ignore ) ){

			$mdp_default_options['meta_box_post_type_selector_' . $mpd_post_type ] = $mpd_post_type;

		}

	}

	$mdp_default_options = apply_filters('mdp_default_options', $mdp_default_options);

	return $mdp_default_options;

}

/**
 * 
 * Log current version in WordPress Options. This is to allow logical upgrade scripts depending on current version
 * in future updates
 * 
 * 
 * @since 0.5
 * @return string A string that informs mpd of the type of upgrate that has orrured on activation
 * 
 * Values can be either 'new_install', 'had_before' or 'change_of_version'
 * 
 */
function mpd_do_version_log(){
	
   $mainfile = MPD_PLUGIN_URL . 'mpd.php';

   $plugin_data = get_plugin_data($mainfile);

   if(get_option( 'mdp_version' )){

   		$type_of_activation = 'new_install';
   		$updating 			= update_option( 'mdp_version', $plugin_data['Version']);

   }else{

   		$type_of_activation = 'had_before';
   		$updating 			= update_option( 'mdp_version', $plugin_data['Version']);

   		if($updating){
   		
   			$type_of_activation = 'change_of_version';

   		}
   }

   return $type_of_activation;

}
/**
 * 
 * Add settings link to the plugin list.
 * 
 * @since 1.2.1
 * @return null
 * 
 */
function mpd_add_plugin_action_links( $links ) {
	return array_merge(
		array(
			'settings' => "<a href='" . esc_url( get_admin_url(null, 'options-general.php?page=multisite_post_duplicator') ) . "'>Settings</a>",
		),
		$links
	);
}

add_filter( 'network_admin_plugin_action_links_' . plugin_basename( __FILE__ ), 'mpd_add_plugin_action_links' );
add_filter( 'admin_plugin_action_links_' . plugin_basename( __FILE__ ), 'mpd_add_plugin_action_links' );
