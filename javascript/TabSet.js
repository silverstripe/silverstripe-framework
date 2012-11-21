(function($){
	$.entwine('ss', function($){
		/**
		 * Lightweight wrapper around jQuery UI tabs.
		 */
		$('.ss-tabset').entwine({

			/*Custom functionality for special action tabsets*/
			actionTabs: function(){
				this.tabs(
					'option', 
					'collapsible',
					true					
				).tabs('option', 'active', false);
					
				//Apply special behaviour to the cms actions row				
				if(this.hasClass('cms-actions-row')){				
					
					/* If actions panel is within the tree, apply active class 
					to help animate open/close on hover
					Position must be reset else anyone coming from main sitetree 
					will see broken tabs */
					var container = this.parent().parent();
					if($(container).hasClass('cms-tree-view-sidebar')){							
						$('.ui-tabs-nav li').hover(function(){								
							$(this).parent().find('li .active').removeClass('active');
							$(this).find('a').addClass('active');															
						});

						this.tabs({
							beforeActivate:function(event, ui){
								var activePanel = ui.newPanel;
								$(activePanel).attr("style","left : auto; right: auto");
								$(this).closest('.ss-ui-action-tabset').removeClass('tabset-open').removeClass('tabset-open-last');
								
								if($(activePanel).length > 0){
									$(activePanel).parent().addClass('tabset-open');	
								}														
							}			
						});	
					}else{		
						/* If the tabs are in the full site tree view, do some 
						positioning so tabPanel stays with relevent tab */					
						this.tabs({
							beforeActivate:function(event, ui){
								var activePanel = ui.newPanel;
								var activeTab = ui.newTab;
								$(this).closest('.ss-ui-action-tabset').removeClass('tabset-open').removeClass('tabset-open-last');
								if($(activePanel).length > 0){
									if($(activeTab).hasClass("last")){
										$(activePanel).attr("style","left : auto; right: 0px");
										$(activePanel).parent().addClass('tabset-open-last');//last needs to be styled differently when open	
									}else{										
										$(activePanel).attr("style","left: "+activeTab.position().left+"px");								
										if($(activeTab).hasClass("first")){	
											$(activePanel).attr("style","left: 0px");
											$(activePanel).parent().addClass('tabset-open');
										}else{
											$(activePanel).attr("style","left: "+activeTab.position().left+"px");
										}	
									}	
								}
																							
							}	
						});
					}
				}else if(this.parents('.south')){
					this.tabs({
						beforeActivate:function(event, ui){
							var activePanel = ui.newPanel;
							var activeTab = ui.newTab;							
							if($(activePanel).length > 0){
								$(activePanel).attr("style","left: "+activeTab.position().left+"px");								
							}																						
						}	
					});	
				}
				//Check if tabs should open upwards, and adjust
				this.riseUp();
			},
			onadd: function() {
				// Can't name redraw() as it clashes with other CMS entwine classes
				this.redrawTabs();
				this._super();
			},
			onremove: function() {
				if(this.data('uiTabs')) this.tabs('destroy');
				this._super();
			},
			riseUp: function(){
				/* Function checks to see if a tab should be opened upwards 
				(based on space concerns. If true, the rise-up class is applied 
				and the position is calculated and applied to the element */
				var elHeight = $(this).find('.ui-tabs-panel').outerHeight();				
				var trigger = $(this).find('.ui-tabs-nav').outerHeight();
				var endOfWindow = ($(window).height() + $(document).scrollTop()) - trigger;
				var elPos = $(this).find('.ui-tabs-nav').offset().top;
				if(elPos + elHeight >= endOfWindow && elPos - elHeight > 0){
					this.addClass('rise-up');	

					/* Apply position to tab */
					this.tabs({
						activate:function(event, ui){
							var activePanel = ui.newPanel;
							var activeTab = ui.newTab;			
							if(activeTab.position()!=null){
								var top = -activePanel.outerHeight();
								var containerSouth = activePanel.parents('.south');
								if(containerSouth){
									var padding = activeTab.offset().top-containerSouth.offset().top;								
									top = top-padding;	
								}	
								var style =	$(activePanel).attr("style");				

								$(activePanel).attr("style", style+"top: "+top+"px;");									
							}
						}
					});				

				}else{
					this.removeClass('rise-up');
				}
				return false;		
			},
			redrawTabs: function() {
				this.rewriteHashlinks();
				this.tabs();
				
				//Apply special behaviour to action tabs: closed by default, and collapsible
				if(this.hasClass('ss-ui-action-tabset')){				
					this.actionTabs();
				}
			},
		
			/**
			 * Ensure hash links are prefixed with the current page URL,
			 * otherwise jQuery interprets them as being external.
			 */
			rewriteHashlinks: function() {
				$(this).find('ul a').each(function() {
					var matches = $(this).attr('href').match(/#.*/);
					if(!matches) return;
					$(this).attr('href', document.location.href.replace(/#.*/, '') + matches[0]);
				});
			}
		});
	});
})(jQuery);
