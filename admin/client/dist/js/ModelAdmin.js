(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.ModelAdmin', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssModelAdmin = mod.exports;
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
		$('.cms-content-tools #Form_SearchForm').entwine({
			onsubmit: function onsubmit(e) {
				this.trigger('beforeSubmit');
			}
		});

		$('.importSpec').entwine({
			onmatch: function onmatch() {
				this.find('div.details').hide();
				this.find('a.detailsLink').click(function () {
					$('#' + $(this).attr('href').replace(/.*#/, '')).slideToggle();
					return false;
				});

				this._super();
			},
			onunmatch: function onunmatch() {
				this._super();
			}
		});
	});
});