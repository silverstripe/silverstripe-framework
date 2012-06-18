/**
 * File: LeftAndMain.AddForm.js
 */
(function($) {
	$.entwine('ss', function($){
		/**
		 * Class: .add-form
		 * 
		 * Simple form with a page type dropdown
		 * which creates a new page through .cms-edit-form and adds a new tree node.
		 * 
		 * Requires:
		 *  ss.i18n
		 *  .cms-edit-form
		 */
		$('.cms-edit-form.cms-add-form').entwine({
			/**
			 * Variable: OrigOptions 
			 * (Array) Map of <option> values to an object of "title" and "value"
			 */
			OrigOptions: null,
	
			/**
			 * Variable: NewPages 
			 * (Array) Internal counter to create unique page identifiers prior to ajax saving
			 */
			NewPages: [],

			getTree: function() {
				return $('.cms-tree');
			},

			fromTree: {
				onselect_node: function(e, data){
					this.refresh(data.rslt.obj);
				}
			},

			/**
			 * Constructor: onmatch
			 */
			onadd: function() {
				var self = this, typeDropdown = this.find(':input[name=PageType]');
		
				// Event bindings
				typeDropdown.bind('change', function(e) {self.refresh();});
				// TODO Bind on tree initialization to set dropdown for selected node
				
				// Store original page type options (they might get filtered to "allowed_children") later on
				// TODO Better DOM element serialization (jQuery 1.4?)
				var opts = {};
				typeDropdown.find('option').each(function(el) {
					opts[$(this).val()] = {html:$(this).html(), value: $(this).val()};
				});
				this.setOrigOptions(opts);
				
				this._super();
			},

			/**
			 * Function: onsubmit
			 * 
			 * Parameters:
			 *  (Event) e
			 */
			onsubmit: function(e) {
				var newPages = this.getNewPages(), tree = this.getTree(), node = $(tree).jstree('get_selected');
				var parentID = (node.length) ? node.data('id') : 0;

				// TODO: Remove 'new-' code http://open.silverstripe.com/ticket/875
				// if(parentID && parentID.substr(0,3) == 'new') {
				// 	alert(ss.i18n._t('CMSMAIN.WARNINGSAVEPAGESBEFOREADDING'));
				// }

				if(node && node.hasClass("nochildren")) {
					alert(ss.i18n._t('CMSMAIN.CANTADDCHILDREN') );
				} 
		
				// Optionally initalize the new pages tracker
				if(!newPages[parentID] ) newPages[parentID] = 1;

				// default to first button
				var button = this.find(':submit:first');
				button.addClass('loading');
		
				// collect data and submit the form
				var data = this.serializeArray();
				data.push({name:'Suffix',value:newPages[parentID]++});
				data.push({name:button.attr('name'),value:button.val()});

				// TODO Should be set by hiddenfield already
				$('.cms-container').submitForm(
					this,
					button,
					function() {
						// Tree updates are triggered by Form_EditForm load events
						button.removeClass('loading');
					},
					{
						type: 'POST',
						data: data,
						// Refresh the whole area to avoid reloading just the form, without the tree around it
						headers: {'X-Pjax': 'Content'}
					}
				);
		
				this.setNewPages(newPages);

				return false;
			},
			
			/**
			 * Function: refresh
			 * This is called after each change event of PageType dropdown
			 * 
			 * Parameters:
			 *  (DOMElement) selectedNode
			 */
			refresh: function(selectedNode) {
				
				var tree = this.getTree(),
				 	selectedNode = selectedNode || $(tree).jstree('get_selected')
					origOptions = this.getOrigOptions(), 
					dropdown = this.find('select[name=PageType]'),
					disallowed = [],
					className = (selectedNode.length>0) ? selectedNode.entwine('ss.tree').getClassname() : null,
					siteTreeHints = $.parseJSON($('#sitetree_ul').attr('data-hints')),
					disableDropDown = true,
					selectedOption = dropdown.val();

				// Clear all existing <option> elements
				// (IE doesn't allow setting display:none on these elements)
				dropdown.find('option').remove();
				
				//Use tree hints to find allowed children for this node
				if (className && siteTreeHints) {
					disallowed = siteTreeHints[className].disallowedChildren;
				}
				
				$.each(origOptions, function(i, optProps) { 
				  if ($.inArray(i, disallowed) === -1 && optProps) {
					  dropdown.append($('<option value="' + optProps.value + '">' + optProps.html + '</option>'));
					  disableDropDown = false;
				  }
				});
				
				// Disable dropdown if no elements are selectable
				if (!disableDropDown) dropdown.removeAttr('disabled');
				else dropdown.attr('disabled', 'disabled');

				//Re-select the currently selected element
				if (selectedOption) dropdown.val(selectedOption);

				// Set default child (optional)
				if(selectedNode.hints && selectedNode.hints.defaultChild) {
					dropdown.val(selectedNode.hints.defaultChild);
				}
		
				// Set parent node (fallback to root)
				this.find(':input[name=ParentID]').val(selectedNode ? selectedNode.data('id') : 0);
			}
		});
	});
}(jQuery));
