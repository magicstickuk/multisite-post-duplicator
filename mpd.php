<?php

/*
Plugin Name: 	Multisite Post Duplicator
Plugin URI: 	http://www.wpmaz.uk
Description:    Duplicate any individual page, post or custom post type from one site on your multisite network to another.
Version: 		0.9.4
Author: 		Mario Jaconelli
Author URI:  	http://www.wpmaz.uk
*/

define('MPD_PLUGIN', true);
define('MPD_SETTING_PAGE', 'mpd_sp');
define('MPD_SETTING_SECTION', 'mpd_sps');
define('MPD_DOMAIN', 'mpd');

include('inc/mpd-functions.php');
include('inc/load-scripts.php');
include('inc/postform_ui.php');
include('inc/admin-ui.php');
include('inc/settings-ui.php');
include('inc/core.php');
include('inc/client-log.php');
include('addons/bulkaction-mpd-addon.php');
include('addons/restrictSites-mpd-addon.php');
include('addons/roleAccess-mpd-addon.php');

/**
 * 
 * Set the default options in WordPress on activation of the plugin
 * 
 * @since 0.5
 * 
 */
function mdp_plugin_activate() {

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

		'mdp_default_prefix' 			=> __('Copy of', MPD_DOMAIN ),
		'mdp_default_tags_copy' 		=> 'tags',
		'mdp_copy_post_categories'		=> 'category',
		'mdp_copy_post_taxonomies'		=> 'taxonomy',
		'mdp_default_featured_image' 	=> 'feat',
		'mdp_copy_content_images' 		=> 'content-image',
		'meta_box_show_radio' 			=> 'all',
		'mdp_ignore_custom_meta'		=> '',
		'mdp_allow_dev_info'			=> 'allow-dev'

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

   $plugin_data = get_plugin_data(__FILE__);

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