<?php
if ( is_multisite() ) {
	add_action( 'admin_menu', 'mdp_add_admin_menu' );
	add_action( 'admin_init', 'mdp_settings_init' );
}

function mdp_add_admin_menu(  ) { 

	add_submenu_page( 'options-general.php', __('Multisite Post Duplicator Settings','mpd'), __('Multisite Post Duplicator Settings', 'mpd'), 'manage_options', 'multisite_post_duplicator', 'mdp_options_page' );

}

function mdp_settings_init(  ) { 

	register_setting( 'mdp_plugin_setting_page', 'mdp_settings' );

	add_settings_section(
		'mdp_mdp_plugin_setting_page_section', 
		'<h2>'. __( 'Multisite Post Duplicator Settings Page' . '</h2>', 'mpd' ), 
		'mdp_settings_section_callback', 
		'mdp_plugin_setting_page'
	);

	add_settings_field( 
		'meta_box_show_radio', 
		__( 'What Post Types you want to show the MPD Meta Box?', 'mpd' ), 
		'meta_box_show_radio_render', 
		'mdp_plugin_setting_page', 
		'mdp_mdp_plugin_setting_page_section' 
	);

	$mpd_post_types 		= get_post_types();
	$loopcount 				= 1;
	$post_types_to_ignore 	= array('revision', 'nav_menu_item');

	foreach ($mpd_post_types as $mpd_post_type){

		if( !in_array( $mpd_post_type, $post_types_to_ignore ) ){

			add_settings_field( 
				'meta_box_post_type_selector_' . $mpd_post_type, 
				$loopcount == 1 ? __("Select post types to show the MPD Meta Box on", 'mpd') : "" , 
				'meta_box_post_type_selector_render', 
				'mdp_plugin_setting_page', 
				'mdp_mdp_plugin_setting_page_section',
				array(
					'mdpposttype' => $mpd_post_type
				)
			);

			$loopcount++;

		}

	}
	
	add_settings_field( 
		'mdp_default_prefix', 
		__( 'Default Prefix', 'mdp' ), 
		'mdp_default_prefix_render', 
		'mdp_plugin_setting_page', 
		'mdp_mdp_plugin_setting_page_section' 
	);

	add_settings_field( 
		'mdp_default_tags_copy', 
		__( 'Copy post tags when duplicating?', 'mdp' ), 
		'mdp_default_tags_copy_render', 
		'mdp_plugin_setting_page', 
		'mdp_mdp_plugin_setting_page_section' 
	);

	add_settings_field( 
		'mdp_default_featured_image', 
		__( 'Copy featured image when duplicating?', 'mdp' ), 
		'mdp_default_feat_image_copy_render', 
		'mdp_plugin_setting_page', 
		'mdp_mdp_plugin_setting_page_section' 
	);

}

function meta_box_show_radio_render(){

	if($options = get_option( 'mdp_settings' )){
		$mdp_radio_label_value = $options['meta_box_show_radio'];
	}else{
		$mdp_radio_label_value = 'all';
	};

	?>
	<div id="mpd_radio_choice_wrap">

		<input type="radio" class="mdp_radio" name='mdp_settings[meta_box_show_radio]' id="meta_box_show_choice_all" <?php checked( $mdp_radio_label_value, 'all'); ?> value="all">

		<label class="mdp_radio_label" for="radio-choice-1"><?php _e('All post types', 'mpd') ?></label>
	    
		<input type="radio" class="mdp_radio" name='mdp_settings[meta_box_show_radio]' id="meta_box_show_choice_some" <?php checked( $mdp_radio_label_value, 'some'); ?> value="some">

	    <label class="mdp_radio_label" for="radio-choice-2"><?php _e('Some post types','mpd') ?></label>

	    <input type="radio" class="mdp_radio" name='mdp_settings[meta_box_show_radio]' id="meta_box_show_choice_none" <?php checked( $mdp_radio_label_value, 'none'); ?> value="none">

	    <label class="mdp_radio_label" for="radio-choice-2"><?php _e('No post types','mpd') ?></label>

    </div>
	<?php
}


function meta_box_post_type_selector_render($args) { 

	$options = get_option( 'mdp_settings' );
	$mpd_post_type = $args['mdpposttype'];
	$the_name = "mdp_settings[meta_box_post_type_selector_" . $mpd_post_type . "]";
	$the_selector = 'meta_box_post_type_selector_' . $mpd_post_type;

	?>

		<input type='checkbox' class="posttypecb" name='<?php echo $the_name; ?>' <?php mpd_checked_lookup($options, $the_selector, $mpd_post_type) ;?> value='<?php echo $mpd_post_type; ?>'> <?php echo $mpd_post_type; ?> <br >

	<?php

}

function mdp_default_prefix_render(  ) { 

	$options = get_option( 'mdp_settings' );
	?>
	<input type='text' name='mdp_settings[mdp_default_prefix]' value='<?php echo $options ? $options['mdp_default_prefix'] : "Copy of"; ?>'>
	<?php

}

function mdp_default_tags_copy_render(  ) { 

	$options = get_option( 'mdp_settings' );

	?>
	<input type='checkbox' name='mdp_settings[mdp_default_tags_copy]' <?php mpd_checked_lookup($options, 'mdp_default_tags_copy', 'tags') ;?> value='tags'> 

	<?php

}

function mdp_default_feat_image_copy_render(  ) { 

	$options = get_option( 'mdp_settings' );

	?>
	<input type='checkbox' name='mdp_settings[mdp_default_featured_image]' <?php mpd_checked_lookup($options, 'mdp_default_featured_image', 'feat') ;?> value='feat'>

	<?php

}

function mdp_settings_section_callback(  ) { 

	_e( 'Here you can change the default settings for Multisite Post Duplicator. Note that any changes to these settings are specific to this site only.', 'mdp' );

}


function mdp_options_page(  ) { 

	?>
	<form action='options.php' method='post'>
		
		<?php
		settings_fields( 'mdp_plugin_setting_page' );
		do_settings_sections( 'mdp_plugin_setting_page' );
		submit_button();
		?>
		
	</form>
	<?php

}

?>