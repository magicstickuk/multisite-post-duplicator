<?php
/**
 * @ignore
 */
function allow_dev_setting_activation($options){

  if(version_compare(mpd_get_version(),'0.9.4.1', '<')){
      $options['mdp_allow_dev_info']    = 'allow-dev';
  }

  return $options;

}
add_filter('mdp_activation_options', 'allow_dev_setting_activation');
/**
 * @ignore
 */
function mdp_screen_id(){
	
	$screen = get_current_screen();

	return $screen->id;
	
}
/**
 * @ignore
 */
function mpd_generate_activation_id(){

	if ( !mpd_get_activation_id() ) {

		$inst_id = uniqid("mpd_", true);

		add_site_option('mpd_inst_id', $inst_id);

		return $inst_id;

	}

	return false;

}
/**
 * @ignore
 */
function mpd_get_activation_id(){

	return get_site_option( 'mpd_inst_id');

}

/**
 * @ignore
 */
function mpd_alert_ajax($createdPostObject){

	$options = get_option( 'mdp_settings' );

	if($options['mdp_allow_dev_info']){

		update_option('mpd_log_completed_' . $createdPostObject['id'] ."_". microtime() , $createdPostObject['id'] );

	}

}

add_action('mpd_end_of_core', 'mpd_alert_ajax', 10, 1);
/**
 * @ignore
 */
function mpd_do_ajax_log() {

	$options 		= get_option( 'mdp_settings' );

	if(isset($options['mdp_allow_dev_info'])){

		mpd_generate_activation_id();

		if(!get_site_option( 'mpd_inst_to_logged')){

			add_site_option('mpd_inst_to_logged', 1);

		}

	}

	$url 			=  'https://wpmaz.uk/mpd/mpdlog.php';
	$commonParams 	=  "type: 'POST',
					    dataType: 'json',
					    crossDomain: true,
					    success: function(responseData, textStatus, jqXHR){},
					    error: function (responseData, textStatus, errorThrown){},";

	if(isset($options['mdp_allow_dev_info'])){

		global $wpdb;

		$query 			= "select * FROM " . $wpdb->options . " where option_name like 'mpd_log_completed_%'";
		$results 		= $wpdb->get_results( $query , OBJECT );
		$resultsCount	= count($results);
		$batch 			= count($results) >= 2 ? "1" : "0";
		
		if($results){

			$data = array(
				'INSID' 		=> mpd_get_activation_id(),
				'DUPED' 		=> current_time('mysql'),
				'SETTINGS' 		=> get_option( 'mdp_settings' ),
				'VERSION' 		=> mpd_get_version(),
				'LOCATION'		=> mdp_screen_id(),
				'BATCH'			=> $batch,
				'BATCH_COUNT'	=> $resultsCount
			);

			$data = json_encode($data);
			?>
			<script>
				jQuery(document).ready(function($) {
					jQuery.ajax({
					    <?php echo $commonParams; ?>
					    url: '<?php echo $url;?>',
					    data: <?php echo $data; ?>
					});
				});
			</script>
			<?php

		}

		foreach ($results as $result) {

			delete_option($result->option_name);

		}

		if(get_site_option( 'mpd_inst_to_logged') && get_site_option( 'mpd_inst_to_logged') == 1){

			global $wp_version;

			$data = array(
				'INSID' 			=> mpd_get_activation_id(),
				'LANG' 				=> get_site_option('WPLANG'),
				'DUPED' 			=> current_time('mysql'),
				'WP_VERSION' 		=> $wp_version,
				'BLOG_COUNT' 		=> get_site_option('blog_count'),
				'SUBDOMAIN_INSTALL' => get_site_option('subdomain_install'),

			);

			$data = json_encode($data);

			?>
			<script>
				jQuery(document).ready(function($) {
					
					jQuery.ajax({
					    <?php echo $commonParams; ?>
					    url: '<?php echo $url;?>',
					    data: <?php echo $data; ?>
					});
				});
			</script>
			<?php

			update_site_option('mpd_inst_to_logged', 0);

		}



	}

	if(!isset($options['mdp_allow_dev_info']) && !get_site_option( 'mpd_dev_optout')){

		$data = array(
				'INSID' 			=> mpd_get_activation_id(),
				'OPTOUT_TIME' 		=> current_time('mysql'),
				'OPTOUT'			=> 1
			);

			$data = json_encode($data);

			?>
			<script>
				jQuery(document).ready(function($) {
					
					jQuery.ajax({
					    <?php echo $commonParams; ?>
					    url: '<?php echo $url;?>',
					    data: <?php echo $data; ?>
					});
				});
			</script>
			<?php

		update_site_option('mpd_dev_optout', 1);

	}
	



}
add_action( 'admin_head', 'mpd_do_ajax_log' );