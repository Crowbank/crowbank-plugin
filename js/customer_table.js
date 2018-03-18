jQuery(document).ready(function(){
    jQuery("li.gf_readonly input").attr("readonly","readonly");

	jQuery("#inputfilter").keyup(function(){
		filter = new RegExp(jQuery(this).val(),'i');
		jQuery("#filterme tbody tr").filter(function(){
			jQuery(this).each(function(){
				found = false;
				jQuery(this).children().each(function(){
					content = jQuery(this).html();
					if(content.match(filter))
					{
						found = true
					}
				});
				if(!found)
				{
					jQuery(this).hide();
				}
				else
				{
					jQuery(this).show();
				}
			});
		});
	});
});