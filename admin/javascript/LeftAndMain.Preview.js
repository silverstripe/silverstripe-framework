(function($) {
	
	$.entwine('ss', function($){

		$('.LeftAndMain .cms-preview').entwine({
			
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
				this.prepend('<div class="cms-preview-toggle west"><a href="#">&laquo;</a></div>');
				this.find('iframe').addClass('center');
				this.layout({type: 'border'});
		
				this.find('iframe').bind('load', function() {
					self._fixIframeLinks();
					self.loadCurrentPage();
				});
				self._fixIframeLinks();
				
				// Limit to CMS forms for the moment
				$('.CMSMain .cms-edit-form').bind('loadnewpage', function(e, ui) {
					// var url = ui.xmlhttp.getResponseHeader('x-frontend-url');
					var url = $(this).find(':input[name=StageURLSegment]').val();
					if(url) self.loadUrl(url + '&cms-preview-disabled=1');
				});
				
				$('.cms-container').bind('afterstatechange', function(e) {
					// var url = ui.xmlhttp.getResponseHeader('x-frontend-url');
					var url = $('.cms-edit-form').find(':input[name=StageURLSegment]').val();
					if(url) self.loadUrl(url + '&cms-preview-disabled=1');
				});

				if(this.hasClass('is-expanded')) this.expand();
				else this.collapse();
		
				this._super();
			},
			
			loadUrl: function(url) {
				this.find('iframe').attr('src', url);
			},
			
			loadCurrentPage: function() {				
				var doc = this.find('iframe')[0].contentDocument, container = this.getLayoutContainer();

				// Only load if we're in the "edit page" view
				if(!container.hasClass('CMSMain') || container.hasClass('CMSPagesController')) return;

				// Load this page in the admin interface if appropriate
				var id = $(doc).find('meta[name=x-page-id]').attr('content'), contentPanel = $('.cms-content');
				// TODO Remove hardcoding
				if(id && contentPanel.find(':input[name=ID]').val() != id) {
					window.History.pushState({}, '', 'admin/page/edit/show/' + id);
				}
			},
			
			_fixIframeLinks: function() {
				var doc = this.find('iframe')[0].contentDocument;

				// Block outside links from going anywhere
				var links = doc.getElementsByTagName('A');
				for (var i = 0; i < links.length; i++) {
					var href = links[i].getAttribute('href');
					if (href && href.match(/^http:\/\//)) {
						links[i].setAttribute('href', 'javascript:false');
					} else {
						links[i].setAttribute('href', href + '?cms-preview-disabled=1');
					}
				}
			},
			
			expand: function() {
				var self = this, containerEl = this.getLayoutContainer(), contentEl = containerEl.find('.cms-content');
				this.removeClass('east').addClass('center').removeClass('is-collapsed');
				// this.css('overflow', 'auto');
				contentEl.removeClass('center').hide();
				this.find('iframe').show();
				containerEl.find('.cms-menu').collapsePanel();
				this.find('.cms-preview-toggle a').html('&raquo;');
				containerEl.redraw();
			},
			
			collapse: function() {
				var self = this, containerEl = this.getLayoutContainer(), contentEl = containerEl.find('.cms-content');
				this.addClass('east').removeClass('center').addClass('is-collapsed').width(10);
				// this.css('overflow', 'hidden');
				contentEl.addClass('center').show();
				this.find('iframe').hide();
				containerEl.find('.cms-menu').expandPanel();
				this.find('.cms-preview-toggle a').html('&laquo;');
				containerEl.redraw();
			},
			
			getLayoutContainer: function() {
				return this.parents('.LeftAndMain');
			},
			
			toggle: function(bool) {
				this[this.hasClass('is-collapsed') ? 'expand' : 'collapse']();
			}
		});
		
		$('.LeftAndMain .cms-preview.collapsed').entwine({
			onmatch: function() {
				this.find('a').text('<');
			}
		});
		
		$('.LeftAndMain .cms-preview.expanded').entwine({
			onmatch: function() {
				this.find('a').text('>');
			}
		});
		
		$('.LeftAndMain .cms-preview .cms-preview-toggle').entwine({
			onclick: function(e) {
				e.preventDefault();
				this.parents('.cms-preview').toggle();
			}
		});
		
		$('.LeftAndMain .cms-switch-view a').entwine({
			onclick: function(e) {
				e.preventDefault();
				var preview = $('.cms-preview');
				preview.toggle(true);
				preview.loadUrl($(e.target).attr('href'));
			}
		});
		
		$('.LeftAndMain .cms-menu li').entwine({
			onclick: function(e) {
				// Prevent reloading of interface when opening the edit panel
				if(this.hasClass('Menu-CMSMain')) {
					var preview = $('.cms-preview');
					preview.toggle(true);
					e.preventDefault();
				}
			}
		});
	});
}(jQuery));