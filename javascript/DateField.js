(function($) {
	/**
	 * Initialize and open a datepicker 
	 * live() doesn't have "onmatch", and jQuery.entwine is a bit too 
	 * heavyweight for this, so we need to do this onclick.
	 */
	var fields = $('.field.date input.text');

	fields.siblings("button").addClass("ui-icon ui-icon-calendar");
	
	fields.live('click', function() {
		var holder = $(this).parents('.field.date:first'), config = holder.metadata({type: 'class'});
		if(!config.showcalendar) return;
		
		if(config.locale && $.datepicker.regional[config.locale]) {
			config = $.extend(config, $.datepicker.regional[config.locale], {});
		}

		$(this).datepicker(config);
		$(this).datepicker('show');
	});
}(jQuery));