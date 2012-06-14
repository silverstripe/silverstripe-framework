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
		$('.cms-panel.cms-menu').entwine({
			togglePanel: function(bool) {
				//apply or unapply the flyout formatting, should only apply to cms-menu-list when the current collapsed panal is the cms menu.
				$('.cms-menu-list').children('li').each(function(){
					if (bool) { //expand
						$(this).children('ul').each(function() {
							$(this).removeClass('collapsed-flyout');
							if ($(this).data('collapse')) {
								$(this).removeData('collapse');
								$(this).addClass('collapse');
							}
						});
					} else {    //collapse
						$(this).children('ul').each(function() {
							$(this).addClass('collapsed-flyout');
							$(this).hasClass('collapse');
							$(this).removeClass('collapse');
							$(this).data('collapse', true);
						});
					}
				});

				this.toggleFlyoutState(bool);					

				this._super(bool);
			},
			toggleFlyoutState: function(bool) {
				if (bool) { //expand
					//show the flyout
					$('.collapsed').find('li').show();

					//hide all the flyout-indicator
					$('.cms-menu-list').find('.child-flyout-indicator').hide();
				} else {    //collapse
					//hide the flyout only if it is not the current section
					$('.collapsed-flyout').find('li').each(function() {
						//if (!$(this).hasClass('current'))
						$(this).hide();
					});

					//show all the flyout-indicators
					var par = $('.cms-menu-list ul.collapsed-flyout').parent();
					if (par.children('.child-flyout-indicator').length === 0) par.append('<span class="child-flyout-indicator"></span>').fadeIn();
					par.children('.child-flyout-indicator').fadeIn();
				}
			}
		});

		$('.cms-menu-list').entwine({
			onmatch: function() {
				var self = this;

				// Select default element (which might reveal children in hidden parents)
				this.find('li.current').select();

				this.updateItems();

				this._super();
			},
			onunmatch: function() {
				this._super();
			},

			updateMenuFromResponse: function(xhr) {
				var controller = xhr.getResponseHeader('X-Controller');
				if(controller) {
					var item = this.find('li#Menu-' + controller);
					if(!item.hasClass('current')) item.select();
				}
				this.updateItems();
			},

			'from .cms-container': {
				onafterstatechange: function(e, data){
					this.updateMenuFromResponse(data.xhr);
				},
				onaftersubmitform: function(e, data){
					this.updateMenuFromResponse(data.xhr);
				}
			},

			'from .cms-edit-form': {
				onrelodeditform: function(e, data){
					this.updateMenuFromResponse(data.xmlhttp);
				}
			},

			getContainingPanel: function(){
				return this.closest('.cms-panel');
			},

			fromContainingPanel: {
				ontoggle: function(e){
					this.toggleClass('collapsed', $(e.target).hasClass('collapsed'));
				}
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

		/** Toggle the flyout panel to appear/disappear when mouse over */
		$('.cms-menu-list li').entwine({
			toggleFlyout: function(bool) {
				fly = $(this);
				if (fly.children('ul').first().hasClass('collapsed-flyout')) {
					if (bool) { //expand
						fly.children('ul').find('li').fadeIn('fast');
					} else {    //collapse
						fly.children('ul').find('li').hide();
					}
				}
			}
		});
		//slight delay to prevent flyout closing from "sloppy mouse movement"
		$('.cms-menu-list li').hoverIntent(function(){$(this).toggleFlyout(true);},function(){$(this).toggleFlyout(false);});
		
		$('.cms-menu-list .toggle').entwine({
			onclick: function(e) {
				this.getMenuItem().toggle();
				e.preventDefault();
			}
		});
		
		$('.cms-menu-list li').entwine({
			onmatch: function() {
				if(this.find('ul').length) {
					this.find('a:first').append('<span class="toggle-children"><span class="toggle-children-icon"></span></span>');
				}
				this._super();
			},
			onunmatch: function() {
				this._super();
			},
			toggle: function() {
				this[this.hasClass('opened') ? 'close' : 'open']();
			},
			/**
			 * "Open" is just a visual state, and unrelated to "current".
			 * More than one item can be open at the same time.
			 */
			open: function() {
				var parent = this.getMenuItem();
				if(parent) parent.open();
				this.addClass('opened').find('ul').show();
				this.find('.toggle-children').addClass('opened');
			},
			close: function() {
				this.removeClass('opened').find('ul').hide();
				this.find('.toggle-children').removeClass('opened');
			},
			select: function() {
				var parent = this.getMenuItem();
				this.addClass('current').open();

				// Remove "current" class from all siblings and their children
				this.siblings().removeClass('current').close();
				this.siblings().find('li').removeClass('current');
				if(parent) {
					var parentSiblings = parent.siblings();
					parent.addClass('current');
					parentSiblings.removeClass('current').close();
					parentSiblings.find('li').removeClass('current').close();
				}
				
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
				var isExternal = $.path.isExternal(this.attr('href'));
				if(e.which > 1 || isExternal) return;
				e.preventDefault();

				var item = this.getMenuItem();

				var url = this.attr('href');
				if(!isExternal) url = $('base').attr('href') + url;
				
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

		$('.cms-menu-list li .toggle-children').entwine({
			onclick: function(e) {
				var li = this.closest('li');
				li.toggle();
				return false; // prevent wrapping link event to fire
			}
		});
		
	});
}(jQuery));
