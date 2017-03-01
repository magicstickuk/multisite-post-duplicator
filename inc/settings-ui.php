<?php
/**
 * 
 * This file controls arte generation of the MPD Settings page
 * @since 0.4
 * @author Mario Jaconelli <mariojaconelli@gmail.com>
 * 
 */
if ( is_multisite() ) {

	add_action( 'admin_menu', 'mdp_add_admin_menu' );
	
	add_action( 'admin_init', 'mdp_settings_init' );

}

/**
 * 
 * Add MDP Settings navigation to WordPress navigation
 * 
 * @since 0.4
 * @return null
 * 
 */
function mdp_add_admin_menu(  ) {

	$options 		= get_option( 'mdp_settings' );
	$settingsLogic 	= current_user_can( 'manage_options' );
	$settingsLogic 	= apply_filters( 'mpd_show_settings_page', $settingsLogic );

	if($settingsLogic){

		add_submenu_page( 'options-general.php', __('Multisite Post Duplicator Settings', 'multisite-post-duplicator' ), __('Multisite Post Duplicator Settings', 'multisite-post-duplicator'), 'manage_options', 'multisite_post_duplicator', 'mdp_options_page' );
		
	}

}


/**
 * 
 * Register the settings for MPD
 * 
 * @since 0.4
 * @return null
 * 
 */
function mdp_settings_init(  ) { 

	register_setting( MPD_SETTING_PAGE, 'mdp_settings' );

	do_action( 'mdp_start_plugin_setting_page' );

	add_settings_section(
		MPD_SETTING_SECTION, 
		false, 
		'mdp_settings_section_callback', 
		MPD_SETTING_PAGE
	);

	mpd_settings_field('meta_box_show_radio', '<i class="fa fa-file-text-o" aria-hidden="true"></i> ' . __( 'What Post Types you want to show the MPD Meta Box?', 'multisite-post-duplicator' ), 'meta_box_show_radio_render');

	$mpd_post_types 		= get_post_types();
	$loopcount 				= 1;
	$post_types_to_ignore 	= mpd_get_post_types_to_ignore();

	foreach ($mpd_post_types as $mpd_post_type){

		if( !in_array( $mpd_post_type, $post_types_to_ignore ) ){

			mpd_settings_field(

					'meta_box_post_type_selector_' . $mpd_post_type,
					$loopcount == 1 ? '<i class="fa fa-file-text-o" aria-hidden="true"></i> ' . __("Select post types to show the MPD Meta Box on", 'multisite-post-duplicator' ) : "",
					'meta_box_post_type_selector_render',
					array(
						'mdpposttype' => $mpd_post_type
					)

			);
			
			$loopcount++;

		}

	}
	
	mpd_settings_field(
		'mdp_default_prefix',
		'<i class="fa fa-pencil" aria-hidden="true"></i> ' . __( 'Default Prefix', 'multisite-post-duplicator' ),
		'mdp_default_prefix_render'
	);
	mpd_settings_field(
		'mdp_default_status',
		'<i class="fa fa-eye-slash" aria-hidden="true"></i> ' . __( 'Default Post Status', 'multisite-post-duplicator' ),
		'mdp_default_status_render'
	);
	mpd_settings_field(
		'mdp_default_tags_copy',
		'<i class="fa fa-tags" aria-hidden="true"></i> ' .__( 'Copy post tags when duplicating?', 'multisite-post-duplicator' ),
		'mdp_default_tags_copy_render');

	mpd_settings_field(
		'mdp_copy_post_categories',

		'<i class="fa fa-files-o" aria-hidden="true"></i> ' . __( 'Copy post categories?', 'multisite-post-duplicator' ),
		'mdp_copy_post_categories_render'
	);

	mpd_settings_field('mdp_copy_post_taxonomies',
		'<i class="fa fa-tag" aria-hidden="true"></i> ' . __( 'Copy post taxonomies?', 'multisite-post-duplicator' ),
		'mdp_copy_post_taxonomies_render'
	);

	mpd_settings_field(
		'mdp_default_featured_image',
		'<i class="fa fa-picture-o" aria-hidden="true"></i> ' . __( 'Copy featured image when duplicating?', 'multisite-post-duplicator' ),
		'mdp_default_feat_image_copy_render'
	);
	mpd_settings_field(
		'mdp_copy_content_images',
		'<i class="fa fa-camera" aria-hidden="true"></i> ' . __( 'Copy post content images to destination media library?', 'multisite-post-duplicator' ),
		'mdp_copy_content_image_render'
	);
	mpd_settings_field(
		'mdp_retain_published_date',
		'<i class="fa fa-calendar-o" aria-hidden="true"></i> ' . __( 'Retain Published Date from Source?', 'multisite-post-duplicator' ),
		'mdp_retain_published_date_render'
	);

	do_action( 'mdp_end_plugin_setting_page' );

	mpd_settings_field('mdp_ignore_custom_meta',
		'<i class="fa fa-fast-forward" aria-hidden="true"></i> ' . __( 'Post Meta to ignore?', 'multisite-post-duplicator' ),
		'mdp_ignore_custom_meta_render'
	);

}
/**
 * 
 * Create the UI for the Post Type Selector in Settings
 * 
 * @since 0.4
 * @return null
 * 
 */
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
		
			<label class="mdp_radio_label" for="radio-choice-1"><?php _e('All post types', 'multisite-post-duplicator' ) ?></label>
			    
			<input type="radio" class="mdp_radio" name='mdp_settings[meta_box_show_radio]' id="meta_box_show_choice_some" <?php checked( $mdp_radio_label_value, 'some'); ?> value="some">
		
			<label class="mdp_radio_label" for="radio-choice-2"><?php _e('Some post types', 'multisite-post-duplicator' ) ?></label>
		
			<input type="radio" class="mdp_radio" name='mdp_settings[meta_box_show_radio]' id="meta_box_show_choice_none" <?php checked( $mdp_radio_label_value, 'none'); ?> value="none">
		
			<label class="mdp_radio_label" for="radio-choice-3"><?php _e('No post types', 'multisite-post-duplicator') ?></label>

	    <?php mpd_information_icon('The MDP meta box is shown on the right of your post/page/custom post type. You can control where you would like this meta box to appear using the selection above. If you select "Some post types" you will get a list of all the post types below to toggle their display.'); ?>
	
		</div>

    </div>
	<?php
}

