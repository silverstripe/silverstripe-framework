/**
 * File: LeftAndMain.BatchActions.js
 */
(function($) {
	$.entwine('ss.tree', function($){
	
		/**
		 * Class: #Form_BatchActionsForm
		 * 
		 * Batch actions which take a bunch of selected pages,
		 * usually from the CMS tree implementation, and perform serverside
		 * callbacks on the whole set. We make the tree selectable when the jQuery.UI tab
		 * enclosing this form is opened.
		 * 
		 * Events:
		 *  register - Called before an action is added.
		 *  unregister - Called before an action is removed.
		 */
		$('#Form_BatchActionsForm').entwine({
	
			/**
			 * Variable: Actions
			 * (Array) Stores all actions that can be performed on the collected IDs as
			 * function closures. This might trigger filtering of the selected IDs,
			 * a confirmation message, etc.
			 */
			Actions: [],

			getTree: function() {
				return $('.cms-tree');
			},

			fromTree: {
				oncheck_node: function(e, data){
					this.serializeFromTree();
				},
				onuncheck_node: function(e, data){
					this.serializeFromTree();
				}
			},

			/**
			 * Constructor: onmatch
			 */
			onadd: function() {
				this._updateStateFromViewMode();
				this._super();
			},

			'from .cms-tree-view-modes :input[name=view-mode]': {
				onclick: function(e){
					var val = $(e.target).val(), dropdown = this.find(':input[name=Action]'), tree = this.getTree();

					if(val == 'multiselect') {
						tree.addClass('multiple');
						this.serializeFromTree();
					} else {
						tree.removeClass('multiple');
					}

					this._updateStateFromViewMode();
				}
			},

			/**
			 * Updates the select box state according to the current view mode.
			 */
			_updateStateFromViewMode: function() {
				var viewMode = $('.cms-tree-view-modes :input[name=view-mode]:checked').val();
				var dropdown = this.find(':input[name=Action]');

				// Batch actions only make sense when multiselect is enabled.
				if(viewMode == 'multiselect') dropdown.removeAttr('disabled').trigger("liszt:updated");
				else dropdown.attr('disabled', true).trigger("liszt:updated");
			},

			/**
			 * Function: register
			 * 
			 * Parameters:
			 * 
			 * 	(String) type - ...
			 * 	(Function) callback - ...
			 */
			register: function(type, callback) {
				this.trigger('register', {type: type, callback: callback});
				var actions = this.getActions();
				actions[type] = callback;
				this.setActions(actions);
			},
		
			/**
			 * Function: unregister
			 * 
			 * Remove an existing action.
			 * 
			 * Parameters:
			 * 
			 *  {String} type
			 */
			unregister: function(type) {
				this.trigger('unregister', {type: type});
			
				var actions = this.getActions();
				if(actions[type]) delete actions[type];
				this.setActions(actions);
			},
		
			/**
			 * Function: _isActive
			 * 
			 * Determines if we should allow and track tree selections.
			 * 
			 * Todo:
			 *  Too much coupling with tabset
			 * 
			 * Returns:
			 *  (boolean)
			 */
			_isActive: function() {
				return $('.cms-content-batchactions').is(':visible');
			},
		
			/**
			 * Function: refreshSelected
			 * 
			 * Ajax callbacks determine which pages is selectable in a certain batch action.
			 * 
			 * Parameters:
			 *  {Object} rootNode
			 */
			refreshSelected : function(rootNode) {
				var self = this, st = this.getTree(), ids = this.getIDs(), allIds = [];
				// Default to refreshing the entire tree
				if(rootNode == null) rootNode = st;

				for(var idx in ids) {
					$($(st).getNodeByID(idx)).addClass('selected').attr('selected', 'selected');
				}

				$(rootNode).find('li').each(function() {
					allIds.push($(this).data('id'));
					
					// Disable the nodes while the ajax request is being processed
					$(this).addClass('treeloading').setEnabled(false);
				});

				// Post to the server to ask which pages can have this batch action applied
				var applicablePagesURL = this.find(':input[name=Action]').val() + '/applicablepages/?csvIDs=' + allIds.join(',');
				jQuery.getJSON(applicablePagesURL, function(applicableIDs) {
					// Set a CSS class on each tree node indicating which can be batch-actioned and which can't
					jQuery(rootNode).find('li').each(function() {
						$(this).removeClass('treeloading');

						var id = $(this).data('id');
						if(id == 0 || $.inArray(id, applicableIDs) >= 0) {
							$(this).setEnabled(true);
						} else {
							// De-select the node if it's non-applicable
							$(this).removeClass('selected').setEnabled(false);
						}
					});
					
					self.serializeFromTree();
				});
			},
			
			/**
			 * Function: serializeFromTree
			 * 
			 * Returns:
			 *  (boolean)
			 */
			serializeFromTree: function() {
				var tree = this.getTree(), ids = tree.getSelectedIDs();
				
				// if no IDs are selected, stop here. This is an implict way for the
				// callback to cancel the actions
				if(!ids || !ids.length) return false;

				// write IDs to the hidden field
				this.setIDs(ids);
				
				return true;
			},
			
			/**
			 * Function: setIDS
			 *  
			 * Parameters:
			 *  {Array} ids
			 */
			setIDs: function(ids) {
				this.find(':input[name=csvIDs]').val(ids ? ids.join(',') : null);
			},
			
			/**
			 * Function: getIDS
			 * 
			 * Returns:
			 *  {Array}
			 */
			getIDs: function() {
				return this.find(':input[name=csvIDs]').val().split(',');
			},
		
			/**
			 * Function: onsubmit
			 * 
			 * Parameters:
			 *  (Event) e
			 */
			onsubmit: function(e) {
				var self = this, ids = this.getIDs(), tree = this.getTree();
				
				// if no nodes are selected, return with an error
				if(!ids || !ids.length) {
					alert(ss.i18n._t('CMSMAIN.SELECTONEPAGE'));
					return false;
				}
				
				// apply callback, which might modify the IDs
				var type = this.find(':input[name=Action]').val();
				if(this.getActions()[type]) ids = this.getActions()[type].apply(this, [ids]);
			
				// write (possibly modified) IDs back into to the hidden field
				this.setIDs(ids);
				
				// Reset failure states
				tree.find('li').removeClass('failed');
			
				var button = this.find(':submit:first');
				button.addClass('loading');
			
				jQuery.ajax({
					// don't use original form url
					url: type,
					type: 'POST',
					data: this.serializeArray(),
					complete: function(xmlhttp, status) {
						button.removeClass('loading');

						// Deselect all nodes
						tree.jstree('uncheck_all');
						self.setIDs([]);

						// Reset action
						self.find(':input[name=Action]').val('').change();
					
						// status message (decode into UTF-8, HTTP headers don't allow multibyte)
						var msg = xmlhttp.getResponseHeader('X-Status');
						if(msg) statusMessage(decodeURIComponent(msg), (status == 'success') ? 'good' : 'bad');
					},
					success: function(data, status) {
						var id, node;
						
						if(data.modified) {
							var modifiedNodes = [];
							for(id in data.modified) {
								node = tree.getNodeByID(id);
								tree.jstree('set_text', node, data.modified[id]['TreeTitle']);
								modifiedNodes.push(node);
							}
							$(modifiedNodes).effect('highlight');
						}
						if(data.deleted) {
							for(id in data.deleted) {
								node = tree.getNodeByID(id);
								if(node.length)	tree.jstree('delete_node', node);
							}
						}
						if(data.error) {
							for(id in data.error) {
								node = tree.getNodeByID(id);
								$(node).addClass('failed');
							}
						}
					},
					dataType: 'json'
				});
			
				return false;
			}
		
		});
	
		/**
		 * Class: #Form_BatchActionsForm :select[name=Action]
		 */
		$('#Form_BatchActionsForm select[name=Action]').entwine({
			
			onmatch: function() {
				this.trigger('change');
				this._super();
			},
			onunmatch: function() {
				this._super();
			},
			/**
			 * Function: onchange
			 * 
			 * Parameters:
			 *  (Event) e
			 */
			onchange: function(e) {
				var form = $(e.target.form), btn = form.find(':submit');
				if($(e.target).val() == -1) {
					btn.attr('disabled', 'disabled').button('refresh');
				} else {
					btn.removeAttr('disabled').button('refresh');
					// form.submit();
				} 

				// TODO Should work by triggering change() along, but doesn't - entwine event bubbling?
				this.trigger("liszt:updated");

				this._super(e);
			}
		});

		$(document).ready(function() {
			/**
			 * Publish selected pages action
			 */
			$('#Form_BatchActionsForm').register('admin/batchactions/publish', function(ids) {
				var confirmed = confirm(
					"You have " + ids.length + " pages selected.\n\n"
					+ "Do your really want to publish?"
				);
				return (confirmed) ? ids : false;
			});
			
			/**
			 * Unpublish selected pages action
			 */
			$('#Form_BatchActionsForm').register('admin/batchactions/unpublish', function(ids) {
				var confirmed = confirm(
					"You have " + ids.length + " pages selected.\n\n"
					+ "Do your really want to unpublish?"
				);
				return (confirmed) ? ids : false;
			});
			
			/**
			 * Delete selected pages action
			 */
			$('#Form_BatchActionsForm').register('admin/batchactions/delete', function(ids) {
				var confirmed = confirm(
					"You have " + ids.length + " pages selected.\n\n"
					+ "Do your really want to delete?"
				);
				return (confirmed) ? ids : false;
			});
			
			/**
			 * Delete selected pages from live action 
			 */
			$('#Form_BatchActionsForm').register('admin/batchactions/deletefromlive', function(ids) {
				var confirmed = confirm(
					"You have " + ids.length + " pages selected.\n\n"
					+ "Do your really want to delete these pages from live?"
				);
				return (confirmed) ? ids : false;
			});
		});
	});
	
})(jQuery);
