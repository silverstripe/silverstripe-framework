/**
 * File: MemberImportForm.js
 */
(function($) {
	$.entwine('ss', function($){
		/**
		 * Class: .import-form .advanced
		 */
		$('.import-form .advanced').entwine({
			onmatch: function() {
				this._super();
				
				this.hide();
			},
			onunmatch: function() {
				this._super();
			}
		});
		
		/**
		 * Class: .import-form a.toggle-advanced
		 */
		$('.import-form a.toggle-advanced').entwine({
			
			/**
			 * Function: onclick
			 */
			onclick: function(e) {
				this.parents('form:eq(0)').find('.advanced').toggle();
				return false;
			}
		});
	});
	
}(jQuery));
