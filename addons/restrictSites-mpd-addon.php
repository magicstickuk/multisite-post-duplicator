<?php
/**
 * MPD Addon: Restrict Sites
 * 
 * This MPD addon allows you to restrict what sites can be the sourse of any Duplication
 * 
 * @ignore
 * @since 0.6
 * @author Mario Jaconelli <mariojaconelli@gmail.com>
 * 
 */

/**
 * @ignore
 */
function restrict_addon_mpd_settings(){

	mpd_settings_field('restrict_option_setting', '<i class="fa fa-user-times" aria-hidden="true"></i> ' . __( 'Restrict MPD to certain sites', 'multisite-post-duplicator' ), 'restrict_option_setting_render');
	mpd_settings_field('restrict_some_option_setting', '<i class="fa fa-user-plus" aria-hidden="true"></i> ' . __( 'Restrict MPD on some sites', 'multisite-post-duplicator' ), 'restrict_some_option_setting_render');
  	mpd_settings_field('master_site_setting', '<i class="fa fa-bank" aria-hidden="true"></i> ' . __( 'Select a Master Site', 'multisite-post-duplicator' ), 'master_site_settings_render');
 	mpd_settings_field('mdp_global_categories_taxonomies',
		'<i class="fa fa-globe" aria-hidden="true"></i> ' . __( 'Make post categories and taxonomies global?', 'multisite-post-duplicator' ),
		'mdp_global_categories_taxonomies_render'
	);
	
}

add_action( 'mdp_end_plugin_setting_page', 'restrict_addon_mpd_settings');

/**
 * @ignore
 */
function restrict_option_setting_render(){
  
  if($options = get_option( 'mdp_settings' )){
		$mdp_restrict_radio_label_value = !empty($options['restrict_option_setting']) ? $options['restrict_option_setting'] : 'none';
	}else{
		$mdp_restrict_radio_label_value = 'none';
	};

  ?>
  
	<div id="mpd_restrict_radio_choice_wrap">

		<div class="mdp-inputcontainer">
			<input type="radio" class="mdp_radio" name='mdp_settings[restrict_option_setting]' id="mpd_restrict_none" <?php checked($mdp_restrict_radio_label_value, 'none'); ?> value="none">
		
			<label class="mdp_radio_label" for="radio-choice-1"><?php _e('No Restrictions', 'multisite-post-duplicator' ) ?></label>
			    
			<input type="radio" class="mdp_radio" name='mdp_settings[restrict_option_setting]' id="mpd_restrict_some" <?php checked($mdp_restrict_radio_label_value, 'some'); ?> value="some">
		
			<label class="mdp_radio_label" for="radio-choice-2"><?php _e('Restrict Some Sites', 'multisite-post-duplicator' ) ?></label>
		
			<input type="radio" class="mdp_radio" name='mdp_settings[restrict_option_setting]' id="mpd_restrict_set_master" <?php checked($mdp_restrict_radio_label_value, 'master'); ?> value="master">
		
			<label class="mdp_radio_label" for="radio-choice-3"><?php _e('Select a Master Site', 'multisite-post-duplicator') ?></label>
			
			<?php mpd_information_icon('You can, if you want, limit MPD functionality to only certain sites.'); ?>
			
	    </div>

    </div>

  <?php

}
/**
 * @ignore
 */
function mpd_get_restrict_some_sites_options(){

	if(!$options = get_option( 'mdp_settings' )){

		return array();

	}else{

		$options = get_option( 'mdp_settings' );
		$requested_restricted_sites = array();
		
		foreach ($options as $key => $value) {
        
        	if (substr($key, 0, 24) == "mpd_restrict_some_sites_") {

            	$requested_restricted_sites[] = $value;

        	}

    	}

    	return $requested_restricted_sites;

	}

}
/**
 * @ignore
 */
function restrict_some_option_setting_render(){
  
  $restricted_ids 	= mpd_get_restrict_some_sites_options();
  $sites   			= mpd_wp_get_sites();

  ?>
		<?php foreach ($sites as $site): ?>
			
			<?php 	

				$blog_details 	= get_blog_details($site->blog_id);
				$checkme		= ''; 
				
				if(in_array($site->blog_id, $restricted_ids)){
					$checkme = 'checked="checked"';
				}

			?>
				<input type='checkbox' class="restrict-some-checkbox" name='mdp_settings[mpd_restrict_some_sites_<?php echo $site->blog_id ?>]' <?php echo $checkme; ?> value='<?php echo $site->blog_id; ?>'> <?php echo $blog_details->blogname; ?> <br >
			

		<?php endforeach;?>

		<p class="mpdtip"><?php _e('Select some sites where you do not want MDP functionality. Note: You should not select all sites here as this will result in no MPD functionality.', 'multisite-post-duplicator' ) ?></p>
  <?php

}

/**
 * @ignore
 */
function master_site_settings_render(){

  $options = get_option( 'mdp_settings' );
  $sites   = mpd_wp_get_sites();

   if($options = get_option( 'mdp_settings' )){
		$mdp_restrict_master_label_value = $options['master_site_setting'];
	};
  ?>
  
  <select name="mdp_settings[master_site_setting]" class="mpd-master-site" style="width:300px;">

		<option></option>
		<?php foreach ($sites as $site): ?>
			<?php $blog_details = get_blog_details($site->blog_id); ?>

			<option value="<?php echo $site->blog_id ?>" <?php selected($mdp_restrict_master_label_value, $site->blog_id); ?>>

			    <?php echo $blog_details->blogname;?>

			</option>

		<?php endforeach ?>
  		
  </select>

  <p class="mpdtip"><?php _e('If you want to only allow duplication to take place from one site then select it here.', 'multisite-post-duplicator')?></p>
  <?php

}

