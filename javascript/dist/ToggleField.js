'use strict';

(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.ToggleField', ['./jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('./jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssToggleField = mod.exports;
	}
})(this, function (_jQuery) {
	var _jQuery2 = _interopRequireDefault(_jQuery);

	function _interopRequireDefault(obj) {
		return obj && obj.__esModule ? obj : {
			default: obj
		};
	}

	var field = (0, _jQuery2.default)('div.toggleField');

	if (field.hasClass('startClosed')) {
		field.find('div.contentMore').hide();
		field.find('div.contentLess').show();
	}

	(0, _jQuery2.default)('div.toggleField .triggerLess, div.toggleField .triggerMore').click(function () {
		field.find('div.contentMore').toggle();
		field.find('div.contentLess').toggle();
	});
});