(function($) {

	$.entwine('ss', function($){
		
		/**
		 * The "content" area contains all of the section specific UI (excluding the menu).
		 * This area can be a form itself, as well as contain one or more forms.
		 * For example, a page edit form might fill the whole area, 
		 * while a ModelAdmin layout shows a search form on the left, and edit form on the right.
		 */
		$('.cms-content').entwine({
			
			onadd: function() {
				var self = this;
				
				// Force initialization of certain UI elements to avoid layout glitches
				this.find('.cms-tabset').redrawTabs();
				this._super();

			},

			redraw: function() {
				if(window.debug) console.log('redraw', this.attr('class'), this.get(0));
				
				// Force initialization of certain UI elements to avoid layout glitches
				this.add(this.find('.cms-tabset')).redrawTabs();
				this.find('.cms-content-header').redraw();
				this.find('.cms-content-actions').redraw();
			}
		});

		/**
		 * Load edit form for the selected node when its clicked.
		 */
		$('.cms-content .cms-tree').entwine({
			onadd: function() {
				var self = this;

				this._super();

				this.bind('select_node.jstree', function(e, data) {
					var node = data.rslt.obj, loadedNodeID = self.find(':input[name=ID]').val(), origEvent = data.args[2], container = $('.cms-container');
					
					// Don't trigger unless coming from a click event.
					// Avoids problems with automated section switches from tree to detail view
					// when JSTree auto-selects elements on first load.
					if(!origEvent) {
						return false;
					}
					
					// Don't allow checking disabled nodes
					if($(node).hasClass('disabled')) return false;

					// Don't allow reloading of currently selected node,
					// mainly to avoid doing an ajax request on initial page load
					if($(node).data('id') == loadedNodeID) return;

					var url = $(node).find('a:first').attr('href');
					if(url && url != '#') {
						// strip possible querystrings from the url to avoid duplicateing document.location.search
						url = url.split('?')[0];
						
						// Deselect all nodes (will be reselected after load according to form state)
						self.jstree('deselect_all');
						self.jstree('uncheck_all');

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
			}
		});

		$('.cms-content .cms-content-fields').entwine({
			redraw: function() {
				if(window.debug) console.log('redraw', this.attr('class'), this.get(0));
			}
		});

		$('.cms-content .cms-content-header, .cms-content .cms-content-actions').entwine({
			redraw: function() {
				if(window.debug) console.log('redraw', this.attr('class'), this.get(0));

				// Fix dimensions to actual extents, in preparation for a relayout via jslayout.
				this.height('auto');
				this.height(this.innerHeight()-this.css('padding-top')-this.css('padding-bottom'));
			}
		});

		
	});

})(jQuery);
