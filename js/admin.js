jQuery(document).ready(function($) {
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