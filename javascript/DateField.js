(function($) {
	$('.field.date input.text').live('click', function() {
		var holder = $(this).parents('.field.date:first'), config = $(this).metadata({type: 'class'});
		if(!config.showcalendar) return;
		
		if(config.locale && $.datepicker.regional[config.locale]) {
			config = $.extend(config, $.datepicker.regional[config.locale], {});
		}
		
		if(config.min) config.minDate = $.datepicker.parseDate('yy-mm-dd', config.min);
		if(config.max) config.maxDate = $.datepicker.parseDate('yy-mm-dd', config.max);
		
		// Initialize and open a datepicker 
		// live() doesn't have "onmatch", and jQuery.entwine is a bit too heavyweight for this, so we need to do this onclick.
		config.dateFormat = config.jqueryDateformat;
		$(this).datepicker(config);
		$(this).datepicker('show');
	});
}(jQuery));