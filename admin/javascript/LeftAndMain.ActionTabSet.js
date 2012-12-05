(function($){
	$.entwine('ss', function($){
		/**
		 * Special rules for ss-ui-action-tabset, used for: 
		 * * Site tree action tabs (to perform actions on the site tree)
		 * * Actions menu (Edit page actions)
		 */
		$('.ss-tabset.ss-ui-action-tabset').entwine({

			onadd: function() {
				// Make sure the .ss-tabset is already initialised to apply our modifications on top.
				this._super();
				this.actionTabs();
			},
			/** 
			 * Apply generic rules for action tabs, 
			 * then call specific functions to handle each type of action tab 
			 */
			actionTabs: function(){
				var that = this;

				//Ignore tab state so it will not be reopened on form submission
				this.data('ignoreTabState', true);

				//Set actionTabs to allow closing and be closed by default
				this.tabs({'collapsible': true, 'active': false});

				// Apply special behaviour depending on whether tabs are 
				// sitetree actions, or an actionmenu
				if(this.parents('.cms-content-actions')){
					this.actionsMenu();
				} else if(this.hasClass('cms-actions-row')){
					this.siteTreeActions();
				}
			},

			/**
			 * Deal with available vertical space 
			 */ 
			'ontabsbeforeactivate': function(event, ui) {
			  this.riseUp(event, ui);
			},

			/**
			 * Handle opening and closing tabs
			 */
			onclick: function(event, ui) {
				this.attachCloseHandler(event, ui);
			},

			/**
			 * Apply custom rules to the Actions Menu
			 */
			actionsMenu: function(){
				this.on( "tabsbeforeactivate", function(event, ui) {
					//Set the position of the opening tab (if it exists)
					if($(ui.newPanel).length > 0){
						$(ui.newPanel).css('left', ui.newTab.position().left+"px");
					}
				});
			},

			/**
			 * Apply rules to the siteTree actions. These action panels should 
			 * receive positioning and classes based on where they appearing 
			 * (eg in the full page site tree view, or in the sidebar)
			 */
			siteTreeActions: function(){
				var that = this, container = this.closest('.cms-tree-view-sidebar');

				
				this.on( "tabsbeforeactivate", function(event, ui) {
					// Remove tabset open classes (Last gets a unique class 
					// in the bigger sitetree. Remove this if we have it)
					$(that).closest('.ss-ui-action-tabset')
							.removeClass('tabset-open tabset-open-last');
				});

				// Apply specific rules if the actions panel appears in the side-bar:
				//  - hover helper class (for animation)
				//  - reset panel positions
				if(container.length > 0){	
					// If actions panel is within the sidebar, apply active class 
					// to help animate open/close on hover
					$('.ui-tabs-nav li').hover(function(){
						$(this).parent().find('li .active').removeClass('active');
						$(this).find('a').addClass('active');
					});

					// Reset position of tabs, else anyone going between the large 
					// and the small sitetree will see broken tabs
					this.on( "tabsbeforeactivate", function(event, ui) {
						// Apply styles with .css, to avoid overriding currently applied styles	
						$(ui.newPanel).css({'left': 'auto', 'right': 'auto'});

						if($(ui.newPanel).length > 0){
							$(ui.newPanel).parent().addClass('tabset-open');
						}
					});
				}else{
					// If the tabs are in the full site tree view, do some 
					// positioning so tabPanel stays with relevent tab
					this.on( "tabsbeforeactivate", function(event, ui) {
						if($( ui.newPanel).length > 0){
							if($(ui.newTab).hasClass("last")){
								// Align open tab to the right (because opened tab is last)
								$(ui.newPanel).css({'left': 'auto', 'right': '0px'});

								// Last needs to be styled differently when open, so apply a unique class
								$(ui.newPanel).parent().addClass('tabset-open-last');
							}else{
								// Assign position to tabpanel based on position of relivent active tab item
								$(ui.newPanel).css('left', ui.newTab.position().left+"px");

								// If this is the first tab, make sure the position doesn't include border 
								// (hard set position to 0 ), and add the tab-set open class
								if($(ui.newTab).hasClass("first")){
									$(ui.newPanel).css('left',"0px");
									$(ui.newPanel).parent().addClass('tabset-open');
								}
							}
						}
					});
				}
			},
			
			/**
			 * Generic function to close open tabs when something other than 
			 * the open tab is clicked. Stores event in a handler, and removes 
			 * the bound event once activated. Used by ss-ui-action-tabset.
			 *
			 * Note: Should be called by a click event attached to 'this'
			 */
			attachCloseHandler: function(event, ui){
				var that = this, frame = $('.cms').find('iframe'), closeHandler; 

				// Create a handler for the click event so we can close tabs 
				// and easily remove the event once done
				closeHandler = function(event){
					var panel, frame;
					panel = $(event.target).closest('.ss-ui-action-tabset .ui-tabs-panel');

					// If anything except the ui-nav button is clicked, 
					// close panel and remove handler
					if (!$(event.target).closest(that).length || $(panel).length) {
						that.tabs('option', 'active', false); // close tabs

						// remove click event from objects it is bound to (iframe's and document)
						frame = $('.cms').find('iframe');
						frame.each(function(index, iframe){
							$(iframe).contents().off('click', closeHandler);
						});
						$(document).off('click', closeHandler);
					}
				};

				// Bind click event to document, and use closeHandler to handle the event
				$(document).on('click', closeHandler);
				// Make sure iframe click also closes tab
				// iframe needs a special case, else the click event will not register here
				if(frame.length > 0){
					frame.each(function(index, iframe){
						$(iframe).contents().on('click', closeHandler);
					});
				}
			},
			/**
			 * Function riseUp checks to see if a tab should be opened upwards 
			 * (based on space concerns). If true, the rise-up class is applied 
			 * and a new position is calculated and applied to the element. 
			 *
			 * Note: Should be called by a tabsbeforeactivate event
			 */
			riseUp: function(event, ui){
				var elHeight, trigger, endOfWindow, elPos, activePanel, activeTab, topPosition, containerSouth, padding;

				// Get the numbers needed to calculate positions
				elHeight = $(this).find('.ui-tabs-panel').outerHeight();
				trigger = $(this).find('.ui-tabs-nav').outerHeight();
				endOfWindow = ($(window).height() + $(document).scrollTop()) - trigger;
				elPos = $(this).find('.ui-tabs-nav').offset().top;
				
				activePanel = ui.newPanel;
				activeTab = ui.newTab;

				if (elPos + elHeight >= endOfWindow && elPos - elHeight > 0){
					this.addClass('rise-up');

					if (activeTab.position() !== null){
						topPosition = -activePanel.outerHeight();
						containerSouth = activePanel.parents('.south');
						if (containerSouth){
							// If container is the southern panel, make tab appear from the top of the container
							padding = activeTab.offset().top - containerSouth.offset().top;
							topPosition = topPosition-padding;
						}
						$(activePanel).css('top',topPosition+"px");
					}
				} else {
					// else remove the rise-up class and set top to 0
					this.removeClass('rise-up'); 
					if (activeTab.position() !== null){
						$(activePanel).css('top','0px');
					}
				}
				return false;
			}
		});
	});
}(jQuery));
