(function($) {

	$.entwine('ss', function($){
		
		$('.cms-content, .cms-content *').entwine({
			/**
			 * Triggered before a new URL is loaded, typically via ajax.
			 * Loading itself is handled by $('.cms-container') and window.history.
			 * 
			 * @param {String}
			 */
			beforeLoad: function(url) {
				this.addClass('loading');
				this.cleanup();
			},
			
			/**
			 * Triggered after an ajax request with new HTML data.
			 * 
			 * @param {String}
			 * @param {String}
			 * @param {XMLHTTPRequest}
			 */
			afterLoad: function(data, status, xhr) {
				this.removeClass('loading');
				this.replaceWith(data);
			},
			
			cleanup: function() {
				this.empty();
			}
		});

		/**
		 * The "content" area contains all of the section specific UI (excluding the menu).
		 * This area can be a form itself, as well as contain one or more forms.
		 * For example, a page edit form might fill the whole area, 
		 * while a ModelAdmin layout shows a search form on the left, and edit form on the right.
		 */
		$('.cms-content').entwine({
			
			onmatch: function() {
				var self = this;
				
				// Listen to tree selection events
				$('.cms-tree').bind('select_node.jstree', function(e, data) {
					var node = data.rslt.obj, loadedNodeID = self.find(':input[name=ID]').val()
					
					// Don't allow checking disabled nodes
					if($(node).hasClass('disabled')) return false;

					// Don't allow reloading of currently selected node,
					// mainly to avoid doing an ajax request on initial page load
					if($(node).data('id') == loadedNodeID) return;

					var url = $(node).find('a:first').attr('href');
					if(url && url != '#') {
						window.History.pushState({}, '', url);
					} else {
						self.removeForm();
					}
				});
				
				this._super();
			},
			
			onunmatch: function() {
				this._super();
			},
			
			/**
			 * Function: loadForm
			 * 
			 * See $('.cms-container').handleStateChange() on a frequently used alternative
			 * to direct ajax loading of content, with support for the window.History object.
			 * 
			 * Parameters:
			 *  (String) url - ..
			 *  (Function) callback - (Optional) Called after the form content as been loaded
			 *  (Object) ajaxOptions - Object literal merged into the jQuery.ajax() call (Optional)
			 * 
			 * Returns:
			 *  (XMLHTTPRequest)
			 */
			loadForm: function(url, form, callback, ajaxOptions) {
				var self = this;
				if(!form || !form.length) var form = $('.cms-content-fields form:first');

				// Alert when unsaved changes are present
				if(form._checkChangeTracker(true) == false) return false;
			
				// hide existing form - shown again through _loadResponse()
				form.addClass('loading');

				this.trigger('loadform', {form: form, url: url});
			
				form.cleanup();

				return jQuery.ajax(jQuery.extend({
					url: url, 
					// Ensure that form view is loaded (rather than whole "Content" template)
					data: {'cms-view-form': 1},
					complete: function(xmlhttp, status) {
						self.loadForm_responseHandler(form, xmlhttp.responseText, status, xmlhttp);
						if(callback) callback.apply(self, arguments);
					}, 
					dataType: 'html'
				}, ajaxOptions));
			},
			
			loadForm_responseHandler: function(oldForm, html, status, xmlhttp) {
				oldForm.replaceWith(html); // triggers onmatch() on form
				
				// set status message based on response
				var _statusMessage = (xmlhttp.getResponseHeader('X-Status')) ? xmlhttp.getResponseHeader('X-Status') : xmlhttp.statusText;
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
			submitForm: function(form, button, callback, ajaxOptions, loadResponse) {
				var self = this;
		  
				// look for save button
				if(!button) button = this.find('.Actions :submit[name=action_save]');
				// default to first button if none given - simulates browser behaviour
				if(!button) button = this.find('.Actions :submit:first');
	
				this.trigger('submitform', {form: form, button: button});
	
				// set button to "submitting" state
				$(button).addClass('loading');
	
				// @todo TinyMCE coupling
				if(typeof tinyMCE != 'undefined') tinyMCE.triggerSave();
	
				// validate if required
				if(!form.validate()) {
					// TODO Automatically switch to the tab/position of the first error
					statusMessage("Validation failed.", "bad");

					$(button).removeClass('loading');

					return false;
				}
				
				// save tab selections in order to reconstruct them later
				var selectedTabs = [];
				form.find('.ss-tabset').each(function(i, el) {
					if($(el).attr('id')) selectedTabs.push({id:$(el).attr('id'), selected:$(el).tabs('option', 'selected')});
				});

				// get all data from the form
				var formData = form.serializeArray();
				// add button action
				formData.push({name: $(button).attr('name'), value:'1'});
				jQuery.ajax(jQuery.extend({
					url: form.attr('action'), 
					data: formData,
					type: 'POST',
					complete: function(xmlhttp, status) {
						$(button).removeClass('loading');
					
						// TODO This should be using the plugin API
						form.removeClass('changed');
					
						if(callback) callback(xmlhttp, status);
					
						// pass along original form data to enable old/new comparisons
						if(loadResponse !== false) {
						  self.submitForm_responseHandler(form, xmlhttp.responseText, status, xmlhttp, formData);
						}
						
						// Re-init tabs (in case the form tag itself is a tabset)
						if(self.hasClass('ss-tabset')) self.removeClass('ss-tabset').addClass('ss-tabset');
						
						// re-select previously saved tabs
						$.each(selectedTabs, function(i, selectedTab) {
							form.find('#' + selectedTab.id).tabs('select', selectedTab.selected);
						});
					}, 
					dataType: 'html'
				}, ajaxOptions));
	
				return false;
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
			submitForm_responseHandler: function(oldForm, data, status, xmlhttp, origData) {
				if(status == 'success') {
					var form = this.replaceForm(oldForm, data);
				
					Behaviour.apply(); // refreshes ComplexTableField

					this.trigger('loadnewpage', {form: form, origData: origData, xmlhttp: xmlhttp});
				}

				// set status message based on response
				var _statusMessage = (xmlhttp.getResponseHeader('X-Status')) ? xmlhttp.getResponseHeader('X-Status') : xmlhttp.statusText;
			},
			
			/**
			 * @return {jQuery} New form element
			 */
			replaceForm: function(form, html) {
				form.cleanup();
				if(html) {
					var parent = form.parent(), id = form.attr('id');
					form.replaceWith(html);
					// Try to get the new form by ID (assuming they're identical), otherwise fall back to the first form in the parent
					return id ? $('#' + id) : parent.children('form:first');
				}	else {
					this.removeForm(form);
					return null;
				}
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
			removeForm: function(form, placeholderHtml) {
				if(!placeholderHtml) placeholderHtml = this.getPlaceholderHtml();
				// Alert when unsaved changes are present
				if(form._checkChangeTracker(true) == false) return;
				this.trigger('removeform');
				this.html(placeholderHtml);
				// TODO This should be using the plugin API
				this.removeClass('changed');
			}
		});
	});
})(jQuery);