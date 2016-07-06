(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.LeftAndMain.Preview', ['jQuery', 'i18n'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'), require('i18n'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery, global.i18n);
		global.ssLeftAndMainPreview = mod.exports;
	}
})(this, function (_jQuery, _i18n) {
	'use strict';

	var _jQuery2 = _interopRequireDefault(_jQuery);

	var _i18n2 = _interopRequireDefault(_i18n);

	function _interopRequireDefault(obj) {
		return obj && obj.__esModule ? obj : {
			default: obj
		};
	}

	_jQuery2.default.entwine('ss.preview', function ($) {
		$('.cms-preview').entwine({
			AllowedStates: ['StageLink', 'LiveLink', 'ArchiveLink'],

			CurrentStateName: null,

			CurrentSizeName: 'auto',

			IsPreviewEnabled: false,

			DefaultMode: 'split',

			Sizes: {
				auto: {
					width: '100%',
					height: '100%'
				},
				mobile: {
					width: '335px',
					height: '568px'
				},
				mobileLandscape: {
					width: '583px',
					height: '320px'
				},
				tablet: {
					width: '783px',
					height: '1024px'
				},
				tabletLandscape: {
					width: '1039px',
					height: '768px'
				},
				desktop: {
					width: '1024px',
					height: '800px'
				}
			},

			changeState: function changeState(stateName, save) {
				var self = this,
				    states = this._getNavigatorStates();
				if (save !== false) {
					$.each(states, function (index, state) {
						self.saveState('state', stateName);
					});
				}

				this.setCurrentStateName(stateName);
				this._loadCurrentState();
				this.redraw();

				return this;
			},

			changeMode: function changeMode(modeName, save) {
				var container = $('.cms-container');

				if (modeName == 'split') {
					container.entwine('.ss').splitViewMode();
					this.setIsPreviewEnabled(true);
					this._loadCurrentState();
				} else if (modeName == 'content') {
					container.entwine('.ss').contentViewMode();
					this.setIsPreviewEnabled(false);
				} else if (modeName == 'preview') {
						container.entwine('.ss').previewMode();
						this.setIsPreviewEnabled(true);
						this._loadCurrentState();
					} else {
						throw 'Invalid mode: ' + modeName;
					}

				if (save !== false) this.saveState('mode', modeName);

				this.redraw();

				return this;
			},

			changeSize: function changeSize(sizeName) {
				var sizes = this.getSizes();

				this.setCurrentSizeName(sizeName);
				this.removeClass('auto desktop tablet mobile').addClass(sizeName);
				this.find('.preview-device-outer').width(sizes[sizeName].width).height(sizes[sizeName].height);
				this.find('.preview-device-inner').width(sizes[sizeName].width);

				this.saveState('size', sizeName);

				this.redraw();

				return this;
			},

			redraw: function redraw() {

				if (window.debug) console.log('redraw', this.attr('class'), this.get(0));

				var currentStateName = this.getCurrentStateName();
				if (currentStateName) {
					this.find('.cms-preview-states').changeVisibleState(currentStateName);
				}

				var layoutOptions = $('.cms-container').entwine('.ss').getLayoutOptions();
				if (layoutOptions) {
					$('.preview-mode-selector').changeVisibleMode(layoutOptions.mode);
				}

				var currentSizeName = this.getCurrentSizeName();
				if (currentSizeName) {
					this.find('.preview-size-selector').changeVisibleSize(this.getCurrentSizeName());
				}

				return this;
			},

			saveState: function saveState(name, value) {
				if (this._supportsLocalStorage()) window.localStorage.setItem('cms-preview-state-' + name, value);
			},

			loadState: function loadState(name) {
				if (this._supportsLocalStorage()) return window.localStorage.getItem('cms-preview-state-' + name);
			},

			disablePreview: function disablePreview() {
				this.setPendingURL(null);
				this._loadUrl('about:blank');
				this._block();
				this.changeMode('content', false);
				this.setIsPreviewEnabled(false);
				return this;
			},

			enablePreview: function enablePreview() {
				if (!this.getIsPreviewEnabled()) {
					this.setIsPreviewEnabled(true);

					if ($.browser.msie && $.browser.version.slice(0, 3) <= 7) {
						this.changeMode('content');
					} else {
						this.changeMode(this.getDefaultMode(), false);
					}
				}
				return this;
			},

			getOrAppendFontFixStyleElement: function getOrAppendFontFixStyleElement() {
				var style = $('#FontFixStyleElement');
				if (!style.length) {
					style = $('<style type="text/css" id="FontFixStyleElement" disabled="disabled">' + ':before,:after{content:none !important}' + '</style>').appendTo('head');
				}

				return style;
			},

			onadd: function onadd() {
				var self = this,
				    layoutContainer = this.parent(),
				    iframe = this.find('iframe');

				iframe.addClass('center');
				iframe.bind('load', function () {
					self._adjustIframeForPreview();

					self._loadCurrentPage();

					$(this).removeClass('loading');
				});

				if ($.browser.msie && 8 === parseInt($.browser.version, 10)) {
					iframe.bind('readystatechange', function (e) {
						if (iframe[0].readyState == 'interactive') {
							self.getOrAppendFontFixStyleElement().removeAttr('disabled');
							setTimeout(function () {
								self.getOrAppendFontFixStyleElement().attr('disabled', 'disabled');
							}, 0);
						}
					});
				}

				this.append('<div class="cms-preview-overlay ui-widget-overlay-light"></div>');
				this.find('.cms-preview-overlay').hide();

				this.disablePreview();

				this._super();
			},

			_supportsLocalStorage: function _supportsLocalStorage() {
				var uid = new Date();
				var storage;
				var result;
				try {
					(storage = window.localStorage).setItem(uid, uid);
					result = storage.getItem(uid) == uid;
					storage.removeItem(uid);
					return result && storage;
				} catch (exception) {
					console.warn('localStorge is not available due to current browser / system settings.');
				}
			},

			onenable: function onenable() {
				var $viewModeSelector = $('.preview-mode-selector');

				$viewModeSelector.removeClass('split-disabled');
				$viewModeSelector.find('.disabled-tooltip').hide();
			},

			ondisable: function ondisable() {
				var $viewModeSelector = $('.preview-mode-selector');

				$viewModeSelector.addClass('split-disabled');
				$viewModeSelector.find('.disabled-tooltip').show();
			},

			_block: function _block() {
				this.addClass('blocked');
				this.find('.cms-preview-overlay').show();
				return this;
			},

			_unblock: function _unblock() {
				this.removeClass('blocked');
				this.find('.cms-preview-overlay').hide();
				return this;
			},

			_initialiseFromContent: function _initialiseFromContent() {
				var mode, size;

				if (!$('.cms-previewable').length) {
					this.disablePreview();
				} else {
					mode = this.loadState('mode');
					size = this.loadState('size');

					this._moveNavigator();
					if (!mode || mode != 'content') {
						this.enablePreview();
						this._loadCurrentState();
					}
					this.redraw();

					if (mode) this.changeMode(mode);
					if (size) this.changeSize(size);
				}
				return this;
			},

			'from .cms-container': {
				onafterstatechange: function onafterstatechange(e, data) {
					if (data.xhr.getResponseHeader('X-ControllerURL')) return;

					this._initialiseFromContent();
				}
			},

			PendingURL: null,

			oncolumnvisibilitychanged: function oncolumnvisibilitychanged() {
				var url = this.getPendingURL();
				if (url && !this.is('.column-hidden')) {
					this.setPendingURL(null);
					this._loadUrl(url);
					this._unblock();
				}
			},

			'from .cms-container .cms-edit-form': {
				onaftersubmitform: function onaftersubmitform() {
					this._initialiseFromContent();
				}
			},

			_loadUrl: function _loadUrl(url) {
				this.find('iframe').addClass('loading').attr('src', url);
				return this;
			},

			_getNavigatorStates: function _getNavigatorStates() {
				var urlMap = $.map(this.getAllowedStates(), function (name) {
					var stateLink = $('.cms-preview-states .state-name[data-name=' + name + ']');
					if (stateLink.length) {
						return {
							name: name,
							url: stateLink.attr('data-link'),
							active: stateLink.is(':radio') ? stateLink.is(':checked') : stateLink.is(':selected')
						};
					} else {
						return null;
					}
				});

				return urlMap;
			},

			_loadCurrentState: function _loadCurrentState() {
				if (!this.getIsPreviewEnabled()) return this;

				var states = this._getNavigatorStates();
				var currentStateName = this.getCurrentStateName();
				var currentState = null;

				if (states) {
					currentState = $.grep(states, function (state, index) {
						return currentStateName === state.name || !currentStateName && state.active;
					});
				}

				var url = null;

				if (currentState[0]) {
					url = currentState[0].url;
				} else if (states.length) {
					this.setCurrentStateName(states[0].name);
					url = states[0].url;
				} else {
					this.setCurrentStateName(null);
				}

				if (url) {
					url += (url.indexOf('?') === -1 ? '?' : '&') + 'CMSPreview=1';
				}

				if (this.is('.column-hidden')) {
					this.setPendingURL(url);
					this._loadUrl('about:blank');
					this._block();
				} else {
					this.setPendingURL(null);

					if (url) {
						this._loadUrl(url);
						this._unblock();
					} else {
						this._block();
					}
				}

				return this;
			},

			_moveNavigator: function _moveNavigator() {
				var previewEl = $('.cms-preview .cms-preview-controls');
				var navigatorEl = $('.cms-edit-form .cms-navigator');

				if (navigatorEl.length && previewEl.length) {
					previewEl.html($('.cms-edit-form .cms-navigator').detach());
				} else {
					this._block();
				}
			},

			_loadCurrentPage: function _loadCurrentPage() {
				if (!this.getIsPreviewEnabled()) return;

				var doc,
				    containerEl = $('.cms-container');
				try {
					doc = this.find('iframe')[0].contentDocument;
				} catch (e) {
					console.warn('Unable to access iframe, possible https mis-match');
				}
				if (!doc) {
					return;
				}

				var id = $(doc).find('meta[name=x-page-id]').attr('content');
				var editLink = $(doc).find('meta[name=x-cms-edit-link]').attr('content');
				var contentPanel = $('.cms-content');

				if (id && contentPanel.find(':input[name=ID]').val() != id) {
					$('.cms-container').entwine('.ss').loadPanel(editLink);
				}
			},

			_adjustIframeForPreview: function _adjustIframeForPreview() {
				var iframe = this.find('iframe')[0],
				    doc;
				if (!iframe) {
					return;
				}

				try {
					doc = iframe.contentDocument;
				} catch (e) {
					console.warn('Unable to access iframe, possible https mis-match');
				}
				if (!doc) {
					return;
				}

				var links = doc.getElementsByTagName('A');
				for (var i = 0; i < links.length; i++) {
					var href = links[i].getAttribute('href');
					if (!href) continue;

					if (href.match(/^http:\/\//)) links[i].setAttribute('target', '_blank');
				}

				var navi = doc.getElementById('SilverStripeNavigator');
				if (navi) navi.style.display = 'none';
				var naviMsg = doc.getElementById('SilverStripeNavigatorMessage');
				if (naviMsg) naviMsg.style.display = 'none';

				this.trigger('afterIframeAdjustedForPreview', [doc]);
			}
		});

		$('.cms-edit-form').entwine({
			onadd: function onadd() {
				this._super();
				$('.cms-preview')._initialiseFromContent();
			}
		});

		$('.cms-preview-states').entwine({
			changeVisibleState: function changeVisibleState(state) {
				this.find('input[data-name="' + state + '"]').prop('checked', true);
			}
		});

		$('.cms-preview-states .state-name').entwine({
			onclick: function onclick(e) {
				this.parent().find('.active').removeClass('active');
				this.next('label').addClass('active');

				var targetStateName = $(this).attr('data-name');

				$('.cms-preview').changeState(targetStateName);
			}
		});

		$('.preview-mode-selector').entwine({
			changeVisibleMode: function changeVisibleMode(mode) {
				this.find('select').val(mode).trigger('chosen:updated')._addIcon();
			}
		});

		$('.preview-mode-selector select').entwine({
			onchange: function onchange(e) {
				this._super(e);
				e.preventDefault();

				var targetStateName = $(this).val();
				$('.cms-preview').changeMode(targetStateName);
			}
		});

		$('.cms-preview.column-hidden').entwine({
			onmatch: function onmatch() {
				$('#preview-mode-dropdown-in-content').show();

				if ($('.cms-preview .result-selected').hasClass('font-icon-columns')) {
					statusMessage(_i18n2.default._t('LeftAndMain.DISABLESPLITVIEW', "Screen too small to show site preview in split mode"), "error");
				}
				this._super();
			},

			onunmatch: function onunmatch() {
				$('#preview-mode-dropdown-in-content').hide();
				this._super();
			}
		});

		$('#preview-mode-dropdown-in-content').entwine({
			onmatch: function onmatch() {
				if ($('.cms-preview').is('.column-hidden')) {
					this.show();
				} else {
					this.hide();
				}
				this._super();
			},
			onunmatch: function onunmatch() {
				this._super();
			}
		});

		$('.preview-size-selector').entwine({
			changeVisibleSize: function changeVisibleSize(size) {
				this.find('select').val(size).trigger('chosen:updated')._addIcon();
			}
		});

		$('.preview-size-selector select').entwine({
			onchange: function onchange(e) {
				e.preventDefault();

				var targetSizeName = $(this).val();
				$('.cms-preview').changeSize(targetSizeName);
			}
		});

		$('.preview-selector select.preview-dropdown').entwine({
			'onchosen:ready': function onchosenReady() {
				this._super();
				this._addIcon();
			},

			_addIcon: function _addIcon() {
				var selected = this.find(':selected');
				var iconClass = selected.attr('data-icon');

				var target = this.parent().find('.chosen-container a.chosen-single');
				var oldIcon = target.attr('data-icon');
				if (typeof oldIcon !== 'undefined') {
					target.removeClass(oldIcon);
				}
				target.addClass(iconClass);
				target.attr('data-icon', iconClass);

				return this;
			}
		});

		$('.preview-mode-selector .chosen-drop li:last-child').entwine({
			onmatch: function onmatch() {
				if ($('.preview-mode-selector').hasClass('split-disabled')) {
					this.parent().append('<div class="disabled-tooltip"></div>');
				} else {
					this.parent().append('<div class="disabled-tooltip" style="display: none;"></div>');
				}
			}
		});

		$('.preview-device-outer').entwine({
			onclick: function onclick() {
				this.toggleClass('rotate');
			}
		});
	});
});