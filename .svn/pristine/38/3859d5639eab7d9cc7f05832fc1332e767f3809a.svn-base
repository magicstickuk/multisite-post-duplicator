jQuery(document).ready(function($) {


	jQuery(".el0").select2({

		placeholder: "Select a post post type to duplicate",
		
	});

	$( ".el0" ).change(function() {

		var post_type_name = $('.el0').find(":selected").text();

		$('.el0sc.spinner-container img').show();

		data =  {
					action : 'mdp_get_posts',
					post_type : post_type_name
				};

		$.post(ajaxurl,data,function(response) {

			$(".el1-container").html(response);

			jQuery(".el1").select2({
				placeholder: "Select a post to duplicate",

			});

			$('.el0sc.spinner-container').addClass('mpd-greentick');

			//Call function to control the new .el1 select box

			$( ".el1" ).change(function() {

				var post__name = $('.el1').find(":selected").text();
					
					$('.el1sc.spinner-container img').show();

					data =  {
								action : 'mdp_get_sites',
								post_name : post__name 
							};

					$.post(ajaxurl,data,function(response) {

						$(".el2-container").html(response);

							jQuery(".el2").select2({
								placeholder: "Select a site to duplicate to",

							});

							$('.el1sc.spinner-container').addClass('mpd-greentick');

							//Call function to control the new .el2 select box

							$( ".el2" ).change(function() {

								var blog_id = $('.el2').find(":selected").val();
								var source_postype = $('.el0').find(":selected").text();
									
									$('.el2sc.spinner-container img').show();

									data =  {
												action : 'mdp_site_users',
												el2blogid : blog_id,
												sourceblog : source_postype
											};

									$.post(ajaxurl,data,function(response) {

										$(".el3-container").html(response);

											jQuery(".el3").select2({
												placeholder: "Select a user to atribute this to",

											});

											$('.el2sc.spinner-container').addClass('mpd-greentick');

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

			// End call function to control the new .el1 select box

		})

		return false;

	});

	

	



});