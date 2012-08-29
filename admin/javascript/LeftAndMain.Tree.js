/**
 * File: LeftAndMain.Tree.js
 */

(function($) {

	$.entwine('ss.tree', function($){
	
		$('.cms-tree').entwine({
			
			Hints: null,

			IsUpdatingTree: false,

			IsLoaded: false,

			onadd: function(){
				this._super();

				// Don't reapply (expensive) tree behaviour if already present
				if($.isNumeric(this.data('jstree_instance_id'))) return;
				
				var hints = this.attr('data-hints');
				if(hints) this.setHints($.parseJSON(hints));
				
				/**
				 * @todo Icon and page type hover support
				 * @todo Sorting of sub nodes (originally placed in context menu)
				 * @todo Automatic load of full subtree via ajax on node checkbox selection (minNodeCount = 0)
				 *  to avoid doing partial selection with "hidden nodes" (unloaded markup)
				 * @todo Disallow drag'n'drop when node has "noChildren" set (see siteTreeHints)
				 * @todo Disallow moving of pages marked as deleted 
				 *  most likely by server response codes rather than clientside
				 * @todo "defaultChild" when creating a page (sitetreeHints)
				 * @todo Duplicate page (originally located in context menu)
				 * @todo Update tree node title information and modified state after reordering (response is a JSON array)
				 * 
				 * Tasks most likely not required after moving to a standalone tree:
				 * 
				 * @todo Context menu - to be replaced by a bezel UI
				 * @todo Refresh form for selected tree node if affected by reordering (new parent relationship)
				 * @todo Cancel current form load via ajax when new load is requested (synchronous loading)
				 */
				var self = this;
					this
						.jstree(this.getTreeConfig())
						.bind('loaded.jstree', function(e, data) {
							self.setIsLoaded(true);
							self.updateFromEditForm();
							self.css('visibility', 'visible');
							// Add ajax settings after init period to avoid unnecessary initial ajax load
							// of existing tree in DOM - see load_node_html()
							data.inst._set_settings({'html_data': {'ajax': {
								'url': self.data('urlTree'),
								'data': function(node) {
									var params = self.data('searchparams') || [];
									// Avoid duplication of parameters
									params = $.grep(params, function(n, i) {return (n.name != 'ID' && n.name != 'value');});
									params.push({name: 'ID', value: $(node).data("id") ? $(node).data("id") : 0});
									params.push({name: 'ajax', value: 1});
									return params;
								}
							}}});
							
							// Only show checkboxes with .multiple class
							data.inst.hide_checkboxes();
						})
						.bind('before.jstree', function(e, data) {
							if(data.func == 'start_drag') {
								// Don't allow drag'n'drop if multi-select is enabled'
								if(!self.hasClass('draggable') || self.hasClass('multiselect')) {
									e.stopImmediatePropagation();
									return false;
								}
							}
							
							if($.inArray(data.func, ['check_node', 'uncheck_node'])) {
								//Don't allow check and uncheck if parent is disabled
								var node = $(data.args[0]).parents('li:first');
								if(node.hasClass('disabled')) {
									e.stopImmediatePropagation();
									return false;
								}
							}
						})
						.bind('move_node.jstree', function(e, data) {
							if(self.getIsUpdatingTree()) return;

							var movedNode = data.rslt.o, newParentNode = data.rslt.np, oldParentNode = data.inst._get_parent(movedNode), newParentID = $(newParentNode).data('id') || 0, nodeID = $(movedNode).data('id');
							var siblingIDs = $.map($(movedNode).siblings().andSelf(), function(el) {
								return $(el).data('id');
							});

							$.ajax({
								'url': self.data('urlSavetreenode'),
								'data': {
									ID: nodeID, 
									ParentID: newParentID,
									SiblingIDs: siblingIDs
								},
								success: function() {
									$('.cms-edit-form :input[name=ParentID]').val(newParentID);
									self.updateNodesFromServer([nodeID]);
								},
								statusCode: {
									403: function() {
										$.jstree.rollback(data.rlbk);
									}
								}
							});
						})
						// Make some jstree events delegatable
						.bind('select_node.jstree check_node.jstree uncheck_node.jstree', function(e, data) {
							$(document).triggerHandler(e, data);
						});
			},
			onremove: function(){
				this.jstree('destroy');
				this._super();
			},

			'from .cms-container': {
				onafterstatechange: function(e){
					this.updateFromEditForm();
					// No need to refresh tree nodes, we assume only form submits cause state changes
				}
			},

			'from .cms-container form': {
				onaftersubmitform: function(e){
					var id = $('.cms-edit-form :input[name=ID]').val();
					// TODO Trigger by implementing and inspecting "changed records" metadata 
					// sent by form submission response (as HTTP response headers)
					this.updateNodesFromServer([id]);
				}
			},

			getTreeConfig: function() {
				var self = this;
				return {
					'core': {
						'initially_open': ['record-0'],
						'animation': 0,
						'html_titles': true
					},
					'html_data': {
						// 'ajax' will be set on 'loaded.jstree' event
					},
					'ui': {
						"select_limit" : 1,
						'initially_select': [this.find('.current').attr('id')]
					},
					 "crrm": {
						 'move': {
							// Check if a node is allowed to be moved.
							// Caution: Runs on every drag over a new node
							'check_move': function(data) {
								var movedNode = $(data.o), newParent = $(data.np), 
									isMovedOntoContainer = data.ot.get_container()[0] == data.np[0],
									movedNodeClass = movedNode.getClassname(), 
									newParentClass = newParent.getClassname(),
									// Check allowedChildren of newParent or against root node rules
									hints = self.getHints(),
									disallowedChildren = [],
									hintKey = newParentClass ? newParentClass : 'Root',
									hint = (hints && typeof hints[hintKey] != 'undefined') ? hints[hintKey] : null;

								// Special case for VirtualPage: Check that original page type is an allowed child
								if(hint && movedNode.attr('class').match(/VirtualPage-([^\s]*)/)) movedNodeClass = RegExp.$1;
								
								if(hint) disallowedChildren = (typeof hint.disallowedChildren != 'undefined') ? hint.disallowedChildren : [];
								var isAllowed = (
									// Don't allow moving the root node
									movedNode.data('id') !== 0 
									// Only allow moving node inside the root container, not before/after it
									&& (!isMovedOntoContainer || data.p == 'inside')
									// Children are generally allowed on parent
									&& !newParent.hasClass('nochildren')
									// movedNode is allowed as a child
									&& (!disallowedChildren.length || $.inArray(movedNodeClass, disallowedChildren) == -1)
								);
								
								return isAllowed;
							}
						}
					},
					'dnd': {
						"drop_target" : false,
						"drag_target" : false
					},
					'checkbox': {
						'two_state': true
					},
					'themes': {
						'theme': 'apple',
						'url': $('body').data('frameworkpath') + '/thirdparty/jstree/themes/apple/style.css'
					},
					// Caution: SilverStripe has disabled $.vakata.css.add_sheet() for performance reasons,
					// which means you need to add any CSS manually to framework/admin/scss/_tree.css
					'plugins': [
						'html_data', 'ui', 'dnd', 'crrm', 'themes', 
						'checkbox' // checkboxes are hidden unless .multiple is set
					]
				};
			},
			
			/**
			 * Function:
			 *  search
			 * 
			 * Parameters:
			 *  (Object) data Pass empty data to cancel search
			 *  (Function) callback Success callback
			 */
			search: function(params, callback) {
				if(params) this.data('searchparams', params);
				else this.removeData('searchparams');
				this.jstree('refresh', -1, callback);
			},
			
			/**
			 * Function: getNodeByID
			 * 
			 * Parameters:
			 *  (Int) id 
			 * 
			 * Returns
			 *  DOMElement
			 */
			getNodeByID: function(id) {
				return this.find('*[data-id='+id+']');
			},

			/**
			 * Creates a new node from the given HTML.
			 * Wrapping around jstree API because we want the flexibility to define
			 * the node's <li> ourselves. Places the node in the tree
			 * according to data.ParentID
			 * 
			 * Parameters:
			 *  (String) HTML New node content (<li>)
			 *  (Object) Map of additional data, e.g. ParentID
			 *  (Function) Success callback
			 */
			createNode: function(html, data, callback) {
				var self = this, 
					parentNode = data.ParentID ? self.find('li[data-id='+data.ParentID+']') : false,
					newNode = $(html);
				
				this.jstree(
					'create_node', 
					parentNode.length ? parentNode : -1, 
					'last', 
					'',
					function(node) {
						var origClasses = node.attr('class');
						// Copy attributes
						for(var i=0; i<newNode[0].attributes.length; i++){
							var attr = newNode[0].attributes[i];
							node.attr(attr.name, attr.value);
						}
						node.addClass(origClasses).html(newNode.html());
						callback(node);
					}
				);
			},

			/**
			 * Updates a node's state in the tree,
			 * including all of its HTML, as well as its position.
			 * 
			 * Parameters:
			 *  (DOMElement) Existing node
			 *  (String) HTML New node content (<li>)
			 *  (Object) Map of additional data, e.g. ParentID
			 */
			updateNode: function(node, html, data) {
				var self = this, newNode = $(html), origClasses = node.attr('class');

				var nextNode = data.NextID ? this.find('li[data-id='+data.NextID+']') : false;
				var prevNode = data.PrevID ? this.find('li[data-id='+data.PrevID+']') : false;
				var parentNode = data.ParentID ? this.find('li[data-id='+data.ParentID+']') : false;

				// Copy attributes. We can't replace the node completely
				// without removing or detaching its children nodes.
				for(var i=0; i<newNode[0].attributes.length; i++){
					var attr = newNode[0].attributes[i];
					node.attr(attr.name, attr.value);
				}

				// Replace inner content
				var origChildren = node.children('ul').detach();
				node.addClass(origClasses).html(newNode.html()).append(origChildren);

				if (nextNode && nextNode.length) {
					this.jstree('move_node', node, nextNode, 'before');
				}
				else if (prevNode && prevNode.length) {
					this.jstree('move_node', node, prevNode, 'after');
				}
				else {
					this.jstree('move_node', node, parentNode.length ? parentNode : -1);
				}
			},
			
			/**
			 * Sets the current state based on the form the tree is managing.
			 */
			updateFromEditForm: function() {
				var node, id = $('.cms-edit-form :input[name=ID]').val();
				if(id) {
					node = this.getNodeByID(id);
					if(node.length) {
						this.jstree('deselect_all');
						this.jstree('select_node', node);
					} else {
						// If form is showing an ID that doesn't exist in the tree,
						// get it from the server
						this.updateNodesFromServer([id]);
					}
				} else {
					// If no ID exists in a form view, we're displaying the tree on its own,
					// hence to page should show as active
					this.jstree('deselect_all');
				}
			},

			/**
			 * Reloads the view of one or more tree nodes
			 * from the server, ensuring that their state is up to date
			 * (icon, title, hierarchy, badges, etc).
			 * This is easier, more consistent and more extensible 
			 * than trying to correct all aspects via DOM modifications, 
			 * based on the sparse data available in the current edit form.
			 *
			 * Parameters:
			 *  (Array) List of IDs to retrieve
			 */
			updateNodesFromServer: function(ids) {
				if(this.getIsUpdatingTree() || !this.getIsLoaded()) return;

				var self = this, includesNewNode = false;
				this.setIsUpdatingTree(true);

				// TODO 'initially_opened' config doesn't apply here
				self.jstree('open_node', this.getNodeByID(0));
				self.jstree('save_opened');
				self.jstree('save_selected');

				$.ajax({
					url: $.path.addSearchParams(this.data('urlUpdatetreenodes'), 'ids=' + ids.join(',')),
					dataType: 'json',
					success: function(data, xhr) {
						$.each(data, function(nodeId, nodeData) {
							var node = self.getNodeByID(nodeId);

							// If no node data is given, assume the node has been removed
							if(!nodeData) {
								self.jstree('delete_node', node);
								return;
							}

							var correctStateFn = function(node) {
								self.jstree('deselect_all');
								self.jstree('select_node', node);
								// Similar to jstree's correct_state, but doesn't remove children
								var hasChildren = (node.children('ul').length > 0);
								node.toggleClass('jstree-leaf', !hasChildren);
								if(!hasChildren) node.removeClass('jstree-closed jstree-open');
							};

							// Check if node exists, create if necessary
							if(node.length) {
								self.updateNode(node, nodeData.html, nodeData);
								setTimeout(function() {
									correctStateFn(node)	;
								}, 500);
							} else {
								includesNewNode = true;
								self.createNode(nodeData.html, nodeData, function(newNode) {
									correctStateFn(newNode);
								});
							}
						});

						if(!includesNewNode) {
							self.jstree('deselect_all');
							self.jstree('reselect');
							self.jstree('reopen');
						}
					},
					complete: function() {
						self.setIsUpdatingTree(false);
					}
				});				
			}

		});
		
		$('.cms-tree.multiple').entwine({
			onmatch: function() {
				this._super();
				this.jstree('show_checkboxes');
			},
			onunmatch: function() {
				this._super();
				this.jstree('uncheck_all');
				this.jstree('hide_checkboxes');
			},
			/**
			 * Function: getSelectedIDs
			 * 
			 * Returns:
			 * 	(Array)
			 */
			getSelectedIDs: function() {
				return $.map($(this).jstree('get_checked'), function(el, i) {return $(el).data('id');});
			}
		});
		
		$('.cms-tree li').entwine({
			
			/**
			 * Function: setEnabled
			 * 
			 * Parameters:
			 * 	(bool)
			 */
			setEnabled: function(bool) {
				this.toggleClass('disabled', !(bool));
			},
			
			/**
			 * Function: getClassname
			 * 
			 * Returns PHP class for this element. Useful to check business rules like valid drag'n'drop targets.
			 */
			getClassname: function() {
				var matches = this.attr('class').match(/class-([^\s]*)/i);
				return matches ? matches[1] : '';
			},
			
			/**
			 * Function: getID
			 * 
			 * Returns:
			 * 	(Number)
			 */
			getID: function() {
				return this.data('id');
			}
		});
		
		$('.cms-tree-view-modes input.view-mode').entwine({
			onmatch: function() {
				// set active by default
				this.redraw();
				this._super();
			},
			onunmatch: function() {
				this._super();
			},
			onclick: function(e) {
				this.redraw();
			},
			redraw: function(type) {
				if(window.debug) console.log('redraw', this.attr('class'), this.get(0));
				
				$('.cms-tree')
					.toggleClass('draggable', this.val() == 'draggable')
					.toggleClass('multiple', this.val() == 'multiselect');
			}
		});
	});
}(jQuery));
