(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.UploadField_select', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssUploadField_select = mod.exports;
	}
})(this, function (_jQuery) {
	'use strict';

	var _jQuery2 = _interopRequireDefault(_jQuery);

	function _interopRequireDefault(obj) {
		return obj && obj.__esModule ? obj : {
			default: obj
		};
	}

	_jQuery2.default.entwine('ss', function ($) {
		$('form.uploadfield-form .TreeDropdownField').entwine({
			onmatch: function onmatch() {
				this._super();

				var self = this;
				this.bind('change', function () {
					var fileList = self.closest('form').find('.grid-field');
					fileList.setState('ParentID', self.getValue());
					fileList.reload();
				});
			},
			onunmatch: function onunmatch() {
				this._super();
			}
		});
	});
});