(function($){
	$.entwine('ss', function($){
		/**
		 * Lightweight wrapper around jQuery UI tabs.
		 */
		$('.ss-tabset').entwine({
			onadd: function() {
				// Can't name redraw() as it clashes with other CMS entwine classes
				this.redrawTabs();
				this._super();
			},
			onremove: function() {
				this.tabs('destroy');
				this._super();
			},
			redrawTabs: function() {
				this.rewriteHashlinks();
				this.tabs();
			},
		
			/**
			 * Replace prefixes for all hashlinks in tabs.
			 * SSViewer rewrites them from "#Root_MyTab" to
			 * e.g. "/admin/#Root_MyTab" which makes them
			 * unusable for jQuery UI.
			 */
			rewriteHashlinks: function() {
				$(this).find('ul a').each(function() {
					var href = $(this).attr('href');
					if(href) $(this).attr('href', href.replace(/.*(#.*)/, '$1'));
				});
			}
		});
	});
})(jQuery);
