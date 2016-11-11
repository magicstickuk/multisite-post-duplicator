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

	if($screenid == 'tools_page_mpd'){

		wp_register_style(
			'mdp-select2-styles',
			plugins_url( '../css/select2.min.css', __FILE__ ),
			false,
			'1.0.0'
		);

		wp_enqueue_style( 'mdp-select2-styles');

		wp_enqueue_script(
			'mdp-select2-core',
			plugins_url( '../js/select2.min.js', __FILE__ ),
			array( 'jquery' ),
			'1.0'
		);

		wp_enqueue_script(
			'admin-scripts',
			plugins_url( '../js/admin-scripts.js', __FILE__ ),
			array( 'mdp-select2-core' ),
			'1.0'
		);

		wp_localize_script('admin-scripts', 'mpd_admin_scripts_vars', array(
				'select_post_type' => __('Select a post post type to duplicate', 'mpd'),
				'select_post' => __('Select a post to duplicate', 'mpd'),
				'select_site' => __('Select a site to duplicate to', 'mpd'),
				'select_user' => __('Select a user to atribute this to', 'mpd'),
			)
		);

	}

	if($screenid == 'tools_page_mpd' || $screenid == 'settings_page_multisite_post_duplicator'){

		wp_register_style(
			'mdp-styles',
			plugins_url( '../css/mpd.css', __FILE__ ),
			false,
			'1.0.0'
		);

		wp_enqueue_style( 'mdp-styles');

	}
		
	if($screenid == 'settings_page_multisite_post_duplicator'){

		wp_enqueue_script(
			'mdp-admin-seetings-scripts',
			plugins_url( '../js/admin-settings.js', __FILE__ ),
			array( 'jquery' ),
			'1.0'
		);

		wp_register_style(
			'mdp-select2-styles',
			plugins_url( '../css/select2.min.css', __FILE__ ),
			false,'1.0.0'
		);

		wp_enqueue_style( 'mdp-select2-styles');

		wp_enqueue_script(
			'mdp-select2-core',
			plugins_url( '../js/select2.min.js', __FILE__ ),
			array( 'jquery' ),'1.0'
		);

	}

	wp_enqueue_script(
		'mdp-admin-settings-scripts',
		plugins_url( '../js/admin.js', __FILE__ ),
		array( 'jquery' ),
		'1.0'
	);

	wp_localize_script('mdp-admin-settings-scripts', 'mpd_admin_vars', array(
		'post_and_update' => __('Post & Update Linked Posts', 'mpd'),
		'post_and_dup' => __('Post & Duplicate', 'mpd'),
		'dup_and_update' => __('& Duplicate & Update Linked Posts', 'mpd'),
		)
	);

	wp_register_style(
		'mdp-font-awesome',
		plugins_url( '../css/font-awesome.min.css', __FILE__ )
		, false,
		'1.0.0'
	);

	wp_enqueue_style( 'mdp-font-awesome');

	wp_register_style(
			'mdp-metabox-styles',
			plugins_url( '../css/mpd-mb.css', __FILE__ ),
			false,
			'1.0.0'
		);

	wp_enqueue_style( 'mdp-metabox-styles');
		
}

add_action('admin_enqueue_scripts','mdp_load_admin_styles');