(function($) {

	$.entwine('ss', function($){
		
		/**
		 * The "content" area contains all of the section specific UI (excluding the menu).
		 * This area can be a form itself, as well as contain one or more forms.
		 * For example, a page edit form might fill the whole area, 
		 * while a ModelAdmin layout shows a search form on the left, and edit form on the right.
		 */
		$('.cms-content').entwine({
			
			onmatch: function() {
				var self = this;
				
				// Force initialization of tabsets to avoid layout glitches
				this.find('.ss-tabset').redrawTabs();
				
				this._super();
			},
						
			redraw: function() {
				// Force initialization of tabsets to avoid layout glitches
				this.add(this.find('.ss-tabset')).redrawTabs();

				this.layout();
			},
			
			/**
			 * Function: loadForm
			 * 
			 * See $('.cms-container').loadPanel() on a frequently used alternative
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
				if(!form || !form.length) {
					var form = $('.cms-content-fields form:first', self);
					if(form.length == 0) form = $('.cms-content-fields').parents("form").eq(0);
				}

				// Alert when unsaved changes are present
				if(!form.confirmUnsavedChanges()) return false;
			
				// hide existing form - shown again through _loadResponse()
				form.addClass('loading');

				this.trigger('loadform', {form: form, url: url});
			
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
			
			/**
			 * Function: loadForm_responseHandler
			 *
			 * Loads the response into the DOM provided. Assumes oldForm is contains
			 * the form tag to replace. If oldForm isn't present in the DOM, such as
			 * if this form is only shown after click, append the whole form.
			 *
			 * Parameters:
			 *  (String) oldForm - HTML or eval'd javascript
			 *  (String) html - HTML to replace oldForm
			 *  (String) status
			 *  (XMLHTTPRequest) xmlhttp
			 */
			loadForm_responseHandler: function(oldForm, html, status, xmlhttp) {

				if(oldForm.length > 0) {
					oldForm.replaceWith(html); // triggers onmatch() on form
				}
				else {
					 $('.cms-content').append(html);
				}
				
				// redraw the layout.
				jQuery('.cms-container').entwine('ss').redraw();
				
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
	
				form.trigger('beforesave');
				this.trigger('submitform', {form: form, button: button});
	
				// set button to "submitting" state
				$(button).addClass('loading');
	
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

						// Redraw the layout
						jQuery('.cms-container').entwine('ss').redraw();
						
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
				
					if(typeof(Behaviour) != 'undefined') Behaviour.apply(); // refreshes ComplexTableField

					this.trigger('reloadeditform', {form: form, origData: origData, xmlhttp: xmlhttp});
				}

				// set status message based on response
				var _statusMessage = (xmlhttp.getResponseHeader('X-Status')) ? xmlhttp.getResponseHeader('X-Status') : xmlhttp.statusText;
			},
			
			/**
			 * @return {jQuery} New form element
			 */
			replaceForm: function(form, html) {
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
				if(!form.confirmUnsavedChanges()) return;
				this.trigger('removeform');
				this.html(placeholderHtml);
				// TODO This should be using the plugin API
				this.removeClass('changed');
			}
		});
	});

	/**
	 * Load edit form for the selected node when its clicked.
	 */
	$('.cms-content .cms-tree').entwine({
		onmatch: function() {
			var self = this;

			this._super();

			this.bind('select_node.jstree', function(e, data) {
				var node = data.rslt.obj, loadedNodeID = self.find(':input[name=ID]').val(), origEvent = data.args[2], container = $('.cms-container');
				
				// Don't trigger unless coming from a click event.
				// Avoids problems with automated section switches from tree to detail view
				// when JSTree auto-selects elements on first load.
				if(!origEvent) {
					return false;
				}else if($(origEvent.target).hasClass('jstree-icon') || $(origEvent.target).hasClass('jstree-pageicon')){
					// in case the click is not on the node title, ie on pageicon or dragicon, 
					return false;
				}
				
				// Don't allow checking disabled nodes
				if($(node).hasClass('disabled')) return false;

				// Don't allow reloading of currently selected node,
				// mainly to avoid doing an ajax request on initial page load
				if($(node).data('id') == loadedNodeID) return;

				var url = $(node).find('a:first').attr('href');
				if(url && url != '#') {
					if($(node).find('a:first').is(':internal')) url = $('base').attr('href') + url;
					// Reload only edit form if it exists (side-by-side view of tree and edit view), otherwise reload whole panel
					if(container.find('.cms-edit-form').length) {
						url += '?cms-view-form=1';
						container.entwine('ss').loadPanel(url, null, {selector: '.cms-edit-form'});
					} else {
						container.entwine('ss').loadPanel(url);	
					}
				} else {
					self.removeForm();
				}
			});
		}
	});
	
	$('.cms-content.loading,.cms-edit-form.loading').entwine({
		onmatch: function() {
			this.append('<div class="cms-content-loading-overlay ui-widget-overlay-light"></div><div class="cms-content-loading-spinner"></div>');
		},
		onunmatch: function() {
			this.find('.cms-content-loading-overlay,.cms-content-loading-spinner').remove();
		}
	});

	/**
	 * Loads
	 */
	$('.cms-content .cms-panel-link').entwine({
		onclick: function(e) {
			var href = this.attr('href'), url = href ? href : this.data('href'),
				data = (this.data('target-panel')) ? {selector: this.data('target-panel')} : null;
			
			$('.cms-container').entwine('ss').loadPanel(url, null, data);
			e.preventDefault();
		}
	});
	
})(jQuery);