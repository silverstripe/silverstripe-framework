import $ from 'jQuery';

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

$('.ss-uploadfield-view-allowed-extensions .toggle').entwine({
	onclick: function(e) {
		var allowedExt = this.closest('.ss-uploadfield-view-allowed-extensions'),
			minHeightVal = this.closest('.ui-tabs-panel').height() + 20;

		allowedExt.toggleClass('active');
		allowedExt.find('.toggle-content').css('minHeight', minHeightVal);
	}
});
