



jQuery(document).ready(function($) {

	mpd_do_create_link_button();

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

	mpd_do_create_link_ui();
	
});

function mpd_do_create_link_button(){
	jQuery("#createLink").on('click', function(e){
		window.markup = jQuery(this).closest('.inside').html();
		e.preventDefault;
		jQuery('.create-link-ui').show('fast');
	});
	
	jQuery('.link-created-create-another').click(function(e){
		e.preventDefault;
		jQuery(this).closest('.inside').hide('slow').empty().append(window.markup);
		jQuery('.inside').show('slow', mpd_do_create_link_ui());
		mpd_do_create_link_button();
	});
}
function mpd_do_create_link_ui(){

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
				
				if(jQuery('#create-link-post-select').val() != -1){

					jQuery('#create-link-submit').remove();
					jQuery('.create-link-submit-spin.mpd-spinner-container').show();

					datasubmit =  {
					 	action 		: 'mpd_create_link_submit',
					 	site   		: jQuery( "#create-link-site-select" ).val(),
					 	post_to_link 	: jQuery( "#create-link-post-select" ).val(),
					 	post_id 		: mpd_admin_vars.post_id
					};

					jQuery.post(ajaxurl,datasubmit,function(response) {
						
						if(response === '1'){
							jQuery('.create-link-submit-spin.mpd-spinner-container').hide();
							jQuery('.link-created-confirm').show();
							jQuery('.link-created-create-another').show('slow');
							
						}

						
					});

				}
				

			});
		
		});

		

	});
}

function accordionClick(classofbutton, classofContainer, speed){
	
	jQuery(classofbutton).on("click", function(event){
		event.preventDefault();
		accordionMe(classofContainer,speed);		    
	});

}
function accordionMe(classOfContainer, speed){

	jQuery(classOfContainer).toggle(speed);

}