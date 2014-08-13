(function($){
	$.entwine('ss', function($){
		/**
		 * Lightweight wrapper around jQuery UI tabs for generic tab set-up
		 */
		$('.ss-tabset').entwine({
			IgnoreTabState: false,

			onadd: function() {
				// Can't name redraw() as it clashes with other CMS entwine classes
				this.redrawTabs();
				this._super();
			},
			onremove: function() {
				if(this.data('tabs')) this.tabs('destroy');
				this._super();
			},
			redrawTabs: function() {
				this.rewriteHashlinks();
				this.tabs();
			},
		
			/**
			 * Ensure hash links are prefixed with the current page URL,
			 * otherwise jQuery interprets them as being external.
			 */
			rewriteHashlinks: function() {
				$(this).find('ul a').each(function() {
					if (!$(this).attr('href')) return;
					
					var matches = $(this).attr('href').match(/#.*/);
					if(!matches) return;
					$(this).attr('href', document.location.href.replace(/#.*/, '') + matches[0]);
				});
			}
		});
	});
})(jQuery);
