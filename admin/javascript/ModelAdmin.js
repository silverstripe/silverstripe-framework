/**
 * File: ModelAdmin.js
 */

/**
 * Javascript handlers for generic model admin.
 * 
 * Most of the work being done here is intercepting clicks on form submits,
 * and managing the loading and sequencing of data between the different panels of
 * the CMS interface.
 * 
 * @todo add live query to manage application of events to DOM refreshes
 * @todo alias the $ function instead of literal jQuery
 */
(function($) {
	$.entwine('ss', function($){

		////////////////////////////////////////////////////////////////// 
		// Search form 
		////////////////////////////////////////////////////////////////// 

		/**
		 * Class: #ModelClassSelector select
		 * 
		 * If a dropdown is used to choose between the classes, it is handled by this code
		 */
		$('#ModelClassSelector select').entwine({
			onmatch: function() {
				// Initialise the form by calling this onchange event straight away
				this.change();
				
				this._super();
			},
			
			/**
			 * Function: onchange
			 * 
			 * Set up an onchange function to show the applicable form and hide all others
			 */
			onchange: function(e) {
				this.find('option').each(function() {
					var $form = $('#'+this.val());
					if(this.val() == this.val()) $form.show();
					else $form.hide();
				});
			}
		});
		
		/**
		 * Class: #SearchForm_holder form
		 * 
		 * Submits a search filter query and attaches event handlers
		 * to the response table, excluding the import form because 
		 * file ($_FILES) submission doesn't work using AJAX 
		 * 
		 * Note: This is used for Form_CreateForm and all Form_SearchForm_* variations
		 */
		$('#SearchForm_holder form').entwine({
			
			/**
			 * Function: onsubmit
			 */
			onsubmit: function(e) {
				// Import forms are processed without ajax
				if(this.attr('id').match(/^Form_ImportForm/)) return true;
				
				$('#contentPanel').closeRightPanel();
			
				this.trigger('beforeSubmit');

				var btn = $(this[0].clickedButton);
				btn.addClass('loading');

				$('#Form_EditForm').loadForm(
					this.attr('action'),
					function() {
						btn.removeClass('loading');
					},
					{data: this.serialize()}
				);

				return false;
			}
		});

		/**
		 * Class: a.form_frontend_function.toggle_result_assembly
		 * 
		 * Column selection in search form
		  */
		$('a.form_frontend_function.toggle_result_assembly').entwine({
			
			/**
			 * Function: onclick
			 */
			onclick: function(e) {
				var toggleElement = $(this).next();
				toggleElement.toggle();
				return false;
			}
		});
	
		/**
		 * Class: a.form_frontend_function.tick_all_result_assembly
		 */
		$('a.form_frontend_function.tick_all_result_assembly').entwine({
			/**
			 * Function: onclick
			 */
			onclick: function(e) {
				var resultAssembly = $(this).prevAll('div#ResultAssembly').find('ul li input');
				resultAssembly.attr('checked', 'checked');
				return false;
			}
		});
	
		$('a.form_frontend_function.untick_all_result_assembly').entwine({
			onclick: function(e) {
				var resultAssembly = $(this).prevAll('div#ResultAssembly').find('ul li input');
				resultAssembly.removeAttr('checked');
				return false;
			}
		});
	
	
		/**
		 * Class: .resultsTable tbody td
		 * 
		 * Table record handler for search result record
		 */
		$('.resultsTable tbody td').entwine({
			/**
			 * Function: onclick
			 */
			onclick: function(e) {
				var firstLink = this.find('a[href]');
				if(!firstLink) return;
				$('#Form_EditForm').loadForm(firstLink.attr('href'));
				return false;
			}
		});

		/**
		 * Class: #Form_ManagedModelsSelect
		 * 
		 * Add object button
		 */
		$('#Form_ManagedModelsSelect').entwine({
			/**
			 * Function: onsubmit
			 */
			onsubmit: function(e) {
				className = $('select option:selected', this).val();
				requestPath = this.attr('action').replace('ManagedModelsSelect', className + '/add');
				var $button = $(':submit', this);
				$('#Form_EditForm').loadForm(
					requestPath,
					function() {
						$button.removeClass('loading');
						$button = null;
					}
				);
				
				return false;
			}
		});
	
		/**
		 * Class: #Form_EditForm input[name=action_doDelete]
		 * 
		 * RHS panel Delete button
		 */
		$('#Form_EditForm input[name=action_doDelete]').entwine({
			// Function: onclick
			onclick: function(e) {
				if(!confirm(ss.i18n._t('ModelAdmin.REALLYDELETE', 'Really delete?'))) {
					this.removeClass('loading');
					return false;
				}
			}
		});
	
		/**
		 * Class: .importSpec
		 * 
		 * Toggle import specifications
		 */
		$('.importSpec').entwine({
			onmatch: function() {
				this.hide();
				this.find('a.detailsLink').click(function() {
					$('#' + $(this).attr('href').replace(/.*#/,'')).toggle();
					return false;
				});
				
				this._super();
			}
		});

		$('#contentPanel').entwine({
			/**
			* Close TinyMCE image, link or flash panel.
			* this function is called everytime a new search, back or add new DataObject are clicked
			**/
			closeRightPanel: function(){
				if($('#contentPanel').is(':visible')) {
					$('#contentPanel').hide();
					$('#Form_EditorToolbarImageForm').hide();
					$('#Form_EditorToolbarFlashForm').hide();
					$('#Form_EditorToolbarLinkForm').hide();
				}
			}
		
		});
	});
})(jQuery);