jQuery(document).ready(function() {
  
  var cb = 'input[type="checkbox"].posttypecb';
  if(!jQuery('#meta_box_show_choice_some').is(':checked') ){
      jQuery(cb).parent().parent().hide();
  }
  jQuery('#mpd_radio_choice_wrap .mdp_radio').change(function() {
  		
  		if (jQuery(this).val() == 'some') {
  			jQuery(cb).parent().parent().show('slow');
  		}else{
  			jQuery(cb).parent().parent().hide('slow');
  		};
  		
  });

  jQuery('.posttypecb').parent().parent().css('border-bottom','0');
  jQuery('.posttypecb').last().parent().parent().css('border-bottom','1px solid #cdcdcd');

});