(function($) {
	$('.ss-assetuploadfield').entwine({
		onmatch: function() {
			this._super();
			
			// Hide the "second step" part until we're actually uploading
			this.find('.ss-uploadfield-editandorganize').hide();
		},
		onunmatch: function() {
			this._super();
		},
		onfileuploadadd: function(e) {
			this.find('.ss-uploadfield-editandorganize').show();
		},
		onfileuploadstart: function(e) {
			this.find('.ss-uploadfield-editandorganize').show();
		}
	});
	$('.ss-uploadfield-view-allowed-extensions').entwine({
		onmatch: function() {
			this.find('.description .toggle-content').hide();
			this._super();
		}
	});

	$('.ss-uploadfield-view-allowed-extensions .toggle').entwine({
		onclick: function(e) {
			jQuery(this).closest('.description').find('.toggle-content').toggle();
			return false;
		}
	});
}(jQuery));
