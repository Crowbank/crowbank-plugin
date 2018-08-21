jQuery(document).ready(function(){
    /* apply only to a input with a class of gf_readonly */
    jQuery("li.gf_readonly input").attr("readonly","readonly");
    
    
    jQuery('div.crowbank_tabset').crowbank_tabset();
    
    console.log('ready from crowbank.js has run');
});

/* Tabset Function ---------------------------------- */
(function ($) {
$.fn.crowbank_tabset = function () {
    var $tabsets = $(this);
    $tabsets.each(function (i) {
        var $tabs = $('li.crowbank_tab a', this);
        $tabs.click(function (e) {
            var $this = $(this);
                panels = $.map($tabs, function (val, i) {
                    return $(val).attr('href');
                });
            $(panels.join(',')).hide();
            $tabs.removeClass('selected');
            $this.addClass('selected').blur();
            $($this.attr('href')).show();
            e.preventDefault();
            return false;
        });
    });
};
})(jQuery);

