(function($) {
	$.entwine('ss', function($) {
		/**
		 * Takes form fields with a title attribute, extracts it, and displays
		 * it as inline help text below the field.
		 */
		$(".cms form .field .middleColumn > [title]").entwine({
			onmatch: function() {
				var title = this.prop("title");

				if(title && title.length) {
					var span = $("<span></span>", {
						"class": "help",
						"text":  title
					});

					this.closest(".field").append(span);
					this.removeProp("title");
				}

				this._super();
			}
		});
	});
}(jQuery));
