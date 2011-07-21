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
			
				this._setupChangeTracker();

				// Can't bind this through jQuery
				window.onbeforeunload = function(e) {return self._checkChangeTracker(false);};
				
				// focus input on first form element
				this.find(':input:visible:first').focus();
				
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
			
			onunmatch: function() {
				// Prepare iframes for removal, otherwise we get loading bugs
				this.find('iframe').each(function() {
					this.contentWindow.location.href = 'about:blank';
					$(this).remove();
				});
				
				// Remove all TinyMCE instances
				if((typeof tinymce != 'undefined') && tinymce.editors) {
					$(tinymce.editors).each(function() {
						if(typeof(this.remove) == 'function') this.remove();
					});
				}
				
				this._super();
			},
			
			redraw: function() {
				// TODO Manually set container height before resizing - shouldn't be necessary'
				this.find('.cms-content-actions').height(this.find('.cms-content-actions .Actions').height());
				
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
			 * Function: _checkChangeTracker
			 * 
			 * Checks the jquery.changetracker plugin status for this form.
			 * Usually bound to window.onbeforeunload.
			 * 
			 * Parameters:
			 *  {boolean} isUnloadEvent - ..
			 * 
			 * Returns:
			 *  (String) Either a string with a confirmation message, or the result of a confirm() dialog,
			 *  based on the isUnloadEvent parameter.
			 */
			_checkChangeTracker: function(isUnloadEvent) {
			  var self = this;
		  
				// @todo TinyMCE coupling
				if(typeof tinyMCE != 'undefined') tinyMCE.triggerSave();
			
				// check for form changes
				if(self.is('.changed')) {
					// returned string will trigger a confirm() dialog, 
					// but only if the method is triggered by an event
					if(isUnloadEvent) {
						return confirm(ss.i18n._t('LeftAndMain.CONFIRMUNSAVED'));
					} else {
						return ss.i18n._t('LeftAndMain.CONFIRMUNSAVEDSHORT');
					}
				}
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
		$('.cms-edit-form .Actions :submit').entwine({
			
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
		 * Add tinymce to HtmlEditorFields within the CMS.
		 */
		$('.cms-edit-form textarea.htmleditor').entwine({
			
			/**
			 * Constructor: onmatch
			 */
			onmatch : function() {
				tinyMCE.execCommand("mceAddControl", true, this.attr('id'));
				this.isChanged = function() {
					return tinyMCE.getInstanceById(this.attr('id')).isDirty();
				};
				this.resetChanged = function() {
					var inst = tinyMCE.getInstanceById(this.attr('id'));
					if (inst) inst.startContent = tinymce.trim(inst.getContent({format : 'raw', no_events : 1}));
				};
			}
		});
	});
}(jQuery));