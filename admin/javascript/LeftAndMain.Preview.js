(function($) {
	
	$.entwine('ss', function($){

		/**
		 * Shows a previewable website state alongside its editable version in backend UI, typically a page.
		 * This allows CMS users to seamlessly switch between preview and edit mode in the same browser window.
		 * The preview panel is embedded in the layout of the backend UI, and loads its content via an iframe.
		 * 
		 * The admin UI itself is collapsible, leaving most screen space to this panel.
		 * Relies on the server responses to indicate if a preview URL is available for the currently loaded
		 * admin interface. If no preview is available, the panel is "blocked" automatically.
		 * 
		 * Internal links within the preview iframe trigger a refresh of the admin panel as well,
		 * while all external links are disabled (via JavaScript).
		 */
		$('.cms-preview').entwine({
			
			// Minimum width to keep the CMS operational
			SharedWidth: null,
			
			onmatch: function() {
				var self = this, layoutContainer = this.parent();
				// this.resizable({
				// 	handles: 'w',
				// 	stop: function(e, ui) {
				// 		$('.cms-container').layout({resize: false});
				// 	}
				// });
				
				// TODO Compute dynamically
				this.setSharedWidth(500);
				
				// Create layout and controls
				this.find('iframe').addClass('center');
				this.layout({type: 'border'});
		
				this.find('iframe').bind('load', function() {
					self._fixIframeLinks();
					self.loadCurrentPage();
				});
				self._fixIframeLinks();
				
				var updateAfterXhr = function() {
					// var url = ui.xmlhttp.getResponseHeader('x-frontend-url');
					var url = $('.cms-edit-form').find(':input[name=StageURLSegment]').val();
					if(url) {
						self.loadUrl(url);
						self.unblock();
					} else {
						self.block();
					}
				}
				
				// Listen to form loads. Limit to CMS forms for the moment
				$('.cms-edit-form').bind('loadnewpage', function(e, ui) {
					updateAfterXhr();
				});
				
				// Listen to history state changes
				$('.cms-container').bind('afterstatechange', function(e) {
					updateAfterXhr();
				});
				
				// Toggle preview when new menu entry is selected
				$('.cms-menu-list li').bind('select', function(e) {
					self.collapse();
				});

				if(this.hasClass('is-expanded')) this.expand();
				else this.collapse();
				
				// Preview might not be available in all admin interfaces - block/disable when necessary
				this.append('<div class="cms-preview-overlay ui-widget-overlay"></div>');
				this.find('.cms-preview-overlay').hide();
		
				this._super();
			},
			
			loadUrl: function(url) {
				this.find('iframe').attr('src', url);
			},

			/**
			 * Loads the matching edit form for a page viewed in the preview iframe,
			 * based on metadata sent along with this document.
			 */
			loadCurrentPage: function() {
				var doc = this.find('iframe')[0].contentDocument, 
					containerEl = this.getLayoutContainer(), 
					contentEl = containerEl.find('.cms-content');

				// Only load if we're in the "edit page" view
				if(!contentEl.hasClass('CMSMain') || contentEl.hasClass('CMSPagesController') || contentEl.hasClass('CMSSettingsController')) return;

				// Load this page in the admin interface if appropriate
				var id = $(doc).find('meta[name=x-page-id]').attr('content'), 
					editLink = $(doc).find('meta[name=x-cms-edit-link]').attr('content'), 
					contentPanel = $('.cms-content');
				// TODO Remove hardcoding
				if(id && contentPanel.find(':input[name=ID]').val() != id) {
					window.History.pushState({}, '', editLink);
				}
			},
			
			_fixIframeLinks: function() {
				var doc = this.find('iframe')[0].contentDocument;

				// Block outside links from going anywhere
				var links = doc.getElementsByTagName('A');
				for (var i = 0; i < links.length; i++) {
					var href = links[i].getAttribute('href');
					if(!href) continue;
					
					// Disable external links
					if (href.match(/^http:\/\//)) links[i].setAttribute('href', 'javascript:false');
				}
			},
			
			expand: function() {
				var self = this, containerEl = this.getLayoutContainer(), contentEl = containerEl.find('.cms-content');
				this.removeClass('east').addClass('center').removeClass('is-collapsed');
				// this.css('overflow', 'auto');
				contentEl.removeClass('center').hide();
				this.find('iframe').show();
				this.find('.cms-preview-toggle a').html('&raquo;');
				this.find('.cms-preview-controls').show();
				containerEl.find('.cms-menu').collapsePanel();
				
				// Already triggered through panel toggle above
				// containerEl.redraw();
			},
			
			collapse: function() {
				var self = this, containerEl = this.getLayoutContainer(), contentEl = containerEl.find('.cms-content');
				this.addClass('east').removeClass('center').addClass('is-collapsed').width(10);
				// this.css('overflow', 'hidden');
				contentEl.addClass('center').show();
				this.find('iframe').hide();
				this.find('.cms-preview-toggle a').html('&laquo;');
				this.find('.cms-preview-controls').hide();
				containerEl.find('.cms-menu').expandPanel();
				
				// Already triggered through panel toggle above
				// containerEl.redraw();
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
			
			toggle: function(bool) {
				this[this.hasClass('is-collapsed') ? 'expand' : 'collapse']();
			},
			redraw: function() {
				this.layout();
			}
		});
		
		$('.cms-preview.collapsed').entwine({
			onmatch: function() {
				this.find('a').text('<');
			}
		});
		
		$('.cms-preview.blocked').entwine({
			onmatch: function() {
				this.find('.cms-preview-overlay').show();
			},
			onunmatch: function() {
				this.find('.cms-preview-overlay').hide();
			}
		});
		
		$('.cms-preview.expanded').entwine({
			onmatch: function() {
				this.find('a').text('>');
			}
		});
		
		$('.cms-preview .cms-preview-toggle').entwine({
			onclick: function(e) {
				e.preventDefault();
				this.parents('.cms-preview').toggle();
			}
		});
		
		$('.cms-switch-view a').entwine({
			onclick: function(e) {
				e.preventDefault();
				var preview = $('.cms-preview');
				preview.toggle(true);
				preview.loadUrl($(e.target).attr('href'));
			}
		});
		
		$('.cms-menu li').entwine({
			onclick: function(e) {
				// Prevent reloading of interface when opening the edit panel
				if(this.hasClass('Menu-CMSMain')) {
					var preview = $('.cms-preview');
					preview.toggle(true);
					e.preventDefault();
				}
			}
		});
				
		$('.cms-preview .cms-preview-states').entwine({
			onmatch: function() {
				this.find('a').addClass('ss-ui-button');
				this.find('.active a').addClass('ui-state-highlight');
			}
		});
		$('.cms-preview .cms-preview-states a').entwine({
			onclick: function(e) {
				e.preventDefault();
				this.parents('.cms-preview').loadUrl(this.attr('href'));
				this.addClass('ui-state-highlight');
				this.parents('.cms-preview-states').find('a').not(this).removeClass('ui-state-highlight');
				
			}
		});
				
		$('.cms-preview-toggle-link').entwine({
			onclick: function(e) {
				e.preventDefault();
				$('.cms-preview').toggle();
				
			}
		});
	});
}(jQuery));