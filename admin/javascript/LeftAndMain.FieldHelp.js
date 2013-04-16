(function($) {
	$.entwine('ss', function($) {
		/**
		 * Converts an inline field description into a tooltip
		 * which is shown on hover over any part of the field container,
		 * as well as when focusing into an input element within the field container.
		 *
		 * Note that some fields don't have distinct focusable
		 * input fields (e.g. GridField), and aren't compatible
		 * with showing tooltips.
		 */
		$(".cms .field.cms-description-tooltip").entwine({
			onmatch: function() {
				var descriptionEl = this.find('.description'), inputEl, tooltipEl;
				if(descriptionEl.length) {
					this
						// TODO Remove title setting, shouldn't be necessary
						.attr('title', descriptionEl.text())
						.tooltip({content: descriptionEl.html()});
					descriptionEl.remove();
				}
			}
					});

		$(".cms .field.cms-description-tooltip :input").entwine({
			onfocusin: function(e) {
				this.closest('.field').tooltip('open');
			},
			onfocusout: function(e) {
				this.closest('.field').tooltip('close');
				}
		});

		});
}(jQuery));
