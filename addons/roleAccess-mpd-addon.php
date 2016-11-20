<?php
/**
 * MPD Addon: Role Access
 * 
 * This MPD addon allows you to restrict the roles that see MPD functionaity
 * 
 * @ignore
 * @since 0.7.1
 * @author Mario Jaconelli <mariojaconelli@gmail.com>
 * 
 */

/**
 * @ignore
 */
 function roleAccess_addon_mpd_settings(){

 	mpd_settings_field('role_option_setting', '<i class="fa fa-users" aria-hidden="true"></i> ' . __( 'Minimum user role allowed to use MPD', 'multisite-post-duplicator' ), 'role_option_setting_render');

 }

add_action( 'mdp_end_plugin_setting_page', 'roleAccess_addon_mpd_settings');

function role_option_setting_render(){

	global $wp_roles;

	$all_roles 		= $wp_roles->roles;
    $editable_roles = apply_filters('editable_roles', $all_roles);

    if($options = get_option( 'mdp_settings' )){
		$mdp_restrict_role = !empty($options['role_option_setting']) ? $options['role_option_setting'] : 'Administrator';
	}else{
		$mdp_restrict_role = 'Administrator';
	};

	?>
		<select name="mdp_settings[role_option_setting]'" class="" style="width:300px;">

			<?php if(current_user_can('manage_sites')):?>
				
				<option value="Super-Admin" <?php echo $mdp_restrict_role == 'Super-Admin' ? 'selected="selected"' : ''; ?>>

			    	<?php _e('Super Administrator', 'multisite-post-duplicator' ); ?>

			    </option>

			<?php endif; ?>

			<?php foreach ($editable_roles as $editable_role):?>

			    <option value="<?php echo $editable_role['name']; ?>" <?php echo $mdp_restrict_role == $editable_role['name'] ? 'selected="selected"' : ''; ?>>

			    	<?php echo $editable_role['name'];?>

			    </option>
			
			<?php endforeach; ?>

		</select>

	<?php

}

function mpd_get_required_cap(){

	if($options = get_option( 'mdp_settings' )){
		$mdp_restrict_role = !empty($options['role_option_setting']) ? $options['role_option_setting'] : 'Administrator';
	}else{
		$mdp_restrict_role = 'Administrator';
	};

	switch ($mdp_restrict_role) {
		case 'Super-Admin':
			$cap = 'manage_sites';
			break;
		case 'Administrator':
			$cap = 'activate_plugins';
			break;
		case 'Editor':
			$cap = 'manage_categories';
			break;
		case 'Contributor':
			$cap = 'edit_posts';
			break;
		case 'Author':
			$cap = 'publish_posts';
			break;
		case 'Subscriber':
			$cap = 'read';
			break;
		
		default:
			$cap = 'activate_plugins';
			break;
	}

	return $cap;

}
