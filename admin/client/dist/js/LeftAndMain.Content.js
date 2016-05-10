(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.LeftAndMain.Content', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssLeftAndMainContent = mod.exports;
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
		$('.cms-content').entwine({

			onadd: function onadd() {
				var self = this;

				this.find('.cms-tabset').redrawTabs();
				this._super();
			},

			redraw: function redraw() {
				if (window.debug) console.log('redraw', this.attr('class'), this.get(0));

				this.add(this.find('.cms-tabset')).redrawTabs();
				this.find('.cms-content-header').redraw();
				this.find('.cms-content-actions').redraw();
			}
		});

		$('.cms-content .cms-tree').entwine({
			onadd: function onadd() {
				var self = this;

				this._super();

				this.bind('select_node.jstree', function (e, data) {
					var node = data.rslt.obj,
					    loadedNodeID = self.find(':input[name=ID]').val(),
					    origEvent = data.args[2],
					    container = $('.cms-container');

					if (!origEvent) {
						return false;
					}

					if ($(node).hasClass('disabled')) return false;

					if ($(node).data('id') == loadedNodeID) return;

					var url = $(node).find('a:first').attr('href');
					if (url && url != '#') {
						url = url.split('?')[0];

						self.jstree('deselect_all');
						self.jstree('uncheck_all');

						if ($.path.isExternal($(node).find('a:first'))) url = url = $.path.makeUrlAbsolute(url, $('base').attr('href'));

						if (document.location.search) url = $.path.addSearchParams(url, document.location.search.replace(/^\?/, ''));

						container.loadPanel(url);
					} else {
						self.removeForm();
					}
				});
			}
		});

		$('.cms-content .cms-content-fields').entwine({
			redraw: function redraw() {
				if (window.debug) console.log('redraw', this.attr('class'), this.get(0));
			}
		});

		$('.cms-content .cms-content-header, .cms-content .cms-content-actions').entwine({
			redraw: function redraw() {
				if (window.debug) console.log('redraw', this.attr('class'), this.get(0));

				this.height('auto');
				this.height(this.innerHeight() - this.css('padding-top') - this.css('padding-bottom'));
			}
		});
	});
});