(function($) {
	$.entwine('ss', function($){

		// Any TreeDowndownField needs to refresh it's contents after a form submission,
		// because the tree on the backend might have changed
		$('.TreeDropdownField').entwine({
			'from .cms-container form': {
				onaftersubmitform: function(e){
					this.find('.tree-holder').empty();
					this._super();
				}
			}
		});

	});

})(jQuery);
