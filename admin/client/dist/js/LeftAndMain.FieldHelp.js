(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.LeftAndMain.FieldHelp', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssLeftAndMainFieldHelp = mod.exports;
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
		$(".cms .field.cms-description-tooltip").entwine({
			onmatch: function onmatch() {
				this._super();

				var descriptionEl = this.find('.description'),
				    inputEl,
				    tooltipEl;
				if (descriptionEl.length) {
					this.attr('title', descriptionEl.text()).tooltip({ content: descriptionEl.html() });
					descriptionEl.remove();
				}
			}
		});

		$(".cms .field.cms-description-tooltip :input").entwine({
			onfocusin: function onfocusin(e) {
				this.closest('.field').tooltip('open');
			},
			onfocusout: function onfocusout(e) {
				this.closest('.field').tooltip('close');
			}
		});
	});
});