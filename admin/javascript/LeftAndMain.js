/**
 * File: LeftAndMain.js
 */

(function($) {
	$.entwine('ss', function($){
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
	
		// setup jquery.entwine
		$.entwine.warningLevel = $.entwine.WARN_LEVEL_BESTPRACTISE;
	
		// global ajax error handlers
		$.ajaxSetup({
			error: function(xmlhttp, status, error) {
				var msg = (xmlhttp.getResponseHeader('X-Status')) ? xmlhttp.getResponseHeader('X-Status') : xmlhttp.statusText;
				statusMessage(msg, 'bad');
			}
		});
		
		/**
		 * Class: .LeftAndMain
		 * 
		 * Main LeftAndMain interface with some control panel and an edit form.
		 * 
		 * Events:
		 *  ajaxsubmit - ...
		 *  validate - ...
		 *  loadnewpage - ...
		 */
		$('.LeftAndMain').entwine({

			/**
			 * Variable: PingIntervalSeconds
			 * (Number) Interval in which /Security/ping will be checked for a valid login session.
			 */
			PingIntervalSeconds: 5*60,

			/**
			 * Constructor: onmatch
			 */
			onmatch: function() {
				var self = this;

				// Remove loading screen
				$('.ss-loading-screen').hide();
				$('body').removeClass('stillLoading');
				$(window).unbind('resize', positionLoadingSpinner);

				this._setupPinging();

				// If tab has no nested tabs, set overflow to auto
				$(this).find('.tab').not(':has(.tab)').css('overflow', 'auto');

				// @todo Doesn't resize properly if the response doesn't contain a tabset (see above)
				$('#Form_EditForm').bind('loadnewpage', function() {
					// HACK Delay resizing to give jquery-ui tabs a change their dimensions
					// through dynamically added css classes
					var timerID = "timerLeftAndMainResize";
					if (window[timerID]) clearTimeout(window[timerID]);
					window[timerID] = setTimeout(function() {
						self._resizeChildren();
					}, 200);
				});

				this._super();
			},

			/**
			 * Function: _setupPinging
			 * 
			 * This function is called by prototype when it receives notification that the user was logged out.
			 * It uses /Security/ping for this purpose, which should return '1' if a valid user session exists.
			 * It redirects back to the login form if the URL is either unreachable, or returns '0'.
			 */
			_setupPinging: function() {
				var onSessionLost = function(xmlhttp, status) {
					if(xmlhttp.status > 400 || xmlhttp.responseText == 0) {
						// TODO will pile up additional alerts when left unattended
						if(window.open('Security/login')) {
						    alert("Please log in and then try again");
						} else {
						    alert("Please enable pop-ups for this site");
						}
					}
				};

				// setup pinging for login expiry
				setInterval(function() {
					jQuery.ajax({
						url: "Security/ping",
						global: false,
						complete: onSessionLost
					});
				}, this.getPingIntervalSeconds() * 1000);
			},

			/**
			 * Function: _resizeChildren
			 * 
			 * Resize elements in center panel
			 * to fit the boundary box provided by the layout manager.
			 * 
			 * Todo:
			 *  Replace with automated less ugly parent/sibling traversal
			 */
			_resizeChildren: function() {
				$("#treepanes", this).accordion("resize");
				$('#sitetree_and_tools', this).fitHeightToParent();
				$('#contentPanel form', this).fitHeightToParent();
				$('#contentPanel form fieldset', this).fitHeightToParent();
				$('#contentPanel form fieldset .content', this).fitHeightToParent();
				$('#Form_EditForm').fitHeightToParent();
				$('#Form_EditForm fieldset', this).fitHeightToParent();
				// Order of resizing is important: Outer to inner
				// TODO Only supports two levels of tabs at the moment
				$('#Form_EditForm fieldset > .ss-tabset', this).fitHeightToParent();
				$('#Form_EditForm fieldset > .ss-tabset > .tab', this).fitHeightToParent();
				$('#Form_EditForm fieldset > .ss-tabset > .tab > .ss-tabset', this).fitHeightToParent();
				$('#Form_EditForm fieldset > .ss-tabset > .tab > .ss-tabset > .tab', this).fitHeightToParent();
			}
		});

		/**
		 * Class: .LeftAndMain :submit, .LeftAndMain button, .LeftAndMain :reset
		 * 
		 * Make all buttons "hoverable" with jQuery theming.
		 * Also sets the clicked button on a form submission, making it available through
		 * a new 'clickedButton' property on the form DOM element.
		 */
		$('.LeftAndMain :submit, .LeftAndMain button, .LeftAndMain :reset').entwine({
			
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
		 * Class: #TreeActions
		 * 
		 * Container for tree actions like "create", "search", etc.
		 */
		$('#TreeActions').entwine({
			/**
			 * Constructor: onmatch
			 * 
			 * Setup "create", "search", "batch actions" layers above tree.
			 * All tab contents are closed by default.
			 */
			onmatch: function() {
				this.tabs({
					collapsible: true,
					selected: parseInt(jQuery.cookie('ui-tabs-TreeActions'), 10) || null,
					cookie: { expires: 30, path: '/', name: 'ui-tabs-TreeActions' }
				});
			}
		});

		/**
		 * Class: a#EditMemberProfile
		 * 
		 * Link for editing the profile for a logged-in member through a modal dialog.
		 */
		$('a#EditMemberProfile').entwine({
			
			/**
			 * Constructor: onmatch
			 */
			onmatch: function() {
				var self = this;

				this.bind('click', function(e) {return self._openPopup();});

				$('body').append(
					'<div id="ss-ui-dialog">'
					+ '<iframe id="ss-ui-dialog-iframe" '
					+ 'marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto">'
					+ '</iframe>'
					+ '</div>'
				);

				var cookieVal = (jQuery.cookie) ? JSON.parse(jQuery.cookie('ss-ui-dialog')) : false;
				$("#ss-ui-dialog").dialog(jQuery.extend({
					autoOpen: false,
					bgiframe: true,
					modal: true,
					height: 300,
					width: 500,
					ghost: true,
					resizeStop: function(e, ui) {
						self._resize();
						self._saveState();
					},
					dragStop: function(e, ui) {
						self._saveState();
					},
					// TODO i18n
					title: 'Edit Profile'
				}, cookieVal)).css('overflow', 'hidden');

				$('#ss-ui-dialog-iframe').bind('load', function(e) {self._resize();});
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
							height: parseInt(container.height(), 10),
							position: [
								parseInt(container.offset().top, 10),
								parseInt(container.offset().left, 10)
							]
						}),
						{ expires: 30, path: '/'}
					);
				}
			}
		});
		
		/**
		 * Class: #switchView a
		 * 
		 * Updates the different stage links which are generated through 
		 * the SilverStripeNavigator class on the serverside each time a form record
		 * is reloaded.
		 */
		$('#switchView').entwine({
			onmatch: function() {
				this._super();
				
				$('#Form_EditForm').bind('loadnewpage delete', function(e) {
					var updatedSwitchView = $('#AjaxSwitchView');
					if(updatedSwitchView.length) {
						$('#SwitchView').html(updatedSwitchView.html());
						updatedSwitchView.remove();
					}
				});
			}
		});

		/**
		 * Class: #switchView a
		 * 
		 * Links for viewing the currently loaded page
		 * in different modes: 'live', 'stage' or 'archived'.
		 * 
		 * Requires:
		 *  jquery.metadata
		 */
		$('#switchView a').entwine({
			/**
			 * Function: onclick
			 */
			onclick: function(e) {
				// Open in popup
				window.open($(e.target).attr('href'));
				return false;
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