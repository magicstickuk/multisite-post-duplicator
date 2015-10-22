<?php

/*
Plugin Name: 	Multisite Post Duplicator
Plugin URI: 	http://www.wpmaz.uk
Description:    Duplicate any individual page, post or custom post type from one site on your multisite network to another.
Version: 		0.5.1
Author: 		Mario Jaconelli
Author URI:  	http://www.wpmaz.uk
*/

include('inc/mpd-functions.php');
include('inc/load-scripts.php');
include('inc/postform_ui.php');
include('inc/admin-ui.php');
include('inc/settings-ui.php');
include('inc/core.php');

function mdp_plugin_activate() {

   $type_of_activation 	= mpd_do_version_log();
   $mdp_default_options = mdp_get_default_options();

   $args           = array('network_id' => null);
   $sites          = wp_get_sites($args);

   foreach ($sites as $site) {
   		
   		$siteid = $site['blog_id'];

   		switch_to_blog($siteid);

	   		if(!$options  = get_option( 'mdp_settings' )){

		   		$options = array();

		   		foreach ($mdp_default_options as $mdp_default_option => $option_value) {

		   			$options[$mdp_default_option] = $option_value;

		   		}

		   		update_option( 'mdp_settings', $options);

		   }else{
		   	
		   		//Add default option for exsisting users with new checkboxes
		   		$options = get_option( 'mdp_settings' );
		   		$options['mdp_copy_content_images'] 		= 'content-image';
		   		$options['mdp_default_tags_copy'] 			= 'tags';
		   		$options['mdp_default_featured_image']		= 'feat';

		   		update_option( 'mdp_settings', $options);

		   }

		 restore_current_blog();

   	}
	   
}

register_activation_hook( __FILE__, 'mdp_plugin_activate' );

function mdp_get_default_options(){

	$mdp_default_options = array(

		'mdp_default_prefix' 			=> __('Copy of'),
		'mdp_default_tags_copy' 		=> 'tags',
		'mdp_default_featured_image' 	=> 'feat',
		'mdp_copy_content_images' 		=> 'content-image',
		'meta_box_show_radio' 			=> 'all',

	);

	$mpd_post_types 		= get_post_types();
	$post_types_to_ignore  	= mpd_get_post_types_to_ignore();

	foreach ($mpd_post_types as $mpd_post_type){

		if( !in_array( $mpd_post_type, $post_types_to_ignore ) ){

			$mdp_default_options['meta_box_post_type_selector_' . $mpd_post_type ] = $mpd_post_type;

		}

	}

	return $mdp_default_options;

}

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