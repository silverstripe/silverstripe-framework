/**
 * File: ModelAdmin.js
 */

(function($) {
	$.entwine('ss', function($){
	
		/**
		 * Class: .importSpec
		 * 
		 * Toggle import specifications
		 */
		$('.importSpec').entwine({
			onmatch: function() {
				this.hide();
				this.find('a.detailsLink').click(function() {
					$('#' + $(this).attr('href').replace(/.*#/,'')).toggle();
					return false;
				});
				
				this._super();
			}
		});

	});
})(jQuery);