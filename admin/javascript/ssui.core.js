(function($) {
	
	$('.ss-ui-button').entwine({
		/**
		 * Constructor: onmatch
		 */
		onmatch: function() {
			this.addClass(
				'ui-state-default ' +
				'ui-corner-all'
			)
			.hover(
				function() {
					$(this).addClass('ui-state-hover');
				},
				function() {
					$(this).removeClass('ui-state-hover');
				}
			)
			.focus(function() {
				$(this).addClass('ui-state-focus');
			})
			.blur(function() {
				$(this).removeClass('ui-state-focus');
			})
			.click(function() {
				var form = this.form;
				// forms don't natively store the button they've been triggered with
				form.clickedButton = this;
				// Reset the clicked button shortly after the onsubmit handlers
				// have fired on the form
				setTimeout(function() {form.clickedButton = null;}, 10);
			});

			this._super();
		}
	});
	
	/**
	 * Creates a jQuery UI tab navigation bar, detached from the container DOM structure.
	 */
	$('.ss-ui-tabs-nav').entwine({
	 onmatch: function() {
		 this.addClass('ui-tabs ui-widget ui-widget-content ui-corner-all ui-tabs-panel ui-corner-bottom');
		 this.find('ul').addClass('ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all');
		 this.find('li').addClass('ui-state-default ui-corner-top');
		 // TODO Figure out selected tab
		 this.find('li:first').selectIt();
	
		 this._super();
	 }
	});
	
	$('.ss-ui-tabs-nav li').entwine({
		onclick: function() {
			this.selectIt();
		},
		selectIt: function() {
			var cls = 'ui-tabs-selected ui-state-active';
			this.addClass(cls).siblings().not(this).removeClass(cls);
		}
	});
	
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