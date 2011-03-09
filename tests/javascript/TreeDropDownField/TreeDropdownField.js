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
				// load fixture (check one child node, one root node)
				$('body').append(
					'<div id="testfield" class="TreeDropdownField multiple" href="/myurl" data-title="Root Node 2,Root Node 3">' +
					'<input type="hidden" name="testfield" value="4,5" />' +
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
					expect(f.entwine('ss').getValue()).toEqual(['4','5']);
				});

				it('it sets the selected values on the input field', function() {
					var f = $('#testfield'), panel = f.entwine('ss').getPanel();
					loadTree(f);
					f.entwine('ss').openPanel();
					
					// TODO loaded.jstree event works with timeouts, so we have to wait before inspection
					waits(200);
					runs(function() {
						panel.find('li[data-id=6] a').click();
						// '4' and '5' were preselected
						expect(f.entwine('ss').getValue()).toEqual(['4','5','6']);

						// Selecting an checked node will remove it from selection
						panel.find('li[data-id=4] a').click();
						expect(f.entwine('ss').getValue()).toEqual(['5','6']);
					});
					
				});
				
				it('it sets the selected titles', function() {
					var f = $('#testfield'), panel = f.entwine('ss').getPanel();
					loadTree(f);
					
					// TODO loaded.jstree event works with timeouts, so we have to wait before inspection
					waits(200);
					runs(function() {
						panel.find('li[data-id=6] a').click();
						expect(f.entwine('ss').getTitle()).toEqual('Root node 2, Root node 3, Root node 4');
					});
				});
				
			});
		});
	});
}(jQuery));