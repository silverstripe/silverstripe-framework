(function($) {
	$('.ss-assetuploadfield').entwine({
		onmatch: function() {
			this._super();
			
			// Hide the "second step" part until we're actually uploading
			this.find('.ss-uploadfield-editandorganize').hide();
		},
		onfileuploadstart: function(e) {
			this.find('.ss-uploadfield-editandorganize').show();
		}

	});
}(jQuery));