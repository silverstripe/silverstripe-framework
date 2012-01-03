/**
 * File: LeftAndMain.js
 */
(function($) {
	$.metadata.setType('html5');
	
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
				this.find('.cms-edit-form[data-layout]').redraw(); 
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
							.addClass(origLayoutClasses.join(' '))
							.attr('style', origStyle)
							.css('visibility', 'hidden');

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
				this.addClass('ss-ui-button');
				this.redraw();
				
				this._super();
			}
		});

		/**
		 * Class: a#profile-link
		 * 
		 * Link for editing the profile for a logged-in member through a modal dialog.
		 */
		$('.cms-container .profile-link').entwine({
			DialogPadding: 40,
			MaxHeight: 800,
			MaxWidth: 800,
			MinHeight: 120,
			MinWidth: 120,
			
			/**
			 * Constructor: onmatch
			 */
			onmatch: function() {
				this.bind('click', function(e) {
					return self._openPopup();
				});
				
				var self = this;

				$('body').append(
					'<div id="ss-ui-dialog">'
					+ '<iframe id="ss-ui-dialog-iframe" '
					+ 'marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto">'
					+ '</iframe>'
					+ '</div>'
				);

				$('#ss-ui-dialog-iframe').bind('load', function(e) {
					self._resize();
				});
				
				$(window).bind('resize', function() {
					self._resize();
				});
				
				self.redraw();
			},
			
			/**
			 * Function: redraw
			 *
			 * Returns: void
			 */
			redraw: function() {
				var self = this;
				var useCookie = false;
				var cookieVal = false;
				
				if(useCookie && jQuery.cookie && jQuery.cookie('ss-ui-dialog')) {
					cookieVal = JSON.parse(jQuery.cookie('ss-ui-dialog'));
				}

				$("#ss-ui-dialog").dialog(jQuery.extend({
					autoOpen: false,
					bgiframe: true,
					modal: true,
					width: self._width(),
					height: self._height(),
					position: 'center',
					
					resizeStop: function(e, ui) {
						self._resize();
					},
					
					dragStop: function(e, ui) {
						self._saveState();
					},
					// TODO i18n
					title: 'Edit Profile'
				}, cookieVal)).css('overflow', 'hidden');
				
			},
			
			/**
			 * Function: _popupHeight
			 * 
			 * Returns a value > minHeight < max height
			 * Returns: Int
			 */
			_height: function() {
				var marginTop = parseInt($(this).css('margin-top').replace('px', ''));
				var marginBottom = parseInt($(this).css('margin-bottom').replace('px', ''));
				var body = $("body").height();

				var height = body - (marginTop + marginBottom) - (this.getDialogPadding() * 2);
				
				if(height > this.getMaxHeight()) 
					return this.getMaxHeight();
				else if(height < this.getMinHeight())
					return this.getMinHeight();
				
				return height;
			},
			
			/**
			 * Function: _popupWidth
			 *
			 * Returns: Int
			 */
			_width: function() {
				var body = $("body").width();
				var width = body - (this.getDialogPadding() * 2);	
				
				if(width > this.getMaxWidth()) 
					return this.getMaxWidth();
				else if(width < this.getMinWidth())
					return this.getMinWidth();
					
				return width;
			},

			/**
			 * Function: _openPopup
			 */
			_openPopup: function(e) {
				$('#ss-ui-dialog-iframe').attr('src', this.attr('href'));

				$("#ss-ui-dialog").dialog('open');

				return false;
			},

			/**
			 * Function: _resize
			 */
			_resize: function() {
				var iframe = $('#ss-ui-dialog-iframe');
				var container = $('#ss-ui-dialog');
				
				container.dialog("option", "width", this._width());
				container.dialog("option", "height", this._height());
				container.dialog('option', 'position', 'center');
				
				iframe.attr('width', 
					container.innerWidth() 
					- parseFloat(container.css('paddingLeft'))
					- parseFloat(container.css('paddingRight'))
				);
				iframe.attr('height', 
					container.innerHeight()
					- parseFloat(container.css('paddingTop')) 
					- parseFloat(container.css('paddingBottom'))
				);
				
				this._saveState();
			},

			/**
			 * Function: _saveState
			 */
			_saveState: function() {
				var container = $('#ss-ui-dialog');

				// save size in cookie (optional)
				if(jQuery.cookie && container.width() && container.height()) {
					jQuery.cookie(
						'ss-ui-dialog',
						JSON.stringify({
							width: parseInt(container.width(), 10), 
							height: parseInt(container.height(), 10)
						}),
						{ expires: 30, path: '/'}
					);
				}
			}
		});
		
		/**
		 * Duplicates functionality in DateField.js, but due to using entwine we can match
		 * the DOM element on creation, rather than onclick - which allows us to decorate
		 * the field with a calendar icon
		 */
		$('.cms-container .field.date input.text').entwine({
			onmatch: function() {
				var holder = $(this).parents('.field.date:first'), config = holder.metadata({type: 'class'});
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

/**
 * Find and enable TinyMCE on all htmleditor fields
 * Pulled in from old tinymce.template.js
 */

function nullConverter(url) {
	return url;
};