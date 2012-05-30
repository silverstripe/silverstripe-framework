(function($) {

	$.entwine('ss', function($){
		
		/**
		 * The "content" area contains all of the section specific UI (excluding the menu).
		 * This area can be a form itself, as well as contain one or more forms.
		 * For example, a page edit form might fill the whole area, 
		 * while a ModelAdmin layout shows a search form on the left, and edit form on the right.
		 */
		$('.cms-content').entwine({
			
			onmatch: function() {
				var self = this;
				
				// Force initialization of tabsets to avoid layout glitches
				this.find('.cms-tabset').redrawTabs();
				
				this._super();
			},
			onunmatch: function() {
				this._super();
			},
						
			redraw: function() {
				// Force initialization of tabsets to avoid layout glitches
				this.add(this.find('.cms-tabset')).redrawTabs();

				this.layout();
			}
		});

		/**
		 * Load edit form for the selected node when its clicked.
		 */
		$('.cms-content .cms-tree').entwine({
			onmatch: function() {
				var self = this;

				this._super();

				this.bind('select_node.jstree', function(e, data) {
					var node = data.rslt.obj, loadedNodeID = self.find(':input[name=ID]').val(), origEvent = data.args[2], container = $('.cms-container');
					
					// Don't trigger unless coming from a click event.
					// Avoids problems with automated section switches from tree to detail view
					// when JSTree auto-selects elements on first load.
					if(!origEvent) {
						return false;
					}else if($(origEvent.target).hasClass('jstree-icon') || $(origEvent.target).hasClass('jstree-pageicon')){
						// in case the click is not on the node title, ie on pageicon or dragicon, 
						return false;
					}
					
					// Don't allow checking disabled nodes
					if($(node).hasClass('disabled')) return false;

					// Don't allow reloading of currently selected node,
					// mainly to avoid doing an ajax request on initial page load
					if($(node).data('id') == loadedNodeID) return;

					var url = $(node).find('a:first').attr('href');
					if(url && url != '#') {

						// Ensure URL is absolute (important for IE)
						if($.path.isExternal($(node).find('a:first'))) url = url = $.path.makeUrlAbsolute(url, $('base').attr('href'));
						// Retain search parameters
						if(document.location.search) url = $.path.addSearchParams(url, document.location.search.replace(/^\?/, ''));
						// Load new page
						container.loadPanel(url);	
					} else {
						self.removeForm();
					}
				});
			},
			onunmatch: function() {
				this._super();
			}
		});

		$('.cms-content.loading,.cms-edit-form.loading,.cms-content-fields.loading,.cms-content-view.loading').entwine({
			onmatch: function() {
				this.append('<div class="cms-content-loading-overlay ui-widget-overlay-light"></div><div class="cms-content-loading-spinner"></div>');
				this._super();
			},
			onunmatch: function() {
				this.find('.cms-content-loading-overlay,.cms-content-loading-spinner').remove();
				this._super();
			}
		});
	});

})(jQuery);
