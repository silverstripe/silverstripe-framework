(function($){
	$.entwine('ss', function($){
		/**
		 * Lightweight wrapper around jQuery UI tabs.
		 * Ensures that anchor links are set properly,
		 * and any nested tabs are scrolled if they have
		 * their height explicitly set. This is important
		 * for forms inside the CMS layout.
		 */
		$('.ss-tabset').entwine({
			onmatch: function() {
				this.rewriteHashlinks();

				// Initialize jQuery UI tabs
				this.tabs({
					cookie: $.cookie ? { expires: 30, path: '/', name: 'ui-tabs-' + this.attr('id') } : false
				});
				
				this._super();
			},
		
			/**
			 * Replace prefixes for all hashlinks in tabs.
			 * SSViewer rewrites them from "#Root_MyTab" to
			 * e.g. "/admin/#Root_MyTab" which makes them
			 * unusable for jQuery UI.
			 */
			rewriteHashlinks: function() {
				$(this).find('ul a').each(function() {
					var href = $(this).attr('href').replace(/.*(#.*)/, '$1');
					if(href) $(this).attr('href', href);
				});
			}
		});
	});
})(jQuery);