/**
 * 
 * Create the UI for the Post Type checkboxes
 * 
 * @since 0.4
 * @param array $args The post type checkbox to render. Probably generated in mdp_settings_init()
 * 
 * @return null
 * 
 */
function meta_box_post_type_selector_render($args) { 

	$options 		= get_option( 'mdp_settings' );
	$mpd_post_type 	= $args['mdpposttype'];
	$the_name 		= "mdp_settings[meta_box_post_type_selector_" . $mpd_post_type . "]";
	$the_selector 	= 'meta_box_post_type_selector_' . $mpd_post_type;

	?>

	<input type='checkbox' class="posttypecb" name='<?php echo $the_name; ?>' <?php mpd_checked_lookup($options, $the_selector, $mpd_post_type) ;?> value='<?php echo $mpd_post_type; ?>'> <?php echo $mpd_post_type; ?> <br >

	<?php

}

/**
 * 
 * Create the UI for the Prefix Setting
 * 
 * @since 0.4
 * @return null
 * 
 */
function mdp_default_prefix_render(  ) { 

	$options = get_option( 'mdp_settings' );
	
	?>
	
	<input type='text' name='mdp_settings[mdp_default_prefix]' value='<?php echo mpd_get_prefix(); ?>'>

	<?php mpd_information_icon('Change the default prefix for your duplication across the network.'); ?>
	
	<?php

}
/**
 * 
 * Create the UI for the Status Setting
 * 
 * @since 1.2.1
 * @return null
 * 
 */
function mdp_default_status_render(  ) { 

	$options = get_option( 'mdp_settings' );
    $post_statuses = mpd_get_post_statuses();
    $status = mpd_get_status();

	?>

	<select name="mdp_settings[mdp_default_status]">

			<?php foreach ($post_statuses as $post_status_key => $post_status_value):?>

			    <option value="<?php echo $post_status_key; ?>" <?php echo $status == $post_status_key ? 'selected="selected"' : ''; ?>>

			    	<?php echo  $post_status_value;?>

			    </option>
			
			<?php endforeach; ?>

	</select>

	

	<?php mpd_information_icon('Change the default status for your duplication across the network. This will be applied to batch processing'); ?>
	
	<?php

}
/**
 * 
 * Create the UI for the Keys to Ignore Setting
 * 
 * @since 0.9
 * @return null
 * 
 */
function mdp_ignore_custom_meta_render(  ) { 

	$options = get_option( 'mdp_settings' ); ?>

			<input id="mdp-ignore-custom-meta" type='text' autocapitalize="none" autocorrect="none" name='mdp_settings[mdp_ignore_custom_meta]' value='<?php echo mpd_get_ignore_keys(); ?>'> 
		
				<?php mpd_information_icon('A comma delimited list of post meta keys you wish to ignore during the duplication process. <em>i.e (without quotes) \'my_custom_meta_key, post_facebook_share_count\'</em></br></br>WARNING: Only edit this option if you are sure what you are doing.'); ?>
			
		
	
	<?php

}
/**
 * 
 * Create the UI for the Tag Copy Selection Setting
 * 
 * @since 0.4
 * @return null
 * 
 */
