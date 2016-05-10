(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.SelectionGroup', ['./jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('./jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssSelectionGroup = mod.exports;
	}
})(this, function (_jQuery) {
	'use strict';

	var _jQuery2 = _interopRequireDefault(_jQuery);

	function _interopRequireDefault(obj) {
		return obj && obj.__esModule ? obj : {
			default: obj
		};
	}

	(0, _jQuery2.default)(document).ready(function () {
		(0, _jQuery2.default)('ul.SelectionGroup input.selector').live('click', function () {
			var li = (0, _jQuery2.default)(this).closest('li');
			li.addClass('selected');

			var prev = li.prevAll('li.selected');
			if (prev.length) prev.removeClass('selected');
			var next = li.nextAll('li.selected');
			if (next.length) next.removeClass('selected');

			(0, _jQuery2.default)(this).focus();
		});
	});
});