<?php

function allow_dev_setting_activation($options){

  if(version_compare(mpd_get_version(),'0.9.4', '<')){
      $options['mdp_allow_dev_info']    = 'allow-dev';
  }

  return $options;

}
add_filter('mdp_activation_options', 'allow_dev_setting_activation');

function mdp_screen_id(){
	
	$screen = get_current_screen();

	return $screen->id;
	
}

function mpd_generate_activation_id(){

	if ( !mpd_get_activation_id() ) {

		$inst_id = uniqid("mpd_", true);

		add_site_option('mpd_inst_id', $inst_id);

		return $inst_id;

	}

	return false;

}

function mpd_get_activation_id(){

	return get_site_option( 'mpd_inst_id');

}

function mpd_on_activation_for_log($mdp_default_options, $sites){

	$options = get_option( 'mdp_settings' );

	if(isset($options['mdp_allow_dev_info'])){

		mpd_generate_activation_id();

		if(!get_site_option( 'mpd_inst_to_logged')){

			add_site_option('mpd_inst_to_logged', 1);

		}

	}


}

add_action('mpd_extend_activation', 'mpd_on_activation_for_log', 10, 2);


function mpd_alert_ajax($createdPostObject){

	$options = get_option( 'mdp_settings' );

	if(isset($options['mdp_allow_dev_info'])){

		update_option('mpd_log_completed_' . $createdPostObject['id'] ."_". microtime() , $createdPostObject['id'] );

	}

}

add_action('mpd_end_of_core', 'mpd_alert_ajax', 10, 1);

function mpd_do_ajax_log() {

	$options = get_option( 'mdp_settings' );

	if(isset($options['mdp_allow_dev_info'])){

		global $wpdb;

		$url 			= 'https://wpmaz.uk/mpd/mpdlog.php';
		$query 			= "select * FROM " . $wpdb->options . " where option_name like 'mpd_log_completed_%'";
		$results 		= $wpdb->get_results( $query , OBJECT );
		$commonParams 	=  "type: 'POST',
					    	dataType: 'json',
					    	crossDomain: true,
					    	success: function(responseData, textStatus, jqXHR){console.log(responseData)},
					    	error: function (responseData, textStatus, errorThrown){},";
		
		if($results){

			$data = array(
				'INSID' 	=> mpd_get_activation_id(),
				'DUPED' 	=> current_time('mysql'),
				'SETTINGS' 	=> get_option( 'mdp_settings' ),
				'VERSION' 	=> mpd_get_version(),
				'LOCATION'	=> mdp_screen_id()
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

}
add_action( 'admin_head', 'mpd_do_ajax_log' );