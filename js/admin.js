jQuery(document).ready(function($) {
	
	sb = jQuery('#publishing-action #publish');
	sV = sb.val();
	jQuery('#delete-action a').css('font-size','11px');
	sb.css('font-size','12px');
	jQuery('#mpd_blogschecklist input:checkbox').change(function() {
		
		if ($("#mpd_blogschecklist input:checkbox:checked").length > 0){

   			sb.val(sV + " & Copy");

		}else{

   			sb.val(sV);

		}
		
	});
	
});