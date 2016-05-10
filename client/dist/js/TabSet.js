(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.TabSet', ['./jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('./jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssTabSet = mod.exports;
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
		$('.ss-tabset').entwine({
			IgnoreTabState: false,

			onadd: function onadd() {
				var hash = window.location.hash;

				this.redrawTabs();

				if (hash !== '') {
					this.openTabFromURL(hash);
				}

				this._super();
			},

			onremove: function onremove() {
				if (this.data('tabs')) this.tabs('destroy');
				this._super();
			},

			redrawTabs: function redrawTabs() {
				this.rewriteHashlinks();
				this.tabs();
			},

			openTabFromURL: function openTabFromURL(hash) {
				var $trigger;

				$.each(this.find('.cms-panel-link'), function () {
					if (this.href.indexOf(hash) !== -1 && $(hash).length === 1) {
						$trigger = $(this);
						return false;
					}
				});

				if ($trigger === void 0) {
					return;
				}

				$(window).one('ajaxComplete', function () {
					$trigger.click();
				});
			},

			rewriteHashlinks: function rewriteHashlinks() {
				$(this).find('ul a').each(function () {
					if (!$(this).attr('href')) return;

					var matches = $(this).attr('href').match(/#.*/);
					if (!matches) return;
					$(this).attr('href', document.location.href.replace(/#.*/, '') + matches[0]);
				});
			}
		});
	});
});