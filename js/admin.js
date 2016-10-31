jQuery(document).ready(function($) {
	sb = jQuery('#publishing-action #publish');
	sV = sb.val();
	jQuery('#delete-action a').css('font-size','11px');
	sb.css('font-size','11px');


	if(jQuery("#multisite_linked_list_metabox").length > 0){
		sb.val(sV + " Post & Update Linked Posts");
	}

	jQuery('#mpd_blogschecklist input:checkbox').change(function() {

		if(($("#mpd_blogschecklist input:checkbox:checked").length > 0 && jQuery("#multisite_linked_list_metabox").length > 0)){

   			sb.val(sV + " & Duplicate & Update Linked Posts");

   		}else if ($("#mpd_blogschecklist input:checkbox:checked").length > 0){

   			sb.val(sV + " Post & Duplicate");
		}else if(jQuery("#multisite_linked_list_metabox").length > 0){

			sb.val(sV + " Post & Update Linked Posts");

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