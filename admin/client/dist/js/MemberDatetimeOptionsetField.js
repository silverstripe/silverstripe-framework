(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.MemberDatetimeOptionsetField', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssMemberDatetimeOptionsetField = mod.exports;
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

		$('.memberdatetimeoptionset').entwine({
			onmatch: function onmatch() {
				this.find('.description .toggle-content').hide();
				this._super();
			}
		});

		$('.memberdatetimeoptionset .toggle').entwine({
			onclick: function onclick(e) {
				jQuery(this).closest('.description').find('.toggle-content').toggle();
				return false;
			}
		});
	});
});