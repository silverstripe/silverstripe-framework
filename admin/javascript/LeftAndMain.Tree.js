/**
 * File: LeftAndMain.Tree.js
 */

(function($) {

	$.entwine('ss.tree', function($){
	
		$('.cms-tree').entwine({
			
			Hints: null,

			onadd: function(){
				this._super();

				// Don't reapply (expensive) tree behaviour if already present
				if($.isNumeric(this.data('jstree_instance_id'))) return;
				
				var hints = this.attr('data-hints');
				if(hints) this.setHints($.parseJSON(hints));
				
				/**
				 * @todo Icon and page type hover support
				 * @todo Sorting of sub nodes (originally placed in context menu)
				 * @todo Refresh after language <select> change (with Translatable enabled)
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
				 * @todo When new edit form is loaded, automatically: Select matching node, set correct parent,
				 *  update icon and title
				 */
				var self = this;
					this
						.jstree(this.getTreeConfig())
						.bind('loaded.jstree', function(e, data) {
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
							var movedNode = data.rslt.o, newParentNode = data.rslt.np, oldParentNode = data.inst._get_parent(movedNode);
							var siblingIDs = $.map($(movedNode).siblings().andSelf(), function(el) {
								return $(el).data('id');
							});

							$.ajax({
								'url': self.data('urlSavetreenode'),
								'data': {
									ID: $(movedNode).data('id'), 
									ParentID: $(newParentNode).data('id') || 0,
									SiblingIDs: siblingIDs
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
						})
			},
			onremove: function(){
				this.jstree('destroy');
				this._super();
			},

			'from .cms-container': {
				onafterstatechange: function(e){
					this.updateFromEditForm(e.origData);
				},

				onaftersubmitform: function(e){
					this.updateFromEditForm(e.origData);
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
			 * Assumes to be triggered by a form element with the following input fields:
			 * ID, ParentID, TreeTitle (or Title), ClassName.
			 * 
			 * @todo Serverside node refresh, see http://open.silverstripe.org/ticket/7450
			 */
			updateFromEditForm: function(origData) {
				var self = this, 
					form = $('.cms-edit-form').get(0),
					id = form ? $(form.ID).val() : null,
					urlEditPage = this.data('urlEditpage');

				// check if a form with a valid ID exists
				if(id) {
					var parentID = $(form.ParentID).val(), 
						parentNode = this.find('li[data-id='+parentID+']');
						node = this.find('li[data-id='+id+']'),
						title = $((form.TreeTitle) ? form.TreeTitle : form.Title).val(),
						className = $(form.ClassName).val();

					// set title (either from TreeTitle or from Title fields)
					// Treetitle has special HTML formatting to denote the status changes.
					// only update immediate text element, we don't want to update all the nested ones
					if(title) node.find('.text:first').html(title);

					// Collect flag classes and also apply to parent
					var statusFlags = [];
					node.children('a').find('.badge').each(function() {
						statusFlags = statusFlags.concat($(this).attr('class').replace('badge', '').split(' '));
					});
					// TODO Doesn't remove classes, gets too complex: Best handled through complete serverside replacement
					node.addClass(statusFlags.join(' ')); 

					// check if node exists, might have been created instead
					if(!node.length && urlEditPage) {
						this.jstree(
							'create_node', 
							parentNode, 
							'inside', 
							{
								data: '', 
								attr: {
									'data-class': className, 
									'class': 'class-' + className, 
									'data-id': id
								}
							},
							function() {
								var newNode = self.find('li[data-id='+id+']');
								// TODO Fix replacement of jstree-icon inside <a> tag
								newNode.find('a:first').html(title).attr('href', ss.i18n.sprintf(
									urlEditPage, id
								));
								self.jstree('deselect_all');
								self.jstree('select_node', newNode);
							}
						);
					}

					if(node.length) {
						// set correct parent (only if it has changed)
						if(parentID && parentID != node.parents('li:first').data('id')) {
							this.jstree('move_node', node, parentNode.length ? parentNode : -1, 'last');
						}

						// Only single selection is supported on initial load
						this.jstree('deselect_all');
						this.jstree('select_node', node);
					}
				} else {
					// If no ID exists in a form view, we're displaying the tree on its own,
					// hence to page should show as active
					this.jstree('deselect_all');

					if(typeof origData != 'undefined') {
						var node = this.find('li[data-id='+origData.ID+']');
						if(node && node.data('id') !== 0) this.jstree('delete_node', node);
					}
				}

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
