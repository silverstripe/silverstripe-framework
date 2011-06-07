/**
 * File: LeftAndMain.js
 */
(function($) {

	$.metadata.setType('html5');
	
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
			 * Constructor: onmatch
			 */
			onmatch: function() {
				var self = this;
				
				// Browser detection
				if($.browser.msie && parseInt($.browser.version, 10) < 7) {
					$('.ss-loading-screen').append(
						'<p><span class="notice">' + 
						ss.i18n._t('LeftAndMain.IncompatBrowserWarning') +
						'</span></p>'
					);
					return;
				}
				
				// Initialize layouts, inner to outer
				this.redraw();
				$(window).resize(function() {self.redraw()});
				
				// Remove loading screen
				$('.ss-loading-screen').hide();
				$('body').removeClass('loading');
				$(window).unbind('resize', positionLoadingSpinner);

				$('.cms-edit-form').live('loadnewpage', function() {self.redraw()});

				this._super();
			},
			
			redraw: function() {
				// Not all edit forms are layouted
				var editForm = this.find('.cms-edit-form[data-layout]').layout();
				this.find('.cms-content').layout();
				this.find('.cms-container').layout({resize: false})
			}
		});
		
		/**
		 * Monitor all panels for layout changes
		 */
		$('.LeftAndMain .cms-panel').entwine({
			ontoggle: function(e) {
				this.parents('.LeftAndMain').redraw();
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
			onmatch: function() {
				// TODO Adding classes in onmatch confuses entwine
				var self = this;
				setTimeout(function() {self.addClass('ss-ui-button');}, 10);
				
				this._super();
			}
		});

		/**
		 * Class: a#profile-link
		 * 
		 * Link for editing the profile for a logged-in member through a modal dialog.
		 */
		$('.LeftAndMain .profile-link').entwine({
			
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
				
				$('.cms-edit-form').bind('loadnewpage delete', function(e) {
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
		
		/**
		 * Duplicates functionality in DateField.js, but due to using entwine we can match
		 * the DOM element on creation, rather than onclick - which allows us to decorate
		 * the field with a calendar icon
		 */
		$('.LeftAndMain .field.date input.text').entwine({
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
		})
		
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