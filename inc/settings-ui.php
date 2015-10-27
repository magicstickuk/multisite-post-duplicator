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
	$post_types_to_ignore 	= mpd_get_post_types_to_ignore();

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

	add_settings_field( 
		'mdp_copy_content_images', 
		__( 'Copy post content images to destination media library?', 'mdp' ), 
		'mdp_copy_content_image_render', 
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

		<div class="mdp-inputcontainer">
			<input type="radio" class="mdp_radio" name='mdp_settings[meta_box_show_radio]' id="meta_box_show_choice_all" <?php checked( $mdp_radio_label_value, 'all'); ?> value="all">
		
			<label class="mdp_radio_label" for="radio-choice-1"><?php _e('All post types', 'mpd') ?></label>
			    
			<input type="radio" class="mdp_radio" name='mdp_settings[meta_box_show_radio]' id="meta_box_show_choice_some" <?php checked( $mdp_radio_label_value, 'some'); ?> value="some">
		
			<label class="mdp_radio_label" for="radio-choice-2"><?php _e('Some post types','mpd') ?></label>
		
			<input type="radio" class="mdp_radio" name='mdp_settings[meta_box_show_radio]' id="meta_box_show_choice_none" <?php checked( $mdp_radio_label_value, 'none'); ?> value="none">
		
			<label class="mdp_radio_label" for="radio-choice-2"><?php _e('No post types','mpd') ?></label>
	    </div>

	    <p class="mpdtip"><?php _e('The MDP meta box is shown on the right of your post/page/custom post type. You can control where you would like this meta box to appear using the selection above. If you select "Some post types" you will get a list of all the post types below to toggle their display.') ?></p>

    </div>
	<?php
}


function meta_box_post_type_selector_render($args) { 

	$options 		= get_option( 'mdp_settings' );
	$mpd_post_type 	= $args['mdpposttype'];
	$the_name 		= "mdp_settings[meta_box_post_type_selector_" . $mpd_post_type . "]";
	$the_selector 	= 'meta_box_post_type_selector_' . $mpd_post_type;

	?>

		<input type='checkbox' class="posttypecb" name='<?php echo $the_name; ?>' <?php mpd_checked_lookup($options, $the_selector, $mpd_post_type) ;?> value='<?php echo $mpd_post_type; ?>'> <?php echo $mpd_post_type; ?> <br >

	<?php

}

function mdp_default_prefix_render(  ) { 

	$options = get_option( 'mdp_settings' );
	?>
	<input type='text' name='mdp_settings[mdp_default_prefix]' value='<?php echo $options ? $options['mdp_default_prefix'] : "Copy of"; ?>'>

	<p class="mpdtip"><?php _e('Change the default prefix for your duplication across the network.')?></p>
	<?php

}

function mdp_default_tags_copy_render(  ) { 

	$options = get_option( 'mdp_settings' );

	?>
	<input type='checkbox' name='mdp_settings[mdp_default_tags_copy]' <?php mpd_checked_lookup($options, 'mdp_default_tags_copy', 'tags') ;?> value='tags'> 

	<p class="mpdtip"><?php _e('This plugin will automatically copy the tags associated with the post. You can turn off this activity by unchecking the box.')?></p>

	<?php

}

function mdp_default_feat_image_copy_render(  ) { 

	$options = get_option( 'mdp_settings' );

	?>
	<input type='checkbox' name='mdp_settings[mdp_default_featured_image]' <?php mpd_checked_lookup($options, 'mdp_default_featured_image', 'feat') ;?> value='feat'>

	<p class="mpdtip"><?php _e('This plugin will automatically copy any featured image associated with the post.You can turn off this activity by unchecking the box.')?></p>
	<?php

}

function mdp_copy_content_image_render(  ) { 

	$options = get_option( 'mdp_settings' );

	?>
	<input type='checkbox' name='mdp_settings[mdp_copy_content_images]' <?php mpd_checked_lookup($options, 'mdp_copy_content_images', 'content-image') ;?> value='content-image'>

	<p class="mpdtip"><?php _e('On duplication this plugin will look at the content within the main post content field and try to identify any images that have been added from your media library. If it finds any it will duplicate the image and all its meta data to your destinations site`s media library for exclusive use there. It will also change the urls in the duplicated post to reference the new media file. You can turn off this activity by unchecking the box')?></p>
	<?php

}

function mdp_settings_section_callback(  ) { 

	_e( 'Here you can change the default settings for Multisite Post Duplicator. Note that these settings are used for every site in your network.', 'mdp' );

}

add_action( 'update_option_mdp_settings', 'mpd_globalise_settings', 10, 2 );

function mpd_globalise_settings( $old_value, $new_value ){
    
    $options 	= get_option( 'mdp_settings' );
    $args 		= array('network_id' => null);
	$sites 		= wp_get_sites($args);

	foreach ($sites as $site) {

		switch_to_blog($site['blog_id']);

			update_option( 'mdp_settings', $options);

		restore_current_blog();

	}
    	
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