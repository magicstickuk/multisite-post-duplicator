function get_selection_value(target){
	var selected_value = jQuery(target).find(":selected").text();
	return selected_value;
}

function set_placeholder(target, placeholder_text){
	jQuery(target).select2({
		placeholder: placeholder_text,	
	});
}

function mark_as_success(target){
	jQuery(target).addClass('mpd-greentick');
}

function do_the_responce(response_ident, placeholdertext, response){
	jQuery(".el" + response_ident + "-container").html(response);
	set_placeholder(".el" + response_ident , placeholdertext);
	mark_as_success('.el' + (response_ident - 1) + 'sc.spinner-container');
}

jQuery(document).ready(function($) {

	set_placeholder(".el0", mpd_admin_scripts_vars.select_post_type);

	$( ".el0" ).change(function() {

		var post_type_name = get_selection_value(this);

		$('.el0sc.spinner-container img').show();

		data =  {
					action : 'mdp_get_posts',
					post_type : post_type_name
				};

		$.post(ajaxurl,data,function(response) {

			do_the_responce("1", mpd_admin_scripts_vars.select_post, response);

			$( ".el1" ).change(function() {

				var post__name = get_selection_value(this);
					
					$('.el1sc.spinner-container img').show();

					data =  {
								action : 'mdp_get_sites',
								post_name : post__name 
							};

					$.post(ajaxurl,data,function(response) {

							do_the_responce("2", mpd_admin_scripts_vars.select_site, response);

							$( ".el2" ).change(function() {

								var blog_id = $(this).find(":selected").val();
									
									$('.el2sc.spinner-container img').show();

									data =  {
												action : 'mdp_site_users',
												el2blogid : blog_id,
												sourceblog : post_type_name
											};

									$.post(ajaxurl,data,function(response) {

											do_the_responce("3", mpd_admin_scripts_vars.select_user, response);

											$( ".el3" ).change(function() {
													$('.el3sc.spinner-container img').show();
													$('.el3sc.spinner-container').addClass('mpd-greentick');
													$('.main-dup-button').show();
											});

									})

									return false;

							});

					})

					return false;

			});

		})

		return false;

	});

});