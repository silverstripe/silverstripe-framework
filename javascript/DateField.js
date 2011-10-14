(function($) {

	$.fn.extend({
		ssDatepicker: function(opts) {
			return $(this).each(function() {
				if($(this).data('datepicker')) return; // already applied
				
				var holder = $(this).parents('.field.date:first'), 
					config = $.extend(opts || {}, $(this).metadata({type: 'class'}), {});
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
			});
		}
	});

	if(typeof($.entwine) != 'undefined') {
		$('.field.date input.text').entwine({
			onmatch: function() {
				this.ssDatepicker();
				this._super();
			}
		});
	} else if(typeof(Behaviour) != 'undefined') {
		Behaviour.register({
			'.field.date input.text': {
				initialize: function() {
					$(this).ssDatepicker();
				}
			}
		});
	} else {
		$('.field.date input.text').live('click', function() {
			$(this).ssDatepicker();
			$(this).datepicker('show');
		});
	}
	
}(jQuery));