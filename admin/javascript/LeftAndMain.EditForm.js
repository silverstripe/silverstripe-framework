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

				// TODO
				// // Rewrite # links
				// html = html.replace(/(<a[^>]+href *= *")#/g, '$1' + window.location.href.replace(/#.*$/,'') + '#');
				// 
				// // Rewrite iframe links (for IE)
				// html = html.replace(/(<iframe[^>]*src=")([^"]+)("[^>]*>)/g, '$1' + $('base').attr('href') + '$2$3');
				
				// Show validation errors if necessary
				if(this.hasClass('validationerror')) {
					// TODO validation shouldnt need a special case
					statusMessage(ss.i18n._t('ModelAdmin.VALIDATIONERROR', 'Validation Error'), 'bad');

					// Ensure the first validation error is visible
					var firstTabWithErrors = this.find('.message.validation:first').closest('.tab');
					$('.cms-container').clearCurrentTabState(); // clear state to avoid override later on
					this.redraw();
					firstTabWithErrors.closest('.ss-tabset').tabs('select', firstTabWithErrors.attr('id'));
				}
			
				this._super();
			},
			onremove: function() {
				this.changetracker('destroy');
				this._super();
			},
			onmatch: function() {
				this._super();

				// focus input on first form element. Exclude elements which
				// specifically opt-out of this behaviour via "data-skip-autofocus".
				// This opt-out is useful if the first visible field is shown far down a scrollable area,
				// for example for the pagination input field after a long GridField listing.
				// Skip if an element in the form is already focused.
				if(!this.find(document.activeElement).length) {
					this.find(':input:not(:submit)[data-skip-autofocus!="true"]').filter(':visible:first').focus();
				}
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
			 * Returns:
			 *  (Boolean) FALSE if the user wants to abort with changes present, TRUE if no changes are detected 
			 *  or the user wants to discard them.
			 */
			confirmUnsavedChanges: function() {
				this.trigger('beforesubmitform');
				return (this.is('.changed')) ? confirm(ss.i18n._t('LeftAndMain.CONFIRMUNSAVED')) : true;
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
