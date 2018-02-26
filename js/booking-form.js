function check_for_sunday (date_field, time_field) {
	var d = date_field.datepicker( "getDate" );
	if (d) {
		var wd = d.getDay();
		
		if (wd == 0) {
			jQuery( time_field ).attr('disabled', true);
			jQuery( time_field ).attr('checked', false);
		} else {
			jQuery( time_field ).attr('disabled', false);
		}
	}
}

jQuery(document).ready(function(){
	gform.addFilter( 'gform_datepicker_options_pre_init', function( optionsObj, formId, fieldId ) {
	    if ( formId == 25 && fieldId == 14 ) {
	        optionsObj.minDate = 0;
	        optionsObj.onClose = function (dateText, inst) {
	             jQuery('#input_25_15').datepicker('option', 'minDate', dateText); /*.datepicker('setDate', dateText); */
	        };
	       /* check_for_sunday(optionsObj, "#choice_19_6_1"); */
	    }
	    
	    if ( formId == 25 && fieldId == 15 ) {
	        /* check_for_sunday(optionsObj, "#choice_19_7_1"); */
	    }	    
	    return optionsObj;
	});
	
	jQuery( "#input_25_14" ).change(function(){
		check_for_sunday(jQuery( "#input_25_14" ), "#choice_25_6_1");
    });

	jQuery( "#input_25_15" ).change(function(){
		check_for_sunday(jQuery( "#input_25_15" ), "#choice_25_7_1");
    });
});