/**
 * 
 * Create the UI for the Global Category and Taxonomy Setting
 * 
 * @since 1.7
 * @return null
 * 
 */
function mdp_global_categories_taxonomies_render(  ) { 

	$options = get_option( 'mdp_settings' ); ?>
	
	<div class="checkbox checkbox-slider--b-flat global-terms">
		
		<label>
			<input type='checkbox' name='mdp_settings[mdp_global_categories_taxonomies]' <?php mpd_checked_lookup($options, 'mdp_global_categories_taxonomies', 'global') ;?> value='global'>
			<span>
				<?php mpd_information_icon('This plugin will make all sub-sites reference the master sites TERMS and TERM TAXONOMY tables. This allows for category IDs to be consistent across all sub-sites when posts are duplicated from the master site. When a post is duplicated from the master site with this setting checked, it can be referenced universally by the same category ID. This comes in handy if you are feeding posts to areas of your site/ sub-sites by category ID. You can turn off this activity by un-checking the box.'); ?>
			</span>
		</label>
	</div>
	<?php

}

/**
 * @ignore
 */
function mpd_add_addon_script_to_settings_page(){
	
	$screenid = get_current_screen()->id;

	if($screenid == 'settings_page_multisite_post_duplicator'){
	?>
	<script>
		jQuery(document).ready(function() {

			var masterSiteLvl 	= jQuery(".mpd-master-site").parent().parent();
			var gTerms 			= jQuery(".global-terms").parent().parent();
			var restrictSomeLvl = jQuery(".restrict-some-checkbox").parent().parent();
			var rcb 			= jQuery('.restrict-some-checkbox:not(:checked)');
			
			masterSiteLvl.hide();
			restrictSomeLvl.hide();
			gTerms.hide();

			if(rcb.length == 1){
				rcb.attr("disabled", true);
			}else{
				rcb.removeAttr("disabled");
			}
			
			jQuery(".mpd-master-site").select2({
				placeholder: '<?php _e("Select a Master Site", 'multisite-post-duplicator') ?>'	
			});

			if(jQuery('#mpd_restrict_set_master').is(':checked') ){
      			masterSiteLvl.show();
      			gTerms.show();
  			}
  			if(jQuery('#mpd_restrict_some').is(':checked') ){
      			restrictSomeLvl.show();
  			}

  			jQuery('#mpd_restrict_radio_choice_wrap .mdp_radio').change(function() {
  		
  				if (jQuery(this).val() == 'master') {
  					masterSiteLvl.show('fast');
  					gTerms.show('fast');
  				}else{
  					masterSiteLvl.hide('fast');
  					gTerms.hide('fast');
  				};

  				if (jQuery(this).val() == 'some') {
  					restrictSomeLvl.show('fast');
  				}else{
  					restrictSomeLvl.hide('fast');
  				};

  			});

  			jQuery('.restrict-some-checkbox').change(function(){
  				var rcb = jQuery('.restrict-some-checkbox:not(:checked)');
  				if(rcb.length == 1){
  					rcb.attr("disabled", true);
  				}else{
  					rcb.removeAttr("disabled");
  				}
  			});

		});
		
	</script>
	<?php
	}
}
add_action('admin_head', 'mpd_add_addon_script_to_settings_page');

/**
 * @ignore
 */
function mpd_is_site_active(){

  $options 			= get_option( 'mdp_settings' );
  $currentSite 		= get_current_blog_id();
  $access			= true;

  $restrict_option = !empty($options['restrict_option_setting']) ? $options['restrict_option_setting'] : 'none';

  switch ($restrict_option) {

    case 'some':
       	
       	$blog_ids_to_restrict = array();
       	foreach ($options as $key => $value) {
        
        	if (substr($key, 0, 24) == "mpd_restrict_some_sites_") {

            	$blog_ids_to_restrict[] = $value;

        	}

    	}

    	if(in_array($currentSite, $blog_ids_to_restrict)){

    		$access = false;

    	}

        break;

    case 'master':
        
   		if($options['master_site_setting']){

   			if($currentSite != $options['master_site_setting']){
   				$access = false;
   			}

   		}

        break;
    
    default:

        $access	= true;

  }

  return $access;

}

add_filter( 'mpd_is_active', 'mpd_is_site_active');

add_action( 'init', 'change_tax_terms_table', 0 );

add_action( 'switch_blog', 'change_tax_terms_table', 0 );

function change_tax_terms_table(){

	$options 		= get_option( 'mdp_settings' );
	$master_id 		= $options['master_site_setting'];

	if((isset($options['mdp_global_categories_taxonomies']) || !$options) && apply_filters('mdp_global_categories_taxonomies', true) ){

            global $wpdb;
    
		    $wpdb->terms = $wpdb->get_blog_prefix($master_id) . 'terms';
		   
		    $wpdb->term_taxonomy = $wpdb->get_blog_prefix($master_id) . 'term_taxonomy';

        }
    
}