<?php
add_action( 'admin_menu', 'mdp_add_admin_menu' );
add_action( 'admin_init', 'mdp_settings_init' );


function mdp_add_admin_menu(  ) { 

	add_submenu_page( 'tools.php', 'Multisite Post Duplicator Settings', 'Multisite Post Duplicator Settings', 'manage_options', 'multisite_post_duplicator', 'mdp_options_page' );

}


function mdp_settings_init(  ) { 

	register_setting( 'pluginPage', 'mdp_settings' );

	add_settings_section(
		'mdp_pluginPage_section', 
		__( 'Your section description', 'mdp' ), 
		'mdp_settings_section_callback', 
		'pluginPage'
	);

	add_settings_field( 
		'mdp_checkbox_field_0', 
		__( 'Settings field description', 'mdp' ), 
		'mdp_checkbox_field_0_render', 
		'pluginPage', 
		'mdp_pluginPage_section' 
	);

	add_settings_field( 
		'mdp_text_field_1', 
		__( 'Settings field description', 'mdp' ), 
		'mdp_text_field_1_render', 
		'pluginPage', 
		'mdp_pluginPage_section' 
	);

	add_settings_field( 
		'mdp_checkbox_field_2', 
		__( 'Settings field description', 'mdp' ), 
		'mdp_checkbox_field_2_render', 
		'pluginPage', 
		'mdp_pluginPage_section' 
	);


}


function mdp_checkbox_field_0_render(  ) { 

	$options = get_option( 'mdp_settings' );
	?>
	<input type='checkbox' name='mdp_settings[mdp_checkbox_field_0]' <?php checked( $options['mdp_checkbox_field_0'], 1 ); ?> value='1'>
	<?php

}


function mdp_text_field_1_render(  ) { 

	$options = get_option( 'mdp_settings' );
	?>
	<input type='text' name='mdp_settings[mdp_text_field_1]' value='<?php echo $options['mdp_text_field_1']; ?>'>
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
		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();
		?>
		
	</form>
	<?php

}

?>