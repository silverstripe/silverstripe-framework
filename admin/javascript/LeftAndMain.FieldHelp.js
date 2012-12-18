(function($) {
	$.entwine('ss', function($) {
		/**
		 * Takes form fields with a title attribute, extracts it, and displays
		 * it as inline help text below the field.
		 */
		$(".cms form .field .middleColumn > [title]").entwine({
			onmatch: function() {
				
				var title = this.prop("title");
				var field = this.closest(".field");

				if(title && title.length && field.has('.help').length == 0) {
					var span = $("<span></span>", {
						"class": "help",
						"html":  title
					});

					field.append(span);
					this.removeProp("title");
				}

				this._super();
			}
		});
	});
}(jQuery));
