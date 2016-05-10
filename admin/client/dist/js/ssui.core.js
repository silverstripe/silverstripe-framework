(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.ssui.core', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssSsuiCore = mod.exports;
	}
})(this, function (_jQuery) {
	'use strict';

	var _jQuery2 = _interopRequireDefault(_jQuery);

	function _interopRequireDefault(obj) {
		return obj && obj.__esModule ? obj : {
			default: obj
		};
	}

	_jQuery2.default.widget('ssui.button', _jQuery2.default.ui.button, {
		options: {
			alternate: {
				icon: null,
				text: null
			},
			showingAlternate: false
		},

		toggleAlternate: function toggleAlternate() {
			if (this._trigger('ontogglealternate') === false) return;

			if (!this.options.alternate.icon && !this.options.alternate.text) return;

			this.options.showingAlternate = !this.options.showingAlternate;
			this.refresh();
		},

		_refreshAlternate: function _refreshAlternate() {
			this._trigger('beforerefreshalternate');

			if (!this.options.alternate.icon && !this.options.alternate.text) return;

			if (this.options.showingAlternate) {
				this.element.find('.ui-button-icon-primary').hide();
				this.element.find('.ui-button-text').hide();
				this.element.find('.ui-button-icon-alternate').show();
				this.element.find('.ui-button-text-alternate').show();
			} else {
				this.element.find('.ui-button-icon-primary').show();
				this.element.find('.ui-button-text').show();
				this.element.find('.ui-button-icon-alternate').hide();
				this.element.find('.ui-button-text-alternate').hide();
			}

			this._trigger('afterrefreshalternate');
		},

		_resetButton: function _resetButton() {
			var iconPrimary = this.element.data('icon-primary'),
			    iconSecondary = this.element.data('icon-secondary');

			if (!iconPrimary) iconPrimary = this.element.data('icon');

			if (iconPrimary) this.options.icons.primary = 'btn-icon-' + iconPrimary;
			if (iconSecondary) this.options.icons.secondary = 'btn-icon-' + iconSecondary;

			_jQuery2.default.ui.button.prototype._resetButton.call(this);

			if (!this.options.alternate.text) {
				this.options.alternate.text = this.element.data('text-alternate');
			}
			if (!this.options.alternate.icon) {
				this.options.alternate.icon = this.element.data('icon-alternate');
			}
			if (!this.options.showingAlternate) {
				this.options.showingAlternate = this.element.hasClass('ss-ui-alternate');
			}

			if (this.options.alternate.icon) {
				this.buttonElement.append("<span class='ui-button-icon-alternate ui-button-icon-primary ui-icon btn-icon-" + this.options.alternate.icon + "'></span>");
			}
			if (this.options.alternate.text) {
				this.buttonElement.append("<span class='ui-button-text-alternate ui-button-text'>" + this.options.alternate.text + "</span>");
			}

			this._refreshAlternate();
		},

		refresh: function refresh() {
			_jQuery2.default.ui.button.prototype.refresh.call(this);

			this._refreshAlternate();
		},

		destroy: function destroy() {
			this.element.find('.ui-button-text-alternate').remove();
			this.element.find('.ui-button-icon-alternate').remove();

			_jQuery2.default.ui.button.prototype.destroy.call(this);
		}
	});

	_jQuery2.default.widget("ssui.ssdialog", _jQuery2.default.ui.dialog, {
		options: {
			iframeUrl: '',
			reloadOnOpen: true,
			dialogExtraClass: '',

			modal: true,
			bgiframe: true,
			autoOpen: false,
			autoPosition: true,
			minWidth: 500,
			maxWidth: 800,
			minHeight: 300,
			maxHeight: 700,
			widthRatio: 0.8,
			heightRatio: 0.8,
			resizable: false
		},
		_create: function _create() {
			_jQuery2.default.ui.dialog.prototype._create.call(this);

			var self = this;

			var iframe = (0, _jQuery2.default)('<iframe marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto"></iframe>');
			iframe.bind('load', function (e) {
				if ((0, _jQuery2.default)(this).attr('src') == 'about:blank') return;

				iframe.addClass('loaded').show();
				self._resizeIframe();
				self.uiDialog.removeClass('loading');
			}).hide();

			if (this.options.dialogExtraClass) this.uiDialog.addClass(this.options.dialogExtraClass);
			this.element.append(iframe);

			if (this.options.iframeUrl) this.element.css('overflow', 'hidden');
		},
		open: function open() {
			_jQuery2.default.ui.dialog.prototype.open.call(this);

			var self = this,
			    iframe = this.element.children('iframe');

			if (this.options.iframeUrl && (!iframe.hasClass('loaded') || this.options.reloadOnOpen)) {
				iframe.hide();
				iframe.attr('src', this.options.iframeUrl);
				this.uiDialog.addClass('loading');
			}

			(0, _jQuery2.default)(window).bind('resize.ssdialog', function () {
				self._resizeIframe();
			});
		},
		close: function close() {
			_jQuery2.default.ui.dialog.prototype.close.call(this);

			this.uiDialog.unbind('resize.ssdialog');
			(0, _jQuery2.default)(window).unbind('resize.ssdialog');
		},
		_resizeIframe: function _resizeIframe() {
			var opts = {},
			    newWidth,
			    newHeight,
			    iframe = this.element.children('iframe');;
			if (this.options.widthRatio) {
				newWidth = (0, _jQuery2.default)(window).width() * this.options.widthRatio;
				if (this.options.minWidth && newWidth < this.options.minWidth) {
					opts.width = this.options.minWidth;
				} else if (this.options.maxWidth && newWidth > this.options.maxWidth) {
					opts.width = this.options.maxWidth;
				} else {
					opts.width = newWidth;
				}
			}
			if (this.options.heightRatio) {
				newHeight = (0, _jQuery2.default)(window).height() * this.options.heightRatio;
				if (this.options.minHeight && newHeight < this.options.minHeight) {
					opts.height = this.options.minHeight;
				} else if (this.options.maxHeight && newHeight > this.options.maxHeight) {
					opts.height = this.options.maxHeight;
				} else {
					opts.height = newHeight;
				}
			}

			if (!jQuery.isEmptyObject(opts)) {
				this._setOptions(opts);

				iframe.attr('width', opts.width - parseFloat(this.element.css('paddingLeft')) - parseFloat(this.element.css('paddingRight')));
				iframe.attr('height', opts.height - parseFloat(this.element.css('paddingTop')) - parseFloat(this.element.css('paddingBottom')));

				if (this.options.autoPosition) {
					this._setOption("position", this.options.position);
				}
			}
		}
	});

	_jQuery2.default.widget("ssui.titlebar", {
		_create: function _create() {
			this.originalTitle = this.element.attr('title');

			var self = this;
			var options = this.options;

			var title = options.title || this.originalTitle || '&nbsp;';
			var titleId = _jQuery2.default.ui.dialog.getTitleId(this.element);

			this.element.parent().addClass('ui-dialog');

			var uiDialogTitlebar = this.element.addClass('ui-dialog-titlebar ' + 'ui-widget-header ' + 'ui-corner-all ' + 'ui-helper-clearfix');

			if (options.closeButton) {
				var uiDialogTitlebarClose = (0, _jQuery2.default)('<a href="#"/>').addClass('ui-dialog-titlebar-close ' + 'ui-corner-all').attr('role', 'button').hover(function () {
					uiDialogTitlebarClose.addClass('ui-state-hover');
				}, function () {
					uiDialogTitlebarClose.removeClass('ui-state-hover');
				}).focus(function () {
					uiDialogTitlebarClose.addClass('ui-state-focus');
				}).blur(function () {
					uiDialogTitlebarClose.removeClass('ui-state-focus');
				}).mousedown(function (ev) {
					ev.stopPropagation();
				}).appendTo(uiDialogTitlebar);

				var uiDialogTitlebarCloseText = (this.uiDialogTitlebarCloseText = (0, _jQuery2.default)('<span/>')).addClass('ui-icon ' + 'ui-icon-closethick').text(options.closeText).appendTo(uiDialogTitlebarClose);
			}

			var uiDialogTitle = (0, _jQuery2.default)('<span/>').addClass('ui-dialog-title').attr('id', titleId).html(title).prependTo(uiDialogTitlebar);

			uiDialogTitlebar.find("*").add(uiDialogTitlebar).disableSelection();
		},

		destroy: function destroy() {
			this.element.unbind('.dialog').removeData('dialog').removeClass('ui-dialog-content ui-widget-content').hide().appendTo('body');

			this.originalTitle && this.element.attr('title', this.originalTitle);
		}
	});

	_jQuery2.default.extend(_jQuery2.default.ssui.titlebar, {
		version: "0.0.1",
		options: {
			title: '',
			closeButton: false,
			closeText: 'close'
		},

		uuid: 0,

		getTitleId: function getTitleId($el) {
			return 'ui-dialog-title-' + ($el.attr('id') || ++this.uuid);
		}
	});
});