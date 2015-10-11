jQuery(document).ready(function() {
  
  if(!jQuery('#meta_box_show_choice_some').is(':checked') ){
      jQuery('input[type="checkbox"]').parent().parent().hide();
  }
  jQuery('#mpd_radio_choice_wrap .mdp_radio').change(function() {
  		
  		if (jQuery(this).val() == 'some') {
  			jQuery('input[type="checkbox"]').parent().parent().show('slow');
  		} else if(jQuery(this).val() != 'some'){
  			jQuery('input[type="checkbox"]').parent().parent().hide('slow');
  		};
  		
  });

});