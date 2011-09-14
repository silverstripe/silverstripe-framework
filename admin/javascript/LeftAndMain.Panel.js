(function($) {
	
	$.entwine('ss', function($){
		
		// setup jquery.entwine
		$.entwine.warningLevel = $.entwine.WARN_LEVEL_BESTPRACTISE;

		/**
		 * Vertically collapsible panel. Generic enough to work with CMS menu as well as various "filter" panels.
		 * 
		 * A panel consists of the following parts:
		 * - Container div: The outer element, with class ".cms-panel"
		 * - Header (optional)
		 * - Content
		 * - Expand and collapse toggle anchors (optional)
		 * 
		 * Sample HTML:
		 * <div class="cms-panel">
		 *  <div class="cms-panel-header">your header</div>
		 * 	<div class="cms-panel-content">your content here</div>
		 * 	<a href="#" class="toggle-expande">your toggle text</a>
		 * 	<a href="#" class="toggle-collapse">your toggle text</a>
		 * </div>
		 */
		$('.cms-panel').entwine({
			
			WidthExpanded: null,
			
			WidthCollapsed: null,
			
			onmatch: function() {
				if(!this.find('.cms-panel-content').length) throw new Exception('Content panel for ".cms-panel" not found');
				
				// Create default controls unless they already exist
				if(!this.find('.toggle-expand').length) this.append('<a class="toggle-expand" href="#"><span>&raquo;</span></a>');
				if(!this.find('.toggle-collapse').length) this.append('<a class="toggle-collapse" href="#"><span>&laquo;</span></a>');

				// Set panel width same as the content panel it contains. Assumes the panel has overflow: hidden.
				this.setWidthExpanded(this.find('.cms-panel-content').innerWidth());
				
				// Assumes the collasped width is indicated by the toggle, or by an optional collapsed view
				var collapsedContent = this.find('.cms-panel-content-collapsed');
				this.setWidthCollapsed(collapsedContent.length ? collapsedContent.innerWidth() : this.find('.toggle-expand').innerWidth());

				this.togglePanel(!jQuery(this).hasClass('collapsed'));
				
				this._super();
			},
			
			onclick: function(e) {
				// By default, the whole collapsed area serves as a trigger
				if(this.data('expandOnClick') && jQuery(this).hasClass('collapsed')) {
					e.preventDefault();
					this.expandPanel();
				}
			},
			
			togglePanel: function(bool) {
				// if((!bool && this.hasClass('collapsed')) || (bool && !this.hasClass('collapsed'))) return;
				
				this.toggleClass('collapsed', !bool);
				var newWidth = bool ? this.getWidthExpanded() : this.getWidthCollapsed();
				
				this.trigger('beforetoggle');
				this.width(newWidth); // the content panel width always stays in "expanded state" to avoid floating elements
				this.find('.toggle-collapse')[bool ? 'show' : 'hide']();
				this.find('.toggle-expand')[bool ? 'hide' : 'show']();
				
				// If an alternative collapsed view exists, toggle it as well
				var collapsedContent = this.find('.cms-panel-content-collapsed');
				if(collapsedContent.length) {
					this.find('.cms-panel-content')[bool ? 'show' : 'hide']();
					this.find('.cms-panel-content-collapsed')[bool ? 'hide' : 'show']();
				}
				
				this.trigger('toggle');
			},
			
			expandPanel: function() {
				this.togglePanel(true);
			},
			
			collapsePanel: function() {
				this.togglePanel(false);
			}
		});
		
		$('.cms-panel *').entwine({
			getPanel: function() {
				return this.parents('.cms-panel:first');
			}
		});
				
		$('.cms-panel .toggle-expand').entwine({
			onclick: function(e) {
				e.preventDefault();
				this.getPanel().expandPanel();
			}
		});
		
		$('.cms-panel .toggle-collapse').entwine({
			onclick: function(e) {
				this.getPanel().collapsePanel();
				return false;
			}
		});
	});
}(jQuery));