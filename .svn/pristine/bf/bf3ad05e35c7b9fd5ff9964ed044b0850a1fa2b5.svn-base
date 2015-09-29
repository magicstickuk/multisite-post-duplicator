<?php

add_action( 'admin_menu', 'mpd_admin_page' );

function mpd_admin_page(){

	add_submenu_page('tools.php','Multisite Post Duplicator','Multisite Post Duplicator', 'manage_options', 'mpd','mpd_admin_menu_markup');
}

function mpd_admin_menu_markup(){

	global $wp_post_types;

	$success = false;

	if(isset($_POST['duplicate-submit'])){

			$mdp_PostType = $_POST['el0'];
			$mdp_PostID = $_POST['el1'];
			$mdp_NewBlog = $_POST['el2'];
			$mdp_userID = $_POST['el3'];
			$mdp_prefix = $_POST['mdp-prefix'];
			$mdp_postStatus = $_POST['mpd-post-status'];

			$new_postid = duplicate_over_multisite($mdp_PostID, $mdp_NewBlog, $mdp_PostType, $mdp_userID, $mdp_prefix, $mdp_postStatus);

			$success = true;

	}

	

	$post_types = get_post_types();
		
	ob_start()?>

	<div class="wrap">

    	<h2>Multisite Post Duplicator</h2>

    	<?php if(!is_multisite()):?>

    		<h2>Attention!</h2>

    		<p>At the moment this plugin is solely for funtioning on a mulitisite. It appears this site doees not have multisite enabled.</p>
			
			<?php return; ?>

		<?php endif ?>

    	<?php if($success):?>

    		<div class="updated mdp-updated">

    			<h3><strong>Congratuations. The page/post was duplicated successfully.</strong></h3>

    		</div>

		<?php endif ?>

    	<form id="thefirstform" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">

    		<div class="metabox">

    			<h2>Options</h2>

    			<p>Select your preferences for the duplication.</p>

    			<h3>Select the status of the new post that will be created</h3>

    			<?php $post_statuses = array('publish', 'future', 'draft', 'pending', 'private');?>

    			<?php  foreach ($post_statuses as $post_status):?>

    				<input type="radio" name="mpd-post-status" value="<?php echo $post_status ?>" <?php echo $post_status == 'draft' ? 'checked' : '' ?>/><?php echo ucfirst($post_status)?>

    			<?php endforeach?>

    			<h3>Select a prefix, if any, for the new post/page to be created:</h3>

    			<input type="text" name="mdp-prefix" value="Copy of"/>
    		
			</div>

			<div class="metabox">

				<h2>Process the duplication</h2>

	    		<h3>1. Select the post type of the post you want to duplicate</h3>
	    		
	    		<input type="hidden" name="action" value="add_foobar">

		    	<select name="el0" class="el0" style="width:300px;">

		    		<option></option>

		    		<option value="any" > - All Post Types -</option>

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

function mdp_get_posts_for_type(){

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

	<h3>2. Select the page you want to duplicate</h3>

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

add_action( 'wp_ajax_mdp_get_posts', 'mdp_get_posts_for_type');

function mdp_get_site_on_network(){

	$sites = wp_get_sites();

	ob_start()?>

	<h3>3. Select the site on this network you want to duplicate to</h3>
	
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
add_action( 'wp_ajax_mdp_get_sites', 'mdp_get_site_on_network');

function mdp_get_users_on_site(){

	$args = array(

		'blog_id'      => $_POST['el2blogid'],

	 );

	$users = get_users( $args );

	ob_start()?>

	<h3>4. Select the user on this site you want to atribute this action to</h3>
	
	<select name="el3" class="el3" style="width:300px;">

	    <option></option>

	    <?php foreach ($users as $user) :?>

	    		<option value="<?php echo $user->ID; ?>"><?php echo $user->first_name ? $user->first_name ." ". $user->last_name : '' . $user->user_login ?></option>

	    <?php endforeach; ?>

	</select> <div class="el3sc spinner-container"><img src="<?php echo plugins_url('../css/select2-spinner.gif',__FILE__); ?>"/></div>

	<p>

		<input type="submit" value="Duplicate" style="display:none;" class="button-primary main-dup-button" name="duplicate-submit">
	
	</p>
	
	<?php
	
	die();

}
add_action( 'wp_ajax_mdp_site_users', 'mdp_get_users_on_site');