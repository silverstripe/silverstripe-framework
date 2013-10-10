(function($){

	$.entwine('ss', function($) {
		$('.ss-gridfield').entwine({
			/**
			 * @param {Object} Additional options for jQuery.ajax() call
			 * @param {successCallback} callback to call after reloading succeeded.
			 */

			reload: function(ajaxOpts, successCallback) {
				var self = this, form = this.closest('form'), 
					focusedElName = this.find(':input:focus').attr('name'), // Save focused element for restoring after refresh
					data = form.find(':input').serializeArray();

				if(!ajaxOpts) ajaxOpts = {};
				if(!ajaxOpts.data) ajaxOpts.data = [];
				ajaxOpts.data = ajaxOpts.data.concat(data);


				// Include any GET parameters from the current URL, as the view state might depend on it.
				// For example, a list prefiltered through external search criteria might be passed to GridField.
				if(window.location.search) {
					ajaxOpts.data = window.location.search.replace(/^\?/, '') + '&' + $.param(ajaxOpts.data);
				}

				form.addClass('loading');

				$.ajax($.extend({}, {
					headers: {"X-Pjax" : 'CurrentField'},
					type: "POST",
					url: this.data('url'),
					dataType: 'html',
					success: function(data) {
						// Replace the grid field with response, not the form.
						// TODO Only replaces all its children, to avoid replacing the current scope
						// of the executing method. Means that it doesn't retrigger the onmatch() on the main container.
						self.empty().append($(data).children());

						// Refocus previously focused element. Useful e.g. for finding+adding
						// multiple relationships via keyboard.
						if(focusedElName) self.find(':input[name="' + focusedElName + '"]').focus();

						var content;
						if(ajaxOpts.data[0].filter=="show"){	
							content = '<span class="non-sortable"></span>';
							self.addClass('show-filter').find('.filter-header').show();														
						}else{
							content = '<button name="showFilter" class="ss-gridfield-button-filter trigger"></button>';
							self.removeClass('show-filter').find('.filter-header').hide();	
						}

						self.find('.sortable-header th:last').html(content);

						form.removeClass('loading');
						if(successCallback) successCallback.apply(this, arguments);
						self.trigger('reload', self);
					},
					error: function(e) {
						alert(ss.i18n._t('GRIDFIELD.ERRORINTRANSACTION'));
						form.removeClass('loading');
					}
				}, ajaxOpts));
			},
			showDetailView: function(url) {
				window.location.href = url;
			},
			getItems: function() {
				return this.find('.ss-gridfield-item');
			},
			/**
			 * @param {String}
			 * @param {Mixed}
			 */
			setState: function(k, v) {
				var state = this.getState();
				state[k] = v;
				this.find(':input[name="' + this.data('name') + '[GridState]"]').val(JSON.stringify(state));
			},
			/**
			 * @return {Object}
			 */
			getState: function() {
				return JSON.parse(this.find(':input[name="' + this.data('name') + '[GridState]"]').val());
			}
		});

		$('.ss-gridfield *').entwine({
			getGridField: function() {
				return this.closest('.ss-gridfield');
			}
		});



		$('.ss-gridfield :button[name=showFilter]').entwine({
			onclick: function(e) {				
				$('.filter-header')
					.show('slow') // animate visibility
					.find(':input:first').focus(); // focus first search field
				this.closest('.ss-gridfield').addClass('show-filter');
				this.parent().html('<span class="non-sortable"></span>');
				e.preventDefault();
			}
		});


		$('.ss-gridfield .ss-gridfield-item').entwine({
			onclick: function(e) {
				if($(e.target).closest('.action').length) {
					this._super(e);
					return false;
				}

				var editLink = this.find('.edit-link');
				if(editLink.length) this.getGridField().showDetailView(editLink.prop('href'));
			},
			onmouseover: function() {
				if(this.find('.edit-link').length) this.css('cursor', 'pointer');
			},
			onmouseout: function() {
				this.css('cursor', 'default');
			}
		});

		$('.ss-gridfield .action').entwine({
			onclick: function(e){
				var filterState='show'; //filterstate should equal current state.
				
				if(this.hasClass('ss-gridfield-button-close') || !(this.closest('.ss-gridfield').hasClass('show-filter'))){
					filterState='hidden';
				}

				this.getGridField().reload({data: [{name: this.attr('name'), value: this.val(), filter: filterState}]});
				e.preventDefault();
			}
		});

		// Covers both tabular delete button, and the button on the detail form 
		$('.ss-gridfield .col-buttons .action.gridfield-button-delete, .cms-edit-form .Actions button.action.action-delete').entwine({
			onclick: function(e){
				if(!confirm(ss.i18n._t('TABLEFIELD.DELETECONFIRMMESSAGE'))) {
					e.preventDefault();
					return false;
				} else {
					this._super(e);
				}
			}
		});
		
		$('.ss-gridfield .action.gridfield-button-print').entwine({
			UUID: null,
			onmatch: function() {
				this._super();
				this.setUUID(new Date().getTime());
			},
			onunmatch: function() {
				this._super();
			},
			onclick: function(e){
				var btn = this.closest(':button'), grid = this.getGridField(),
					form = this.closest('form'), data = form.find(':input.gridstate').serialize();;

				// Add current button
				data += "&" + encodeURIComponent(btn.attr('name')) + '=' + encodeURIComponent(btn.val());

				// Include any GET parameters from the current URL, as the view
				// state might depend on it.
				// For example, a list prefiltered through external search criteria
				// might be passed to GridField.
				if(window.location.search) {
					data = window.location.search.replace(/^\?/, '') + '&' + data;
				}

				// decide whether we should use ? or & to connect the URL
				var connector = grid.data('url').indexOf('?') == -1 ? '?' : '&';

				var url = $.path.makeUrlAbsolute(
					grid.data('url') + connector + data,
					$('base').attr('href')
				);

				var newWindow = window.open(url);

				return false;
			}
		});
		
		$('.ss-gridfield-print-iframe').entwine({
			onmatch: function(){
				this.hide().bind('load', function() {
					this.focus();
					var ifWin = this.contentWindow || this;
					ifWin.print();
				});;
			},
			onunmatch: function() {
				this._super();
			}
		});
		
		/**
		 * Prevents actions from causing an ajax reload of the field.
		 *
		 * Useful e.g. for actions which rely on HTTP response headers being
		 * interpreted natively by the browser, like file download triggers.
		 */
		$('.ss-gridfield .action.no-ajax').entwine({
			onclick: function(e){
				var self = this, btn = this.closest(':button'), grid = this.getGridField(), 
					form = this.closest('form'), data = form.find(':input.gridstate').serialize();

				// Add current button
				data += "&" + encodeURIComponent(btn.attr('name')) + '=' + encodeURIComponent(btn.val());

				// Include any GET parameters from the current URL, as the view
				// state might depend on it. For example, a list pre-filtered
				// through external search criteria might be passed to GridField.
				if(window.location.search) {
					data = window.location.search.replace(/^\?/, '') + '&' + data;
				}

				// decide whether we should use ? or & to connect the URL
				var connector = grid.data('url').indexOf('?') == -1 ? '?' : '&';

				window.location.href = $.path.makeUrlAbsolute(
					grid.data('url') + connector + data,
					$('base').attr('href')
				);

				return false;
			}
		});

		$('.ss-gridfield .action-detail').entwine({
			onclick: function() {
				this.getGridField().showDetailView($(this).prop('href'));
				return false;
			}
		});

		/**
		 * Allows selection of one or more rows in the grid field.
		 * Purely clientside at the moment.
		 */
		$('.ss-gridfield[data-selectable]').entwine({
			/**
			 * @return {jQuery} Collection
			 */
			getSelectedItems: function() {
				return this.find('.ss-gridfield-item.ui-selected');
			},
			/**
			 * @return {Array} Of record IDs
			 */
			getSelectedIDs: function() {
				return $.map(this.getSelectedItems(), function(el) {return $(el).data('id');});
			}
		});
		$('.ss-gridfield[data-selectable] .ss-gridfield-items').entwine({
			onmatch: function() {
				this._super();
				
				// TODO Limit to single selection
				this.selectable();
			},
			onunmatch: function() {
				this._super();
				this.selectable('destroy');
			}
		});
		
		/**
		 * Catch submission event in filter input fields, and submit the correct button
		 * rather than the whole form.
		 */
		$('.ss-gridfield .filter-header :input').entwine({
			onmatch: function() {
				var filterbtn = this.closest('.fieldgroup').find('.ss-gridfield-button-filter'),
					resetbtn = this.closest('.fieldgroup').find('.ss-gridfield-button-reset');
				
				if(this.val()) {
					filterbtn.addClass('filtered');
					resetbtn.addClass('filtered');
				}
				this._super();
			},
			onunmatch: function() {
				this._super();
			},
			onkeydown: function(e) {
				// Skip reset button events, they should trigger default submission
				if(this.closest('.ss-gridfield-button-reset').length) return;

				var filterbtn = this.closest('.fieldgroup').find('.ss-gridfield-button-filter'),
					resetbtn = this.closest('.fieldgroup').find('.ss-gridfield-button-reset');

				if(e.keyCode == '13') {
					var btns = this.closest('.filter-header').find('.ss-gridfield-button-filter');
					var filterState='show'; //filterstate should equal current state.				
					if(this.hasClass('ss-gridfield-button-close')||!(this.closest('.ss-gridfield').hasClass('show-filter'))){
						filterState='hidden';
					}
					
					this.getGridField().reload({data: [{name: btns.attr('name'), value: btns.val(), filter: filterState}]});
					return false;
				}else{
					filterbtn.addClass('hover-alike');
					resetbtn.addClass('hover-alike');
				}
			}
		});

		$(".ss-gridfield .relation-search").entwine({
			onfocusin: function (event) {
				this.autocomplete({
					source: function(request, response){
						var searchField = $(this.element);
						var form = $(this.element).closest("form");
						$.ajax({
							headers: {
								"X-Pjax" : 'Partial'
							},
							type: "GET",
							url: $(searchField).data('searchUrl'),
							data: encodeURIComponent(searchField.attr('name'))+'='+encodeURIComponent(searchField.val()), 
							success: function(data) {
								response( $.map(JSON.parse(data), function( name, id ) {
									return { label: name, value: name, id: id };
								}));
							},
							error: function(e) {
								alert(ss.i18n._t('GRIDFIELD.ERRORINTRANSACTION', 'An error occured while fetching data from the server\n Please try again later.'));
							}
						});
					},
					select: function(event, ui) {
						$(this).closest(".ss-gridfield").find("#action_gridfield_relationfind").replaceWith(
							'<input type="hidden" name="relationID" value="'+ui.item.id+'" id="relationID"/>'
						);
						var addbutton = $(this).closest(".ss-gridfield").find("#action_gridfield_relationadd");
						if(addbutton.data('button')){
							addbutton.button('enable');
						}else{
							addbutton.removeAttr('disabled');
						}
					}
				});
			}
		});

		$(".ss-gridfield .pagination-page-number input").entwine({
			onkeydown: function(event) {
				if(event.keyCode == 13) {
					var newpage = parseInt($(this).val(), 10);

					var gridfield = $(this).getGridField();
					gridfield.setState('GridFieldPaginator', {currentPage: newpage});
					gridfield.reload();

					return false;
				}
			}
		});
	});
}(jQuery));
