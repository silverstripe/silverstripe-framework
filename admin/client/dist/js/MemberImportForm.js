(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.MemberImportForm', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssMemberImportForm = mod.exports;
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
		$('.import-form .advanced').entwine({
			onmatch: function onmatch() {
				this._super();

				this.hide();
			},
			onunmatch: function onunmatch() {
				this._super();
			}
		});

		$('.import-form a.toggle-advanced').entwine({
			onclick: function onclick(e) {
				this.parents('form:eq(0)').find('.advanced').toggle();
				return false;
			}
		});
	});
});