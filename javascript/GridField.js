(function($){

	$('.ss-gridfield').entwine({
		/**
		 * @param {Object} Additional options for jQuery.ajax() call
		 * @param {successCallback} callback to call after reloading succeeded.
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
					self.trigger('reload', self);
				},
				error: function(e) {
					alert(ss.i18n._t('GRIDFIELD.ERRORINTRANSACTION'));
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

	$('.ss-gridfield *').entwine({
		getGridField: function() {
			return this.closest('.ss-gridfield');
		}
	});
		
	$('.ss-gridfield .action').entwine({
		onclick: function(e){
			this.getGridField().reload({data: [{name: this.attr('name'), value: this.val()}]});
			e.preventDefault();
		}
	});

	$('.ss-gridfield .action-deleterecord').entwine({
		onclick: function(e){
			if(!confirm(ss.i18n._t('TABLEFIELD.DELETECONFIRMMESSAGE'))) return false;
			else this._super(e);
		}
	});

	$('fieldset.ss-gridfield .new-link').entwine({
		onclick: function(e) {
			$(this).trigger('opennewview', $(this).prop('href'));
			return false;
		}
	});

	$('fieldset.ss-gridfield .edit-link').entwine({
		onclick: function(e) {
			$(this).trigger('openeditview', $(this).prop('href'));
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

}(jQuery));
