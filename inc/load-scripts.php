<?php

function mdp_load_admin_styles(){

		if(get_current_screen()->id == 'tools_page_mpd'){

			wp_register_style( 'mdp-select2-styles', plugins_url( '../css/select2.min.css', __FILE__ ) , false, '1.0.0' );

			wp_register_style( 'mdp-styles', plugins_url( '../css/mpd.css', __FILE__ ) , false, '1.0.0' );

			wp_enqueue_style( 'mdp-styles');

			wp_enqueue_style( 'mdp-select2-styles');

			wp_enqueue_script( 'mdp-select2-core', plugins_url( '../js/select2.min.js', __FILE__ ), array( 'jquery' ), '1.0' );

			wp_enqueue_script( 'adminscripts', plugins_url( '../js/admin-scripts.js', __FILE__ ), array( 'mdp-select2-core' ), '1.0' );


		}
		
}

add_action('admin_enqueue_scripts','mdp_load_admin_styles');