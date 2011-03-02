(function($) {
	describe("TreeDropdownField", function() {
		$.entwine.warningLevel = $.entwine.WARN_LEVEL_BESTPRACTISE;		
		$.entwine.synchronous_mode();
		
		// helpers
		var loadTree = function(container, html) {
			if(!html) html = readFixtures('fixtures/tree.html');
			container.entwine('ss').loadTree();
			var request = mostRecentAjaxRequest();
			request.response({
				status: 200,
				contentType: 'text/html',
				responseText: html
			});
			// loaded doesnt trigger automatically in test mode
			container.find('.tree-holder').jstree('loaded');
		}
		
		describe('when field is basic', function() {

			beforeEach(function() {
				// load fixture
				$('body').append(
					'<div id="testfield" class="TreeDropdownField single" href="/myurl" data-title="Selected">' +
					'<input type="hidden" name="testfield" value="1" />' +
					'</div>'
				);
		  });

			afterEach(function() {
				$('#testfield').remove();
			});
			
			it('displays the default title', function() {
				expect($('#testfield').entwine('ss').getTitle()).toEqual('Selected');
			});

			it('opens the tree panel when edit link is clicked', function() {
				var panel = $('#testfield').entwine('ss').getPanel();
				expect(panel).toBeHidden();
				$('#testfield a.toggle-panel-link').click();
				expect(panel).toBeVisible();
			});

			it('loads the tree when panel is first shown', function() {
				var panel = $('#testfield').entwine('ss').getPanel();
				$('#testfield').entwine('ss').openPanel();
				var request = mostRecentAjaxRequest();
				request.response({
					status: 200,
					contentType: 'text/html',
					responseText: readFixtures('fixtures/tree.html')
				});

				expect(panel).toContain('ul');
			});

			describe('when an item is selected', function() {
				it('closes the panel', function() {
					var f = $('#testfield'), panel = f.entwine('ss').getPanel();
					loadTree(f);
					f.entwine('ss').openPanel();
					panel.find('li[data-id=2] a').click();
					expect(panel).toBeHidden();
				});

				it('it sets the selected value on the input field', function() {
					var f = $('#testfield'), panel = f.entwine('ss').getPanel();
					loadTree(f);
					panel.find('li[data-id=2] a').click();
					expect(f.entwine('ss').getValue()).toEqual('2');
				});

				it('it sets the selected title', function() {
					var f = $('#testfield'), panel = f.entwine('ss').getPanel();
					loadTree(f);
					panel.find('li[data-id=2] a').click();
					expect(f.entwine('ss').getTitle()).toEqual('Child node 1');
				});
			});
			
		});

		describe('when field is searchable', function() {
			
			beforeEach(function() {
				// load fixture
				$('body').append(
					'<div id="testfield" class="TreeDropdownField searchable" href="/myurl" data-title="Selected">' +
					'<input type="hidden" name="testfield" value="1" />' +
					'</div>'
				);
		  });

			afterEach(function() {
				$('#testfield').remove();
			});
			
			it('only shows search results', function() {
				var f = $('#testfield'), panel = f.entwine('ss').getPanel();
				f.entwine('ss').search('Child node 1');
				var request = mostRecentAjaxRequest();
				request.response({
					status: 200,
					contentType: 'text/html',
					responseText: readFixtures('fixtures/tree.search.html')
				});
		
				expect(panel.text().match('Child node 1')).toBeTruthy();
				expect(panel.text().match('Root node 2')).not.toBeTruthy();
			});
			
			// TODO Key events: Enter/ESC
		});
		
		describe('when field allows multiple selections', function() {
			beforeEach(function() {
				// load fixture
				$('body').append(
					'<div id="testfield" class="TreeDropdownField multiple" href="/myurl" data-title="Root Node 1,Child Node 1">' +
					'<input type="hidden" name="testfield" value="1,2" />' +
					'</div>'
				);
		  });

			afterEach(function() {
				$('#testfield').remove();
			});
			
			describe('when more than one item is selected', function() {
				it('doesnt close the panel', function() {
					var f = $('#testfield'), panel = f.entwine('ss').getPanel();
					loadTree(f);
					f.entwine('ss').openPanel();
					panel.find('li[data-id=2]').click();
					expect(panel).toBeVisible();
				});
				
				it('selects the tree nodes based on initial values', function() {
					var f = $('#testfield'), panel = f.entwine('ss').getPanel();
					loadTree(f);
					f.entwine('ss').openPanel();
					expect(f.entwine('ss').getValue()).toEqual(['1','2']);
				});

				// it('it sets the selected values on the input field', function() {
				// 	var f = $('#testfield'), panel = f.entwine('ss').getPanel();
				// 	loadTree(f);
				// 	f.entwine('ss').openPanel();
				// 	panel.find('li[data-id=2] a').click();
				// 	panel.find('li[data-id=3] a').click();
				// 	// '1' and '2' were preselected
				// 	expect(f.entwine('ss').getValue()).toEqual(['1','2','3']);
				// 	
				// 	// Selecting an checked node will remove it from selection
				// 	panel.find('li[data-id=2] a').click();
				// 	expect(f.entwine('ss').getValue()).toEqual(['1','3']);
				// });
				// 
				// it('it sets the selected titles', function() {
				// 	var f = $('#testfield'), panel = f.entwine('ss').getPanel();
				// 	loadTree(f);
				// 	panel.find('li[data-id=2] a').click();
				// 	panel.find('li[data-id=3] a').click();
				// 	expect(f.entwine('ss').getTitle()).toEqual('Child node 1, Child node 2');
				// });
			});
		});
		
	});
}(jQuery));