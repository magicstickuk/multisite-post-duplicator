jQuery(document).ready(function($) {

	jQuery("#createLink").click(function(e){

		e.preventDefault;
		jQuery('.create-link-ui').show('fast');

	});

	jQuery( "#create-link-site-select" ).change(function() {

		jQuery('.create-link-site-spin.mpd-spinner-container').show();
		jQuery('#create-link-post-select').remove();
		jQuery('#create-link-submit').remove();
		jQuery('.create-link-submit-spin.mpd-spinner-container').remove();

		data =  {
		 	action : 'mpd_create_link_post_list',
		 	site   : jQuery( "#create-link-site-select" ).val(),
		 	post_id : mpd_admin_vars.post_id
		};

		jQuery.post(ajaxurl,data,function(response) {

			jQuery('.create-link-site-spin.mpd-spinner-container').hide();
			jQuery( "#create-link-site-select" ).after(response);

			jQuery("#create-link-submit").click(function(e){
				e.preventDefault;
				
				jQuery('#create-link-submit').remove();
				jQuery('.create-link-submit-spin.mpd-spinner-container').show();

				datasubmit =  {
				 	action 			: 'mpd_create_link_submit',
				 	site   			: jQuery( "#create-link-site-select" ).val(),
				 	post_to_link 	: jQuery( "#create-link-post-select" ).val(),
				 	post_id 		: mpd_admin_vars.post_id
				};

				jQuery.post(ajaxurl,datasubmit,function(response) {

					jQuery('.create-link-submit-spin.mpd-spinner-container').hide();
					jQuery('.link-created-confirm').show();

				});

			});
		
		});

		

	});

	jQuery('#mpd_blogschecklist .mpd-site-checkbox input').change(function(){
		
  		var rcb = jQuery('.mpd-site-checkbox input:checked');
  		var cdl = jQuery('input[name="persist"]');

  		if(rcb.length >= 1){
  			cdl.prop("disabled", false);
  			cdl.parent().removeClass('disabled');
  		}else{
  			cdl.prop("disabled", true);
  			cdl.parent().addClass('disabled');
  		}
  	
  	});

	sb = jQuery('#publishing-action #publish');
	sV = sb.val();
	jQuery('#delete-action a').css('font-size','11px');
	sb.css('font-size','11px');


	if(jQuery("#multisite_linked_list_metabox").length > 0){

		sb.val(sV + " " + mpd_admin_vars.post_and_update);

	}
	
	jQuery('#mpd_blogschecklist input:checkbox').change(function() {

		if((jQuery("#mpd_blogschecklist input:checkbox:checked").length > 0 && jQuery("#multisite_linked_list_metabox").length > 0)){

   			sb.val(sV + " " + mpd_admin_vars.dup_and_update);

   		}else if (jQuery("#mpd_blogschecklist input:checkbox:checked").length > 0){

   			sb.val(sV + " " + mpd_admin_vars.post_and_dup);

		}else if(jQuery("#multisite_linked_list_metabox").length > 0){

			sb.val(sV + " " + mpd_admin_vars.post_and_update);

		}else{

   			sb.val(sV);

		}
		
	});
	
});

function accordionClick(classofbutton, classofContainer, speed){
	
	jQuery(classofbutton).on("click", function(event){
		event.preventDefault();
		accordionMe(classofContainer,speed);		    
	});

}
function accordionMe(classOfContainer, speed){

	jQuery(classOfContainer).toggle(speed);

}