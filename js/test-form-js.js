jQuery(document).ready(function(){
	gform.addFilter( 'gform_datepicker_options_pre_init', function( optionsObj, formId, fieldId ) {
	    if ( formId == 21 && fieldId == 9 ) {
	        optionsObj.minDate = 0;
	        optionsObj.onClose = function (dateText, inst) {
	             jQuery('#input_21_10').datepicker('option', 'minDate', dateText).datepicker('setDate', dateText);
	        };
	    }
	    return optionsObj;
	});
	
	jQuery( "#input_21_10" ).change(function(){
		var d = jQuery( "#input_21_10" ).datepicker( "getDate" );
		var wd = d.getDay();
		
		if (wd == 0) {
			jQuery( "#choice_21_8_1" ).attr('disabled', true);
		} else {
			jQuery( "#choice_21_8_1" ).attr('disabled', false);
		}
    });
});
