jQuery.noConflict();

/**
 * File: LeftAndMain.js
 */
(function($) {
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
		var applyChosen = function(el){
			if(el.outerWidth()){
				el.chosen().addClass("has-chzn");
				// Copy over title attribute if required
				if(el.attr('title')) el.siblings('.chzn-container').attr('title', el.attr('title'));
			} else {
				setTimeout(function() {applyChosen(el);},500);
			}
		};
		
		$(window).bind('resize', positionLoadingSpinner).trigger('resize');

		// global ajax handlers
		$(document).ajaxComplete(function(e, xhr, settings) {
			// Simulates a redirect on an ajax response.
			if(window.History.enabled) {
				var url = xhr.getResponseHeader('X-ControllerURL');
				// Normalize trailing slashes in URL to work around routing weirdnesses in SS_HTTPRequest.
				var isSame = (url && History.getPageUrl().replace(/\/+$/, '') == url.replace(/\/+$/, ''));
				if(url && !isSame) {
					var opts = {
						pjax: settings.headers ? settings.headers['X-Pjax'] : null, 
						selector: settings.headers ? settings.headers['X-Pjax-Selector'] : null
					};
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
		 *  reloadeditform - ...
		 */
		$('.cms-container').entwine({
			
			CurrentXHR: null,
			
			/**
			 * Constructor: onmatch
			 */
			onmatch: function() {
				var self = this;

				// Browser detection
				if($.browser.msie && parseInt($.browser.version, 10) < 7) {
					$('.ss-loading-screen').append(
						'<p class="ss-loading-incompat-warning"><span class="notice">' + 
						'Your browser is not compatible with the CMS interface. Please use Internet Explorer 7+, Google Chrome 10+ or Mozilla Firefox 3.5+.' +
						'</span></p>'
					).css('z-index', $('.ss-loading-screen').css('z-index')+1);
					$('.loading-animation').remove();

					this._super();
					return;
				}
				
				// Initialize layouts
				this.redraw();

				// Monitor window resizes, panel changes and edit form loads for layout changes.
				// Also triggers redraw through handleStateChange()
				$(window).resize(function() {
					self.redraw();
				});
				
				$('.cms-panel').live('toggle', function() {
					self.redraw();
				});
				
				$('.cms-edit-form').live('reloadeditform', function(e, data) {
					self.redraw();
				});
				
				// Remove loading screen
				$('.ss-loading-screen').hide();
				$('body').removeClass('loading');
				$(window).unbind('resize', positionLoadingSpinner);
				
				History.Adapter.bind(window,'statechange',function(){ 
					self.handleStateChange();
				});

				this._super();
			},
			onunmatch: function() {
				this._super();
			},
			
			redraw: function() {
				// Move from inner to outer layouts. Some of the elements might not exist.
				// Not all edit forms are layouted, so qualify by their data value.
				this.find('.cms-content-fields[data-layout-type]').redraw(); 
				this.find('.cms-edit-form[data-layout-type]').redraw(); 
				
				// Only redraw preview if its visible
				this.find('.cms-preview').redraw();

				// Only redraw the content area if its not the same as the edit form
				var contentEl = this.find('.cms-content');
				if(!contentEl.is('.cms-edit-form')) contentEl.redraw();
				
				this.layout({resize: false});
		
				this.find('.cms-panel-layout').redraw(); // sidebar panels.
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
			 */
			loadPanel: function(url, title, data) {
				if(!data) data = {};
				if(!title) title = "";
				if(!data.selector) data.selector = '.cms-content';
				var contentEl = $(data.selector);
				
				// Check change tracking (can't use events as we need a way to cancel the current state change)
				var trackedEls = contentEl.find(':data(changetracker)').add(contentEl.filter(':data(changetracker)'));
				
				if(trackedEls.length) {
					var abort = false;
					
					trackedEls.each(function() {
						if(!$(this).confirmUnsavedChanges()) abort = true;
					});
					
					if(abort) return;
				}
				
				if(window.History.enabled) {
					// Active menu item is set based on X-Controller ajax header,
					// which matches one class on the menu
					window.History.pushState(data, title, url);
				} else {
					window.location = $.path.makeUrlAbsolute(url, $('base').attr('href'));
				}
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
				var self = this, h = window.History, state = h.getState(); 
				
				// Don't allow parallel loading to avoid edge cases
				if(this.getCurrentXHR()) this.getCurrentXHR().abort();
				
				var selector = state.data.selector || '.cms-content', contentEl = $(selector);
				
				this.trigger('beforestatechange', {
					state: state, element: contentEl
				});

				var headers = {};
				if(state.data.pjax) {
					headers['X-Pjax'] = state.data.pjax;
				} else {
					// Replace full RHS content area
					headers["X-Pjax"] = 'Content';
				}
				headers['X-Pjax-Selector'] = selector;

				contentEl.addClass('loading');
				var xhr = $.ajax({
					headers: headers,
					url: state.url,
					success: function(data, status, xhr) {
						// Pseudo-redirects via X-ControllerURL might return empty data, in which
						// case we'll ignore the response
						if(!data) return;

						// Update title
						var title = xhr.getResponseHeader('X-Title');
						if(title) document.title = title;
						
						// Update panels
						var newContentEl = $(data);
						if(newContentEl.find('.cms-container').length) {
							throw 'Content loaded via ajax is not allowed to contain tags matching the ".cms-container" selector to avoid infinite loops';
						}
						
						// Set loading state and store element state
						newContentEl.addClass('loading');
						var origStyle = contentEl.attr('style');
						var layoutClasses = ['east', 'west', 'center', 'north', 'south'];
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
						newContentEl.css('visibility', 'hidden');

						// Allow injection of inline styles, as they're not allowed in the document body.
						// Not handling this through jQuery.ondemand to avoid parsing the DOM twice.
						var styles = newContentEl.find('style').detach();
						if(styles.length) $(document).find('head').append(styles);

						// Replace panel completely (we need to override the "layout" attribute, so can't replace the child instead)
						contentEl.replaceWith(newContentEl);

						// Unset loading and restore element state (to avoid breaking existing panel visibility, e.g. with preview expanded)
						self.redraw();
						newContentEl.css('visibility', 'visible');
						newContentEl.removeClass('loading');

						self.trigger('afterstatechange', {data: data, status: status, xhr: xhr, element: newContentEl});
					},
					error: function(xhr, status, e) {
						contentEl.removeClass('loading');
						errorMessage(e);
					}
				});
				
				this.setCurrentXHR(xhr);
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
			}
		});
		
		$('.cms-content-fields').entwine({
			redraw: function() {
				this.layout();
			}
		});

		/**
		 * Make all buttons "hoverable" with jQuery theming.
		 * Also sets the clicked button on a form submission, making it available through
		 * a new 'clickedButton' property on the form DOM element.
		 */
		$('.cms input[type="submit"], .cms button, .cms input[type="reset"]').entwine({
			onmatch: function() {
				if(!this.hasClass('ss-ui-button')) this.addClass('ss-ui-button');
				
				this._super();
			},
			onunmatch: function() {
				this._super();
			}
		});

		$('.cms .ss-ui-button').entwine({
			onmatch: function() {
				if(!this.data('button')) this.button();

				this._super();
			},
			onunmatch: function() {
				this._super();
			}
		});

		/**
		 * Loads the link's 'href' attribute into a panel via ajax,
		 * as opposed to triggering a full page reload.
		 * Little helper to avoid repetition, and make it easy to
		 * "opt in" to panel loading, while by default links still exhibit their default behaviour.
		 * Same goes for breadcrumbs in the CMS.
		 */
		$('.cms .cms-panel-link').entwine({
			onclick: function(e) {
				var href = this.attr('href'), 
					url = (href && !href.match(/^#/)) ? href : this.data('href'),
					data = {
						selector: this.data('targetPanel'),
						pjax: this.data('pjax')
					};

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
							if (typeof msg != "undefined" && msg != null) eval(msg);
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
		$('.cms .Actions').entwine({
			onmatch: function() {
				this.find('.ss-ui-button').click(function() {
						var form = this.form;
						// forms don't natively store the button they've been triggered with
						if(form) {
							form.clickedButton = this;
							// Reset the clicked button shortly after the onsubmit handlers
							// have fired on the form
							setTimeout(function() {form.clickedButton = null;}, 10);
						}
					});

				this.redraw();
				this._super();
			},
			onunmatch: function() {
				this._super();
			},
			redraw: function() {
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
		
		$('.cms .field.dropdown select, .cms .field select[multiple]').entwine({
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
				this.layout({
					resize: false
				});
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
				if(window.location.search) url += window.location.search;
				$('.cms-container').loadPanel(url);
			}
		});


		/**
		 * Generic search form in the CMS, often hooked up to a GridField results display.
		 */	
		$('.cms-search-form').entwine({

			onsubmit: function() {
				// Remove empty elements and make the URL prettier
				var nonEmptyInputs = this.find(':input:not(:submit)').filter(function() {
					// Use fieldValue() from jQuery.form plugin rather than jQuery.val(),
					// as it handles checkbox values more consistently
					var vals = $.grep($(this).fieldValue(), function(val) { return (val);});
					return (vals.length);
				});
				var url = this.attr('action');
				if(nonEmptyInputs.length) url = $.path.addSearchParams(url, nonEmptyInputs.serialize());

				var container = this.closest('.cms-container');
				container.find('.cms-edit-form').tabs('select',0);  //always switch to the first tab (list view) when searching
				container.loadPanel(url);
				return false;
			},

			/**
			 * Resets are processed on the serverside, so need to trigger a submit.
			 */
			onreset: function(e) {
				this.clearForm();
				this.submit();
			}

		});

		/**
		 * Simple toggle link, which points to a DOm element by its ID selector
		 * in the href attribute (which doubles as an anchor link to that element).
		 */
		$('.cms .cms-help-toggle').entwine({
			onmatch: function() {
				this._super();

				$(this.attr('href')).hide();
			},
			onunmatch: function() {
				this._super();
			},
			onclick: function(e) {
				$(this.attr('href')).toggle();
				e.preventDefault();
			}
		});

		/**
		 * Allows to lazy load a panel, by leaving it empty
		 * and declaring a URL to load its content via a 'url' HTML5 data attribute.
		 * The loaded HTML is cached, with cache key being the 'url' attribute.
		 * In order for this to work consistently, we assume that the responses are stateless.
		 * To avoid caching, add a 'deferred-no-cache' to the node.
		 */
		window._panelDeferredCache = {};
		$('.cms-panel-deferred').entwine({
			onmatch: function() {
				this._super();
				this.redraw();
			},
			onunmatch: function() {
				// Save the HTML state at the last possible moment.
				// Don't store the DOM to avoid memory leaks.
				if(!this.data('deferredNoCache')) window._panelDeferredCache[this.data('url')] = this.html();
				this._super();
			},
			redraw: function() {
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
			onmatch: function() {
				// Can't name redraw() as it clashes with other CMS entwine classes
				this.redrawTabs();
				this._super();
			},
			onunmatch: function() {
				this._super();
			},
			redrawTabs: function() {
				this.rewriteHashlinks();

				var id = this.attr('id'), cookieId = 'ui-tabs-' + id, 
					selectedTab = this.find('ul:first .ui-state-selected');

				// Fix for wrong cookie storage of deselected tabs
				if($.cookie && id && $.cookie(cookieId) == -1) $.cookie(cookieId, 0);
				this.tabs({
					cookie: ($.cookie && id) ? { expires: 30, path: '/', name: cookieId } : false,
					ajaxOptions: {
						// Overwrite ajax loading to use CMS logic instead
						beforeSend: function(xhr, settings) {
							var makeAbs = $.path.makeUrlAbsolute,
								baseUrl = $('base').attr('href'),
								isSame = (makeAbs(settings.url, baseUrl) == makeAbs(document.location.href));
								
							if(!isSame) $('.cms-container').loadPanel(settings.url);
							return false;
						}
					},
					selected: (selectedTab.index() != -1) ? selectedTab.index() : 0
				});
			},
		
			/**
			 * Replace prefixes for all hashlinks in tabs.
			 * SSViewer rewrites them from "#Root_MyTab" to
			 * e.g. "/admin/#Root_MyTab" which makes them
			 * unusable for jQuery UI.
			 */
			rewriteHashlinks: function() {
				$(this).find('ul a').each(function() {
					var href = $(this).attr('href').replace(/.*(#.*)/, '$1');
					if(href) $(this).attr('href', href);
				});
			}
		});
	});
	
}(jQuery));

var statusMessage = function(text, type) {
	jQuery.noticeAdd({text: text, type: type});
};

var errorMessage = function(text) {
	jQuery.noticeAdd({text: text, type: 'error'});
};
