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
				this.find('.cms-tabset').redrawTabs();
				
				this._super();
			},
						
			redraw: function() {
				// Force initialization of tabsets to avoid layout glitches
				this.add(this.find('.cms-tabset')).redrawTabs();

				this.layout();
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
				form.find('.cms-tabset').each(function(i, el) {
					if($(el).attr('id')) selectedTabs.push({id:$(el).attr('id'), selected:$(el).tabs('option', 'selected')});
				});

				// get all data from the form
				var formData = form.serializeArray();
				// add button action
				formData.push({name: $(button).attr('name'), value:'1'});
				// Artificial HTTP referer, IE doesn't submit them via ajax. 
				// Also rewrites anchors to their page counterparts, which is important
				// as automatic browser ajax response redirects seem to discard the hash/fragment.
				formData.push({name: 'BackURL', value:History.getPageUrl()});

				jQuery.ajax(jQuery.extend({
					headers: {
						"X-Pjax" : "CurrentForm",
						'X-Pjax-Selector': '.cms-edit-form'
					},
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
						if(self.hasClass('cms-tabset')) self.removeClass('cms-tabset').addClass('cms-tabset');

						// re-select previously saved tabs
						$.each(selectedTabs, function(i, selectedTab) {
							form.find('#' + selectedTab.id).tabs('select', selectedTab.selected);
						});

						// Redraw the layout
						$('.cms-container').redraw();
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
					if(!data) return;

					var form, newContent = $(data);

					// HACK If response contains toplevel panel rather than a form, replace it instead.
					// For example, a page view shows tree + edit form. Deleting this page redirects to
					// the "pages" overview, which doesn't have a separate tree panel.
					if(newContent.is('.cms-content')) {
						$('.cms-content').replaceWith(newContent);
					} else {
						form = this.replaceForm(oldForm, newContent);	
					}
				
					if(typeof(Behaviour) != 'undefined') Behaviour.apply(); // refreshes ComplexTableField
					
					this.trigger('reloadeditform', {form: form, origData: origData, xmlhttp: xmlhttp});
				}

				// Display status message, if available.
				var _statusMessage = (xmlhttp.getResponseHeader('X-Status')) ? xmlhttp.getResponseHeader('X-Status') : xmlhttp.statusText;
				if (typeof _statusMessage==='string') {
					statusMessage(_statusMessage);
				}
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

					// Ensure URL is absolute (important for IE)
					if($.path.isExternal($(node).find('a:first'))) url = url = $.path.makeUrlAbsolute(url, $('base').attr('href'));
					// Retain search parameters
					if(document.location.search) url = $.path.addSearchParams(url, document.location.search.replace(/^\?/, ''));
					// Load new page
					container.entwine('ss').loadPanel(url);	
				} else {
					self.removeForm();
				}
			});
		}
	});

	$('.cms-content.loading,.cms-edit-form.loading,.cms-content-fields.loading,.cms-content-view.loading').entwine({
		onmatch: function() {
			this.append('<div class="cms-content-loading-overlay ui-widget-overlay-light"></div><div class="cms-content-loading-spinner"></div>');
		},
		onunmatch: function() {
			this.find('.cms-content-loading-overlay,.cms-content-loading-spinner').remove();
		}
	});

})(jQuery);
