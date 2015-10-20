<?php

add_action( 'admin_menu', 'mpd_admin_pages' );

function mpd_admin_pages(){

	add_submenu_page('tools.php', __('Multisite Post Duplicator','mpd'),__('Multisite Post Duplicator','mpd'), 'manage_options', 'mpd','mpd_admin_menu_markup');

}

function mpd_admin_menu_markup(){

	global $wp_post_types;

	$success = false;

	if(isset($_POST['duplicate-submit'])){

			$mdp_PostType 	= $_POST['el0'];
			$mdp_PostID 	= $_POST['el1'];
			$mdp_NewBlog 	= $_POST['el2'];
			$mdp_userID 	= $_POST['el3'];
			$mdp_prefix 	= $_POST['mdp-prefix'];
			$mdp_postStatus = $_POST['mpd-post-status'];

			$new_post_obj 	= mpd_duplicate_over_multisite($mdp_PostID, $mdp_NewBlog, $mdp_PostType, $mdp_userID, $mdp_prefix, $mdp_postStatus);

			$success 		= true;

	}

	

	$post_types = get_post_types();
		
	ob_start()?>

	<div class="wrap">

    	<h2><?php _e('Multisite Post Duplicator','mpd'); ?></h2>

    	<?php if(!is_multisite()):?>

    		<h2><?php _e('Attention!','mpd'); ?></h2>

    		<p><?php _e('At the moment this plugin is solely for funtioning on a mulitisite. It appears this site doees not have multisite enabled','mpd'); ?></p>
			
			<?php return; ?>

		<?php endif ?>

    	<?php if($success):?>

    		<div class="updated">

    			<p><?php _e('Congratulations. The page/post was duplicated successfully.','mpd'); ?> <a href="<?php echo $new_post_obj['edit_url']; ?>"><?php _e('Edit this post','mpd'); ?></a></p>

    		</div>

		<?php endif ?>

    	<form id="thefirstform" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">

    		<div class="metabox">

    			<h2><?php _e('Options','mpd'); ?></h2>

    			<p><?php _e('Select your preferences for the duplication.','mpd'); ?></p>

    			<h3><?php _e('Select the status of the new post that will be created.','mpd'); ?></h3>

    			<?php $post_statuses = get_post_statuses(); ?>

    			<?php  foreach ($post_statuses as $post_status_key => $post_status_value):?>

    				<input type="radio" name="mpd-post-status" value="<?php echo $post_status_key ?>" <?php echo $post_status_key == 'draft' ? 'checked' : '' ?>/><?php echo $post_status_value; ?>

    			<?php endforeach;?>

    			<h3><?php _e('Select a prefix, if any, for the new post/page to be created','mpd'); ?>:</h3>
				
    			<input type="text" name="mdp-prefix" value="<?php echo mpd_get_prefix(); ?>"/>
    		
			</div>

			<div class="metabox">

				<h2><?php _e('Process the duplication','mpd'); ?></h2>

	    		<h3>1. <?php _e('Select the post type of the post you want to duplicate','mpd'); ?></h3>
	    		
	    		<input type="hidden" name="action" value="add_foobar">

		    	<select name="el0" class="el0" style="width:300px;">

		    		<option></option>

		    		<option value="any" > - <?php _e('All Post Types','mpd'); ?> -</option>

		    		<?php foreach ($post_types as $post_type):?>

		    			<option value="<?php echo $post_type; ?>">

		    				<?php echo ucfirst($post_type)?>

		    			</option>

					<?php endforeach; ?>

					<?php wp_reset_postdata(); ?>

				</select>

				<div class="el0sc spinner-container"><img src="<?php echo plugins_url('../css/select2-spinner.gif',__FILE__); ?>"/></div>

				<div class="el1-container"></div>
		    	
				<div class="el2-container"></div>

				<div class="el3-container"></div>

			</div>

		</form>

	</div>

	<?php 
}

function mpd_get_posts_for_type(){

	if($_POST['post_type'] == ' - All Post Types -' ){
			$mpd_posttype_query = 'any';
			$all= true;
	}else{
			$mpd_posttype_query = $_POST['post_type'];
			$all= false;
	}

	$args = array(
		'post_type' => $mpd_posttype_query,
		'posts_per_page' => -1
	);

	$the_query = new WP_Query( $args );

	ob_start()?>

	<h3>2. <?php _e('Select the page you want to duplicate','mpd'); ?></h3>

	<select name="el1" class="el1" style="width:300px;">

    		<option></option>

    		<?php while ( $the_query->have_posts() ) : $the_query->the_post(); ?>

    			<option value="<?php echo get_the_ID(); ?>">

    				<?php the_title( );?><?php if($all){ echo " - (" . get_post_type( get_the_ID()) . ")" ;} ?>

    			</option>

			<?php endwhile; ?>

			<?php wp_reset_postdata(); ?>
		</select> <div class="el1sc spinner-container"><img src="<?php echo plugins_url('../css/select2-spinner.gif',__FILE__); ?>"/></div>

	<?php

	die();
}

add_action( 'wp_ajax_mdp_get_posts', 'mpd_get_posts_for_type');

function mpd_get_site_on_network(){

	$args 	= array('network_id' => null);
	$sites 	= wp_get_sites($args);

	ob_start()?>

	<h3>3. <?php _e('Select the site on this network you want to duplicate to','mpd'); ?></h3>
	
	<select name="el2" class="el2" style="width:300px;">

	    <option></option>

	    <?php foreach ($sites as $site) :?>

	    	<?php $blog_details = get_blog_details($site['blog_id']);?>

	    		<option value="<?php echo $site['blog_id'] ?>"><?php echo $blog_details->blogname; ?></option>

	    <?php endforeach; ?>

	</select> <div class="el2sc spinner-container"><img src="<?php echo plugins_url('../css/select2-spinner.gif',__FILE__); ?>"/></div>

	
	<?php
	
	die();

}
add_action( 'wp_ajax_mpd_get_sites', 'mpd_get_site_on_network');

function mpd_get_users_on_site(){

	$args = array(

		'blog_id'      => $_POST['el2blogid'],

	 );

	$users = get_users( $args );

	ob_start()?>

	<h3>4. <?php _e('Select the user on this site you want to atribute this action to','mpd'); ?></h3>
	
	<select name="el3" class="el3" style="width:300px;">

	    <option></option>

	    <?php foreach ($users as $user) :?>

	    		<option value="<?php echo $user->ID; ?>"><?php echo $user->first_name ? $user->first_name ." ". $user->last_name : '' . $user->user_login ?></option>

	    <?php endforeach; ?>

	</select> <div class="el3sc spinner-container"><img src="<?php echo plugins_url('../css/select2-spinner.gif',__FILE__); ?>"/></div>

	<p>

		<input type="submit" value="<?php _e('Duplicate','mpd'); ?>" style="display:none;" class="button-primary main-dup-button" name="duplicate-submit">
	
	</p>
	
	<?php
	
	die();

}
add_action( 'wp_ajax_mpd_site_users', 'mpd_get_users_on_site');