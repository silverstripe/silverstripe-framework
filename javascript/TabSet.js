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
				// Can't name redraw() as it clashes with other CMS entwine classes
				this.redrawTabs();
				this._super();
			},
			
			redrawTabs: function() {
				this.rewriteHashlinks();

				var id = this.attr('id'), cookieId = 'ui-tabs-' + id;

				// Fix for wrong cookie storage of deselected tabs
				if($.cookie && id && $.cookie(cookieId) == -1) $.cookie(cookieId, 0);

				this.tabs({cookie: ($.cookie && id) ? { expires: 30, path: '/', name: cookieId } : false});
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