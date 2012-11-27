(function($) {
	$.entwine('ss', function($){

		/**
		 * Shows a previewable website state alongside its editable version in backend UI, 
		 * typically a page. This allows CMS users to seamlessly switch between preview and 
		 * edit mode in the same browser window. The preview panel is embedded in the layout 
		 * of the backend UI, and loads its content via an iframe.
		 * 
		 * The admin UI itself is collapsible, leaving most screen space to this panel.
		 *
		 * Relies on the server responses to indicate if a preview URL is available for the 
		 * currently loaded admin interface. If no preview is available, the panel is "blocked"
		 * automatically.
		 * 
		 * Internal links within the preview iframe trigger a refresh of the admin panel as well,
		 * while all external links are disabled (via JavaScript).
		 */
		$('.cms-preview').entwine({
			
			onadd: function() {
				var self = this, layoutContainer = this.parent();
				// this.resizable({
				// 	handles: 'w',
				// 	stop: function(e, ui) {
				// 		$('.cms-container').layout({resize: false});
				// 	}
				// });
				
				// Create layout and controls
				this.find('iframe').addClass('center');
				this.find('iframe').bind('load', function() {
					self._fixIframeLinks();

					// Load edit view for new page, but only if the preview is activated at the moment.
					// This avoids e.g. force-redirections of the edit view on RedirectorPage instances.
					self.loadCurrentPage();
				});
				
				this.data('cms-preview-initialized', true);
				
				// Preview might not be available in all admin interfaces - block/disable when necessary
				this.append('<div class="cms-preview-overlay ui-widget-overlay-light"></div>');
				this.find('.cms-preview-overlay-light').hide();
				$('.cms-preview-toggle-link')[this.canPreview() ? 'show' : 'hide']();

				self._fixIframeLinks();
				this.updatePreview();

				this._super();
			},

			loadUrl: function(url) {
				this.find('iframe').attr('src', url);
			},

			updatePreview: function() {
				var url = $('.cms-edit-form').choosePreviewLink();

				if(url) {
					this.loadUrl(url);
					this.unblock();
				} else {
					this.block();
					this.toggle();
				}
			},

			updateAfterXhr: function(){
				$('.cms-preview-toggle-link')[this.canPreview() ? 'show' : 'hide']();
				this.updatePreview();
			},

			/**
			 * Update preview whenever any panels are reloaded.
			 */
			'from .cms-container': {
				onafterstatechange: function(){
					this.updateAfterXhr();
				}
			},

			/**
			 * Update preview whenever form is submitted. This does not use the usual LeftAndmMain::loadPanel
			 * functionality which is already covered in onafterstatechange above.
			 */
			'from .cms-container .cms-edit-form': {
				onaftersubmitform: function(){
					this.updateAfterXhr();
				}
			},

			/**
			 * Loads the matching edit form for a page viewed in the preview iframe,
			 * based on metadata sent along with this document.
			 */
			loadCurrentPage: function() {
				var doc = this.find('iframe')[0].contentDocument, containerEl = this.getLayoutContainer();

				if(!this.canPreview()) return;

				// Load this page in the admin interface if appropriate
				var id = $(doc).find('meta[name=x-page-id]').attr('content'); 
				var editLink = $(doc).find('meta[name=x-cms-edit-link]').attr('content');
				var contentPanel = $('.cms-content');
				
				if(id && contentPanel.find(':input[name=ID]').val() != id) {
					// Ignore behaviour without history support (as we need ajax loading 
					// for the new form to load in the background)
					if(window.History.enabled) 
						$('.cms-container').loadPanel(editLink);
				}
			},

			/**
			 * Determines if the current interface is capable of previewing its managed record.
			 *
			 * Returns: {boolean}
			 */
			canPreview: function() {
				var contentEl = this.getLayoutContainer().find('.cms-content');
				// Only load if we're in the "edit page" view
				var blockedClasses = ['CMSPagesController', 'CMSPageHistoryController'];
				return !(contentEl.is('.' + blockedClasses.join(',.')));
			},
			
			_fixIframeLinks: function() {
				var iframe = this.find('iframe')[0];
				if(iframe){
					var doc = iframe.contentDocument;
				}else{
					return;
				}
		
				if(!doc) return;

				// Block outside links from going anywhere
				var links = doc.getElementsByTagName('A');
				for (var i = 0; i < links.length; i++) {
					var href = links[i].getAttribute('href');
					if(!href) continue;
					
					// Open external links in new window to avoid "escaping" the
					// internal page context in the preview iframe,
					// which is important to stay in for the CMS logic.
					if (href.match(/^http:\/\//)) links[i].setAttribute('target', '_blank');
				}

				// Hide duplicate navigator, as it replicates existing UI in the CMS
				var navi = doc.getElementById('SilverStripeNavigator');
				if(navi) navi.style.display = 'none';
				var naviMsg = doc.getElementById('SilverStripeNavigatorMessage');
				if(naviMsg) naviMsg.style.display = 'none';
			},

			block: function() {
				this.addClass('blocked');
			},
			
			unblock: function() {
				this.removeClass('blocked');
			},
			
			getLayoutContainer: function() {
				return this.parents('.cms-container');
			},
			
			redraw: function() {
				if(window.debug) console.log('redraw', this.attr('class'), this.get(0));
			}
		});
		
		$('.cms-preview.blocked').entwine({
			onmatch: function() {
				this.find('.cms-preview-overlay').show();
				this._super();
			},
			onunmatch: function() {
				this.find('.cms-preview-overlay').hide();
				this._super();
			}
		});
		
		$('.switch-options a').entwine({
			onclick: function(e) {			
				var preview = $('.cms-preview');
				var loadSibling = $(this).siblings('a');
				var checkbox = $(this).closest('.cms-preview-states').find('input');
				if(checkbox.attr('checked') !== undefined){
					checkbox.attr('checked', false);
				}else{
					checkbox.attr('checked', true);
				}
				preview.loadUrl($(loadSibling).attr('href'));
				return false;
			}
		});	
		
		$('.preview-mode-selector select').entwine({
			onchange: function(e) {
				e.preventDefault();

				var container = $('.cms-container');
				var state = $(this).val();

				if (state == 'split') {
					container.splitViewMode();
				} else if (state == 'edit') {
					container.contentViewMode();
				} else {
					container.previewMode();
				}

				this.addIcon();

				// Synchronise other preview-mode selectors to display the same state.
				$('.preview-mode-selector select').not(this)
					.val(this.val())
					.trigger('liszt:updated')
					.addIcon();
			}
		});

		$('.preview-size-selector select').entwine({
			onchange: function(e) {
				e.preventDefault();

				var preview = $('.cms-preview');
				var size = $(this).val();

				preview
					.removeClass('auto desktop tablet mobile')
					.addClass(size);

				this.addIcon();
			}
		});

		/**
		 * React to state view mode changes by showing/hiding the preview-mode selector.
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
		 * Initialise the CMS's preview-mode selector.
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

		/*
		*	Add a class to the chzn select trigger based on the currently 
		*	selected option. Update as this changes
		*/
		$('.preview-selector select.preview-dropdown').entwine({
			'onliszt:showing_dropdown': function() {
				this.siblings().find('.chzn-drop').addClass('open').alignRight();
			},
			'onliszt:hiding_dropdown': function() {
				this.siblings().find('.chzn-drop').removeClass('open').removeRightAlign();
			},
			addIcon: function(){	
				var selected = this.find(':selected');				
				var iconClass = selected.attr('data-icon');	
								
				var target = this.parent().find('.chzn-container a.chzn-single');
				var oldIcon = target.attr('data-icon');
				if(oldIcon != undefined){
					target.removeClass(oldIcon);
				}
				target.addClass(iconClass);
				target.attr('data-icon', iconClass);				
			}
		});

		/*
		* When chzn initiated run select addIcon
		* Apply description text if applicable
		*/
		$('.preview-selector a.chzn-single').entwine({
			onmatch: function() {								
				this.closest('.preview-selector').find('select').addIcon();	
				this._super();
			},
			onunmatch: function() {
				this._super();
			}
		});


		$('.preview-selector .chzn-drop').entwine({
			alignRight: function(){					
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
			removeRightAlign:function(){
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


		$('.cms-edit-form').entwine({
			/**
			 * Choose applicable preview link based on form data,
			 * in a fixed order of priority: The PreviewURL field is used as an override,
			 * which falls back to stage or live URLs.
			 *
			 * @return String Absolute URL
			 */
			choosePreviewLink: function() {
				var self = this, urls = $.map(['PreviewURL', 'StageLink', 'LiveLink'], function(name) {
					var val = self.find(':input[name=' + name + ']').val();
					return val ? val : null;
				});
				return urls ? urls[0] : false;
			}
		});


		// Recalculate the preview space to allow for horizontal scrollbar and the preview actions panel
		var toolbarSize = 53; 							// Height of the preview actions panel
		$('.preview-scroll').entwine({
			redraw: function() {
				if(window.debug) console.log('redraw', this.attr('class'), this.get(0));
				var previewHeight = (this.height() - toolbarSize);
				this.height(previewHeight);
			}, 
			onmatch: function() {
				this.redraw();
				this._super();
			},
			onunmatch: function() {
				this._super();
			}
			// Todo: Need to recalculate on resize of browser

		});

	});
}(jQuery));
