(function($) {
	$.entwine('ss', function($){
		$('.LeftAndMain .cms-preview').entwine({
			
			onmatch: function() {
				var self = this, layoutContainer = this.parent();
				// this.resizable({
				// 	handles: 'w',
				// 	stop: function(e, ui) {
				// 		$('.cms-container').layout({resize: false});
				// 	}
				// });
				
				// Create layout and controls
				this.prepend('<div class="cms-preview-toggle west"><a href="#">&lt;</a></div>');
				this.find('iframe').addClass('center');
				this.layout({type: 'border'});
		
				this.find('iframe').bind('load', function() {self._fixIframeLinks();});
				self._fixIframeLinks();
				
				$('.cms-edit-form').bind('loadnewpage', function(e, ui) {
					// var url = ui.xmlhttp.getResponseHeader('x-frontend-url');
					var url = $(this).find(':input[name=StageURLSegment]').val();
					if(url) self.loadUrl(url);
				});
		
				this._super();
			},
			
			loadUrl: function(url) {
				this.find('iframe').attr('src', url);
			},
			
			_fixIframeLinks: function() {
				var doc = this.find('iframe')[0].contentDocument;

				// Block outside links from going anywhere
				var links = doc.getElementsByTagName('A');
				for (var i = 0; i < links.length; i++) {
					var href = links[i].getAttribute('href');
					if (href && href.match(/^http:\/\//)) {
						links[i].setAttribute('href', 'javascript:false');
					}
				}

				// Load this page in the admin interface if appropriate
				var id = $(doc).find('meta[name=x-page-id]').attr('content'), form = $('.cms-edit-form');
				// TODO Remove hardcoding
				if(id && form.find(':input[name=ID]').val() != id) form.loadForm('admin/page/edit/show/' + id);
			},
			
			toggle: function() {
				var self = this, 
					width = this.width(), 
					relayout = function() {$('.cms-container').layout({resize: false});},
					minWidth = this.find('.cms-preview-toggle').width(),
					wasCollapsed = (width <= minWidth), 
					sharedWidth = $('.cms-content').width() / 2, // half of content area by default
					newWidth = wasCollapsed ? sharedWidth : minWidth,
					newOverflow = wasCollapsed ? 'auto' : 'hidden';
					
				this.css('overflow', newOverflow).width(newWidth);
				this.toggleClass('collapsed', !wasCollapsed).toggleClass('expanded', wasCollapsed);
				this.find('iframe').toggle(wasCollapsed);
				relayout();
				
				// this.css('overflow', newOverflow).animate(
				// 	{width: newWidth+'px'},
				// 	{
				// 		duration: 500, 
				// 		complete: function() {
				// 			relayout();
				// 			self.toggleClass('collapsed', !wasCollapsed).toggleClass('expanded', wasCollapsed);
				// 			self.find('iframe').toggle(wasCollapsed);
				// 		}, 
				// 		step: relayout
				// 	}
				// );
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
	});
}(jQuery));