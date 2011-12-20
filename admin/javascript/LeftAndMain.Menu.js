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
		 * 
		 * Custom Events:
		 * - 'select': Fires when a menu item is selected (on any level).
		 */
		$('.cms-menu-list').entwine({
			onmatch: function() {
				var self = this;

				var updateMenuFromResponse = function(xhr) {
					var controller = xhr.getResponseHeader('X-Controller');
					if(controller) {
						var item = self.find('li#Menu-' + controller);
						if(!item.hasClass('current')) item.select();
					}
					self.updateItems();
				};
				$('.cms-container').live('afterstatechange', function(e, data) {
					updateMenuFromResponse(data.xhr);
				});
				$('.cms-edit-form').live('reloadeditform', function(e, data) {
					updateMenuFromResponse(data.xmlhttp);
				});
				
				// Sync collapsed state with parent panel
				this.parents('.cms-panel:first').bind('toggle', function(e) {
					self.toggleClass('collapsed', $(this).hasClass('collapsed'));
				});
				
				// Select default element (which might reveal children in hidden parents)
				this.find('li.current').select();

				this.updateItems();

				this._super();
			},
			
			updateItems: function() {
				// Hide "edit page" commands unless the section is activated
				var editPageItem = this.find('#Menu-CMSMain');
				
				editPageItem[editPageItem.is('.current') ? 'show' : 'hide']();
				
				// Update the menu links to reflect the page ID if the page has changed the URL.
				var currentID = $('.cms-content input[name=ID]').val();
				if(currentID) {
					this.find('li').each(function() {
						if($.isFunction($(this).setRecordID)) $(this).setRecordID(currentID);
					});
				}
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
				this.getMenu().updateItems();

				this.trigger('select');
			}
		});
		
		$('.cms-menu-list *').entwine({
			getMenu: function() {
				return this.parents('.cms-menu-list:first');
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

				var item = this.getMenuItem();

				var url = this.attr('href');
				if(this.is(':internal')) url = $('base').attr('href') + url;
				
				var children = item.find('li');

				if(children.length) {
					children.first().find('a').click();
				} else {
					// Load URL, but give the loading logic an opportunity to veto the action
					// (e.g. because of unsaved changes)
					if(!$('.cms-container').loadPanel(url)) return false;	
				}

				item.select();
			}
		});

		
		$('.cms-menu-list #Menu-CMSPageSettingsController, .cms-menu-list #Menu-CMSPageHistoryController, .cms-menu-list #Menu-CMSPageEditController').entwine({
			setRecordID: function(id) {
				var link = this.find('a:first'), href = link.attr("href").split('/')
				// Assumes that current ID will always be the last URL segment (and not a query parameter)
				href[href.length -1] = id;
				link.attr('href', href.join('/'));
			}
		})

		$('.cms-menu-list #Menu-CMSPageAddController').entwine({
			setRecordID: function(id) {
				var link = this.find('a:first');
				link.attr('href', link.attr('href').replace('/\?.*/', '?ParentID=' . id));
			}
		});
		
	});

	// Internal Helper
	$.expr[':'].internal = function(obj){return obj.href.match(/^mailto\:/) || (obj.hostname == location.hostname);};
	$.expr[':'].external = function(obj){return !$(obj).is(':internal')};
}(jQuery));