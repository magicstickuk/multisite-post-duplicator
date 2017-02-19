<?php
/**
 * 
 * Load scripts and CSS to be used in this plugin
 * 
 */

/**
 * 
 * Enqueue all the required files for this plugin to display/run
 * 
 * @since 0.1
 * @author Mario Jaconelli <mariojaconelli@gmail.com>
 * 
 */
function mdp_load_admin_styles(){

	$screenid = get_current_screen()->id;

	if($screenid == 'tools_page_multisite-post-duplicator'){

		wp_register_style(
			'mdp-select2-styles',
			plugins_url( '../css/select2.min.css', __FILE__ ),
			false
		);

		wp_enqueue_style( 'mdp-select2-styles');

		wp_enqueue_script(
			'mdp-select2-core',
			plugins_url( '../js/select2.min.js', __FILE__ ),
			array( 'jquery' )
		);

		wp_enqueue_script(
			'admin-scripts',
			plugins_url( '../js/admin-scripts.js', __FILE__ ),
			array( 'mdp-select2-core' )
		);

		wp_localize_script('admin-scripts', 'mpd_admin_scripts_vars', array(
				'select_post_type' => __('Select a post post type to duplicate', 'multisite-post-duplicator'),
				'select_post' => __('Select a post to duplicate', 'multisite-post-duplicator'),
				'select_site' => __('Select a site to duplicate to', 'multisite-post-duplicator'),
				'select_user' => __('Select a user to atribute this to', 'multisite-post-duplicator'),
			)
		);

	}

	if($screenid == 'tools_page_multisite-post-duplicator' || $screenid == 'settings_page_multisite_post_duplicator'){

		wp_register_style(
			'mdp-styles',
			plugins_url( '../css/mpd.css', __FILE__ ),
			false
		);

		wp_enqueue_style( 'mdp-styles');

	}
		
	if($screenid == 'settings_page_multisite_post_duplicator'){

		wp_enqueue_script(
			'mdp-admin-seetings-scripts',
			plugins_url( '../js/admin-settings.js', __FILE__ ),
			array( 'jquery' )
		);

		wp_register_style(
			'mdp-select2-styles',
			plugins_url( '../css/select2.min.css', __FILE__ ),
			false
		);

		wp_enqueue_style( 'mdp-select2-styles');

		wp_register_style(
			'mdp-toggle-styles',
			plugins_url( '../css/ti-ta-toggle.css', __FILE__ ),
			false
		);

		wp_enqueue_style( 'mdp-toggle-styles');

		wp_enqueue_script(
			'mdp-select2-core',
			plugins_url( '../js/select2.min.js', __FILE__ ),
			array( 'jquery' )
		);

	}

	wp_enqueue_script(
		'mdp-admin-settings-scripts',
		plugins_url( '../js/admin.js', __FILE__ ),
		array( 'jquery' )
		
	);

	global $post;

	$post_id = $post ? $post->ID : 0;
	
	wp_localize_script('mdp-admin-settings-scripts', 'mpd_admin_vars', array(
		'post_and_update' => __('Post & Update Linked Posts', 'multisite-post-duplicator'),
		'post_and_dup' => __('Post & Duplicate', 'multisite-post-duplicator'),
		'dup_and_update' => __('& Duplicate & Update Linked Posts', 'multisite-post-duplicator'),
		'post_id' => $post_id
		)
	);

	wp_register_style(
		'mdp-font-awesome',
		plugins_url( '../css/font-awesome.min.css', __FILE__ )
		, false
	);

	wp_enqueue_style( 'mdp-font-awesome');

	wp_register_style(
			'mdp-metabox-styles',
			plugins_url( '../css/mpd-mb.css', __FILE__ ),
			false
		);

	wp_enqueue_style( 'mdp-metabox-styles');
		
}

add_action('admin_enqueue_scripts','mdp_load_admin_styles');