function mdp_default_tags_copy_render(  ) { 

	$options = get_option( 'mdp_settings' ); ?>
	
	<div class="checkbox checkbox-slider--b-flat">
		
		<label>

			<input type='checkbox' name='mdp_settings[mdp_default_tags_copy]' <?php mpd_checked_lookup($options, 'mdp_default_tags_copy', 'tags') ;?> value='tags'> 
			<span>
				<?php mpd_information_icon('This plugin will automatically copy the tags associated with the post. You can turn off this activity by unchecking the box.'); ?>
			</span>
		
		</label>
	
	</div>
	<?php

}

/**
 * 
 * Create the UI for the Category Copy Selection Setting
 * 
 * @since 0.8
 * @return null
 * 
 */
function mdp_copy_post_categories_render(  ) { 

	$options = get_option( 'mdp_settings' ); ?>
	
	<div class="checkbox checkbox-slider--b-flat">
		
		<label>
			<input type='checkbox' name='mdp_settings[mdp_copy_post_categories]' <?php mpd_checked_lookup($options, 'mdp_copy_post_categories', 'category') ;?> value='category'>
			<span>
			<?php mpd_information_icon('This plugin will automatically copy the categories associated with the post. If the category doesn\'t exist in the destination site the category will be created for you. You can turn off this activity by unchecking the box.'); ?>
			</span>
		</label>

	</div>
	<?php

}
/**
 * 
 * Create the UI for the Taxonomy Copy Selection Setting
 * 
 * @since 0.8
 * @return null
 * 
 */
function mdp_copy_post_taxonomies_render(  ) { 

	$options = get_option( 'mdp_settings' ); ?>
	
	<div class="checkbox checkbox-slider--b-flat">
		
		<label>
			<input type='checkbox' name='mdp_settings[mdp_copy_post_taxonomies]' <?php mpd_checked_lookup($options, 'mdp_copy_post_taxonomies', 'taxonomy') ;?> value='taxonomy'>
			<span>
				<?php mpd_information_icon('This plugin will automatically copy the taxonomy TERMS associated with the post. If the taxonomy TERMS don\'t exist in the destination site the will be created for you. Note: This functionality assumes you have the taxonomies in your source site also registered in your destination site. You can turn off this activity by unchecking the box.'); ?>
			</span>
		</label>
	</div>
	<?php

}

/**
 * 
 * Create the UI for the Featured Image Copy Selection Setting
 * 
 * @since 0.4
 * @return null
 * 
 */
function mdp_default_feat_image_copy_render(  ) { 

	$options = get_option( 'mdp_settings' ); ?>

	<div class="checkbox checkbox-slider--b-flat">
		
		<label>

			<input type='checkbox' name='mdp_settings[mdp_default_featured_image]' <?php mpd_checked_lookup($options, 'mdp_default_featured_image', 'feat') ;?> value='feat'>
			<span>
	
				<?php mpd_information_icon('This plugin will automatically copy any featured image associated with the post. You can turn off this activity by unchecking the box.'); ?>
				
			</span>
		
		</label>
	</div>
	
	<?php

}

/**
 * 
 * Create the UI for the Inline Image Copy Selection setting
 * 
 * @since 0.4
 * @return null
 * 
 */
function mdp_copy_content_image_render(  ) { 

	$options = get_option( 'mdp_settings' );
	?>
	<div class="checkbox checkbox-slider--b-flat">
		<label>
			<input type='checkbox' name='mdp_settings[mdp_copy_content_images]' <?php mpd_checked_lookup($options, 'mdp_copy_content_images', 'content-image') ;?> value='content-image'>
			<span>
				<?php mpd_information_icon('On duplication this plugin will look at the content within the main post content field and try to identify any images that have been added from your media library. If it finds any it will duplicate the image and all its meta data to your destinations site`s media library for exclusive use there. It will also change the urls in the duplicated post to reference the new media file. You can turn off this activity by unchecking the box'); ?>
			</span>
		</label>
	</div>	
	<?php

}
/**
 * 
 * Create the UI for the Retain Published Date Option
 * 
 * @since 0.9.4
 * @return null
 * 
 */
