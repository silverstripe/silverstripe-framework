/**
 * File: LeftAndMain.EditForm.js
 */
(function($) {
	$.entwine('ss', function($){

		/**
		 * Class: #Form_EditForm
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
		$('#Form_EditForm').entwine(/** @lends ss.Form_EditForm */{	
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
			
				this._super();
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
				this.ajaxSubmit();
				
				return false;
			},

			/**
			 * Function: ajaxSubmit
			 * 
			 * Parameters:
			 *  {DOMElement} button - The pressed button (optional)
			 *  {Function} callback - Called in complete() handler of jQuery.ajax()
			 *  {Object} ajaxOptions - Object literal to merge into $.ajax() call
			 *  {boolean} loadResponse - Render response through _loadResponse() (Default: true)
			 * 
			 * Returns:
			 *  (boolean)
			 */
			ajaxSubmit: function(button, callback, ajaxOptions, loadResponse) {
				var self = this;
		  
				// look for save button
				if(!button) button = this.find('.Actions :submit[name=action_save]');
				// default to first button if none given - simulates browser behaviour
				if(!button) button = this.find('.Actions :submit:first');
	
				this.trigger('ajaxsubmit', {button: button});
	
				// set button to "submitting" state
				$(button).addClass('loading');
	
				// @todo TinyMCE coupling
				if(typeof tinyMCE != 'undefined') tinyMCE.triggerSave();
	
				// validate if required
				if(!this.validate()) {
					// TODO Automatically switch to the tab/position of the first error
					statusMessage("Validation failed.", "bad");

					$(button).removeClass('loading');

					return false;
				}
				
				// save tab selections in order to reconstruct them later
				var selectedTabs = [];
				this.find('.ss-tabset').each(function(i, el) {
					if($(el).attr('id')) selectedTabs.push({id:$(el).attr('id'), selected:$(el).tabs('option', 'selected')});
				});

				// get all data from the form
				var formData = this.serializeArray();
				// add button action
				formData.push({name: $(button).attr('name'), value:'1'});
				jQuery.ajax(jQuery.extend({
					url: this.attr('action'), 
					data: formData,
					type: 'POST',
					complete: function(xmlhttp, status) {
						$(button).removeClass('loading');
					
						// TODO This should be using the plugin API
						self.removeClass('changed');
					
						if(callback) callback(xmlhttp, status);
					
						// pass along original form data to enable old/new comparisons
						if(loadResponse !== false) {
						  self._loadResponse(xmlhttp.responseText, status, xmlhttp, formData);
						}
						
						// re-select previously saved tabs
						$.each(selectedTabs, function(i, selectedTab) {
							self.find('#' + selectedTab.id).tabs('select', selectedTab.selected);
						});
					}, 
					dataType: 'html'
				}, ajaxOptions));
	
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
			},

			/**
			 * Function: loadForm
			 * 
			 * Parameters:
			 *  (String) url - ..
			 *  (Function) callback - (Optional) Called after the form content as been loaded
			 *  (Object) ajaxOptions - Object literal merged into the jQuery.ajax() call (Optional)
			 * 
			 * Returns:
			 *  (XMLHTTPRequest)
			 */
			loadForm: function(url, callback, ajaxOptions) {
				var self = this;

				// Alert when unsaved changes are present
				if(this._checkChangeTracker(true) == false) return false;
			
				// hide existing form - shown again through _loadResponse()
				this.addClass('loading');

				this.trigger('load', {url: url});
			
				this.cleanup();

				return jQuery.ajax(jQuery.extend({
					url: url, 
					complete: function(xmlhttp, status) {
					  // TODO This should be using the plugin API
						self.removeClass('changed');

						self._loadResponse(xmlhttp.responseText, status, xmlhttp);
					
						self.removeClass('loading');

						if(callback) callback.apply(self, arguments);
					}, 
					dataType: 'html'
				}, ajaxOptions));
			},

			/**
			 * Function: removeForm
			 * 
			 * Remove everying inside the <form> tag
			 * with a custom HTML fragment. Useful e.g. for deleting a page in the CMS.
			 * Checks for unsaved changes before removing the form
			 * 
			 * Parameters:
			 *  {String} placeholderHtml - Short note why the form has been removed, displayed in <p> tags.
			 *  Falls back to the default RemoveText() option (Optional)
			 */
			removeForm: function(placeholderHtml) {
				if(!placeholderHtml) placeholderHtml = this.getPlaceholderHtml();
				// Alert when unsaved changes are present
				if(this._checkChangeTracker(true) == false) return;
				this.trigger('removeform');
				this.html(placeholderHtml);
				// TODO This should be using the plugin API
				this.removeClass('changed');
			},

			/**
			 * Function: cleanup
			 * 
			 * Remove all the currently active TinyMCE editors.
			 * Note: Everything that calls this externally has an inappropriate coupling to TinyMCE.
			 */
			cleanup: function() {
				if((typeof tinymce != 'undefined') && tinymce.editors) {
					$(tinymce.editors).each(function() {
						if(typeof(this.remove) == 'function') {
							this.remove();
						}
					});
				}
			},

			/**
			 * Function: _loadResponse
			 * 
			 * Parameters:
			 *  {String} data - Either HTML for straight insertion, or eval'ed JavaScript.
			 *  If passed as HTML, it is assumed that everying inside the <form> tag is replaced,
			 *  but the old <form> tag itself stays intact.
			 *  {String} status
			 *  {XMLHTTPRequest} xmlhttp - ..
			 *  {Array} origData - The original submitted data, useful to do comparisons of changed
			 *  values in new form output, e.g. to detect a URLSegment being changed on the serverside.
			 *  Array in jQuery serializeArray() notation.
			 */
			_loadResponse: function(data, status, xmlhttp, origData) {
				if(status == 'success') {
					this.cleanup();
				
					var html = data;

					// Rewrite # links
					html = html.replace(/(<a[^>]+href *= *")#/g, '$1' + window.location.href.replace(/#.*$/,'') + '#');

					// Rewrite iframe links (for IE)
					html = html.replace(/(<iframe[^>]*src=")([^"]+)("[^>]*>)/g, '$1' + $('base').attr('href') + '$2$3');

					// Prepare iframes for removal, otherwise we get loading bugs
					this.find('iframe').each(function() {
						this.contentWindow.location.href = 'about:blank';
						$(this).remove();
					});

					// update form content
					if(html) {
						this.html(html);
					} else {
						this.removeForm();
					}
				
					// @todo Coupling to avoid FOUC (entwine applies to late)
					this.find('.ss-tabset').tabs();
				
					this._setupChangeTracker();
			
					// Optionally get the form attributes from embedded fields, see Form->formHtmlContent()
					for(var overrideAttr in {'action':true,'method':true,'enctype':true,'name':true}) {
						var el = this.find(':input[name='+ '_form_' + overrideAttr + ']');
						if(el) {
							this.attr(overrideAttr, el.val());
							el.remove();
						}
					}
				
					Behaviour.apply(); // refreshes ComplexTableField

					// focus input on first form element
					this.find(':input:visible:first').focus();

					this.trigger('loadnewpage', {data: data, origData: origData});
				}

				// set status message based on response
				var _statusMessage = (xmlhttp.getResponseHeader('X-Status')) ? xmlhttp.getResponseHeader('X-Status') : xmlhttp.statusText;
				if(this.hasClass('validationerror')) {
					// TODO validation shouldnt need a special case
					statusMessage(ss.i18n._t('ModelAdmin.VALIDATIONERROR', 'Validation Error'), 'bad');
				}
			}
		});

		/**
		 * Class: #Form_EditForm .Actions :submit
		 * 
		 * All buttons in the right CMS form go through here by default.
		 * We need this onclick overloading because we can't get to the
		 * clicked button from a form.onsubmit event.
		 */
		$('#Form_EditForm .Actions :submit').entwine({
			
			/**
			 * Function: onclick
			 */
			onclick: function(e) {
				jQuery('#Form_EditForm').entwine('ss').ajaxSubmit(this);
				return false;
			}
		});
	
		/**
		 * Class: #Form_EditForm textarea.htmleditor
		 * 
		 * Add tinymce to HtmlEditorFields within the CMS.
		 */
		$('#Form_EditForm textarea.htmleditor').entwine({
			
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