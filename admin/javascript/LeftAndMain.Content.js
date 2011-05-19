(function($) {

	$.entwine('ss', function($){

		$('.LeftAndMain .cms-content').entwine({
			
			redraw: function() {
			},
			
			cleanup: function() {
				this.empty();
			},

			loadPanel: function(url, callback, ajaxOptions) {
				var self = this;

				this.trigger('load', {url: url, args: arguments});

				this.cleanup();

				// TODO Add browser history support
				return $.ajax($.extend({
					url: url, 
					complete: function(xmlhttp, status) {
						self.loadPanel_onSuccess(xmlhttp.responseText, status, xmlhttp);
						self.removeClass('loading');

						if(callback) callback.apply(self, arguments);
					}, 
					dataType: 'html'
				}, ajaxOptions));
			},

			loadPanel_onSuccess: function(html, status, xmlhttp) {
				this.html(html);
				this.redraw();
			}
		});
	});
})(jQuery);