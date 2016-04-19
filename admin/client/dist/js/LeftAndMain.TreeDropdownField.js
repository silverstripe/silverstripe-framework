(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.LeftAndMain.TreeDropdownField', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssLeftAndMainTreeDropdownField = mod.exports;
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
		$('.TreeDropdownField').entwine({
			'from .cms-container form': {
				onaftersubmitform: function onaftersubmitform(e) {
					this.find('.tree-holder').empty();
					this._super();
				}
			}
		});
	});
});