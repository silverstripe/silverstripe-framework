import $ from 'jQuery';

// entwine also required, but can't be included more than once without error
require('../../../thirdparty/jquery-ui/jquery-ui.js');

$.fn.extend({
	ssDatepicker: function(opts) {
		return $(this).each(function() {
      // disabled, readonly or already applied
			if($(this).prop('disabled') || $(this).prop('readonly') || $(this).data('datepicker')) return;

			$(this).siblings("button").addClass("ui-icon ui-icon-calendar");

			var holder = $(this).closest('.field.date'),
				config = $.extend(opts || {}, $(this).data(), $(this).data('jqueryuiconfig'), {});
			if(!config.showcalendar) return;

			if(config.locale && $.datepicker.regional[config.locale]) {
				config = $.extend(config, $.datepicker.regional[config.locale], {});
			}

			if(config.min) config.minDate = $.datepicker.parseDate('yy-mm-dd', config.min);
			if(config.max) config.maxDate = $.datepicker.parseDate('yy-mm-dd', config.max);

			// Initialize and open a datepicker
			// live() doesn't have "onmatch", and jQuery.entwine is a bit too heavyweight for this, so we need to do this onclick.
			config.dateFormat = config.jquerydateformat;
			$(this).datepicker(config);
		});
	}
});

$(document).on("click", ".field.date input.text,input.text.date", function() {
	$(this).ssDatepicker();

	if($(this).data('datepicker')) {
		$(this).datepicker('show');
	}
});
