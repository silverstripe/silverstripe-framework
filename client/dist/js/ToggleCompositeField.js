(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.ToggleCompositeField', ['./jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('./jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssToggleCompositeField = mod.exports;
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
		$('.ss-toggle').entwine({
			onadd: function onadd() {
				this._super();

				this.accordion({
					heightStyle: "content",
					collapsible: true,
					active: this.hasClass("ss-toggle-start-closed") ? false : 0
				});
			},
			onremove: function onremove() {
				if (this.data('accordion')) this.accordion('destroy');
				this._super();
			},

			getTabSet: function getTabSet() {
				return this.closest(".ss-tabset");
			},

			fromTabSet: {
				ontabsshow: function ontabsshow() {
					this.accordion("resize");
				}
			}
		});
	});
});