function mdp_retain_published_date_render(  ) { 

	$options = get_option( 'mdp_settings' ); ?>
	
	<div class="checkbox checkbox-slider--b-flat">
		<label>
			<input type='checkbox' name='mdp_settings[mdp_retain_published_date]' <?php mpd_checked_lookup($options, 'mdp_retain_published_date', 'retain-published') ;?> value='retain-published'><span><?php mpd_information_icon('Check this box if you would like the destination post to keep the source published date. NOTE! If you check this option the destination post status will be set to published by default'); ?></span>
		</label>
	</div>	

	<?php

}
/**
 * 
 * Create the UI for the Allow Usage data
 * 
 * @since 0.9.4
 * @return null
 * 
 */
function mdp_allow_dev_info_render(  ) { 

	$options = get_option( 'mdp_settings' ); ?>
		
	<input type='checkbox' name='mdp_settings[mdp_allow_dev_info]' <?php mpd_checked_lookup($options, 'mdp_allow_dev_info', 'allow-dev') ;?> value='allow-dev'>
		
	<?php mpd_information_icon('If this box is checked you are allowing anonymous usage data to be sent to the developers. This provides valuable information in order to improve and maintain this plugin. Thanks so much for your help'); ?>

	<?php

}
/**
 * 
 * Generate a sub heading for the settings page
 * 
 * @since 0.4
 * @return null
 * 
 */
function mdp_settings_section_callback(  ) { 

	_e( 'Here you can change the default settings for Multisite Post Duplicator. Note that these settings are used for every site in your network.', 'multisite-post-duplicator' );

}

add_action( 'update_option_mdp_settings', 'mpd_globalise_settings', 10, 2 );

/**
 * 
 * This function is used to copy the saved settings to all other sites options table,
 * therefore globalising the MPD settings arcroos all sites.
 * 
 * @since 0.4
 * @return null
 * 
 */
function mpd_globalise_settings(){
    
    $options 	= get_option( 'mdp_settings' );
    $sites 		= mpd_wp_get_sites();

	foreach ($sites as $site) {

		switch_to_blog($site->blog_id);

			update_option( 'mdp_settings', $options);

		restore_current_blog();

	}
    	
}
/**
 * 
 * Generate the complete Settings page.
 * See https://codex.wordpress.org/Creating_Options_Pages for info.
 * 
 * @since 0.4
 * @return null
 * 
 */
function mdp_options_page(  ) { 

	$active_tab = '';

	if( isset( $_GET[ 'tab' ] ) ) {
        
        $active_tab = $_GET[ 'tab' ];
    
    }

    $options 		= get_option( 'mdp_settings' );
	$settingsLogic 	= current_user_can( mpd_get_required_cap() );
	$settingsLogic 	= apply_filters( 'mpd_show_settings_page', $settingsLogic );

	if($logic = $settingsLogic):?>

		<?php if(isset($options['add_logging']) || isset($options['allow_persist'])):?>

			<h2 class="nav-tab-wrapper">

    			<a href="options-general.php?page=multisite_post_duplicator" class="nav-tab <?php echo $active_tab == '' ? 'nav-tab-active' : ''; ?>"><i class="fa fa-sliders fa-fw" aria-hidden="true"></i> Settings</a>

    	<?php endif; ?>

    	<?php if(isset($options['add_logging'])) :?>

    			<a href="options-general.php?page=multisite_post_duplicator&tab=log" class="nav-tab <?php echo $active_tab == 'log' ? 'nav-tab-active' : ''; ?>"><i class="fa fa-list-ul fa-fw" aria-hidden="true"></i> Activity Log</a>

    	<?php endif;?>

    	<?php if(isset($options['allow_persist'])):?>
    		
    			<a href="options-general.php?page=multisite_post_duplicator&tab=persists" class="nav-tab <?php echo $active_tab == 'persists' ? 'nav-tab-active' : ''; ?>"><i class="fa fa-link fa-fw" aria-hidden="true"></i> Linked Duplications</a>

    	<?php endif; ?>

    	<?php if(isset($options['add_logging']) || isset($options['allow_persist'])):?>
			</h2>
		<?php endif; ?>

		<div class="donate-link button-secondary"><a target="_blank" class="mpd-smile" href="https://www.paypal.me/mariojaconelli/5">Buy me a beer</a></div>
		
	<?php endif; 

	if($active_tab == 'log' && $logic){

		mdp_log_page();

		}elseif($active_tab == 'persists' && $logic){

			mpd_persist_page();
		
		}else{
			
			echo "<div class='wrap'>";
			echo "<h2><i class='fa fa-link' aria-hidden='true'></i> ". __("Multisite Post Duplicator Settings", 'multisite-post-duplicator')."</h2>";
			echo "<form action='options.php' method='post'>";
			settings_fields( MPD_SETTING_PAGE );
			do_settings_sections( MPD_SETTING_PAGE );
			submit_button();
		
			echo "</form>";

	}

}

?>