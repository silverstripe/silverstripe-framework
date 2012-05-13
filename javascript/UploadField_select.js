(function($) {
	$.entwine('ss', function($) {
		// Install the directory selection handler
		$('form.uploadfield-form #ParentID .TreeDropdownField').entwine({
			onmatch: function() {
				this._super();

				var self = this;
				this.bind('change', function() {
					// Display the contents of the folder in the listing field.
					var fileList = self.closest('form').find('.ss-gridfield');
					fileList.setState('ParentID', self.getValue());
					fileList.reload();
				});
			},
			onunmatch: function() {
				this._super();
			}
		});
	});
})(jQuery);
