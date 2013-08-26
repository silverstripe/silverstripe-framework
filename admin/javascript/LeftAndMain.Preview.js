(function($) {
	$.entwine('ss.preview', function($){

		/**
		 * Shows a previewable website state alongside its editable version in backend UI.
		 *
		 * Relies on the server responses to indicate if a preview is available for the 
		 * currently loaded admin interface - signified by class ".cms-previewable" being present.
		 *
		 * The preview options at the bottom are constructured by grabbing a SilverStripeNavigator 
		 * structure also provided by the backend.
		 */
		$('.cms-preview').entwine({

			/**
			 * List of SilverStripeNavigator states (SilverStripeNavigatorItem classes) to search for.
			 * The order is significant - if the state is not available, preview will start searching the list
			 * from the beginning.
			 */
			AllowedStates: ['StageLink', 'LiveLink','ArchiveLink'],

			/**
			 * API
			 * Name of the current preview state - one of the "AllowedStates".
			 */
			CurrentStateName: null,

			/**
			 * API
			 * Current size selection.
			 */
			CurrentSizeName: 'auto',

			/**
			 * Flags whether the preview is available on this CMS section.
			 */
			IsPreviewEnabled: false,

			/**
			 * Mode in which the preview will be enabled.
			 */
			DefaultMode: 'split',

			Sizes: {
				auto: {
					width: '100%',
					height: '100%'
				},
				mobile: {
					width: '335px', // add 15px for approx desktop scrollbar 
					height: '568px' 
				},
				mobileLandscape: {
					width: '583px', // add 15px for approx desktop scrollbar
					height: '320px'
				},
				tablet: {
					width: '783px', // add 15px for approx desktop scrollbar
					height: '1024px'
				},
				tabletLandscape: {
					width: '1039px', // add 15px for approx desktop scrollbar
					height: '768px'
				},
				desktop: {
					width: '1024px',
					height: '800px'
				}
			},

			/**
			 * API
			 * Switch the preview to different state.
			 * stateName can be one of the "AllowedStates".
			 *
			 * @param {String}
			 * @param {Boolean} Set to FALSE to avoid persisting the state
			 */
			changeState: function(stateName, save) {				
				var self = this, states = this._getNavigatorStates();
				if(save !== false) {
					$.each(states, function(index, state) {
						self.saveState('state', stateName);
					});
				}

				this.setCurrentStateName(stateName);
				this._loadCurrentState();
				this.redraw();

				return this;
			},

			/**
			 * API
			 * Change the preview mode.
			 * modeName can be: split, content, preview.
			 */
			changeMode: function(modeName, save) {				
				var container = $('.cms-container');

				if (modeName == 'split') {
					container.entwine('.ss').splitViewMode();
					this.setIsPreviewEnabled(true);
					this._loadCurrentState();
				} else if (modeName == 'content') {
					container.entwine('.ss').contentViewMode();
					this.setIsPreviewEnabled(false);
					// Do not load content as the preview is not visible.
				} else if (modeName == 'preview') {
					container.entwine('.ss').previewMode();
					this.setIsPreviewEnabled(true);
					this._loadCurrentState();
				} else {
					throw 'Invalid mode: ' + modeName;
				}

				if(save !== false) this.saveState('mode', modeName);

				this.redraw();

				return this;
			},

			/**
			 * API
			 * Change the preview size.
			 * sizeName can be: auto, desktop, tablet, mobile.
			 */
			changeSize: function(sizeName) {
				var sizes = this.getSizes();

				this.setCurrentSizeName(sizeName);
				this.removeClass('auto desktop tablet mobile').addClass(sizeName);
				this.find('.preview-device-outer')
					.width(sizes[sizeName].width)
					.height(sizes[sizeName].height);
				this.find('.preview-device-inner')
					.width(sizes[sizeName].width);

				this.saveState('size', sizeName);

				this.redraw();

				return this;
			},

			/**
			 * API
			 * Update the visual appearance to match the internal preview state.
			 */
			redraw: function() {			

				if(window.debug) console.log('redraw', this.attr('class'), this.get(0));

				// Update preview state selector.
				var currentStateName = this.getCurrentStateName();
				if (currentStateName) {
					this.find('.cms-preview-states').changeVisibleState(currentStateName);
				}

				// Update preview mode selectors.
				var layoutOptions = $('.cms-container').entwine('.ss').getLayoutOptions();
				if (layoutOptions) {
					// There are two mode selectors that we need to keep in sync. Redraw both.
					$('.preview-mode-selector').changeVisibleMode(layoutOptions.mode);
				}

				// Update preview size selector.
				var currentSizeName = this.getCurrentSizeName();
				if (currentSizeName) {
					this.find('.preview-size-selector').changeVisibleSize(this.getCurrentSizeName());
				}

				return this;
			},

			/**
			 * Store the preview options for this page.
			 */
			saveState : function(name, value) {
				if(!window.localStorage) return;
				
				window.localStorage.setItem('cms-preview-state-' + name, value);
			},

			/**
			 * Load previously stored preferences
			 */
			loadState : function(name) {
				if(!window.localStorage) return;
				
				return window.localStorage.getItem('cms-preview-state-' + name);
			}, 

			/**
			 * Disable the area - it will not appear in the GUI.
			 * Caveat: the preview will be automatically enabled when ".cms-previewable" class is detected.
			 */
			disablePreview: function() {
				this.setPendingURL(null);
				this._loadUrl('about:blank');
				this._block();
				this.changeMode('content', false);
				this.setIsPreviewEnabled(false);
				return this;
			},

			/**
			 * Enable the area and start updating to reflect the content editing.
			 */
			enablePreview: function() {
				if (!this.getIsPreviewEnabled()) {
					this.setIsPreviewEnabled(true);

					// Initialise mode.
					if ($.browser.msie && $.browser.version.slice(0,3)<=7) {
						// We do not support the split mode in IE < 8.
						this.changeMode('content');
					} else {
						this.changeMode(this.getDefaultMode(), false);
					}
				}
				return this;
			},

			/**
			 * Return a style element we can use in IE8 to fix fonts (see readystatechange binding in onadd below)
			 */
			getOrAppendFontFixStyleElement: function() {
				var style = $('#FontFixStyleElement');
				if (!style.length) {
					style = $(
						'<style type="text/css" id="FontFixStyleElement" disabled="disabled">'+
							':before,:after{content:none !important}'+
						'</style>'
					).appendTo('head');
				}

				return style;
			},

			/**
			 * Initialise the preview element.
			 */
			onadd: function() {
				var self = this, layoutContainer = this.parent(), iframe = this.find('iframe');

				// Create layout and controls
				iframe.addClass('center');
				iframe.bind('load', function() {
					self._adjustIframeForPreview();

					// Load edit view for new page, but only if the preview is activated at the moment.
					// This avoids e.g. force-redirections of the edit view on RedirectorPage instances.
					self._loadCurrentPage();
					
					$(this).removeClass('loading');
				});

				// If there's any webfonts in the preview, IE8 will start glitching. This fixes that.
				if ($.browser.msie && 8 === parseInt($.browser.version, 10)) {
					iframe.bind('readystatechange', function(e) {
						if(iframe[0].readyState == 'interactive') {
							self.getOrAppendFontFixStyleElement().removeAttr('disabled');
							setTimeout(function(){ self.getOrAppendFontFixStyleElement().attr('disabled', 'disabled'); }, 0);
						}
					});
				}

				// Preview might not be available in all admin interfaces - block/disable when necessary
				this.append('<div class="cms-preview-overlay ui-widget-overlay-light"></div>');
				this.find('.cms-preview-overlay').hide();			

				this.disablePreview();

				this._super();
			},

			/**
			 * Set the preview to unavailable - could be still visible. This is purely visual.
			 */
			_block: function() {
				this.addClass('blocked');
				this.find('.cms-preview-overlay').show();
				return this;
			},

			/**
			 * Set the preview to available (remove the overlay);
			 */
			_unblock: function() {
				this.removeClass('blocked');
				this.find('.cms-preview-overlay').hide();
				return this;
			},

			/**
			 * Update the preview according to browser and CMS section capabilities.
			 */
			_initialiseFromContent: function() {
				var mode, size;

				if (!$('.cms-previewable').length) {
					this.disablePreview();
				} else {
					mode = this.loadState('mode');
					size = this.loadState('size');

					this._moveNavigator();
					if(!mode || mode != 'content') {
						this.enablePreview();
						this._loadCurrentState();
					}
					this.redraw();

					// now check the cookie to see if we have any preview settings that have been
					// retained for this page from the last visit
					if(mode) this.changeMode(mode);
					if(size) this.changeSize(size);
				}
				return this;
			},

			/**
			 * Update preview whenever any panels are reloaded.
			 */
			'from .cms-container': {
				onafterstatechange: function(){
					this._initialiseFromContent();
				}
			},

			/** @var string A URL that should be displayed in this preview panel once it becomes visible */
			PendingURL: null,

			oncolumnvisibilitychanged: function() {
				var url = this.getPendingURL();
				if (url && !this.is('.column-hidden')) {
					this.setPendingURL(null);
					this._loadUrl(url);
					this._unblock();
				}
			},

			/**
			 * Update preview whenever a form is submitted.
			 * This is an alternative to the LeftAndmMain::loadPanel functionality which we already
			 * cover in the onafterstatechange handler.
			 */
			'from .cms-container .cms-edit-form': {
				onaftersubmitform: function(){
					this._initialiseFromContent();
				}
			},

			/**
			 * Change the URL of the preview iframe (if its not already displayed).
			 */
			_loadUrl: function(url) {
				this.find('iframe').addClass('loading').attr('src', url);
				return this;
			},

			/**
			 * Fetch available states from the current SilverStripeNavigator (SilverStripeNavigatorItems).
			 * Navigator is supplied by the backend and contains all state options for the current object.
			 */
			_getNavigatorStates: function() {
				// Walk through available states and get the URLs.
				var urlMap = $.map(this.getAllowedStates(), function(name) {
					var stateLink = $('.cms-preview-states .state-name[data-name=' + name + ']');
					if(stateLink.length) {
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

			/**
			 * Load current state into the preview (e.g. StageLink or LiveLink).
			 * We try to reuse the state we have been previously in. Otherwise we fall back
			 * to the first state available on the "AllowedStates" list.
			 *
			 * @returns New state name.
			 */
			_loadCurrentState: function() {
				if (!this.getIsPreviewEnabled()) return this;

				var states = this._getNavigatorStates();
				var currentStateName = this.getCurrentStateName();
				var currentState = null;

				// Find current state within currently available states.
				if (states) {
					currentState = $.grep(states, function(state, index) {
						return (
							currentStateName === state.name ||
							(!currentStateName && state.active)
						);
					});
				}

				var url = null;

				if (currentState[0]) {
					// State is available on the newly loaded content. Get it.
					url = currentState[0].url;
				} else if (states.length) {
					// Fall back to the first available content state.
					this.setCurrentStateName(states[0].name);
					url = states[0].url;
				} else {
					// No state available at all.
					this.setCurrentStateName(null);
				}

				// If this preview panel isn't visible at the moment, delay loading the URL until it (maybe) is later
				if (this.is('.column-hidden')) {
					this.setPendingURL(url);
					this._loadUrl('about:blank');
					this._block();
				}
				else {
					this.setPendingURL(null);

					if (url) {
						this._loadUrl(url);
						this._unblock();
					}
					else {
						this._block();
					}
				}

				return this;
			},

			/**
			 * Move the navigator from the content to the preview bar.
			 */
			_moveNavigator: function() {
				var previewEl = $('.cms-preview .cms-preview-controls');
				var navigatorEl = $('.cms-edit-form .cms-navigator');

				if (navigatorEl.length && previewEl.length) {
					// Navigator is available - install the navigator.
					previewEl.html($('.cms-edit-form .cms-navigator').detach());
				} else {
					// Navigator not available.
					this._block();
				}
			},

			/**
			 * Loads the matching edit form for a page viewed in the preview iframe,
			 * based on metadata sent along with this document.
			 */
			_loadCurrentPage: function() {
				if (!this.getIsPreviewEnabled()) return;

				var doc = this.find('iframe')[0].contentDocument,
					containerEl = $('.cms-container');

				// Load this page in the admin interface if appropriate
				var id = $(doc).find('meta[name=x-page-id]').attr('content'); 
				var editLink = $(doc).find('meta[name=x-cms-edit-link]').attr('content');
				var contentPanel = $('.cms-content');
				
				if(id && contentPanel.find(':input[name=ID]').val() != id) {
					// Ignore behaviour without history support (as we need ajax loading 
					// for the new form to load in the background)
					if(window.History.enabled) 
						$('.cms-container').entwine('.ss').loadPanel(editLink);
				}
			},

			/**
			 * Prepare the iframe content for preview.
			 */
			_adjustIframeForPreview: function() {
				var iframe = this.find('iframe')[0];
				if(iframe){
					var doc = iframe.contentDocument;
				}else{
					return;
				}
		
				if(!doc) return;

				// Open external links in new window to avoid "escaping" the internal page context in the preview
				// iframe, which is important to stay in for the CMS logic.
				var links = doc.getElementsByTagName('A');
				for (var i = 0; i < links.length; i++) {
					var href = links[i].getAttribute('href');
					if(!href) continue;
					
					if (href.match(/^http:\/\//)) links[i].setAttribute('target', '_blank');
				}

				// Hide the navigator from the preview iframe and use only the CMS one.
				var navi = doc.getElementById('SilverStripeNavigator');
				if(navi) navi.style.display = 'none';
				var naviMsg = doc.getElementById('SilverStripeNavigatorMessage');
				if(naviMsg) naviMsg.style.display = 'none';

				// Trigger extensions.
				this.trigger('afterIframeAdjustedForPreview', [ doc ]);
			}
		});

		$('.cms-edit-form').entwine({
			onadd: function() {	
				$('.cms-preview')._initialiseFromContent();
			}
		});
		
		/**
		 * "Preview state" functions.
		 * -------------------------------------------------------------------
		 */
		$('.cms-preview-states').entwine({
			/**
			 * Change the appearance of the state selector.
			 */
			changeVisibleState: function(state) {
				this.find('input[data-name="'+state+'"]').prop('checked', true);
			}
		});

		$('.cms-preview-states .state-name').entwine({
			/**
			 * Reacts to the user changing the state of the preview.
			 */
			onclick: function(e) {	
				//Add and remove classes to make switch work ok in old IE
				this.parent().find('.active').removeClass('active');
				this.next('label').addClass('active');

				var targetStateName = $(this).attr('data-name');
				// Reload preview with the selected state.
				$('.cms-preview').changeState(targetStateName);				
			}
		});	
		
		/**
		 * "Preview mode" functions
		 * -------------------------------------------------------------------
		 */
		$('.preview-mode-selector').entwine({
			/**
			 * Change the appearance of the mode selector.
			 */
			changeVisibleMode: function(mode) {
				this.find('select')
					.val(mode)
					.trigger('liszt:updated')
					._addIcon();
			}
		});	

		$('.preview-mode-selector select').entwine({
			/**
			 * Reacts to the user changing the preview mode.
			 */
			onchange: function(e) {				
				this._super(e);
				e.preventDefault();

				var targetStateName = $(this).val();
				$('.cms-preview').changeMode(targetStateName);
			}
		});

		
		$('.preview-mode-selector .chzn-results li').entwine({
			/**
			 *  IE8 doesn't support programatic access to onchange event 
			 *	so react on click
			 */
			onclick:function(e){				
				if ($.browser.msie) {
					e.preventDefault();					
					var index = this.index();
					var targetStateName = this.closest('.preview-mode-selector').find('select option:eq('+index+')').val();					
													
					//var targetStateName = $(this).val();
					$('.cms-preview').changeMode(targetStateName);
				}
			}
		});
		
		/**
		 * Adjust the visibility of the preview-mode selector in the CMS part (hidden if preview is visible).
		 */
		$('.cms-preview.column-hidden').entwine({
			onmatch: function() {
				$('#preview-mode-dropdown-in-content').show();
				this._super();
			},
			onunmatch: function() {
				$('#preview-mode-dropdown-in-content').hide();
				this._super();
			}
		});

		/**
		 * Initialise the preview-mode selector in the CMS part (could be hidden if preview is visible).
		 */
		$('#preview-mode-dropdown-in-content').entwine({
			onmatch: function() {
				if ($('.cms-preview').is('.column-hidden')) {
					this.show();
				}
				else {
					this.hide();
				}
				this._super();
			},
			onunmatch: function() {
				this._super();
			}
		});

		/**
		 * "Preview size" functions
		 * -------------------------------------------------------------------
		 */
		$('.preview-size-selector').entwine({
			/**
			 * Change the appearance of the size selector.
			 */
			changeVisibleSize: function(size) {				
				this.find('select')
					.val(size)
					.trigger('liszt:updated')
					._addIcon();
			}
		});

		$('.preview-size-selector select').entwine({
			/**
			 * Trigger change in the preview size.
			 */
			onchange: function(e) {
				e.preventDefault();

				var targetSizeName = $(this).val();
				$('.cms-preview').changeSize(targetSizeName);
			}
		});

		
		/**
		 * "Chosen" plumbing.
		 * -------------------------------------------------------------------
		 */

		/*
		*	Add a class to the chzn select trigger based on the currently 
		*	selected option. Update as this changes
		*/
		$('.preview-selector select.preview-dropdown').entwine({
			'onliszt:showing_dropdown': function() {
				this.siblings().find('.chzn-drop').addClass('open')._alignRight();
			},

			'onliszt:hiding_dropdown': function() {
				this.siblings().find('.chzn-drop').removeClass('open')._removeRightAlign();
			},

			/**
			 * Trigger additional initial icon update when the control is fully loaded.
			 * Solves an IE8 timing issue.
			 */
			'onliszt:ready': function() {
				this._super();
				this._addIcon();
			},

			_addIcon: function(){
				var selected = this.find(':selected');				
				var iconClass = selected.attr('data-icon');	
								
				var target = this.parent().find('.chzn-container a.chzn-single');
				var oldIcon = target.attr('data-icon');
				if(typeof oldIcon !== 'undefined'){
					target.removeClass(oldIcon);
				}
				target.addClass(iconClass);
				target.attr('data-icon', iconClass);				

				return this;
			}
		});

		$('.preview-selector .chzn-drop').entwine({
			_alignRight: function(){
				var that = this;
				$(this).hide();
				/* Delay so styles applied after chosen applies css	
				   (the line after we find out the dropdown is open)
				*/
				setTimeout(function(){ 
					$(that).css({left:'auto', right:0});
					$(that).show();	
				}, 100);							
			},
			_removeRightAlign:function(){
				$(this).css({right:'auto'});
			}

		});

		/* 
		* Means of having extra styled data in chzn 'preview-selector' selects 
		* When chzn ul is ready, grab data-description from original select. 
		* If it exists, append to option and add description class to list item
		*/
		/*

		Currently buggy (adds dexcription, then re-renders). This may need to 
		be done inside chosen. Chosen recommends to do this stuff in the css, 
		but that option is inaccessible and untranslatable 
		(https://github.com/harvesthq/chosen/issues/399)

		$('.preview-selector .chzn-drop ul').entwine({
			onmatch: function() {
				this.extraData();
				this._super();
			},
			onunmatch: function() {
				this._super();
			},
			extraData: function(){
				var that = this;
				var options = this.closest('.preview-selector').find('select option');	
					
				$.each(options, function(index, option){
					var target = $(that).find("li:eq(" + index + ")");
					var description = $(option).attr('data-description');
					if(description != undefined && !$(target).hasClass('description')){
						$(target).append('<span>' + description + '</span>');
						$(target).addClass('description');						
					}
				});
			}
		}); */

		/**
		 * Recalculate the preview space to allow for horizontal scrollbar and the preview actions panel
		 */
		$('.preview-scroll').entwine({
			/**
			 * Height of the preview actions panel
			 */
			ToolbarSize: 53,

			_redraw: function() {
				var toolbarSize = this.getToolbarSize();

				if(window.debug) console.log('redraw', this.attr('class'), this.get(0));
				var previewHeight = (this.height() - toolbarSize);
				this.height(previewHeight);
			}, 

			onmatch: function() {
				this._redraw();
				this._super();
			},

			onunmatch: function() {
				this._super();
			}
			// TODO: Need to recalculate on resize of browser

		});

		/**
		 * Rotate preview to landscape
		 */
		$('.preview-device-outer').click(function() {
			if(!$('.preview-device-outer').hasClass('rotate')) {
				$('.preview-device-outer').addClass('rotate');
			} else {
				$('.preview-device-outer').removeClass('rotate');
			}
		});
	});
}(jQuery));
