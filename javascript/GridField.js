(function($){

	$('fieldset.ss-gridfield').entwine({
		/**
		 * @param {Object} Additional options for jQuery.ajax() call
		 */
		reload: function(ajaxOpts, successCallback) {
			var self = this, form = this.closest('form'), data = form.find(':input').serializeArray();
			if(!ajaxOpts) ajaxOpts = {};
			if(!ajaxOpts.data) ajaxOpts.data = [];
			ajaxOpts.data = ajaxOpts.data.concat(data);

			form.addClass('loading');

			$.ajax($.extend({}, {
				headers: {"X-Get-Fragment" : 'CurrentField'},
				type: "POST",
				url: this.data('url'),
				dataType: 'html',
				success: function(data) {
					// Replace the grid field with response, not the form.
					// TODO Only replaces all its children, to avoid replacing the current scope
					// of the executing method. Means that it doesn't retrigger the onmatch() on the main container.
					self.empty().append($(data).children());

					form.removeClass('loading');
					if(successCallback) successCallback.apply(this, arguments);
				},
				error: function(e) {
					alert(ss.i18n._t('GRIDFIELD.ERRORINTRANSACTION', 'An error occured while fetching data from the server\n Please try again later.'));
					form.removeClass('loading');
				}
			}, ajaxOpts));
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

	$('fieldset.ss-gridfield *').entwine({
		getGridField: function() {
			return this.parents('fieldset.ss-gridfield:first');
		}
	});
		
	$('fieldset.ss-gridfield .action').entwine({
		onclick: function(e){
			this.getGridField().reload({data: [{name: this.attr('name'), value: this.val()}]});
			e.preventDefault();
		}
	});
	
	/*
	 * Upon focusing on a filter <input> element, move "filter" and "reset" buttons and display next to the current <input> element
	 * ToDo ensure filter-button state is maintained after filtering (see resetState param)
	 * ToDo get working in IE 6-7
	 */
	$('fieldset.ss-gridfield input.ss-gridfield-sort').entwine({
		onfocusin: function(e) {
			// Dodgy results in IE <=7 & ignore if only one filter-field
			countfields = $('fieldset.ss-gridfield input.ss-gridfield-sort').length;
			if(($.browser.msie && $.browser.version <= 7) || countfields == 1) {
				return false;
			}
			var eleInput = $(this);

			// Remove existing <div> and <button> elements in-lieu of cloning
			this.getGridField().find('th > div').each(function(i,v) {$(v).remove();});	

			var eleButtonSetFilter = $('#action_filter');
			var eleButtonResetFilter = $('#action_reset');
			// Retain current widths to ensure <th>'s don't shift widths
			var eleButtonWidth = eleButtonSetFilter.width();					
			// Check <th> doesn't already have an (extra) cloned <button> appended, otherwise clone
			if(eleInput.closest('th').children().length == 1) {
				var newButtonCss = {
					'position':'absolute',
					'top':'-23px',
					'left':'0',
					'border':'#EEE solid 1px',
					'padding':'0',
					'margin-left':'0'
				};	
				// Append a <div> element used purely for CSS positioning - table elements on their own are untrustworthy to style in this manner
				$('<div/>').append(
					eleButtonSetFilter.clone().css(newButtonCss),
					eleButtonResetFilter.clone().css(newButtonCss).css('left',(eleButtonWidth+4)+'px')
				).css({'position':'relative','margin':'0 auto','width':'65%'}).appendTo(eleInput.closest('th'));
			}
		}
	});	

	/**
	 * Allows selection of one or more rows in the grid field.
	 * Purely clientside at the moment.
	 */
	$('fieldset.ss-gridfield[data-selectable]').entwine({
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
	$('fieldset.ss-gridfield[data-selectable] .ss-gridfield-items').entwine({
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

}(jQuery));