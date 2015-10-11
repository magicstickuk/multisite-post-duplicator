jQuery(document).ready(function() {
  
  jQuery('input[type="checkbox"]').parent().parent().hide();

  jQuery('#mpd_radio_choice_wrap .mdp_radio').change(function() {
  		
  		if (jQuery(this).val() == 'some') {
  			jQuery('input[type="checkbox"]').parent().parent().show('slow');
  		} else if(jQuery(this).val() != 'some'){
  			jQuery('input[type="checkbox"]').parent().parent().hide('slow');
  		};
  		
  });

});