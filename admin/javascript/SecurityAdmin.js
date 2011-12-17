/**
 * File: SecurityAdmin.js
 */
(function($) {
	
	var refreshAfterImport = function(e) {
		// Check for a message <div>, an indication that the form has been submitted.
		var existingFormMessage = $($(this).contents()).find('.message');
		if(existingFormMessage && existingFormMessage.html()) {
			// Refresh member listing
			var memberTableField = $(window.parent.document).find('#Form_EditForm_Members').get(0);
			if(memberTableField) memberTableField.refresh();
			
			// Refresh tree
			var tree = $(window.parent.document).find('.cms-tree').get(0);
			if(tree) tree.reload();
		}
	};
	
	/**
	 * Refresh the member listing every time the import iframe is loaded,
	 * which is most likely a form submission.
	 */
	$(window).bind('load', function(e) {
		$('#MemberImportFormIframe,#GroupImportFormIframe').entwine({
			onmatch: function() {
				this._super();
				
				// TODO entwine can't seem to bind to iframe load events
				$(this).bind('load', refreshAfterImport);
			}
		});
	});
	
	/**
	 * Delete selected folders through "batch actions" tab.
	 * Not sure the $(document).ready is necessary below
	 */
	$(document).ready(function() {
		$('#Form_BatchActionsForm').entwine('ss').register(
			// TODO Hardcoding of base URL
			'admin/security/batchactions/delete', 
			function(ids) {
				var confirmed = confirm(
					ss.i18n.sprintf(
						ss.i18n._t('SecurityAdmin.BATCHACTIONSDELETECONFIRM'),
						ids.length
					)
				);
				return (confirmed) ? ids : false;
			}
		);
	});
	
	$.entwine('ss', function($){
		
		$('.security-tree').entwine({
			
			onmatch: function() {
				this._super();
				
				//console.log(this.jstree);

				var self = this;
				this
				.jstree({
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
								return true;
							}
						}
					},
					'dnd': {
						"drop_target" : false,
						"drag_target" : false
					},
					'themes': {
						'theme': 'apple',
						'url': 'sapphire/thirdparty/jstree/themes/apple/style.css'
					},
					// Caution: SilverStripe has disabled $.vakata.css.add_sheet() for performance reasons,
					// which means you need to add any CSS manually to sapphire/admin/scss/_tree.css
					'plugins': [
						'html_data', 'ui', 'dnd', 'crrm', 'themes'
					]
				})
				.bind('loaded.jstree', function(e, data) {
					self.css('visibility', 'visible');
				})
				.bind('move_node.jstree', function(e, data) {
					var movedNode = data.rslt.o, newParentNode = data.rslt.np, oldParentNode = data.inst._get_parent(movedNode);
					var siblingIDs = $.map($(movedNode).siblings().andSelf(), function(el) {
						return $(el).data('id');
					});

					$.ajax({
						'url': self.data('url-savetreenode'),
						'data': {
							ID: $(movedNode).data('id'), 
							ParentID: $(newParentNode).data('id') || 0,
							SiblingIDs: siblingIDs
						}
					});
				});
			}
			
		}),
		
		
		/**
		 * Class: .cms-edit-form .Actions #Form_EditForm_action_addmember
		 */
		$('.cms-edit-form .Actions #Form_EditForm_action_addmember').entwine({
			// Function: onclick
			onclick: function(e) {
				// CAUTION: Assumes that a MemberTableField-instance is present as an editing form
				var t = $('#Form_EditForm_Members');
				t[0].openPopup(
					null,
					$('base').attr('href') + t.find('a.addlink').attr('href'),
					t.find('table')[0]
				);
				return false;
			}
		});
	});
	
}(jQuery));