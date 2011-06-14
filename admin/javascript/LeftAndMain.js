/**
 * File: LeftAndMain.js
 */
(function($) {

	$.metadata.setType('html5');
	
	$.entwine('ss', function($){
		
		/**
		 * Position the loading spinner animation below the ss logo
		 */ 
		var positionLoadingSpinner = function() {
			var offset = 120; // offset from the ss logo
			var spinner = $('.ss-loading-screen .loading-animation'); 
			var top = ($(window).height() - spinner.height()) / 2;
			spinner.css('top', top + offset);
			spinner.show();
		}
		$(window).bind('resize', positionLoadingSpinner).trigger('resize');
	
		// setup jquery.entwine
		$.entwine.warningLevel = $.entwine.WARN_LEVEL_BESTPRACTISE;
	
		// global ajax error handlers
		$.ajaxSetup({
			error: function(xmlhttp, status, error) {
				var msg = (xmlhttp.getResponseHeader('X-Status')) ? xmlhttp.getResponseHeader('X-Status') : xmlhttp.statusText;
				statusMessage(msg, 'bad');
			}
		});
		
		/**
		 * Class: .LeftAndMain
		 * 
		 * Main LeftAndMain interface with some control panel and an edit form.
		 * 
		 * Events:
		 *  ajaxsubmit - ...
		 *  validate - ...
		 *  loadnewpage - ...
		 */
		$('.LeftAndMain').entwine({

			/**
			 * Variable: PingIntervalSeconds
			 * (Number) Interval in which /Security/ping will be checked for a valid login session.
			 */
			PingIntervalSeconds: 5*60,

			/**
			 * Constructor: onmatch
			 */
			onmatch: function() {
				var self = this;
				
				// Browser detection
				if($.browser.msie && parseInt($.browser.version, 10) < 7) {
					$('.ss-loading-screen').append(
						'<p><span class="notice">' + 
						ss.i18n._t('LeftAndMain.IncompatBrowserWarning') +
						'</span></p>'
					);
					return;
				}
				
				// Initialize layouts, inner to outer
				var doInnerLayout = function() {$('.cms-content').layout();}
				var outer = $('.cms-container');
				var doOuterLayout = function() {outer.layout({resize: false});}
				doInnerLayout();
				doOuterLayout();
				$(window).resize(doOuterLayout);
				
				// Remove loading screen
				$('.ss-loading-screen').hide();
				$('body').removeClass('loading');
				$(window).unbind('resize', positionLoadingSpinner);

				this._setupPinging();

				$('.cms-edit-form').live('loadnewpage', function() {
					doInnerLayout();
					doOuterLayout();
				});

				this._super();
			},

			/**
			 * Function: _setupPinging
			 * 
			 * This function is called by prototype when it receives notification that the user was logged out.
			 * It uses /Security/ping for this purpose, which should return '1' if a valid user session exists.
			 * It redirects back to the login form if the URL is either unreachable, or returns '0'.
			 */
			_setupPinging: function() {
				var onSessionLost = function(xmlhttp, status) {
					if(xmlhttp.status > 400 || xmlhttp.responseText == 0) {
						// TODO will pile up additional alerts when left unattended
						if(window.open('Security/login')) {
						    alert("Please log in and then try again");
						} else {
						    alert("Please enable pop-ups for this site");
						}
					}
				};

				// setup pinging for login expiry
				setInterval(function() {
					jQuery.ajax({
						url: "Security/ping",
						global: false,
						complete: onSessionLost
					});
				}, this.getPingIntervalSeconds() * 1000);
			}
		});

		/**
		 * Class: .LeftAndMain :submit, .LeftAndMain button, .LeftAndMain :reset
		 * 
		 * Make all buttons "hoverable" with jQuery theming.
		 * Also sets the clicked button on a form submission, making it available through
		 * a new 'clickedButton' property on the form DOM element.
		 */
		$('.LeftAndMain :submit, .LeftAndMain button, .LeftAndMain :reset').entwine({
			onmatch: function() {
				// TODO Adding classes in onmatch confuses entwine
				var self = this;
				setTimeout(function() {self.addClass('ss-ui-button');}, 10);
				
				this._super();
			}
		});

		/**
		 * Class: a#profile-link
		 * 
		 * Link for editing the profile for a logged-in member through a modal dialog.
		 */
		$('.LeftAndMain .profile-link').entwine({
			
			/**
			 * Constructor: onmatch
			 */
			onmatch: function() {
				var self = this;

				this.bind('click', function(e) {return self._openPopup();});

				$('body').append(
					'<div id="ss-ui-dialog">'
					+ '<iframe id="ss-ui-dialog-iframe" '
					+ 'marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto">'
					+ '</iframe>'
					+ '</div>'
				);

				var cookieVal = (jQuery.cookie) ? JSON.parse(jQuery.cookie('ss-ui-dialog')) : false;
				$("#ss-ui-dialog").dialog(jQuery.extend({
					autoOpen: false,
					bgiframe: true,
					modal: true,
					height: 300,
					width: 500,
					ghost: true,
					resizeStop: function(e, ui) {
						self._resize();
						self._saveState();
					},
					dragStop: function(e, ui) {
						self._saveState();
					},
					// TODO i18n
					title: 'Edit Profile'
				}, cookieVal)).css('overflow', 'hidden');

				$('#ss-ui-dialog-iframe').bind('load', function(e) {self._resize();});
			},

			/**
			 * Function: _openPopup
			 */
			_openPopup: function(e) {
				$('#ss-ui-dialog-iframe').attr('src', this.attr('href'));

				$("#ss-ui-dialog").dialog('open');

				return false;
			},

			/**
			 * Function: _resize
			 */
			_resize: function() {
				var iframe = $('#ss-ui-dialog-iframe');
				var container = $('#ss-ui-dialog');

				iframe.attr('width', 
					container.innerWidth() 
					- parseFloat(container.css('paddingLeft'))
					- parseFloat(container.css('paddingRight'))
				);
				iframe.attr('height', 
					container.innerHeight()
					- parseFloat(container.css('paddingTop')) 
					- parseFloat(container.css('paddingBottom'))
				);

				this._saveState();
			},

			/**
			 * Function: _saveState
			 */
			_saveState: function() {
				var container = $('#ss-ui-dialog');

				// save size in cookie (optional)
				if(jQuery.cookie && container.width() && container.height()) {
					jQuery.cookie(
						'ss-ui-dialog',
						JSON.stringify({
							width: parseInt(container.width(), 10), 
							height: parseInt(container.height(), 10),
							position: [
								parseInt(container.offset().top, 10),
								parseInt(container.offset().left, 10)
							]
						}),
						{ expires: 30, path: '/'}
					);
				}
			}
		});
		
		/**
		 * Class: #switchView a
		 * 
		 * Updates the different stage links which are generated through 
		 * the SilverStripeNavigator class on the serverside each time a form record
		 * is reloaded.
		 */
		$('#switchView').entwine({
			onmatch: function() {
				this._super();
				
				$('.cms-edit-form').bind('loadnewpage delete', function(e) {
					var updatedSwitchView = $('#AjaxSwitchView');
					if(updatedSwitchView.length) {
						$('#SwitchView').html(updatedSwitchView.html());
						updatedSwitchView.remove();
					}
				});
			}
		});

		/**
		 * Class: #switchView a
		 * 
		 * Links for viewing the currently loaded page
		 * in different modes: 'live', 'stage' or 'archived'.
		 * 
		 * Requires:
		 *  jquery.metadata
		 */
		$('#switchView a').entwine({
			/**
			 * Function: onclick
			 */
			onclick: function(e) {
				// Open in popup
				window.open($(e.target).attr('href'));
				return false;
			}
		});
		
		/**
		 * Duplicates functionality in DateField.js, but due to using entwine we can match
		 * the DOM element on creation, rather than onclick - which allows us to decorate
		 * the field with a calendar icon
		 */
		$('.LeftAndMain .field.date input.text').entwine({
			onmatch: function() {
				var holder = $(this).parents('.field.date:first'), config = holder.metadata({type: 'class'});
				if(!config.showcalendar) return;

				config.showOn = 'button';
				if(config.locale && $.datepicker.regional[config.locale]) {
					config = $.extend(config, $.datepicker.regional[config.locale], {});
				}

				$(this).datepicker(config);
				// // Unfortunately jQuery UI only allows configuration of icon images, not sprites
				// this.next('button').button('option', 'icons', {primary : 'ui-icon-calendar'});
				
				this._super();
			}
		})
		
	});
	
	
	$(document).ready(function() {
	 	/**
	 	 * GUI Display Code - Actions - Select Box
	 	 * Hooking up UI to replace select box with UL LI elements
	 	 */
	 	 
	 	//$('#Form_BatchActionsForm').hide();
	 	//grab a reference to the BatchActions Select element
	 	//var selectElement = $('#Form_BatchActionsForm_Action');
	 	var selectElement = $('form.cms-batch-actions select');

	 	//get the option elements in that selectBox
	 	var optionValues = selectElement.find('option');
	 	var actionButton = $('form.cms-batch-actions input.action');
	 	
	 	//mapping class names to match actions (use for style & js hooks)
	 	optionValues.each(function() {
	 		optionValueAttr = ($(this).attr('value'));
	 		optionValueAttrArray = optionValueAttr.split('/');
	 		optionClassAttr = optionValueAttrArray.pop();
	 		$(this).addClass(optionClassAttr);
	 	})
	 	
	 	//get the ID of the select element
	 	var selectElementID = selectElement.attr('id');
	 	
	 	//get initial selected index put in try/catch block to avoid throwing errors on empty set
	 	try {
	 		var initialSelectedIndex = selectElement[0].selectedIndex;
	 	} catch(e) {	}
	 	
	 	//get the selected option's text value
	 	var selectedOptionText = selectElement.find('option') .eq(initialSelectedIndex).text();
	 	
	 	//get the selected option's class attribute value
	 	var selectedOptionClass = selectElement.find('option') .eq(initialSelectedIndex).attr('class');
	 	
	 	//get array of all select option classes, for quick removal
	 	var allOptionClasses = selectElement.find('option')
	 	//convert option classes to array
	 	.map(function(){ return $(this).attr('class'); })
	 	//join array into string of classes separated by a space
	 	.get().join(' ');
	 	
	 	//create IDs for button, menu
	 	var buttonID = selectElementID + '-button'; 
	 	var actionMenuID = selectElementID + '-menu';
	 	
	
	 	//create empty menu button
	 	var button = $('<a class="cms-batchactions-custom-select" id="'+ buttonID +'" role="button" href="#" aria-haspopup="true" aria-owns="' + actionMenuID +'"></a>');
	 	
	 	//create button text, icon, and roletext spans, and append to button
	 	var selectmenuStatus = $('<span class="cms-batchactions-custom-select-status">' + selectedOptionText +'</span>').appendTo(button);
	 	var selectmenuIcon = $('<span class="cms-batchactions-custom-select-button-icon"></span>') .appendTo(button);
	 	var roleText = $('<span class="cms-batchactions-custom-select-roletext"> select</span>') .appendTo(button);
	 	
	 	//transfer tabindex attribute from select, if it's specified
	 	if(selectElement.is('[tabindex]')){ 
	 		button.attr('tabindex', selectElement.attr('tabindex'));
	 	}
	 	//add selected option class defined earlier
	 	button.addClass(selectedOptionClass);
	 	//insert button after select
	 	button.prependTo('.cms-content-batchactions');
	 	
	 	//associate select's label to new button
	 	$('label[for='+selectElementID+']').attr('for', buttonID).bind('click', function(){
	 		button.focus(); return false;
	 	});
	 	
	 	//Create the UL and LI replacements/substitions for the select/options
	 	//create menu ul
	 	var menu = $('<ul class="cms-batchactions-custom-select-menu" id="'+ actionMenuID +'" role="listbox" aria-hidden="true" aria-labelledby="' + buttonID +'"></ul>');
	 	
	 	//find all option elements in selectElement
        selectElement.find('option')
            //iterate through each option element, tracking the index
            .each(function(index){
                //create li with option's text and class attribute 
                var li = $('<li class="'+ $(this).attr('class') +'"><a href="#" tabindex="-1" role="option" aria-selected="false">'+  $(this).text() +'</a></li>');

                //check if option is selected
                if(index == initialSelectedIndex){
                    //add selected attributes
                    li.addClass('selected')
                        .attr('aria-selected',true);
                }
                if($(this).attr('class')== -1) {
                	li.addClass('cms-batch-action-group-header');
                }
                //append li to menu
                li.appendTo(menu);  
            });	 	
	 	//append menu to end of page (still visual)     
        menu.appendTo('.cms-content-batchactions');
                
        //set height of menu, if needed for overflow)
        if(menu.outerHeight() > 300){
            menu.height(300);
        }
            
        //hide menu
        menu.addClass('cms-batchactions-custom-select-menu-hidden');
        
	 	
	 	//custom show event
	 	menu.bind('show', function(){
	 		$(this)
		 	//remove hidden class
		 	.removeClass('cms-batchactions-custom-select-menu-hidden')
		 	//remove aria hidden attribute
		 	.attr('aria-hidden', false)
		 	//position the menu under the button
		 	.css({ top: button.outerHeight(), left: button.outerWidth()-menu.outerWidth()})
		 	//send focus to the selected option
		 	.find('.selected a')[0].focus();
		 	//add open class from button
		 	button.addClass('cms-batchactions-custom-select-open');
		 });
		 
		 //custom hide event
		 menu.bind('hide', function(){
			 //remove open class from button
			 button.removeClass('cms-batchactions-custom-select-open');
			 $(this)
				 //remove hidden class
				 .addClass('cms-batchactions-custom-select-menu-hidden')
				 //remove aria hidden attribute
				 .attr('aria-hidden', false);
	 	});
	 	
	 	//The toggle event conditionally shows and hides the menu. If the menu is hidden, we’ll trigger the show event; if the menu is already visible, we’ll trigger the hide event:
	 	//apply mousedown event to button
	 	menu.bind('toggle', function(){
		 	//if the menu is hidden, first set its positioning
		 	if(menu.is(':hidden')){
			 	//show menu
			 	menu.trigger('show');
		 	}
		 	else {
		 		//hide menu
		 		menu.trigger('hide');
		 	}
		 });
		 
		 //event to update select menu with current selection (proxy to select)
         menu.find('a').bind('select',function(){        
             
             //deselect previous option in menu
             menu
                 .find('li.selected')
                 .removeClass('selected')
                 .attr('aria-selected', false);
                         
             //get new selected li's class attribute
             var newListItemClass = $(this).parent().attr('class');
             
             //update button icon class to match this li
             button.removeClass(allOptionClasses).addClass( newListItemClass );  
             
             //update button text this anchor's content
             selectmenuStatus.html( $(this).html() );
             
             //update this list item's selected attributes 
             $(this)
                 .parent()
                 .addClass('selected')
                 .attr('aria-selected', true);
             
             //hide menu
             menu.trigger('hide');
                 
             var changed = false;
                 
             //update the native select with the new selection
             if(selectElement[0].selectedIndex != menu.find('a').index(this)){
                 changed = true;
             }
             
             selectElement[0].selectedIndex = menu.find('a').index(this);
                     
             if(changed){ 
             	$(this).trigger('change');
             	selectElement.trigger('change');
             	var currentSelectValue = selectElement[0].options[selectElement[0].selectedIndex].value;
             	if (currentSelectValue == -1){
             		actionButton.addClass('action-hidden');
             	} 
             	else {
             		actionButton.removeClass('action-hidden');
             	}
             }
         });
		         
		 //specific events
		         
         //apply click to button 
         button.mousedown(function(){
             menu.trigger('toggle');
             return false;   
         });
         
  
         //disable click event (use mousedown/up instead)
         button.click(function(){ return false; });
         
         
         //apply mouseup event to menu, for making selections    
         //allows us to drag and release
         menu.find('a').mouseup(function(event){
         	 event.preventDefault();
             $(this).trigger('select');
             //prevent browser scroll
         });
          
         //bind click to document for hiding menu
         $(document).click(function(){ menu.trigger('hide'); });
 
         //hover and focus states
         menu.find('a').bind('mouseover focus',function(){ 
                 //remove class from previous hover-focused option
                 menu.find('.hover-focus').removeClass('hover-focus');
                 //add class to this option 
                 if(!$(this).parent().hasClass('selected')) {
                 	$(this).parent().addClass('hover-focus');
                 }                  
                  
             })
             .bind('mouseout blur',function(){ 
                 //remove class from this option
                 $(this).parent().removeClass('hover-focus'); 
             });
	 	
	 	/*define menu instance in select element's data*/
        selectElement.data('selectmenu', menu);
        
        //hide native select
        selectElement.addClass('select-hidden').attr('aria-hidden', true);
        actionButton.addClass('action-hidden').attr('aria-hidden', true);
        
        $('#' + actionMenuID + ' a').bind('click', function(event){
        	event.preventDefault();
        });
	 	
	 	//end batchActions GUI alterations and actions
	 });
	 
	
}(jQuery));

// Backwards compatibility
var statusMessage = function(text, type) {
	jQuery.noticeAdd({text: text, type: type});
};

var errorMessage = function(text) {
	jQuery.noticeAdd({text: text, type: 'error'});
};

returnFalse = function() {
	return false;
};

/**
 * Find and enable TinyMCE on all htmleditor fields
 * Pulled in from old tinymce.template.js
 */

function nullConverter(url) {
	return url;
};