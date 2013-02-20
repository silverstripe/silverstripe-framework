(function($){
	$.entwine('ss', function($){
		/**
		 * IE8, IE9 and Opera don't support maxlength on textarea, this is a JS fallback.
		 */
		$('textarea[maxlength]').entwine({
			onkeyup: function() {
				this.truncateText();
				this._super();
			},
			onpaste: function() {
				// Short pause to wait for paste to complete
				var that = this;
				setTimeout(function() {
					that.truncateText();
				}, 50);

				this._super();
			},
			truncateText: function() {
				var maxlength = $(this).attr('maxlength');
				var value = $(this).val();
				if (value.length > maxlength) {
					$(this).val(value.slice(0, maxlength));
				}
			}
		});
	});
})(jQuery);
