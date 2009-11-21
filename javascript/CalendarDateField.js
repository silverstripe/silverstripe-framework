(function($) {
	/**
	 * Formats a <input type="text"> field with a jQuery UI datepicker.
	 * 
	 * Requires: concrete, ui.datepicker, jquery.metadata
	 * 
	 * @author Ingo Schommer, SilverStripe Ltd.
	 */
	$('.calendardate').concrete('ss', {
		onmatch: function() {
			this.find('input').each(function() {
				var conf = $(this).metadata();
				if(conf.minDate) conf.minDate = new Date(Date.parse(conf.minDate));
				
				//
				$(this).datepicker(conf);
			});
		}
	})
}(jQuery));