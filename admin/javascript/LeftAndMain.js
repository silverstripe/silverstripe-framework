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
		}
		
		$(window).bind('resize', positionLoadingSpinner).trigger('resize');

		// global ajax error handlers
		$.ajaxSetup({
			error: function(xmlhttp, status, error) {
				var msg = (xmlhttp.getResponseHeader('X-Status')) ? xmlhttp.getResponseHeader('X-Status') : xmlhttp.statusText;
				statusMessage(msg, 'bad');
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
					// Simulates a redirect on an ajax response - just exchange the URL without re-requesting it
					if(window.History.enabled) {
						var url = data.xmlhttp.getResponseHeader('X-ControllerURL');
						if(url) window.history.replaceState({}, '', url);
					}
					
					self.redraw()
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
			
			redraw: function() {
				// Move from inner to outer layouts. Some of the elements might not exist.
				// Not all edit forms are layouted, so qualify by their data value.
				
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
				var data = data || {};
				var selector = data.selector || '.cms-content'
				var contentEl = $(selector);
				
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
					window.location = url;
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
			 * 
			 * Alternatively, you can load new content via $('.cms-content').loadForm(<url>).
			 * In this case, the action won't be recorded in the browser history.
			 */
			handleStateChange: function() {
				var self = this, h = window.History, state = h.getState(); 
				
				// Don't allow parallel loading to avoid edge cases
				if(this.getCurrentXHR()) this.getCurrentXHR().abort();
				
				var selector = state.data.selector || '.cms-content', contentEl = $(selector);
				
				this.trigger('beforestatechange', {
					state: state, element: contentEl
				});

				contentEl.addClass('loading');
				
				var xhr = $.ajax({
					url: state.url,
					success: function(data, status, xhr) {
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
						
						var origLayoutClasses = $.grep(
							elemClasses.split(' '),
							function(val) { 
								return ($.inArray(val, layoutClasses) >= 0);
							}
						);
						
						newContentEl
							.removeClass(layoutClasses.join(' '))
							.addClass(origLayoutClasses.join(' '));
						if(origStyle) newContentEl.attr('style', origStyle)
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

						// Simulates a redirect on an ajax response - just exchange the URL without re-requesting it
						if(window.History.enabled) {
							var url = xhr.getResponseHeader('X-ControllerURL');
							if(url) window.history.replaceState({}, '', url);
						}
						
						self.trigger('afterstatechange', {data: data, status: status, xhr: xhr, element: newContentEl});
					},
					error: function(xhr, status, e) {
						contentEl.removeClass('loading');
					}
				});
				
				this.setCurrentXHR(xhr);
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
			}
		});

		$('.cms .ss-ui-button').entwine({
			onmatch: function() {
				if(!this.data('button')) this.button();

				this._super();
			}
		});

		/**
		 * Trigger dialogs with iframe based on the links href attribute (see ssui-core.js).
		 */
		$('.cms-container .ss-ui-dialog-link').entwine({
			UUID: null,
			onmatch: function() {
				this._super();
				this.setUUID(new Date().getTime());
			},
			onclick: function() {
				this._super();

				var self = this, id = 'ss-ui-dialog-' + this.getUUID();

				var dialog = $('#' + id);
				if(!dialog.length) {
					dialog = $('<div class="ss-ui-dialog" id="' + id + '" />');
					$('body').append(dialog);
				}
			
				dialog.ssdialog({iframeUrl: this.attr('href'), autoOpen: true});
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
				// .children().removeClass('ui-corner-all').addClass('buttonset')
				// 	.first().addClass('ui-corner-left').end()
				// 	.last().addClass('ui-corner-right');;
		}
	});
		
		/**
		 * Duplicates functionality in DateField.js, but due to using entwine we can match
		 * the DOM element on creation, rather than onclick - which allows us to decorate
		 * the field with a calendar icon
		 */
		$('.cms-container .field.date input.text').entwine({
			onmatch: function() {
				var holder = $(this).parents('.field.date:first'), config = holder.data();
				if(!config.showcalendar) return;

				config.showOn = 'button';
				if(config.locale && $.datepicker.regional[config.locale]) {
					config = $.extend(config, $.datepicker.regional[config.locale], {});
				}

				$(this).datepicker(config);
				// // Unfortunately jQuery UI only allows configuration of icon images, not sprites
				// this.next('button').button('option', 'icons', {primary : 'ui-icon-calendar'});
				
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
		
		$('.cms-container .field.dropdown').entwine({
			onmatch: function() {
				$(this).find("select:not(.no-chzn)").chosen();
				$(this).addClass("has-chzn");
				
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
	});	 
}(jQuery));

// Backwards compatibility
var statusMessage = function(text, type) {
	jQuery.noticeAdd({text: text, type: type});
};

var errorMessage = function(text) {
	jQuery.noticeAdd({text: text, type: 'error'});
};

returnFalse = function() {
	return false;
};