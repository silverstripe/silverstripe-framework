(function($) {
	
	$.entwine('ss', function($){
		
		// setup jquery.entwine
		$.entwine.warningLevel = $.entwine.WARN_LEVEL_BESTPRACTISE;

		/**
		 * Hoizontal collapsible panel. Generic enough to work with CMS menu as well as various "filter" panels.
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
		 *	<div class="cms-panel-toggle">
		 * 		<a href="#" class="toggle-expande">your toggle text</a>
		 * 		<a href="#" class="toggle-collapse">your toggle text</a>
		 *	</div>
		 * </div>
		 */
		$('.cms-panel').entwine({
			
			WidthExpanded: null,
			
			WidthCollapsed: null,
			
			onadd: function() {
				if(!this.find('.cms-panel-content').length) throw new Exception('Content panel for ".cms-panel" not found');
				
				// Create default controls unless they already exist.
				if(!this.find('.cms-panel-toggle').length) {
					var container = $("<div class='cms-panel-toggle south'></div>")
						.append('<a class="toggle-expand" href="#"><span>&raquo;</span></a>')
						.append('<a class="toggle-collapse" href="#"><span>&laquo;</span></a>');
						
					this.append(container);
				}
				
				// Set panel width same as the content panel it contains. Assumes the panel has overflow: hidden.
				this.setWidthExpanded(this.find('.cms-panel-content').innerWidth());
				
				// Assumes the collasped width is indicated by the toggle, or by an optional collapsed view
				var collapsedContent = this.find('.cms-panel-content-collapsed');
				this.setWidthCollapsed(collapsedContent.length ? collapsedContent.innerWidth() : this.find('.toggle-expand').innerWidth());

				// Set inital collapsed state, either from cookie or from default CSS classes
				var collapsed, cookieCollapsed;
				if($.cookie && this.attr('id')) {
					cookieCollapsed = $.cookie('cms-panel-collapsed-' + this.attr('id'));
					if(typeof cookieCollapsed != 'undefined' && cookieCollapsed != null) collapsed = (cookieCollapsed == 'true');
				} 
				if(typeof collapsed == 'undefined') collapsed = jQuery(this).hasClass('collapsed');

				// Toggle visibility
				this.togglePanel(!collapsed, true);
				
				this._super();
			},
			/**
			 * @param {Boolean} TRUE to expand, FALSE to collapse.
			 * @param {Boolean} TRUE means that events won't be fired, which is useful for the component initialization phase.
			 */
			togglePanel: function(bool, silent) {
				if(!silent) {
					this.trigger('beforetoggle.sspanel', bool);
					this.trigger(bool ? 'beforeexpand' : 'beforecollapse');
				}

				this.toggleClass('collapsed', !bool);
				var newWidth = bool ? this.getWidthExpanded() : this.getWidthCollapsed();
				
				this.width(newWidth); // the content panel width always stays in "expanded state" to avoid floating elements
				
				// If an alternative collapsed view exists, toggle it as well
				var collapsedContent = this.find('.cms-panel-content-collapsed');
				if(collapsedContent.length) {
					this.find('.cms-panel-content')[bool ? 'show' : 'hide']();
					this.find('.cms-panel-content-collapsed')[bool ? 'hide' : 'show']();
				}

				// Save collapsed state in cookie
				if($.cookie && this.attr('id')) $.cookie('cms-panel-collapsed-' + this.attr('id'), !bool, {path: '/', expires: 31});

				// TODO Fix redraw order (inner to outer), and re-enable silent flag
				// to avoid multiple expensive redraws on a single load.
				// if(!silent) {
					this.trigger('toggle', bool);
					this.trigger(bool ? 'expand' : 'collapse');
				// }
			},
			
			expandPanel: function(force) {
				if(!force && !this.hasClass('collapsed')) return;

				this.togglePanel(true);
			},
			
			collapsePanel: function(force) {
				if(!force && this.hasClass('collapsed')) return;

				this.togglePanel(false);
			}
		});

		$('.cms-panel.collapsed .cms-panel-toggle').entwine({
			onclick: function(e) {
				this.expandPanel();
				e.preventDefault();
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
