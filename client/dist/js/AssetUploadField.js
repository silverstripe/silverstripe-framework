(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.AssetUploadField', ['./jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('./jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssAssetUploadField = mod.exports;
	}
})(this, function (_jQuery) {
	'use strict';

	var _jQuery2 = _interopRequireDefault(_jQuery);

	function _interopRequireDefault(obj) {
		return obj && obj.__esModule ? obj : {
			default: obj
		};
	}

	(0, _jQuery2.default)('.ss-assetuploadfield').entwine({
		onmatch: function onmatch() {
			this._super();

			this.find('.ss-uploadfield-editandorganize').hide();
		},
		onunmatch: function onunmatch() {
			this._super();
		},
		onfileuploadadd: function onfileuploadadd(e) {
			this.find('.ss-uploadfield-editandorganize').show();
		},
		onfileuploadstart: function onfileuploadstart(e) {
			this.find('.ss-uploadfield-editandorganize').show();
		}
	});

	(0, _jQuery2.default)('.ss-uploadfield-view-allowed-extensions .toggle').entwine({
		onclick: function onclick(e) {
			var allowedExt = this.closest('.ss-uploadfield-view-allowed-extensions'),
			    minHeightVal = this.closest('.ui-tabs-panel').height() + 20;

			allowedExt.toggleClass('active');
			allowedExt.find('.toggle-content').css('minHeight', minHeightVal);
		}
	});
});