(function($) {
	$.entwine('ss', function($) {
		/**
		 * Generates context-sensitive help displays from
		 * the "title" attribute on elements within a form field,
		 * usually a form field like <input />.
		 *
		 * Displays the help text underneath the field by default,
		 * but can optionally show as a tooltip via applying the 'cms-help-tooltip' class.
		 *
		 * Note that some fields don't have distinct focusable
		 * input fields (e.g. GridField), and aren't compatible
		 * with showing tooltips.
		 *
		 * Title content is assumed to be escaped HTML.
		 */
		$(".cms .cms-help").entwine({
			onmatch: function() {
				var self = this, field = this.closest('.field'),
					// supports nested elements, necessary due to serverside HTML restrictions
					el = this.is('[title]') ? this : this.find('[title]:first'),
					title = el.prop("title");

				if(title && !el.data('cms-help')) {
					title = $("<div/>").html(title).html(); // unescape
					if(self.is('.cms-help-tooltip')) {
						// Create tooltip, triggers on hover/focus
						el.tooltip({
							content: title
						});						
					} else {
						// Append to field container, fall back to appending to element itself
						if(!field.length) field = self;
						field.append($('<span class="help">' + title + '</span>'));
					}

					el.data('title', title).attr('title', '').data('cms-help', true);
				}

				this._super();
			}
		});

		/**
		 * Special case for chosen.js dropdown tooltips,
		 * need to transfer functionality from <select> field
		 * to chosen DOM structure.
		 */
		$(".cms .cms-help .chzn-container").entwine({
			onmatch: function() {
				var inputEl = this.siblings('.has-chzn'),
					title = inputEl.data("title");

				if(this.is('.cms-help-tooltip')) {
					this.tooltip({
						content: title
					});
				}

				this._super();
			}
		});
	});
}(jQuery));
