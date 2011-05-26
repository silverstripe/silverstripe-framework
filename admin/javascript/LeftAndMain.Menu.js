(function($) {
	
	$.entwine('ss', function($){
		
		/**
		 * Vertical CMS menu with two levels, built from a nested unordered list. 
		 * The (optional) second level is collapsible, hiding its children.
		 * The whole menu (including second levels) is collapsible as well,
		 * exposing only a preview for every menu item in order to save space.
		 * In this "preview/collapsed" mode, the secondary menu hovers over the menu item,
		 * rather than expanding it.
		 * 
		 * Example:
		 * 
		 * <ul class="cms-menu-list">
		 *  <li><a href="#">Item 1</a></li>
		 *  <li class="current opened">
		 *    <a href="#">Item 2</a>
		 *    <ul>
		 *      <li class="current opened"><a href="#">Item 2.1</a></li>
		 *      <li><a href="#">Item 2.2</a></li>
		 *    </ul>
		 *  </li>
		 * </ul>
		 */
		$('.cms-menu-list').entwine({
			onmatch: function() {
				// TODO Fix icon etc.
				// this.children('li').each(function() {
				// 	$(this).find('a:first').append('<span class="toggle">o</span>');
				// });
				
				// Sync collapsed state with parent panel
				this.parents('.cms-panel:first').bind('toggle', function(e) {
					self.toggleClass('collapsed', $(this).hasClass('collapsed'));
				});
				
				this._super();
			}
		});
		
		$('.cms-menu-list .toggle').entwine({
			onclick: function(e) {
				this.getMenuItem().toggle();
				e.preventDefault();
			}
		});
		
		$('.cms-menu-list li').entwine({
			toggle: function() {
				this[this.hasClass('opened') ? 'close' : 'open']();
			},
			open: function() {
				var parent = this.getMenuItem();
				if(parent) parent.open();
				this.addClass('opened').find('ul').show();
			},
			close: function() {
				this.removeClass('opened').find('ul').hide();
			},
			select: function() {
				var parent = this.getMenuItem();
				this.addClass('current').open();
				// Remove "current" class from all siblings and their children
				this.siblings().removeClass('current').close();
				this.siblings().find('li').removeClass('current');
				if(parent) parent.addClass('current').siblings().removeClass('current');
			}
		});
		
		$('.cms-menu-list li *').entwine({
			getMenuItem: function() {
				return this.parents('li:first');
			}
		});
		
		/**
		 * Both primary and secondary nav.
		 */
		$('.cms-menu-list li a').entwine({
			onclick: function(e) {
				// Only catch left clicks, in order to allow opening in tabs.
				// Ignore external links, fallback to standard link behaviour
				if(e.which > 1 || this.is(':external')) return;
				e.preventDefault();
				
				// Expand this, and collapse all other items
				var item = this.getMenuItem();
				item.select();
				
				var children = item.find('li');
				if(children.length) {
					children.first().find('a').click();
				} else {
					window.History.pushState({}, '', this.attr('href'));
				}
			}
		});
		
	});
	
	// Internal Helper
	$.expr[':'].internal = function(obj){return obj.href.match(/^mailto\:/) || (obj.hostname == location.hostname);};
	$.expr[':'].external = function(obj){return !$(obj).is(':internal')};
}(jQuery));