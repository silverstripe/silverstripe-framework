/**
 * File: LeftAndMain.EditForm.js
 */
(function($) {
	$.entwine('ss', function($){

		/**
		 * Class: .cms-edit-form
		 * 
		 * Base edit form, provides ajaxified saving
		 * and reloading itself through the ajax return values.
		 * Takes care of resizing tabsets within the layout container.
		 * @name ss.Form_EditForm
		 * @require jquery.changetracker
		 * 
		 * Events:
		 *  ajaxsubmit - Form is about to be submitted through ajax
		 *  validate - Contains validation result
		 *  removeform - A form is about to be removed from the DOM
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
			ChangeTrackerOptions: {},
		
			/**
			 * Constructor: onmatch
			 */
			onmatch: function() {
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

				// Can't bind this through jQuery
				window.onbeforeunload = function(e) {
					self.trigger('beforesave');
					if(self.is('.changed')) return ss.i18n._t('LeftAndMain.CONFIRMUNSAVEDSHORT');
				};

				// Catch navigation events before they reach handleStateChange(),
				// in order to avoid changing the menu state if the action is cancelled by the user
				$('.cms-menu')
				
				// focus input on first form element
				this.find(':input:visible:not(:submit):first').focus();
				
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
				}
				
				// Move navigator to preview if one is available.
				// If not, just leave the links in the form.
				var previewEl = $('.cms-preview');
				if(previewEl.length) {
					// TODO Relies on DOM element order (the second .cms-navigator is the "old" one)
					previewEl.find('.cms-preview-controls').html(this.find('.cms-navigator').detach());
				}
			
				this._super();
			},
						
			redraw: function() {
				// Force initialization of tabsets to avoid layout glitches
				this.add(this.find('.ss-tabset')).redrawTabs();

				var approxWidth = $('.cms-container').width() - $('.cms-menu').width();
				this.find('.cms-content-actions').width(approxWidth).height('auto');
				
				this.layout();
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
				this.trigger('beforesave');
				return (this.is('.changed')) ? confirm(ss.i18n._t('LeftAndMain.CONFIRMUNSAVED')) : true;
			},

			/**
			 * Function: onsubmit
			 * 
			 * Suppress submission unless it is handled through ajaxSubmit().
			 */
			onsubmit: function(e) {
				this.parents('.cms-content').submitForm(this);
				
				return false;
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
		$('.cms-edit-form .Actions input, .cms-edit-form .Actions button').entwine({
			
			/**
			 * Function: onclick
			 */
			onclick: function(e) {
				$('.cms-content').submitForm(this.parents('form'), this);
				return false;
			}
		});
	
		/**
		 * Class: .cms-edit-form textarea.htmleditor
		 * 
		 * Add tinymce to HtmlEditorFields within the CMS. Works in combination
		 * with a TinyMCE.init() call which is prepopulated with the used HTMLEditorConfig settings,
		 * and included in the page as an inline <script> tag.
		 */
		$('.cms-edit-form textarea.htmleditor').entwine({
			
			/**
			 * Constructor: onmatch
			 */
			onmatch : function() {
				var self = this;
				this.closest('form').bind('beforesave', function() {
					if(typeof tinyMCE == 'undefined') return;

					// TinyMCE modifies input, so change tracking might get false
					// positives when comparing string values - don't save if the editor doesn't think its dirty.
					if(self.isChanged()) {
						tinyMCE.triggerSave();
						// TinyMCE assigns value attr directly, which doesn't trigger change event
						self.trigger('change'); 	
					}
				});

				// Only works after TinyMCE.init() has been invoked, see $(window).bind() call below for details.
				this.redraw();

				this._super();
			},

			redraw: function() {
				// Using a global config (generated through HTMLEditorConfig PHP logic)
				var config = ssTinyMceConfig, self = this;

				// Avoid flicker (also set in CSS to apply as early as possible)
				self.css('visibility', '');

				// Create editor instance and render it.
				// Similar logic to adapter/jquery/jquery.tinymce.js, but doesn't rely on monkey-patching
				// jQuery methods, and avoids replicate the script lazyloading which is already in place with jQuery.ondemand.
				var ed = new tinymce.Editor(this.attr('id'), config);
				ed.onInit.add(function() {
					self.css('visibility', 'visible');
				});
				ed.render();

				// Handle editor de-registration by hooking into state changes.
				// TODO Move to onunmatch for less coupling (once we figure out how to work with detached DOM nodes in TinyMCE)
				$('.cms-container').bind('beforestatechange', function() {
					self.css('visibility', 'hidden');
					var ed = tinyMCE.get(self.attr('id'));
					if(ed) ed.remove();
				});

				this._super();
			},

			isChanged: function() {
				if(typeof tinyMCE == 'undefined') return;

				return tinyMCE.getInstanceById(this.attr('id')).isDirty();
			},

			resetChanged: function() {
				if(typeof tinyMCE == 'undefined') return;

				var inst = tinyMCE.getInstanceById(this.attr('id'));
				if (inst) inst.startContent = tinymce.trim(inst.getContent({format : 'raw', no_events : 1}));
			},

			onunmatch: function() {
				// TODO Throws exceptions in Firefox, most likely due to the element being removed from the DOM at this point
				// var ed = tinyMCE.get(this.attr('id'));
				// if(ed) ed.remove();

				this._super();
			}
		});
		
	});

}(jQuery));