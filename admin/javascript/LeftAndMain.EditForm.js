/**
 * File: LeftAndMain.EditForm.js
 */
(function($) {
	
	// Can't bind this through jQuery
	window.onbeforeunload = function(e) {
		var form = $('.cms-edit-form');
		form.trigger('beforesubmitform');
		if(form.is('.changed')) return ss.i18n._t('LeftAndMain.CONFIRMUNSAVEDSHORT');
	};

	$.entwine('ss', function($){

		/**
		 * Class: .cms-edit-form
		 * 
		 * Base edit form, provides ajaxified saving
		 * and reloading itself through the ajax return values.
		 * Takes care of resizing tabsets within the layout container.
		 *
		 * Change tracking is enabled on all fields within the form. If you want
		 * to disable change tracking for a specific field, add a "no-change-track"
		 * class to it.
		 *
		 * @name ss.Form_EditForm
		 * @require jquery.changetracker
		 * 
		 * Events:
		 *  ajaxsubmit - Form is about to be submitted through ajax
		 *  validate - Contains validation result
		 *  load - Form is about to be loaded through ajax
		 */
		$('.cms-edit-form').entwine(/** @lends ss.Form_EditForm */{	
			/**
			 * Variable: PlaceholderHtml
			 * (String_ HTML text to show when no form content is chosen.
			 * Will show inside the <form> tag.
			 */
			PlaceholderHtml: '',
		
			/**
			 * Variable: ChangeTrackerOptions
			 * (Object)
			 */
			ChangeTrackerOptions: {
				ignoreFieldSelector: '.no-change-track, .ss-upload :input, .cms-navigator :input'
			},

			/**
			 * Variable: ValidationErrorShown
			 * Boolean for tracking whether a validation error has been already been shown. Used because tabs can
			 * sometimes be inadvertently initialised multiple times, but we don't want duplicate messages
			 * (Boolean)
			 */
			ValidationErrorShown: false,
		
			/**
			 * Constructor: onmatch
			 */
			onadd: function() {
				var self = this;

				// Turn off autocomplete to fix the access tab randomly switching radio buttons in Firefox
				// when refresh the page with an anchor tag in the URL. E.g: /admin#Root_Access.
				// Autocomplete in the CMS also causes strangeness in other browsers,
				// filling out sections of the form that the user does not want to be filled out,
				// so this turns it off for all browsers.
				// See the following page for demo and explanation of the Firefox bug:
				//  http://www.ryancramer.com/journal/entries/radio_buttons_firefox/
				this.attr("autocomplete", "off");
			
				this._setupChangeTracker();

				// Catch navigation events before they reach handleStateChange(),
				// in order to avoid changing the menu state if the action is cancelled by the user
				// $('.cms-menu')
				
				// Optionally get the form attributes from embedded fields, see Form->formHtmlContent()
				for(var overrideAttr in {'action':true,'method':true,'enctype':true,'name':true}) {
					var el = this.find(':input[name='+ '_form_' + overrideAttr + ']');
					if(el) {
						this.attr(overrideAttr, el.val());
						el.remove();
					}
				}

				// Reset error display
				this.setValidationErrorShown(false);

				// TODO
				// // Rewrite # links
				// html = html.replace(/(<a[^>]+href *= *")#/g, '$1' + window.location.href.replace(/#.*$/,'') + '#');
				// 
				// // Rewrite iframe links (for IE)
				// html = html.replace(/(<iframe[^>]*src=")([^"]+)("[^>]*>)/g, '$1' + $('base').attr('href') + '$2$3');

				this._super();
			},
			'from .cms-tabset': {
				onafterredrawtabs: function () {
					// Show validation errors if necessary
					if(this.hasClass('validationerror')) {
						// Ensure the first validation error is visible
						var tabError = this.find('.message.validation, .message.required').first().closest('.tab');
						$('.cms-container').clearCurrentTabState(); // clear state to avoid override later on

						// Attempt #1: Look for nearest .ss-tabset (usually nested deeper underneath a .cms-tabset).
						var $tabSet = tabError.closest('.ss-tabset');

						// Attempt #2: Next level in tab-ception, try to select the tab within this higher level .cms-tabset if possible
						if (!$tabSet.length) {
							$tabSet = tabError.closest('.cms-tabset');
						}

						if ($tabSet.length) {
							$tabSet.tabs('option', 'active', tabError.index('.tab'));
						} else if (!this.getValidationErrorShown()) {
							// Ensure that this error message popup won't be added more than once
							this.setValidationErrorShown(true);
							errorMessage(ss.i18n._t('ModelAdmin.VALIDATIONERROR', 'Validation Error'));
						}
					}
				}
			},
			onremove: function() {
				this.changetracker('destroy');
				this._super();
			},
			onmatch: function() {
				this._super();
			},
			onunmatch: function() {
				this._super();
			},
			redraw: function() {
				if(window.debug) console.log('redraw', this.attr('class'), this.get(0));
				
				// Force initialization of tabsets to avoid layout glitches
				this.add(this.find('.cms-tabset')).redrawTabs();
				this.find('.cms-content-header').redraw();
			},

			/**
			 * Function: _setupChangeTracker
			 */
			_setupChangeTracker: function() {
				// Don't bind any events here, as we dont replace the
				// full <form> tag by any ajax updates they won't automatically reapply
				this.changetracker(this.getChangeTrackerOptions());
			},
		
			/**
			 * Function: confirmUnsavedChanges
			 * 
			 * Checks the jquery.changetracker plugin status for this form,
			 * and asks the user for confirmation via a browser dialog if changes are detected.
			 * Doesn't cancel any unload or form removal events, you'll need to implement this based on the return
			 * value of this message.
			 * 
			 * If changes are confirmed for discard, the 'changed' flag is reset.
			 * 
			 * Returns:
			 *  (Boolean) FALSE if the user wants to abort with changes present, TRUE if no changes are detected 
			 *  or the user wants to discard them.
			 */
			confirmUnsavedChanges: function() {
				this.trigger('beforesubmitform');
				if(!this.is('.changed')) {
					return true;
				}
				var confirmed = confirm(ss.i18n._t('LeftAndMain.CONFIRMUNSAVED'));
				if(confirmed) {
					// confirm discard changes
					this.removeClass('changed');
				}
				return confirmed;
			},

			/**
			 * Function: onsubmit
			 * 
			 * Suppress submission unless it is handled through ajaxSubmit().
			 */
			onsubmit: function(e, button) {
				// Only submit if a button is present.
				// This supressed submits from ENTER keys in input fields,
				// which means the browser auto-selects the first available form button.
				// This might be an unrelated button of the form field,
				// or a destructive action (if "save" is not available, or not on first position).
				if(this.prop("target") != "_blank") {
					if(button) this.closest('.cms-container').submitForm(this, button);
					return false;
				}
			},

			/**
			 * Function: validate
			 * 
			 * Hook in (optional) validation routines.
			 * Currently clientside validation is not supported out of the box in the CMS.
			 * 
			 * Todo:
			 *  Placeholder implementation
			 * 
			 * Returns:
			 *  {boolean}
			 */
			validate: function() {
				var isValid = true;
				this.trigger('validate', {isValid: isValid});
	
				return isValid;
			},
			/*
			 * Track focus on htmleditor fields
			 */
			'from .htmleditor': {
				oneditorinit: function(e){
					var self = this,
						field = $(e.target).closest('.field.htmleditor'),
						editor = field.find('textarea.htmleditor').getEditor().getInstance();

					// TinyMCE 4 will add a focus event, but for now, use click
					editor.onClick.add(function(e){
						self.saveFieldFocus(field.attr('id'));
					});
				}
			},
			/*
			 * Track focus on inputs
			 */
			'from .cms-edit-form :input:not(:submit)': {
				onclick: function(e){
					this.saveFieldFocus($(e.target).attr('id'));
				},
				onfocus: function(e){
					this.saveFieldFocus($(e.target).attr('id'));
				}
			},
			/*
			 * Track focus on treedropdownfields. 
			 */
			'from .cms-edit-form .treedropdown *': {
				onfocusin: function(e){
					var field = $(e.target).closest('.field.treedropdown');
					this.saveFieldFocus(field.attr('id'));
				}
			},
			/*
			 * Track focus on chosen selects
			 */
			'from .cms-edit-form .dropdown .chzn-container a': {
				onfocusin: function(e){
					var field = $(e.target).closest('.field.dropdown');
					this.saveFieldFocus(field.attr('id'));
				}
			},
			/*
			 * Restore fields after tabs are restored
			 */
			'from .cms-container': {
				ontabstaterestored: function(e){
					this.restoreFieldFocus();
				}
			},
			/*
			 * Saves focus in Window session storage so it that can be restored on page load
			 */
			saveFieldFocus: function(selected){
				if(typeof(window.sessionStorage)=="undefined" || window.sessionStorage === null) return;
				
				var id = $(this).attr('id'),
					focusElements = [];

				focusElements.push({
					id:id, 
					selected:selected
				});

				if(focusElements) {
					try {
						window.sessionStorage.setItem(id, JSON.stringify(focusElements));
					} catch(err) {
						if (err.code === DOMException.QUOTA_EXCEEDED_ERR && window.sessionStorage.length === 0) {
							// If this fails we ignore the error as the only issue is that it 
							// does not remember the focus state.
							// This is a Safari bug which happens when private browsing is enabled.
							return;
						} else {
							throw err;
						}
					}
				}
			},
			/**
			 * Set focus or window to previously saved fields.
			 * Requires HTML5 sessionStorage support.
			 *
			 * Must follow tab restoration, as reliant on active tab
			 */
			restoreFieldFocus: function(){
				if(typeof(window.sessionStorage)=="undefined" || window.sessionStorage === null) return;
			
				var self = this,
					hasSessionStorage = (typeof(window.sessionStorage)!=="undefined" && window.sessionStorage),
					sessionData = hasSessionStorage ? window.sessionStorage.getItem(this.attr('id')) : null,
					sessionStates = sessionData ? JSON.parse(sessionData) : false,
					elementID,
					tabbed = (this.find('.ss-tabset').length !== 0),
					activeTab,
					elementTab,
					toggleComposite,
					scrollY;

				if(hasSessionStorage && sessionStates.length > 0){
					$.each(sessionStates, function(i, sessionState) {
						if(self.is('#' + sessionState.id)){
							elementID = $('#' + sessionState.selected);
						}
					});

					// If the element IDs saved in session states don't match up to anything in this particular form
					// that probably means we haven't encountered this form yet, so focus on the first input
					if($(elementID).length < 1){
						this.focusFirstInput();
						return;
					}

					activeTab = $(elementID).closest('.ss-tabset').find('.ui-tabs-nav .ui-tabs-active .ui-tabs-anchor').attr('id');
					elementTab  = 'tab-' + $(elementID).closest('.ss-tabset .ui-tabs-panel').attr('id');

					// Last focussed element differs to last selected tab, do nothing
					if(tabbed && elementTab !== activeTab){
						return;
					}

					toggleComposite = $(elementID).closest('.togglecomposite');

					//Reopen toggle fields
					if(toggleComposite.length > 0){
						toggleComposite.accordion('activate', toggleComposite.find('.ui-accordion-header'));
					}

					//Calculate position for scroll
					scrollY = $(elementID).position().top;

					//Fall back to nearest visible element if hidden (for select type fields)
					if(!$(elementID).is(':visible')){
						elementID = '#' + $(elementID).closest('.field:visible').attr('id');
						scrollY = $(elementID).position().top;
					}

					//set focus to focus variable if element focusable
					$(elementID).focus();

					// Scroll fallback when element is not focusable
					// Only scroll if element at least half way down window
					if(scrollY > $(window).height() / 2){
						self.find('.cms-content-fields').scrollTop(scrollY);
					}
				
				} else {
					// If session storage is not supported or there is nothing stored yet, focus on the first input 
					this.focusFirstInput();
				}
			},
			/**
			 * Skip if an element in the form is already focused. Exclude elements which specifically
			 * opt-out of this behaviour via "data-skip-autofocus". This opt-out is useful if the
			 * first visible field is shown far down a scrollable area, for example for the pagination
			 * input field after a long GridField listing.
			 */
			focusFirstInput: function() {
				this.find(':input:not(:submit)[data-skip-autofocus!="true"]').filter(':visible:first').focus();
			}
		});

		/**
		 * Class: .cms-edit-form .Actions :submit
		 * 
		 * All buttons in the right CMS form go through here by default.
		 * We need this onclick overloading because we can't get to the
		 * clicked button from a form.onsubmit event.
		 */
		$('.cms-edit-form .Actions input.action[type=submit], .cms-edit-form .Actions button.action').entwine({
			/**
			 * Function: onclick
			 */
			onclick: function(e) {
				// Confirmation on delete. 
				if(
					this.hasClass('gridfield-button-delete')
					&& !confirm(ss.i18n._t('TABLEFIELD.DELETECONFIRMMESSAGE'))
				) {
					e.preventDefault();
					return false;
				}

				if(!this.is(':disabled')) {
					this.parents('form').trigger('submit', [this]);
				}
				e.preventDefault();
				return false;
			}
		});

		/**
		 * If we've a history state to go back to, go back, otherwise fall back to
		 * submitting the form with the 'doCancel' action.
		 */
		$('.cms-edit-form .Actions input.action[type=submit].ss-ui-action-cancel, .cms-edit-form .Actions button.action.ss-ui-action-cancel').entwine({
			onclick: function(e) {
				if (History.getStateByIndex(1)) {
					History.back();
				} else {
					this.parents('form').trigger('submit', [this]);
				}
				e.preventDefault();
			}
		});

		/**
		 * Hide tabs when only one is available.
		 * Special case is actiontabs - tabs between buttons, where we want to have
		 * extra options hidden within a tab (even if only one) by default.
		 */
		$('.cms-edit-form .ss-tabset').entwine({
			onmatch: function() {
				if (!this.hasClass('ss-ui-action-tabset')) {
					var tabs = this.find("> ul:first");

					if(tabs.children("li").length == 1) {
						tabs.hide().parent().addClass("ss-tabset-tabshidden");
					}
				}

				this._super();
			},
			onunmatch: function() {
				this._super();
			}
		});

	});

}(jQuery));
