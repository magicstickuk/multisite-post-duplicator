<?php
add_action( 'admin_menu', 'mdp_add_admin_menu' );
add_action( 'admin_init', 'mdp_settings_init' );


function mdp_add_admin_menu(  ) { 

	add_submenu_page( 'tools.php', 'Multisite Post Duplicator Settings', 'Multisite Post Duplicator Settings', 'manage_options', 'multisite_post_duplicator', 'mdp_options_page' );

}


function mdp_settings_init(  ) { 

	register_setting( 'mdp_plugin_setting_page', 'mdp_settings' );

	add_settings_section(
		'mdp_mdp_plugin_setting_page_section', 
		__( 'Your section description', 'mdp' ), 
		'mdp_settings_section_callback', 
		'mdp_plugin_setting_page'
	);

	add_settings_field( 
		'meta_box_show', 
		__( 'Show Multisite Post Duplicator box in posts?', 'mdp' ), 
		'meta_box_show_render', 
		'mdp_plugin_setting_page', 
		'mdp_mdp_plugin_setting_page_section' 
	);

	add_settings_field( 
		'mdp_default_prefix', 
		__( 'Default Prefix', 'mdp' ), 
		'mdp_default_prefix_render', 
		'mdp_plugin_setting_page', 
		'mdp_mdp_plugin_setting_page_section' 
	);

	add_settings_field( 
		'mdp_checkbox_field_2', 
		__( 'Settings field description', 'mdp' ), 
		'mdp_checkbox_field_2_render', 
		'mdp_plugin_setting_page', 
		'mdp_mdp_plugin_setting_page_section' 
	);


}


function meta_box_show_render(  ) { 

	$options = get_option( 'mdp_settings' );
	?>
	<input type='checkbox' name='mdp_settings[meta_box_show]' <?php checked( $options['meta_box_show'], 1 ); ?> value='1'>
	<?php

}


function mdp_default_prefix_render(  ) { 

	$options = get_option( 'mdp_settings' );
	?>
	<input type='text' name='mdp_settings[mdp_default_prefix]' value='<?php echo $options ? $options['mdp_default_prefix'] : "Copy of"; ?>'>
	<?php

}


function mdp_checkbox_field_2_render(  ) { 

	$options = get_option( 'mdp_settings' );
	?>
	<input type='checkbox' name='mdp_settings[mdp_checkbox_field_2]' <?php checked( $options['mdp_checkbox_field_2'], 1 ); ?> value='1'>
	<?php

}


function mdp_settings_section_callback(  ) { 

	echo __( 'This section description', 'mdp' );

}


function mdp_options_page(  ) { 

	?>
	<form action='options.php' method='post'>
		
		<h2>Multisite Post Duplicator Settings Page</h2>
		
		<?php
		settings_fields( 'mdp_plugin_setting_page' );
		do_settings_sections( 'mdp_plugin_setting_page' );
		submit_button();
		?>
		
	</form>
	<?php

}

?>