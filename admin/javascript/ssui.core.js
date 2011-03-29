(function($) {
	$.widget("ssui.titlebar", {
		_create: function() {
			this.originalTitle = this.element.attr('title');

			var self = this;
			var options = this.options;

			var title = options.title || this.originalTitle || '&nbsp;';
			var titleId = $.ui.dialog.getTitleId(this.element);

			this.element.parent().addClass('ui-dialog');

			var uiDialogTitlebar = this.element.
				addClass(
					'ui-dialog-titlebar ' +
					'ui-widget-header ' +
					'ui-corner-all ' +
					'ui-helper-clearfix'
				);

				// By default, the

				if(options.closeButton) {
					var uiDialogTitlebarClose = $('<a href="#"/>')
						.addClass(
							'ui-dialog-titlebar-close ' +
							'ui-corner-all'
						)
						.attr('role', 'button')
						.hover(
							function() {
								uiDialogTitlebarClose.addClass('ui-state-hover');
							},
							function() {
								uiDialogTitlebarClose.removeClass('ui-state-hover');
							}
						)
						.focus(function() {
							uiDialogTitlebarClose.addClass('ui-state-focus');
						})
						.blur(function() {
							uiDialogTitlebarClose.removeClass('ui-state-focus');
						})
						.mousedown(function(ev) {
							ev.stopPropagation();
						})
						.appendTo(uiDialogTitlebar);

					var uiDialogTitlebarCloseText = (this.uiDialogTitlebarCloseText = $('<span/>'))
						.addClass(
							'ui-icon ' +
							'ui-icon-closethick'
						)
						.text(options.closeText)
						.appendTo(uiDialogTitlebarClose);
				}

				var uiDialogTitle = $('<span/>')
					.addClass('ui-dialog-title')
					.attr('id', titleId)
					.html(title)
					.prependTo(uiDialogTitlebar);

				uiDialogTitlebar.find("*").add(uiDialogTitlebar).disableSelection();
		},

		destroy: function() {
			this.element
				.unbind('.dialog')
				.removeData('dialog')
				.removeClass('ui-dialog-content ui-widget-content')
				.hide().appendTo('body');

			(this.originalTitle && this.element.attr('title', this.originalTitle));
		}
	});
	
	$.extend($.ssui.titlebar, {
		version: "0.0.1",
		options: {
			title: '',
			closeButton: false,
			closeText: 'close'
		},

		uuid: 0,

		getTitleId: function($el) {
			return 'ui-dialog-title-' + ($el.attr('id') || ++this.uuid);
		}
	});
}(jQuery));