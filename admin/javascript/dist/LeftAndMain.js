(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.LeftAndMain', ['jQuery'], factory);
	} else if (typeof exports !== "undefined") {
		factory(require('jQuery'));
	} else {
		var mod = {
			exports: {}
		};
		factory(global.jQuery);
		global.ssLeftAndMain = mod.exports;
	}
})(this, function (_jQuery) {
	'use strict';

	var _jQuery2 = _interopRequireDefault(_jQuery);

	function _interopRequireDefault(obj) {
		return obj && obj.__esModule ? obj : {
			default: obj
		};
	}

	var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) {
		return typeof obj;
	} : function (obj) {
		return obj && typeof Symbol === "function" && obj.constructor === Symbol ? "symbol" : typeof obj;
	};

	_jQuery2.default.noConflict();

	window.ss = window.ss || {};

	var windowWidth, windowHeight;

	window.ss.debounce = function (func, wait, immediate) {
		var timeout, context, args;

		var later = function later() {
			timeout = null;
			if (!immediate) func.apply(context, args);
		};

		return function () {
			var callNow = immediate && !timeout;

			context = this;
			args = arguments;

			clearTimeout(timeout);
			timeout = setTimeout(later, wait);

			if (callNow) {
				func.apply(context, args);
			}
		};
	};

	(0, _jQuery2.default)(window).bind('resize.leftandmain', function (e) {
		var cb = function cb() {
			(0, _jQuery2.default)('.cms-container').trigger('windowresize');
		};

		if (_jQuery2.default.browser.msie && parseInt(_jQuery2.default.browser.version, 10) < 9) {
			var newWindowWidth = (0, _jQuery2.default)(window).width(),
			    newWindowHeight = (0, _jQuery2.default)(window).height();
			if (newWindowWidth != windowWidth || newWindowHeight != windowHeight) {
				windowWidth = newWindowWidth;
				windowHeight = newWindowHeight;
				cb();
			}
		} else {
			cb();
		}
	});

	_jQuery2.default.entwine.warningLevel = _jQuery2.default.entwine.WARN_LEVEL_BESTPRACTISE;
	_jQuery2.default.entwine('ss', function ($) {
		$(window).on("message", function (e) {
			var target,
			    event = e.originalEvent,
			    data = _typeof(event.data) === 'object' ? event.data : JSON.parse(event.data);

			if ($.path.parseUrl(window.location.href).domain !== $.path.parseUrl(event.origin).domain) return;

			target = typeof data.target === 'undefined' ? $(window) : $(data.target);

			switch (data.type) {
				case 'event':
					target.trigger(data.event, data.data);
					break;
				case 'callback':
					target[data.callback].call(target, data.data);
					break;
			}
		});

		var positionLoadingSpinner = function positionLoadingSpinner() {
			var offset = 120;
			var spinner = $('.ss-loading-screen .loading-animation');
			var top = ($(window).height() - spinner.height()) / 2;
			spinner.css('top', top + offset);
			spinner.show();
		};

		var applyChosen = function applyChosen(el) {
			if (el.is(':visible')) {
				el.addClass('has-chzn').chosen({
					allow_single_deselect: true,
					disable_search_threshold: 20
				});

				var title = el.prop('title');

				if (title) {
					el.siblings('.chzn-container').prop('title', title);
				}
			} else {
				setTimeout(function () {
					el.show();
					applyChosen(el);
				}, 500);
			}
		};

		var isSameUrl = function isSameUrl(url1, url2) {
			var baseUrl = $('base').attr('href');
			url1 = $.path.isAbsoluteUrl(url1) ? url1 : $.path.makeUrlAbsolute(url1, baseUrl), url2 = $.path.isAbsoluteUrl(url2) ? url2 : $.path.makeUrlAbsolute(url2, baseUrl);
			var url1parts = $.path.parseUrl(url1),
			    url2parts = $.path.parseUrl(url2);
			return url1parts.pathname.replace(/\/*$/, '') == url2parts.pathname.replace(/\/*$/, '') && url1parts.search == url2parts.search;
		};

		var ajaxCompleteEvent = window.ss.debounce(function () {
			$(window).trigger('ajaxComplete');
		}, 1000, true);

		$(window).bind('resize', positionLoadingSpinner).trigger('resize');

		$(document).ajaxComplete(function (e, xhr, settings) {
			if (window.History.enabled) {
				var url = xhr.getResponseHeader('X-ControllerURL'),
				    origUrl = History.getPageUrl().replace(/\/$/, ''),
				    destUrl = settings.url,
				    opts;

				if (url !== null && (!isSameUrl(origUrl, url) || !isSameUrl(destUrl, url))) {
					opts = {
						id: new Date().getTime() + String(Math.random()).replace(/\D/g, ''),
						pjax: xhr.getResponseHeader('X-Pjax') ? xhr.getResponseHeader('X-Pjax') : settings.headers['X-Pjax']
					};
					window.History.pushState(opts, '', url);
				}
			}

			var msg = xhr.getResponseHeader('X-Status') ? xhr.getResponseHeader('X-Status') : xhr.statusText,
			    reathenticate = xhr.getResponseHeader('X-Reauthenticate'),
			    msgType = xhr.status < 200 || xhr.status > 399 ? 'bad' : 'good',
			    ignoredMessages = ['OK'];

			if (reathenticate) {
				$('.cms-container').showLoginDialog();
				return;
			}

			if (xhr.status !== 0 && msg && $.inArray(msg, ignoredMessages)) {
				statusMessage(decodeURIComponent(msg), msgType);
			}

			ajaxCompleteEvent(this);
		});

		$('.cms-container').entwine({
			StateChangeXHR: null,

			FragmentXHR: {},

			StateChangeCount: 0,

			LayoutOptions: {
				minContentWidth: 940,
				minPreviewWidth: 400,
				mode: 'content'
			},

			onadd: function onadd() {
				var self = this;

				if ($.browser.msie && parseInt($.browser.version, 10) < 8) {
					$('.ss-loading-screen').append('<p class="ss-loading-incompat-warning"><span class="notice">' + 'Your browser is not compatible with the CMS interface. Please use Internet Explorer 8+, Google Chrome or Mozilla Firefox.' + '</span></p>').css('z-index', $('.ss-loading-screen').css('z-index') + 1);
					$('.loading-animation').remove();

					this._super();
					return;
				}

				this.redraw();

				$('.ss-loading-screen').hide();
				$('body').removeClass('loading');
				$(window).unbind('resize', positionLoadingSpinner);
				this.restoreTabState();
				this._super();
			},

			fromWindow: {
				onstatechange: function onstatechange(e) {
					this.handleStateChange(e);
				}
			},

			'onwindowresize': function onwindowresize() {
				this.redraw();
			},

			'from .cms-panel': {
				ontoggle: function ontoggle() {
					this.redraw();
				}
			},

			'from .cms-container': {
				onaftersubmitform: function onaftersubmitform() {
					this.redraw();
				}
			},

			'from .cms-menu-list li a': {
				onclick: function onclick(e) {
					var href = $(e.target).attr('href');
					if (e.which > 1 || href == this._tabStateUrl()) return;
					this.splitViewMode();
				}
			},

			updateLayoutOptions: function updateLayoutOptions(newSpec) {
				var spec = this.getLayoutOptions();

				var dirty = false;

				for (var k in newSpec) {
					if (spec[k] !== newSpec[k]) {
						spec[k] = newSpec[k];
						dirty = true;
					}
				}

				if (dirty) this.redraw();
			},

			splitViewMode: function splitViewMode() {
				this.updateLayoutOptions({
					mode: 'split'
				});
			},

			contentViewMode: function contentViewMode() {
				this.updateLayoutOptions({
					mode: 'content'
				});
			},

			previewMode: function previewMode() {
				this.updateLayoutOptions({
					mode: 'preview'
				});
			},

			RedrawSuppression: false,

			redraw: function redraw() {
				if (this.getRedrawSuppression()) return;

				if (window.debug) console.log('redraw', this.attr('class'), this.get(0));

				this.data('jlayout', jLayout.threeColumnCompressor({
					menu: this.children('.cms-menu'),
					content: this.children('.cms-content'),
					preview: this.children('.cms-preview')
				}, this.getLayoutOptions()));

				this.layout();

				this.find('.cms-panel-layout').redraw();
				this.find('.cms-content-fields[data-layout-type]').redraw();
				this.find('.cms-edit-form[data-layout-type]').redraw();
				this.find('.cms-preview').redraw();
				this.find('.cms-content').redraw();
			},

			checkCanNavigate: function checkCanNavigate(selectors) {
				var contentEls = this._findFragments(selectors || ['Content']),
				    trackedEls = contentEls.find(':data(changetracker)').add(contentEls.filter(':data(changetracker)')),
				    safe = true;

				if (!trackedEls.length) {
					return true;
				}

				trackedEls.each(function () {
					if (!$(this).confirmUnsavedChanges()) {
						safe = false;
					}
				});

				return safe;
			},

			loadPanel: function loadPanel(url, title, data, forceReload, forceReferer) {
				if (!data) data = {};
				if (!title) title = "";
				if (!forceReferer) forceReferer = History.getState().url;

				if (!this.checkCanNavigate(data.pjax ? data.pjax.split(',') : ['Content'])) {
					return;
				}

				this.saveTabState();

				if (window.History.enabled) {
					$.extend(data, { __forceReferer: forceReferer });

					if (forceReload) {
						$.extend(data, { __forceReload: Math.random() });
						window.History.replaceState(data, title, url);
					} else {
						window.History.pushState(data, title, url);
					}
				} else {
					window.location = $.path.makeUrlAbsolute(url, $('base').attr('href'));
				}
			},

			reloadCurrentPanel: function reloadCurrentPanel() {
				this.loadPanel(window.History.getState().url, null, null, true);
			},

			submitForm: function submitForm(form, button, callback, ajaxOptions) {
				var self = this;

				if (!button) button = this.find('.Actions :submit[name=action_save]');

				if (!button) button = this.find('.Actions :submit:first');

				form.trigger('beforesubmitform');
				this.trigger('submitform', { form: form, button: button });

				$(button).addClass('loading');

				var validationResult = form.validate();
				if (typeof validationResult !== 'undefined' && !validationResult) {
					statusMessage("Validation failed.", "bad");

					$(button).removeClass('loading');

					return false;
				}

				var formData = form.serializeArray();

				formData.push({ name: $(button).attr('name'), value: '1' });

				formData.push({ name: 'BackURL', value: History.getPageUrl().replace(/\/$/, '') });

				this.saveTabState();

				jQuery.ajax(jQuery.extend({
					headers: { "X-Pjax": "CurrentForm,Breadcrumbs" },
					url: form.attr('action'),
					data: formData,
					type: 'POST',
					complete: function complete() {
						$(button).removeClass('loading');
					},
					success: function success(data, status, xhr) {
						form.removeClass('changed');
						if (callback) callback(data, status, xhr);

						var newContentEls = self.handleAjaxResponse(data, status, xhr);
						if (!newContentEls) return;

						newContentEls.filter('form').trigger('aftersubmitform', { status: status, xhr: xhr, formData: formData });
					}
				}, ajaxOptions));

				return false;
			},

			LastState: null,

			PauseState: false,

			handleStateChange: function handleStateChange() {
				if (this.getPauseState()) {
					return;
				}

				if (this.getStateChangeXHR()) this.getStateChangeXHR().abort();

				var self = this,
				    h = window.History,
				    state = h.getState(),
				    fragments = state.data.pjax || 'Content',
				    headers = {},
				    fragmentsArr = fragments.split(','),
				    contentEls = this._findFragments(fragmentsArr);

				this.setStateChangeCount(this.getStateChangeCount() + 1);
				var isLegacyIE = $.browser.msie && parseInt($.browser.version, 10) < 9;
				if (isLegacyIE && this.getStateChangeCount() > 20) {
					document.location.href = state.url;
					return;
				}

				if (!this.checkCanNavigate()) {
					if (h.emulated.pushState) {
						return;
					}

					var lastState = this.getLastState();

					this.setPauseState(true);

					if (lastState) {
						h.pushState(lastState.id, lastState.title, lastState.url);
					} else {
						h.back();
					}
					this.setPauseState(false);

					return;
				}
				this.setLastState(state);

				if (contentEls.length < fragmentsArr.length) {
					fragments = 'Content', fragmentsArr = ['Content'];
					contentEls = this._findFragments(fragmentsArr);
				}

				this.trigger('beforestatechange', { state: state, element: contentEls });

				headers['X-Pjax'] = fragments;

				if (typeof state.data.__forceReferer !== 'undefined') {
					var url = state.data.__forceReferer;

					try {
						url = decodeURI(url);
					} catch (e) {} finally {
						headers['X-Backurl'] = encodeURI(url);
					}
				}

				contentEls.addClass('loading');
				var xhr = $.ajax({
					headers: headers,
					url: state.url,
					complete: function complete() {
						self.setStateChangeXHR(null);

						contentEls.removeClass('loading');
					},
					success: function success(data, status, xhr) {
						var els = self.handleAjaxResponse(data, status, xhr, state);
						self.trigger('afterstatechange', { data: data, status: status, xhr: xhr, element: els, state: state });
					}
				});

				this.setStateChangeXHR(xhr);
			},

			loadFragment: function loadFragment(url, pjaxFragments) {

				var self = this,
				    xhr,
				    headers = {},
				    baseUrl = $('base').attr('href'),
				    fragmentXHR = this.getFragmentXHR();

				if (typeof fragmentXHR[pjaxFragments] !== 'undefined' && fragmentXHR[pjaxFragments] !== null) {
					fragmentXHR[pjaxFragments].abort();
					fragmentXHR[pjaxFragments] = null;
				}

				url = $.path.isAbsoluteUrl(url) ? url : $.path.makeUrlAbsolute(url, baseUrl);
				headers['X-Pjax'] = pjaxFragments;

				xhr = $.ajax({
					headers: headers,
					url: url,
					success: function success(data, status, xhr) {
						var elements = self.handleAjaxResponse(data, status, xhr, null);

						self.trigger('afterloadfragment', { data: data, status: status, xhr: xhr, elements: elements });
					},
					error: function error(xhr, status, _error) {
						self.trigger('loadfragmenterror', { xhr: xhr, status: status, error: _error });
					},
					complete: function complete() {
						var fragmentXHR = self.getFragmentXHR();
						if (typeof fragmentXHR[pjaxFragments] !== 'undefined' && fragmentXHR[pjaxFragments] !== null) {
							fragmentXHR[pjaxFragments] = null;
						}
					}
				});

				fragmentXHR[pjaxFragments] = xhr;

				return xhr;
			},

			handleAjaxResponse: function handleAjaxResponse(data, status, xhr, state) {
				var self = this,
				    url,
				    selectedTabs,
				    guessFragment,
				    fragment,
				    $data;

				if (xhr.getResponseHeader('X-Reload') && xhr.getResponseHeader('X-ControllerURL')) {
					var baseUrl = $('base').attr('href'),
					    rawURL = xhr.getResponseHeader('X-ControllerURL'),
					    url = $.path.isAbsoluteUrl(rawURL) ? rawURL : $.path.makeUrlAbsolute(rawURL, baseUrl);

					document.location.href = url;
					return;
				}

				if (!data) return;

				var title = xhr.getResponseHeader('X-Title');
				if (title) document.title = decodeURIComponent(title.replace(/\+/g, ' '));

				var newFragments = {},
				    newContentEls;

				if (xhr.getResponseHeader('Content-Type').match(/^((text)|(application))\/json[ \t]*;?/i)) {
					newFragments = data;
				} else {
					fragment = document.createDocumentFragment();

					jQuery.clean([data], document, fragment, []);
					$data = $(jQuery.merge([], fragment.childNodes));

					guessFragment = 'Content';
					if ($data.is('form') && !$data.is('[data-pjax-fragment~=Content]')) guessFragment = 'CurrentForm';

					newFragments[guessFragment] = $data;
				}

				this.setRedrawSuppression(true);
				try {
					$.each(newFragments, function (newFragment, html) {
						var contentEl = $('[data-pjax-fragment]').filter(function () {
							return $.inArray(newFragment, $(this).data('pjaxFragment').split(' ')) != -1;
						}),
						    newContentEl = $(html);

						if (newContentEls) newContentEls.add(newContentEl);else newContentEls = newContentEl;

						if (newContentEl.find('.cms-container').length) {
							throw 'Content loaded via ajax is not allowed to contain tags matching the ".cms-container" selector to avoid infinite loops';
						}

						var origStyle = contentEl.attr('style');
						var origParent = contentEl.parent();
						var origParentLayoutApplied = typeof origParent.data('jlayout') !== 'undefined';
						var layoutClasses = ['east', 'west', 'center', 'north', 'south', 'column-hidden'];
						var elemClasses = contentEl.attr('class');
						var origLayoutClasses = [];
						if (elemClasses) {
							origLayoutClasses = $.grep(elemClasses.split(' '), function (val) {
								return $.inArray(val, layoutClasses) >= 0;
							});
						}

						newContentEl.removeClass(layoutClasses.join(' ')).addClass(origLayoutClasses.join(' '));
						if (origStyle) newContentEl.attr('style', origStyle);

						var styles = newContentEl.find('style').detach();
						if (styles.length) $(document).find('head').append(styles);

						contentEl.replaceWith(newContentEl);

						if (!origParent.is('.cms-container') && origParentLayoutApplied) {
							origParent.layout();
						}
					});

					var newForm = newContentEls.filter('form');
					if (newForm.hasClass('cms-tabset')) newForm.removeClass('cms-tabset').addClass('cms-tabset');
				} finally {
					this.setRedrawSuppression(false);
				}

				this.redraw();
				this.restoreTabState(state && typeof state.data.tabState !== 'undefined' ? state.data.tabState : null);

				return newContentEls;
			},

			_findFragments: function _findFragments(fragments) {
				return $('[data-pjax-fragment]').filter(function () {
					var i,
					    nodeFragments = $(this).data('pjaxFragment').split(' ');
					for (i in fragments) {
						if ($.inArray(fragments[i], nodeFragments) != -1) return true;
					}
					return false;
				});
			},

			refresh: function refresh() {
				$(window).trigger('statechange');

				$(this).redraw();
			},

			saveTabState: function saveTabState() {
				if (typeof window.sessionStorage == "undefined" || window.sessionStorage === null) return;

				var selectedTabs = [],
				    url = this._tabStateUrl();
				this.find('.cms-tabset,.ss-tabset').each(function (i, el) {
					var id = $(el).attr('id');
					if (!id) return;
					if (!$(el).data('tabs')) return;
					if ($(el).data('ignoreTabState') || $(el).getIgnoreTabState()) return;

					selectedTabs.push({ id: id, selected: $(el).tabs('option', 'selected') });
				});

				if (selectedTabs) {
					var tabsUrl = 'tabs-' + url;
					try {
						window.sessionStorage.setItem(tabsUrl, JSON.stringify(selectedTabs));
					} catch (err) {
						if (err.code === DOMException.QUOTA_EXCEEDED_ERR && window.sessionStorage.length === 0) {
							return;
						} else {
							throw err;
						}
					}
				}
			},

			restoreTabState: function restoreTabState(overrideStates) {
				var self = this,
				    url = this._tabStateUrl(),
				    hasSessionStorage = typeof window.sessionStorage !== "undefined" && window.sessionStorage,
				    sessionData = hasSessionStorage ? window.sessionStorage.getItem('tabs-' + url) : null,
				    sessionStates = sessionData ? JSON.parse(sessionData) : false;

				this.find('.cms-tabset, .ss-tabset').each(function () {
					var index,
					    tabset = $(this),
					    tabsetId = tabset.attr('id'),
					    tab,
					    forcedTab = tabset.find('.ss-tabs-force-active');

					if (!tabset.data('tabs')) {
						return;
					}

					tabset.tabs('refresh');

					if (forcedTab.length) {
						index = forcedTab.index();
					} else if (overrideStates && overrideStates[tabsetId]) {
						tab = tabset.find(overrideStates[tabsetId].tabSelector);
						if (tab.length) {
							index = tab.index();
						}
					} else if (sessionStates) {
						$.each(sessionStates, function (i, sessionState) {
							if (tabset.is('#' + sessionState.id)) {
								index = sessionState.selected;
							}
						});
					}
					if (index !== null) {
						tabset.tabs('option', 'active', index);
						self.trigger('tabstaterestored');
					}
				});
			},

			clearTabState: function clearTabState(url) {
				if (typeof window.sessionStorage == "undefined") return;

				var s = window.sessionStorage;
				if (url) {
					s.removeItem('tabs-' + url);
				} else {
					for (var i = 0; i < s.length; i++) {
						if (s.key(i).match(/^tabs-/)) s.removeItem(s.key(i));
					}
				}
			},

			clearCurrentTabState: function clearCurrentTabState() {
				this.clearTabState(this._tabStateUrl());
			},

			_tabStateUrl: function _tabStateUrl() {
				return History.getState().url.replace(/\?.*/, '').replace(/#.*/, '').replace($('base').attr('href'), '');
			},

			showLoginDialog: function showLoginDialog() {
				var tempid = $('body').data('member-tempid'),
				    dialog = $('.leftandmain-logindialog'),
				    url = 'CMSSecurity/login';

				if (dialog.length) dialog.remove();

				url = $.path.addSearchParams(url, {
					'tempid': tempid,
					'BackURL': window.location.href
				});

				dialog = $('<div class="leftandmain-logindialog"></div>');
				dialog.attr('id', new Date().getTime());
				dialog.data('url', url);
				$('body').append(dialog);
			}
		});

		$('.leftandmain-logindialog').entwine({
			onmatch: function onmatch() {
				this._super();

				this.ssdialog({
					iframeUrl: this.data('url'),
					dialogClass: "leftandmain-logindialog-dialog",
					autoOpen: true,
					minWidth: 500,
					maxWidth: 500,
					minHeight: 370,
					maxHeight: 400,
					closeOnEscape: false,
					open: function open() {
						$('.ui-widget-overlay').addClass('leftandmain-logindialog-overlay');
					},
					close: function close() {
						$('.ui-widget-overlay').removeClass('leftandmain-logindialog-overlay');
					}
				});
			},
			onunmatch: function onunmatch() {
				this._super();
			},
			open: function open() {
				this.ssdialog('open');
			},
			close: function close() {
				this.ssdialog('close');
			},
			toggle: function toggle(bool) {
				if (this.is(':visible')) this.close();else this.open();
			},

			reauthenticate: function reauthenticate(data) {
				if (typeof data.SecurityID !== 'undefined') {
					$(':input[name=SecurityID]').val(data.SecurityID);
				}

				if (typeof data.TempID !== 'undefined') {
					$('body').data('member-tempid', data.TempID);
				}
				this.close();
			}
		});

		$('form.loading,.cms-content.loading,.cms-content-fields.loading,.cms-content-view.loading').entwine({
			onmatch: function onmatch() {
				this.append('<div class="cms-content-loading-overlay ui-widget-overlay-light"></div><div class="cms-content-loading-spinner"></div>');
				this._super();
			},
			onunmatch: function onunmatch() {
				this.find('.cms-content-loading-overlay,.cms-content-loading-spinner').remove();
				this._super();
			}
		});

		$('.cms input[type="submit"], .cms button, .cms input[type="reset"], .cms .ss-ui-button').entwine({
			onadd: function onadd() {
				this.addClass('ss-ui-button');
				if (!this.data('button')) this.button();
				this._super();
			},
			onremove: function onremove() {
				if (this.data('button')) this.button('destroy');
				this._super();
			}
		});

		$('.cms .cms-panel-link').entwine({
			onclick: function onclick(e) {
				if ($(this).hasClass('external-link')) {
					e.stopPropagation();

					return;
				}

				var href = this.attr('href'),
				    url = href && !href.match(/^#/) ? href : this.data('href'),
				    data = { pjax: this.data('pjaxTarget') };

				$('.cms-container').loadPanel(url, null, data);
				e.preventDefault();
			}
		});

		$('.cms .ss-ui-button-ajax').entwine({
			onclick: function onclick(e) {
				$(this).removeClass('ui-button-text-only');
				$(this).addClass('ss-ui-button-loading ui-button-text-icons');

				var loading = $(this).find(".ss-ui-loading-icon");

				if (loading.length < 1) {
					loading = $("<span></span>").addClass('ss-ui-loading-icon ui-button-icon-primary ui-icon');

					$(this).prepend(loading);
				}

				loading.show();

				var href = this.attr('href'),
				    url = href ? href : this.data('href');

				jQuery.ajax({
					url: url,

					complete: function complete(xmlhttp, status) {
						var msg = xmlhttp.getResponseHeader('X-Status') ? xmlhttp.getResponseHeader('X-Status') : xmlhttp.responseText;

						try {
							if (typeof msg != "undefined" && msg !== null) eval(msg);
						} catch (e) {}

						loading.hide();

						$(".cms-container").refresh();

						$(this).removeClass('ss-ui-button-loading ui-button-text-icons');
						$(this).addClass('ui-button-text-only');
					},
					dataType: 'html'
				});
				e.preventDefault();
			}
		});

		$('.cms .ss-ui-dialog-link').entwine({
			UUID: null,
			onmatch: function onmatch() {
				this._super();
				this.setUUID(new Date().getTime());
			},
			onunmatch: function onunmatch() {
				this._super();
			},
			onclick: function onclick() {
				this._super();

				var self = this,
				    id = 'ss-ui-dialog-' + this.getUUID();
				var dialog = $('#' + id);
				if (!dialog.length) {
					dialog = $('<div class="ss-ui-dialog" id="' + id + '" />');
					$('body').append(dialog);
				}

				var extraClass = this.data('popupclass') ? this.data('popupclass') : '';

				dialog.ssdialog({ iframeUrl: this.attr('href'), autoOpen: true, dialogExtraClass: extraClass });
				return false;
			}
		});

		$('.cms-content .Actions').entwine({
			onmatch: function onmatch() {
				this.find('.ss-ui-button').click(function () {
					var form = this.form;

					if (form) {
						form.clickedButton = this;

						setTimeout(function () {
							form.clickedButton = null;
						}, 10);
					}
				});

				this.redraw();
				this._super();
			},
			onunmatch: function onunmatch() {
				this._super();
			},
			redraw: function redraw() {
				if (window.debug) console.log('redraw', this.attr('class'), this.get(0));

				this.contents().filter(function () {
					return this.nodeType == 3 && !/\S/.test(this.nodeValue);
				}).remove();

				this.find('.ss-ui-button').each(function () {
					if (!$(this).data('button')) $(this).button();
				});

				this.find('.ss-ui-buttonset').buttonset();
			}
		});

		$('.cms .field.date input.text').entwine({
			onmatch: function onmatch() {
				var holder = $(this).parents('.field.date:first'),
				    config = holder.data();
				if (!config.showcalendar) {
					this._super();
					return;
				}

				config.showOn = 'button';
				if (config.locale && $.datepicker.regional[config.locale]) {
					config = $.extend(config, $.datepicker.regional[config.locale], {});
				}

				$(this).datepicker(config);


				this._super();
			},
			onunmatch: function onunmatch() {
				this._super();
			}
		});

		$('.cms .field.dropdown select, .cms .field select[multiple], .fieldholder-small select.dropdown').entwine({
			onmatch: function onmatch() {
				if (this.is('.no-chzn')) {
					this._super();
					return;
				}

				if (!this.data('placeholder')) this.data('placeholder', ' ');

				this.removeClass('has-chzn chzn-done');
				this.siblings('.chzn-container').remove();

				applyChosen(this);

				this._super();
			},
			onunmatch: function onunmatch() {
				this._super();
			}
		});

		$(".cms-panel-layout").entwine({
			redraw: function redraw() {
				if (window.debug) console.log('redraw', this.attr('class'), this.get(0));
			}
		});

		$('.cms .ss-gridfield').entwine({
			showDetailView: function showDetailView(url) {
				var params = window.location.search.replace(/^\?/, '');
				if (params) url = $.path.addSearchParams(url, params);
				$('.cms-container').loadPanel(url);
			}
		});

		$('.cms-search-form').entwine({
			onsubmit: function onsubmit(e) {
				var nonEmptyInputs, url;

				nonEmptyInputs = this.find(':input:not(:submit)').filter(function () {
					var vals = $.grep($(this).fieldValue(), function (val) {
						return val;
					});
					return vals.length;
				});

				url = this.attr('action');

				if (nonEmptyInputs.length) {
					url = $.path.addSearchParams(url, nonEmptyInputs.serialize());
				}

				var container = this.closest('.cms-container');
				container.find('.cms-edit-form').tabs('select', 0);
				container.loadPanel(url, "", {}, true);

				return false;
			}
		});

		$(".cms-search-form button[type=reset], .cms-search-form input[type=reset]").entwine({
			onclick: function onclick(e) {
				e.preventDefault();

				var form = $(this).parents('form');

				form.clearForm();
				form.find(".dropdown select").prop('selectedIndex', 0).trigger("liszt:updated");
				form.submit();
			}
		});

		window._panelDeferredCache = {};
		$('.cms-panel-deferred').entwine({
			onadd: function onadd() {
				this._super();
				this.redraw();
			},
			onremove: function onremove() {
				if (window.debug) console.log('saving', this.data('url'), this);

				if (!this.data('deferredNoCache')) window._panelDeferredCache[this.data('url')] = this.html();
				this._super();
			},
			redraw: function redraw() {
				if (window.debug) console.log('redraw', this.attr('class'), this.get(0));

				var self = this,
				    url = this.data('url');
				if (!url) throw 'Elements of class .cms-panel-deferred need a "data-url" attribute';

				this._super();

				if (!this.children().length) {
					if (!this.data('deferredNoCache') && typeof window._panelDeferredCache[url] !== 'undefined') {
						this.html(window._panelDeferredCache[url]);
					} else {
						this.addClass('loading');
						$.ajax({
							url: url,
							complete: function complete() {
								self.removeClass('loading');
							},
							success: function success(data, status, xhr) {
								self.html(data);
							}
						});
					}
				}
			}
		});

		$('.cms-tabset').entwine({
			onadd: function onadd() {
				this.redrawTabs();
				this._super();
			},
			onremove: function onremove() {
				if (this.data('tabs')) this.tabs('destroy');
				this._super();
			},
			redrawTabs: function redrawTabs() {
				this.rewriteHashlinks();

				var id = this.attr('id'),
				    activeTab = this.find('ul:first .ui-tabs-active');

				if (!this.data('uiTabs')) this.tabs({
					active: activeTab.index() != -1 ? activeTab.index() : 0,
					beforeLoad: function beforeLoad(e, ui) {
						return false;
					},
					activate: function activate(e, ui) {
						if (ui.newTab) {
							ui.newTab.find('.cms-panel-link').click();
						}

						var actions = $(this).closest('form').find('.Actions');
						if ($(ui.newTab).closest('li').hasClass('readonly')) {
							actions.fadeOut();
						} else {
							actions.show();
						}
					}
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

		$('#filters-button').entwine({
			onmatch: function onmatch() {
				this._super();

				this.data('collapsed', true);
				this.data('animating', false);
			},
			onunmatch: function onunmatch() {
				this._super();
			},
			showHide: function showHide() {
				var self = this,
				    $filters = $('.cms-content-filters').first(),
				    collapsed = this.data('collapsed');

				if (this.data('animating')) {
					return;
				}

				this.toggleClass('active');
				this.data('animating', true);

				$filters[collapsed ? 'slideDown' : 'slideUp']({
					complete: function complete() {
						self.data('collapsed', !collapsed);
						self.data('animating', false);
					}
				});
			},
			onclick: function onclick() {
				this.showHide();
			}
		});
	});

	var statusMessage = function statusMessage(text, type) {
		text = jQuery('<div/>').text(text).html();
		jQuery.noticeAdd({ text: text, type: type, stayTime: 5000, inEffect: { left: '0', opacity: 'show' } });
	};

	var errorMessage = function errorMessage(text) {
		jQuery.noticeAdd({ text: text, type: 'error', stayTime: 5000, inEffect: { left: '0', opacity: 'show' } });
	};
});