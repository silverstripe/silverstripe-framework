jQuery.noConflict();

/**
 * File: LeftAndMain.js
 */
(function($) {

	var windowWidth, windowHeight;
	$(window).bind('resize.leftandmain', function(e) {
		// Entwine's 'fromWindow::onresize' does not trigger on IE8. Use synthetic event.
		var cb = function() {$('.cms-container').trigger('windowresize');};

		// Workaround to avoid IE8 infinite loops when elements are resized as a result of this event 
		if($.browser.msie && parseInt($.browser.version, 10) < 9) {
			var newWindowWidth = $(window).width(), newWindowHeight = $(window).height();
			if(newWindowWidth != windowWidth || newWindowHeight != windowHeight) {
				windowWidth = newWindowWidth;
				windowHeight = newWindowHeight;
				cb();
			}
		} else {
			cb();
		}
	});

	// setup jquery.entwine
	$.entwine.warningLevel = $.entwine.WARN_LEVEL_BESTPRACTISE;
	$.entwine('ss', function($) {
		/**
		 * Position the loading spinner animation below the ss logo
		 */ 
		var positionLoadingSpinner = function() {
			var offset = 120; // offset from the ss logo
			var spinner = $('.ss-loading-screen .loading-animation'); 
			var top = ($(window).height() - spinner.height()) / 2;
			spinner.css('top', top + offset);
			spinner.show();
		};
		
		// apply an select element only when it is ready, ie. when it is rendered into a template
		// with css applied and got a width value.
		var applyChosen = function(el) {
			if(el.is(':visible')) {
				el.addClass('has-chzn').chosen({
					allow_single_deselect: true,
					disable_search_threshold: 20
				});

				var title = el.prop('title');

				if(title) {
					el.siblings('.chzn-container').prop('title', title);
				}
			} else {
				setTimeout(function() {
					// Make sure it's visible before applying the ui
					el.show();
					applyChosen(el); }, 
				500);
			}
		};

		/**
		 * Compare URLs, but normalize trailing slashes in 
		 * URL to work around routing weirdnesses in SS_HTTPRequest.
		 * Also normalizes relative URLs by prefixing them with the <base>.
		 */
		var isSameUrl = function(url1, url2) {
			var baseUrl = $('base').attr('href');
			url1 = $.path.isAbsoluteUrl(url1) ? url1 : $.path.makeUrlAbsolute(url1, baseUrl),
			url2 = $.path.isAbsoluteUrl(url2) ? url2 : $.path.makeUrlAbsolute(url2, baseUrl);
			var url1parts = $.path.parseUrl(url1), url2parts = $.path.parseUrl(url2);
			return (
				url1parts.pathname.replace(/\/*$/, '') == url2parts.pathname.replace(/\/*$/, '') && 
				url1parts.search == url2parts.search
			);
		};
		
		$(window).bind('resize', positionLoadingSpinner).trigger('resize');

		// global ajax handlers
		$(document).ajaxComplete(function(e, xhr, settings) {
			// Simulates a redirect on an ajax response.
			if(window.History.enabled) {
				var url = xhr.getResponseHeader('X-ControllerURL'), 
					// TODO Replaces trailing slashes added by History after locale (e.g. admin/?locale=en/)
					origUrl = History.getPageUrl().replace(/\/$/, ''),
					opts, requestHeaders = settings.headers;

				if(url !== null && !isSameUrl(origUrl, url)) {
					opts = {pjax: xhr.getResponseHeader('X-Pjax') ? xhr.getResponseHeader('X-Pjax') : settings.headers['X-Pjax']};
					window.History.pushState(opts, '', url);
				}
			}

			// Handle custom status message headers
			var msg = (xhr.getResponseHeader('X-Status')) ? xhr.getResponseHeader('X-Status') : xhr.statusText,
				msgType = (xhr.status < 200 || xhr.status > 399) ? 'bad' : 'good',
				ignoredMessages = ['OK'];

			// Show message (but ignore aborted requests)
			if(xhr.status !== 0 && msg && $.inArray(msg, ignoredMessages)) {
				// Decode into UTF-8, HTTP headers don't allow multibyte
				statusMessage(decodeURIComponent(msg), msgType);
			}
		});

		/**
		 * Main LeftAndMain interface with some control panel and an edit form.
		 * 
		 * Events:
		 *  ajaxsubmit - ...
		 *  validate - ...
		 *  aftersubmitform - ...
		 */
		$('.cms-container').entwine({
			
			/**
			 * Tracks current panel request.
			 */
			StateChangeXHR: null,

			/**
			 * Tracks current fragment-only parallel PJAX requests.
			 */
			FragmentXHR: {},

			StateChangeCount: 0,
			
			/**
			 * Options for the threeColumnCompressor layout algorithm.
			 *
			 * See LeftAndMain.Layout.js for description of these options.
			 */
			LayoutOptions: {
				minContentWidth: 820,
				minPreviewWidth: 400,
				mode: 'content'
			},

			/**
			 * Constructor: onmatch
			 */
			onadd: function() {
				var self = this;

				// Browser detection
				if($.browser.msie && parseInt($.browser.version, 10) < 8) {
					$('.ss-loading-screen').append(
						'<p class="ss-loading-incompat-warning"><span class="notice">' + 
						'Your browser is not compatible with the CMS interface. Please use Internet Explorer 8+, Google Chrome or Mozilla Firefox.' +
						'</span></p>'
					).css('z-index', $('.ss-loading-screen').css('z-index')+1);
					$('.loading-animation').remove();

					this._super();
					return;
				}
				
				// Initialize layouts
				this.redraw();

				// Remove loading screen
				$('.ss-loading-screen').hide();
				$('body').removeClass('loading');
				$(window).unbind('resize', positionLoadingSpinner);
				this.restoreTabState();
				
				this._super();
			},

			fromWindow: {
				onstatechange: function(){ this.handleStateChange(); }
			},

			'onwindowresize': function() {
				this.redraw();
			},

			'from .cms-panel': {
				ontoggle: function(){ this.redraw(); }
			},

			'from .cms-container': {
				onaftersubmitform: function(){ this.redraw(); }
			},

			/**
			 * Ensure the user can see the requested section - restore the default view.
			 */
			'from .cms-menu-list li a': {
				onclick: function(e) {
					var href = $(e.target).attr('href');
					if(e.which > 1 || href == this._tabStateUrl()) return;
					this.splitViewMode();
				}
			},

			/**
			 * Change the options of the threeColumnCompressor layout, and trigger layouting if needed.
			 * You can provide any or all options. The remaining options will not be changed.
			 */
			updateLayoutOptions: function(newSpec) {
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

			/**
			 * Enable the split view - with content on the left and preview on the right.
			 */
			splitViewMode: function() {
				this.updateLayoutOptions({
					mode: 'split'
				});
			},

			/**
			 * Content only.
			 */
			contentViewMode: function() {
				this.updateLayoutOptions({
					mode: 'content'
				});
			},

			/**
			 * Preview only.
			 */
			previewMode: function() {
				this.updateLayoutOptions({
					mode: 'preview'
				});
			},

			RedrawSuppression: false,

			redraw: function() {
				if (this.getRedrawSuppression()) return;

				if(window.debug) console.log('redraw', this.attr('class'), this.get(0));

				// Reset the algorithm.
				this.data('jlayout', jLayout.threeColumnCompressor(
					{
						menu: this.children('.cms-menu'),
						content: this.children('.cms-content'),
						preview: this.children('.cms-preview')
					},
					this.getLayoutOptions()
				));

				// Trigger layout algorithm once at the top. This also lays out children - we move from outside to
				// inside, resizing to fit the parent.
				this.layout();

				// Redraw on all the children that need it
				this.find('.cms-panel-layout').redraw();
				this.find('.cms-content-fields[data-layout-type]').redraw();
				this.find('.cms-edit-form[data-layout-type]').redraw();
				this.find('.cms-preview').redraw();
				this.find('.cms-content').redraw();
			},

			/**
			 * Proxy around History.pushState() which handles non-HTML5 fallbacks,
			 * as well as global change tracking. Change tracking needs to be synchronous rather than event/callback
			 * based because the user needs to be able to abort the action completely.
			 * 
			 * See handleStateChange() for more details.
			 * 
			 * Parameters:
			 *  - {String} url
			 *  - {String} title New window title
			 *  - {Object} data Any additional data passed through to History.pushState()
			 *  - {boolean} forceReload Forces the replacement of the current history state, even if the URL is the same, i.e. allows reloading.
			 */
			loadPanel: function(url, title, data, forceReload, forceReferer) {
				if(!data) data = {};
				if(!title) title = "";
				if (!forceReferer) forceReferer = History.getState().url;

				// Check change tracking (can't use events as we need a way to cancel the current state change)
				var contentEls = this._findFragments(data.pjax ? data.pjax.split(',') : ['Content']);
				var trackedEls = contentEls.find(':data(changetracker)').add(contentEls.filter(':data(changetracker)'));
				
				if(trackedEls.length) {
					var abort = false;
					
					trackedEls.each(function() {
						if(!$(this).confirmUnsavedChanges()) abort = true;
					});
					
					if(abort) return;
				}

				// Save tab selections so we can restore them later
				this.saveTabState();
				
				if(window.History.enabled) {
					$.extend(data, {__forceReferer: forceReferer});
					// Active menu item is set based on X-Controller ajax header,
					// which matches one class on the menu
					if(forceReload) {
						// Add a parameter to make sure the page gets reloaded even if the URL is the same.
						$.extend(data, {__forceReload: Math.random()});
						window.History.replaceState(data, title, url);
					} else {
						window.History.pushState(data, title, url);
					}
				} else {
					window.location = $.path.makeUrlAbsolute(url, $('base').attr('href'));
				}
			},

			/**
			 * Nice wrapper for reloading current history state.
			 */
			reloadCurrentPanel: function() {
				this.loadPanel(window.History.getState().url, null, null, true);
			},

			/**
			 * Function: submitForm
			 * 
			 * Parameters:
			 *  {DOMElement} form - The form to be submitted. Needs to be passed
			 *   in to avoid entwine methods/context being removed through replacing the node itself.
			 *  {DOMElement} button - The pressed button (optional)
			 *  {Function} callback - Called in complete() handler of jQuery.ajax()
			 *  {Object} ajaxOptions - Object literal to merge into $.ajax() call
			 * 
			 * Returns:
			 *  (boolean)
			 */
			submitForm: function(form, button, callback, ajaxOptions) {
				var self = this;
		
				// look for save button
				if(!button) button = this.find('.Actions :submit[name=action_save]');
				// default to first button if none given - simulates browser behaviour
				if(!button) button = this.find('.Actions :submit:first');
	
				form.trigger('beforesubmitform');
				this.trigger('submitform', {form: form, button: button});
	
				// set button to "submitting" state
				$(button).addClass('loading');
	
				// validate if required
				var validationResult = form.validate();
				if(typeof validationResult!=='undefined' && !validationResult) {
					// TODO Automatically switch to the tab/position of the first error
					statusMessage("Validation failed.", "bad");

					$(button).removeClass('loading');

					return false;
				}
				
				// get all data from the form
				var formData = form.serializeArray();
				// add button action
				formData.push({name: $(button).attr('name'), value:'1'});
				// Artificial HTTP referer, IE doesn't submit them via ajax. 
				// Also rewrites anchors to their page counterparts, which is important
				// as automatic browser ajax response redirects seem to discard the hash/fragment.
				formData.push({name: 'BackURL', value:History.getPageUrl()});

				// Save tab selections so we can restore them later
				this.saveTabState();

				// Standard Pjax behaviour is to replace the submitted form with new content.
				// The returned view isn't always decided upon when the request
				// is fired, so the server might decide to change it based on its own logic,
				// sending back different `X-Pjax` headers and content
				jQuery.ajax(jQuery.extend({
					headers: {"X-Pjax" : "CurrentForm,Breadcrumbs"},
					url: form.attr('action'), 
					data: formData,
					type: 'POST',
					complete: function() {
						$(button).removeClass('loading');
					},
					success: function(data, status, xhr) {
						form.removeClass('changed'); // TODO This should be using the plugin API
						if(callback) callback(data, status, xhr);

						var newContentEls = self.handleAjaxResponse(data, status, xhr);
						if(!newContentEls) return;

						newContentEls.filter('form').trigger('aftersubmitform', {status: status, xhr: xhr, formData: formData});
					}
				}, ajaxOptions));
	
				return false;
			},

			/**
			 * Handles ajax loading of new panels through the window.History object.
			 * To trigger loading, pass a new URL to window.History.pushState().
			 * Use loadPanel() as a pushState() wrapper as it provides some additional functionality
			 * like global changetracking and user aborts.
			 * 
			 * Due to the nature of history management, no callbacks are allowed.
			 * Use the 'beforestatechange' and 'afterstatechange' events instead,
			 * or overwrite the beforeLoad() and afterLoad() methods on the 
			 * DOM element you're loading the new content into.
			 * Although you can pass data into pushState(), it shouldn't contain 
			 * DOM elements or callback closures.
			 * 
			 * The passed URL should allow reconstructing important interface state
			 * without additional parameters, in the following use cases:
			 * - Explicit loading through History.pushState()
			 * - Implicit loading through browser navigation event triggered by the user (forward or back)
			 * - Full window refresh without ajax
			 * For example, a ModelAdmin search event should contain the search terms
			 * as URL parameters, and the result display should automatically appear 
			 * if the URL is loaded without ajax.
			 */
			handleStateChange: function() {
				// Don't allow parallel loading to avoid edge cases
				if(this.getStateChangeXHR()) this.getStateChangeXHR().abort();

				var self = this, h = window.History, state = h.getState(),
					fragments = state.data.pjax || 'Content', headers = {},
					fragmentsArr = fragments.split(','),
					contentEls = this._findFragments(fragmentsArr);

				// For legacy IE versions (IE7 and IE8), reload without ajax
				// as a crude way to fix memory leaks through whole window refreshes.
				this.setStateChangeCount(this.getStateChangeCount() + 1);
				var isLegacyIE = ($.browser.msie && parseInt($.browser.version, 10) < 9);
				if(isLegacyIE && this.getStateChangeCount() > 20) {
					document.location.href = state.url;
					return;
				}

				// If any of the requested Pjax fragments don't exist in the current view,
				// fetch the "Content" view instead, which is the "outermost" fragment
				// that can be reloaded without reloading the whole window.
				if(contentEls.length < fragmentsArr.length) {
					fragments = 'Content', fragmentsArr = ['Content'];
					contentEls = this._findFragments(fragmentsArr);					
				}
				
				this.trigger('beforestatechange', {state: state, element: contentEls});

				// Set Pjax headers, which can declare a preference for the returned view.
				// The actually returned view isn't always decided upon when the request
				// is fired, so the server might decide to change it based on its own logic.
				headers['X-Pjax'] = fragments;
		
				// Set 'fake' referer - we call pushState() before making the AJAX request, so we have to
				// set our own referer here
				if (typeof state.data.__forceReferer !== 'undefined') {
					headers['X-Backurl'] = state.data.__forceReferer;
				}
				
				contentEls.addClass('loading');
				var xhr = $.ajax({
					headers: headers,
					url: state.url,
					complete: function() {
						self.setStateChangeXHR(null);
						// Remove loading indication from old content els (regardless of which are replaced)
						contentEls.removeClass('loading');
					},
					success: function(data, status, xhr) {
						var els = self.handleAjaxResponse(data, status, xhr, state);
						self.trigger('afterstatechange', {data: data, status: status, xhr: xhr, element: els, state: state});
					}
				});
				
				this.setStateChangeXHR(xhr);
			},

			/**
			 * ALternative to loadPanel/submitForm.
			 *
			 * Triggers a parallel-fetch of a PJAX fragment, which is a separate request to the
			 * state change requests. There could be any amount of these fetches going on in the background,
			 * and they don't register as a HTML5 history states.
			 *
			 * This is meant for updating a PJAX areas that are not complete panel/form reloads. These you'd
			 * normally do via submitForm or loadPanel which have a lot of automation built in.
			 *
			 * On receiving successful response, the framework will update the element tagged with appropriate
			 * data-pjax-fragment attribute (e.g. data-pjax-fragment="<pjax-fragment-name>"). Make sure this element
			 * is available.
			 *
			 * Example usage:
			 * $('.cms-container').loadFragment('admin/foobar/', 'FragmentName');
			 *
			 * @param url string Relative or absolute url of the controller.
			 * @param pjaxFragments string PJAX fragment(s), comma separated.
			 */
			loadFragment: function(url, pjaxFragments) {

				var self = this,
					xhr,
					headers = {},
					baseUrl = $('base').attr('href'),
					fragmentXHR = this.getFragmentXHR();

				// Make sure only one XHR for a specific fragment is currently in progress.
				if(
					typeof fragmentXHR[pjaxFragments]!=='undefined' &&
					fragmentXHR[pjaxFragments]!==null
				) {
					fragmentXHR[pjaxFragments].abort();
					fragmentXHR[pjaxFragments] = null;
				}

				url = $.path.isAbsoluteUrl(url) ? url : $.path.makeUrlAbsolute(url, baseUrl);
				headers['X-Pjax'] = pjaxFragments;

				xhr = $.ajax({
					headers: headers,
					url: url,
					success: function(data, status, xhr) {
						var elements = self.handleAjaxResponse(data, status, xhr, null);

						// We are fully done now, make it possible for others to hook in here.
						self.trigger('afterloadfragment', { data: data, status: status, xhr: xhr, elements: elements });
					},
					error: function(xhr, status, error) {
						self.trigger('loadfragmenterror', { xhr: xhr, status: status, error: error });
					},
					complete: function() {
						// Reset the current XHR in tracking object.
						var fragmentXHR = self.getFragmentXHR();
						if(
							typeof fragmentXHR[pjaxFragments]!=='undefined' &&
							fragmentXHR[pjaxFragments]!==null
						) {
							fragmentXHR[pjaxFragments] = null;
						}
					}
				});

				// Store the fragment request so we can abort later, should we get a duplicate request.
				fragmentXHR[pjaxFragments] = xhr;

				return xhr;
			},

			/**
			 * Handles ajax responses containing plain HTML, or mulitple
			 * PJAX fragments wrapped in JSON (see PjaxResponseNegotiator PHP class).
			 * Can be hooked into an ajax 'success' callback.
			 *
			 * Parameters:
			 * 	(Object) data
			 * 	(String) status
			 * 	(XMLHTTPRequest) xhr
			 * 	(Object) state The original history state which the request was initiated with
			 */
			handleAjaxResponse: function(data, status, xhr, state) {
				var self = this, url, selectedTabs, guessFragment;

				// Support a full reload
				if(xhr.getResponseHeader('X-Reload') && xhr.getResponseHeader('X-ControllerURL')) {
					document.location.href = $('base').attr('href').replace(/\/*$/, '') 
						+ '/' + xhr.getResponseHeader('X-ControllerURL');
					return;
				}

				// Pseudo-redirects via X-ControllerURL might return empty data, in which
				// case we'll ignore the response
				if(!data) return;

				// Update title
				var title = xhr.getResponseHeader('X-Title');
				if(title) document.title = decodeURIComponent(title.replace(/\+/g, ' '));

				var newFragments = {}, newContentEls;
				// If content type is text/json (ignoring charset and other parameters)
				if(xhr.getResponseHeader('Content-Type').match(/^text\/json[ \t]*;?/i)) {
					newFragments = data;
				} else {
					// Fall back to replacing the content fragment if HTML is returned
					$data = $(data);

					// Try and guess the fragment if none is provided
					// TODO: data-pjax-fragment might actually give us the fragment. For now we just check most common case
					guessFragment = 'Content';
					if ($data.is('form') && !$data.is('[data-pjax-fragment~=Content]')) guessFragment = 'CurrentForm';

					newFragments[guessFragment] = $data;
				}

				this.setRedrawSuppression(true);
				try {
					// Replace each fragment individually
					$.each(newFragments, function(newFragment, html) {
						var contentEl = $('[data-pjax-fragment]').filter(function() {
							return $.inArray(newFragment, $(this).data('pjaxFragment').split(' ')) != -1;
						}), newContentEl = $(html);

						// Add to result collection
						if(newContentEls) newContentEls.add(newContentEl);
						else newContentEls = newContentEl;

						// Update panels
						if(newContentEl.find('.cms-container').length) {
							throw 'Content loaded via ajax is not allowed to contain tags matching the ".cms-container" selector to avoid infinite loops';
						}

						// Set loading state and store element state
						var origStyle = contentEl.attr('style');
						var origParent = contentEl.parent();
						var origParentLayoutApplied = (typeof origParent.data('jlayout')!=='undefined');
						var layoutClasses = ['east', 'west', 'center', 'north', 'south', 'column-hidden'];
						var elemClasses = contentEl.attr('class');
						var origLayoutClasses = [];
						if(elemClasses) {
							origLayoutClasses = $.grep(
								elemClasses.split(' '),
								function(val) { return ($.inArray(val, layoutClasses) >= 0);}
							);
						}

						newContentEl
							.removeClass(layoutClasses.join(' '))
							.addClass(origLayoutClasses.join(' '));
						if(origStyle) newContentEl.attr('style', origStyle);

						// Allow injection of inline styles, as they're not allowed in the document body.
						// Not handling this through jQuery.ondemand to avoid parsing the DOM twice.
						var styles = newContentEl.find('style').detach();
						if(styles.length) $(document).find('head').append(styles);

						// Replace panel completely (we need to override the "layout" attribute, so can't replace the child instead)
						contentEl.replaceWith(newContentEl);

						// Force jlayout to rebuild internal hierarchy to point to the new elements.
						// This is only necessary for elements that are at least 3 levels deep. 2nd level elements will
						// be taken care of when we lay out the top level element (.cms-container).
						if (!origParent.is('.cms-container') && origParentLayoutApplied) {
							origParent.layout();
						}
					});

					// Re-init tabs (in case the form tag itself is a tabset)
					var newForm = newContentEls.filter('form');
					if(newForm.hasClass('cms-tabset')) newForm.removeClass('cms-tabset').addClass('cms-tabset');
				}
				finally {
					this.setRedrawSuppression(false);
				}

				this.redraw();
				this.restoreTabState((state && typeof state.data.tabState !== 'undefined') ? state.data.tabState : null);

				return newContentEls;
			},

			/**
			 * 
			 * 
			 * Parameters: 
			 * - fragments {Array}
			 * Returns: jQuery collection
			 */
			_findFragments: function(fragments) {
				return $('[data-pjax-fragment]').filter(function() {
					// Allows for more than one fragment per node
					var i, nodeFragments = $(this).data('pjaxFragment').split(' ');
					for(i in fragments) {
						if($.inArray(fragments[i], nodeFragments) != -1) return true;
					}
					return false;
				});
			},

			/**
			 * Function: refresh
			 * 
			 * Updates the container based on the current url
			 *
			 * Returns: void
			 */
			refresh: function() {
				$(window).trigger('statechange');
				
				$(this).redraw();
			},

			/**
			 * Save tab selections in order to reconstruct them later.
			 * Requires HTML5 sessionStorage support.
			 */
			saveTabState: function() {
				if(typeof(window.sessionStorage)=="undefined" || window.sessionStorage === null) return;

				var selectedTabs = [], url = this._tabStateUrl();
				this.find('.cms-tabset,.ss-tabset').each(function(i, el) {
					var id = $(el).attr('id');
					if(!id) return; // we need a unique reference
					if(!$(el).data('tabs')) return; // don't act on uninit'ed controls

					// Allow opt-out via data element or entwine property.
					if($(el).data('ignoreTabState') || $(el).getIgnoreTabState()) return;

					selectedTabs.push({id:id, selected:$(el).tabs('option', 'selected')});
				});

				if(selectedTabs) {
					var tabsUrl = 'tabs-' + url;
					try {
						window.sessionStorage.setItem(tabsUrl, JSON.stringify(selectedTabs));
					} catch(err) {
						if (err.code === DOMException.QUOTA_EXCEEDED_ERR && window.sessionStorage.length === 0) {
							// If this fails we ignore the error as the only issue is that it 
							// does not remember the tab state.
							// This is a Safari bug which happens when private browsing is enabled.
							return;
						} else {
							throw err;
						}
					}
				}
			},

			/**
			 * Re-select previously saved tabs.
			 * Requires HTML5 sessionStorage support.
			 *
			 * Parameters:
			 * 	(Object) Map of tab container selectors to tab selectors.
			 * 	Used to mark a specific tab as active regardless of the previously saved options.
			 */
			restoreTabState: function(overrideStates) {
				var self = this, url = this._tabStateUrl(),
					hasSessionStorage = (typeof(window.sessionStorage)!=="undefined" && window.sessionStorage),
					sessionData = hasSessionStorage ? window.sessionStorage.getItem('tabs-' + url) : null,
					sessionStates = sessionData ? JSON.parse(sessionData) : false;

				this.find('.cms-tabset, .ss-tabset').each(function() {
					var index, tabset = $(this), tabsetId = tabset.attr('id'), tab,
						forcedTab = tabset.find('.ss-tabs-force-active');

					if(!tabset.data('tabs')) return; // don't act on uninit'ed controls

					// The tabs may have changed, notify the widget that it should update its internal state.
					tabset.tabs('refresh');

					// Make sure the intended tab is selected.
					if(forcedTab.length) {
						index = forcedTab.index();
					} else if(overrideStates && overrideStates[tabsetId]) {
						tab = tabset.find(overrideStates[tabsetId].tabSelector);
						if(tab.length) index = tab.index();
					} else if(sessionStates) {
						$.each(sessionStates, function(i, sessionState) {
							if(tabset.is('#' + sessionState.id)) index = sessionState.selected;
					});
				}
					if(index !== null) tabset.tabs('select', index);
				});
			},

			/**
			 * Remove any previously saved state.
			 *
			 * Parameters:
			 *  (String) url Optional (sanitized) URL to clear a specific state.
			 */
			clearTabState: function(url) {
				if(typeof(window.sessionStorage)=="undefined") return;

				var s = window.sessionStorage;
				if(url) {
					s.removeItem('tabs-' + url);	
				} else {
					for(var i=0;i<s.length;i++) {
						if(s.key(i).match(/^tabs-/)) s.removeItem(s.key(i));
				}
				}
			},

			/**
			 * Remove tab state for the current URL.
			 */
			clearCurrentTabState: function() {
				this.clearTabState(this._tabStateUrl());
			},

			_tabStateUrl: function() {
				return History.getState().url
					.replace(/\?.*/, '')
					.replace(/#.*/, '')
					.replace($('base').attr('href'), '');
			}
		});
		
		/**
		 * Add loading overlay to selected regions in the CMS automatically.
		 * Not applied to all "*.loading" elements to avoid secondary regions
		 * like the breadcrumbs showing unnecessary loading status.
		 */
		$('form.loading,.cms-content.loading,.cms-content-fields.loading,.cms-content-view.loading').entwine({
			onmatch: function() {
				this.append('<div class="cms-content-loading-overlay ui-widget-overlay-light"></div><div class="cms-content-loading-spinner"></div>');
				this._super();
			},
			onunmatch: function() {
				this.find('.cms-content-loading-overlay,.cms-content-loading-spinner').remove();
				this._super();
			}
		});

		/** Make all buttons "hoverable" with jQuery theming. */
		$('.cms input[type="submit"], .cms button, .cms input[type="reset"], .cms .ss-ui-button').entwine({
			onadd: function() {
				this.addClass('ss-ui-button');
				if(!this.data('button')) this.button();
				this._super();
			},
			onremove: function() {
				if(this.data('button')) this.button('destroy');
				this._super();
			}
		});

		/**
		 * Loads the link's 'href' attribute into a panel via ajax,
		 * as opposed to triggering a full page reload.
		 * Little helper to avoid repetition, and make it easy to
		 * "opt in" to panel loading, while by default links still exhibit their default behaviour.
		 * The PJAX target can be specified via a 'data-pjax-target' attribute.
		 */
		$('.cms .cms-panel-link').entwine({
			onclick: function(e) {
				if($(this).hasClass('external-link')) {
					e.stopPropagation();

					return;
				}

				var href = this.attr('href'), 
					url = (href && !href.match(/^#/)) ? href : this.data('href'),
					data = {pjax: this.data('pjaxTarget')};

				$('.cms-container').loadPanel(url, null, data);
				e.preventDefault();
			}
		});

		/**
		 * Does an ajax loads of the link's 'href' attribute via ajax and displays any FormResponse messages from the CMS.
		 * Little helper to avoid repetition, and make it easy to trigger actions via a link,
		 * without reloading the page, changing the URL, or loading in any new panel content.
		 */
		$('.cms .ss-ui-button-ajax').entwine({
			onclick: function(e) {
				$(this).removeClass('ui-button-text-only');
				$(this).addClass('ss-ui-button-loading ui-button-text-icons');
				
				var loading = $(this).find(".ss-ui-loading-icon");
				
				if(loading.length < 1) {
					loading = $("<span></span>").addClass('ss-ui-loading-icon ui-button-icon-primary ui-icon');
					
					$(this).prepend(loading);
				}
				
				loading.show();
				
				var href = this.attr('href'), url = href ? href : this.data('href');

				jQuery.ajax({
					url: url,
					// Ensure that form view is loaded (rather than whole "Content" template)
					complete: function(xmlhttp, status) {
						var msg = (xmlhttp.getResponseHeader('X-Status')) ? xmlhttp.getResponseHeader('X-Status') : xmlhttp.responseText;
						
						try {
							if (typeof msg != "undefined" && msg !== null) eval(msg);
						}
						catch(e) {}
						
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

		/**
		 * Trigger dialogs with iframe based on the links href attribute (see ssui-core.js).
		 */
		$('.cms .ss-ui-dialog-link').entwine({
			UUID: null,
			onmatch: function() {
				this._super();
				this.setUUID(new Date().getTime());
			},
			onunmatch: function() {
				this._super();
			},
			onclick: function() {
				this._super();

				var self = this, id = 'ss-ui-dialog-' + this.getUUID();
				var dialog = $('#' + id);
				if(!dialog.length) {
					dialog = $('<div class="ss-ui-dialog" id="' + id + '" />');
					$('body').append(dialog);
				}
				
				var extraClass = this.data('popupclass')?this.data('popupclass'):'';
				
				dialog.ssdialog({iframeUrl: this.attr('href'), autoOpen: true, dialogExtraClass: extraClass});
				return false;
			}
		});
		
		/**
		 * Add styling to all contained buttons, and create buttonsets if required.
		 */
		$('.cms-content .Actions').entwine({
			onmatch: function() {
				this.find('.ss-ui-button').click(function() {
						var form = this.form;

						// forms don't natively store the button they've been triggered with
						if(form) {
							form.clickedButton = this;
							// Reset the clicked button shortly after the onsubmit handlers
							// have fired on the form
						setTimeout(function() {
							form.clickedButton = null;
						}, 10);
						}
					});

				this.redraw();
				this._super();
			},
			onunmatch: function() {
				this._super();
			},
			redraw: function() {
				if(window.debug) console.log('redraw', this.attr('class'), this.get(0));

				// Remove whitespace to avoid gaps with inline elements
				this.contents().filter(function() { 
					return (this.nodeType == 3 && !/\S/.test(this.nodeValue)); 
				}).remove();

				// Init buttons if required
				this.find('.ss-ui-button').each(function() {
					if(!$(this).data('button')) $(this).button();
				});
				
				// Mark up buttonsets
				this.find('.ss-ui-buttonset').buttonset();
			}
		});
		
		/**
		 * Duplicates functionality in DateField.js, but due to using entwine we can match
		 * the DOM element on creation, rather than onclick - which allows us to decorate
		 * the field with a calendar icon
		 */
		$('.cms .field.date input.text').entwine({
			onmatch: function() {
				var holder = $(this).parents('.field.date:first'), config = holder.data();
				if(!config.showcalendar) {
					this._super();
					return;
				}

				config.showOn = 'button';
				if(config.locale && $.datepicker.regional[config.locale]) {
					config = $.extend(config, $.datepicker.regional[config.locale], {});
				}

				$(this).datepicker(config);
				// // Unfortunately jQuery UI only allows configuration of icon images, not sprites
				// this.next('button').button('option', 'icons', {primary : 'ui-icon-calendar'});
				
				this._super();
			},
			onunmatch: function() {
				this._super();
			}
		});
		
		/**
		 * Styled dropdown select fields via chosen. Allows things like search and optgroup
		 * selection support. Rather than manually adding classes to selects we want 
		 * styled, we style everything but the ones we tell it not to.
		 *
		 * For the CMS we also need to tell the parent div that his has a select so
		 * we can fix the height cropping.
		 */
		
		$('.cms .field.dropdown select, .cms .field select[multiple], .fieldholder-small select.dropdown').entwine({
			onmatch: function() {
				if(this.is('.no-chzn')) {
					this._super();
					return;
				}

				// Explicitly disable default placeholder if no custom one is defined
				if(!this.data('placeholder')) this.data('placeholder', ' ');

				// We could've gotten stale classes and DOM elements from deferred cache.
				this.removeClass('has-chzn chzn-done');
				this.siblings('.chzn-container').remove();

				// Apply Chosen
				applyChosen(this);
				
				this._super();
			},
			onunmatch: function() {
				this._super();
			}
		});
	
		$(".cms-panel-layout").entwine({
			redraw: function() {
				if(window.debug) console.log('redraw', this.attr('class'), this.get(0));
			}
		});
	
		/**
		 * Overload the default GridField behaviour (open a new URL in the browser)
		 * with the CMS-specific ajax loading.
		 */
		$('.cms .ss-gridfield').entwine({
			showDetailView: function(url) {
				// Include any GET parameters from the current URL, as the view state might depend on it.
				// For example, a list prefiltered through external search criteria might be passed to GridField.
				var params = window.location.search.replace(/^\?/, '');
				if(params) url = $.path.addSearchParams(url, params);
				$('.cms-container').loadPanel(url);
			}
		});


		/**
		 * Generic search form in the CMS, often hooked up to a GridField results display.
		 */	
		$('.cms-search-form').entwine({
			onsubmit: function(e) {
				// Remove empty elements and make the URL prettier
				var nonEmptyInputs,
					url;

				nonEmptyInputs = this.find(':input:not(:submit)').filter(function() {
					// Use fieldValue() from jQuery.form plugin rather than jQuery.val(),
					// as it handles checkbox values more consistently
					var vals = $.grep($(this).fieldValue(), function(val) { return (val);});
					return (vals.length);
				});

				url = this.attr('action');

				if(nonEmptyInputs.length) {
					url = $.path.addSearchParams(url, nonEmptyInputs.serialize());
				}

				var container = this.closest('.cms-container');
				container.find('.cms-edit-form').tabs('select',0);  //always switch to the first tab (list view) when searching
				container.loadPanel(url, "", {}, true);

				return false;
			}
		});

		/**
		 * Reset button handler. IE8 does not bubble reset events to
		 */
		$(".cms-search-form button[type=reset], .cms-search-form input[type=reset]").entwine({
			onclick: function(e) {
				e.preventDefault();

				var form = $(this).parents('form');

				form.clearForm();
				form.find(".dropdown select").prop('selectedIndex', 0).trigger("liszt:updated"); // Reset chosen.js
				form.submit();
				}
		})

		/**
		 * Allows to lazy load a panel, by leaving it empty
		 * and declaring a URL to load its content via a 'url' HTML5 data attribute.
		 * The loaded HTML is cached, with cache key being the 'url' attribute.
		 * In order for this to work consistently, we assume that the responses are stateless.
		 * To avoid caching, add a 'deferred-no-cache' to the node.
		 */
		window._panelDeferredCache = {};
		$('.cms-panel-deferred').entwine({
			onadd: function() {
				this._super();
				this.redraw();
			},
			onremove: function() {
				if(window.debug) console.log('saving', this.data('url'), this);
				
				// Save the HTML state at the last possible moment.
				// Don't store the DOM to avoid memory leaks.
				if(!this.data('deferredNoCache')) window._panelDeferredCache[this.data('url')] = this.html();
				this._super();
			},
			redraw: function() {
				if(window.debug) console.log('redraw', this.attr('class'), this.get(0));

				var self = this, url = this.data('url');
				if(!url) throw 'Elements of class .cms-panel-deferred need a "data-url" attribute';

				this._super();

				// If the node is empty, try to either load it from cache or via ajax.
				if(!this.children().length) {
					if(!this.data('deferredNoCache') && typeof window._panelDeferredCache[url] !== 'undefined') {
						this.html(window._panelDeferredCache[url]);
					} else {
						this.addClass('loading');
						$.ajax({
							url: url,
							complete: function() {
								self.removeClass('loading');
							},
							success: function(data, status, xhr) {
								self.html(data);
							}
						});
					}
				}
			}
		});

		/**
		 * Lightweight wrapper around jQuery UI tabs.
		 * Ensures that anchor links are set properly,
		 * and any nested tabs are scrolled if they have
		 * their height explicitly set. This is important
		 * for forms inside the CMS layout.
		 */
		$('.cms-tabset').entwine({
			onadd: function() {
				// Can't name redraw() as it clashes with other CMS entwine classes
				this.redrawTabs();
				this._super();
			},
			onremove: function() {
				if (this.data('tabs')) this.tabs('destroy');
				this._super();
			},
			redrawTabs: function() {
				this.rewriteHashlinks();

				var id = this.attr('id'), activeTab = this.find('ul:first .ui-tabs-active');

				if(!this.data('uiTabs')) this.tabs({
					active: (activeTab.index() != -1) ? activeTab.index() : 0,
					beforeLoad: function(e, ui) {
						// Disable automatic ajax loading of tabs without matching DOM elements, 
						// determining if the current URL differs from the tab URL is too error prone.
						return false;
					},
					activate: function(e, ui) {
						// Accessibility: Simulate click to trigger panel load when tab is focused
						// by a keyboard navigation event rather than a click
						if(ui.newTab) {
							ui.newTab.find('.cms-panel-link').click();
						}

						// Usability: Hide actions for "readonly" tabs (which don't contain any editable fields)
						var actions = $(this).closest('form').find('.Actions');
						if($(ui.newTab).closest('li').hasClass('readonly')) {
							actions.fadeOut();
						} else {
							actions.show();
						}
					}
				});
			},
		
			/**
			 * Ensure hash links are prefixed with the current page URL,
			 * otherwise jQuery interprets them as being external.
			 */
			rewriteHashlinks: function() {
				$(this).find('ul a').each(function() {
					if (!$(this).attr('href')) return;
					var matches = $(this).attr('href').match(/#.*/);
					if(!matches) return;
					$(this).attr('href', document.location.href.replace(/#.*/, '') + matches[0]);
				});
			}
		});
	});
	
}(jQuery));

var statusMessage = function(text, type) {
	text = jQuery('<div/>').text(text).html(); // Escape HTML entities in text
	jQuery.noticeAdd({text: text, type: type});
};

var errorMessage = function(text) {
	jQuery.noticeAdd({text: text, type: 'error'});
};
