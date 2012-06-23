(function($) {

	$.entwine('ss', function($) {
		/**
		 * Creates a jQuery UI tab navigation bar, detached from the container DOM structure.
		 */
		$('.ss-ui-tabs-nav').entwine({
			onadd: function() {
				this.redraw();
			},
			redraw: function() {
				this.addClass('ui-tabs ui-widget ui-widget-content ui-corner-all ui-tabs-panel ui-corner-bottom');
				this.find('ul').addClass('ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all');
				this.find('li').addClass('ui-state-default ui-corner-top');
				// TODO Figure out selected tab
				var selected = this.find('li.current');
				if(!selected.length) selected = this.find('li:first');
				selected.selectIt();
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
	});

	/**
	 * Allows icon definition via HTML5 data attrs for easier handling in PHP
	 */
	$.widget('ssui.button', $.ui.button, {
		_resetButton: function() {
			var iconPrimary = this.element.data('iconPrimary') ? this.element.data('iconPrimary') : this.element.data('icon'),
				iconSecondary = this.element.data('iconSecondary');
			// TODO Move prefix out of this method, without requriing it for every icon definition in a data attr
			if(iconPrimary) this.options.icons.primary = 'btn-icon-' + iconPrimary;
			if(iconSecondary) this.options.icons.secondary = 'btn-icon-' + iconSecondary;

			$.ui.button.prototype._resetButton.call(this);
		}
	});

	/**
	 * Extends jQueryUI dialog with iframe abilities (and related resizing logic),
	 * and sets some CMS-wide defaults.
	 */
	$.widget("ssui.ssdialog", $.ui.dialog, {
		options: {
			// Custom properties
			iframeUrl: '',
			reloadOnOpen: true,
			dialogExtraClass: '',

			// Defaults
			width: '80%',
			height: 500,
			position: 'center',
			modal: true,
			bgiframe: true,
			autoOpen: false
		},
		_create: function() {
			$.ui.dialog.prototype._create.call(this);

			var self = this;

			// Create iframe
			var iframe = $('<iframe marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto"></iframe>');
			iframe.bind('load', function(e) {
				if($(this).attr('src') == 'about:blank') return;
				
				$(this).show();
				self._resizeIframe();
				self.uiDialog.removeClass('loading');
			}).hide();
			
			if(this.options.dialogExtraClass) this.uiDialog.addClass(this.options.dialogExtraClass);
			this.element.append(iframe);

			// Let the iframe handle its scrolling
			this.element.css('overflow', 'hidden');
		},
		open: function() {
			$.ui.dialog.prototype.open.call(this);
			
			var self = this, iframe = this.element.children('iframe');

			// Load iframe
			if(!iframe.attr('src') || this.options.reloadOnOpen) {
				iframe.hide();
				iframe.attr('src', this.options.iframeUrl);
				this.uiDialog.addClass('loading');
			}

			// Resize events
			this.uiDialog.bind('resize.ssdialog', function() {self._resizeIframe();});
			$(window).bind('resize.ssdialog', function() {self._resizeIframe();});
		},
		close: function() {
			$.ui.dialog.prototype.close.call(this);

			this.uiDialog.unbind('resize.ssdialog');
			$(window).unbind('resize.ssdialog');
		},
		_resizeIframe: function() {
			var el = this.element, iframe = el.children('iframe');

			iframe.attr('width', 
				el.innerWidth() 
				- parseFloat(el.css('paddingLeft'))
				- parseFloat(el.css('paddingRight'))
			);
			iframe.attr('height', 
				el.innerHeight()
				- parseFloat(el.css('paddingTop')) 
				- parseFloat(el.css('paddingBottom'))
			);
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
