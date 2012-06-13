(function($){
	$.entwine('ss', function($){
		$('.ss-toggle').entwine({
			onmatch: function() {
				var self = $(this);
				var opts = { collapsible: true };
				var tab  = self.parents(".ss-tabset");

				if(self.hasClass("ss-toggle-start-closed")) {
					opts.active = false;
				}

				if(tab.length) {
					tab.bind("tabsshow", function() {
						self.accordion("resize");
					});
				}

				this.accordion(opts);
			},
			onunmatch: function() {
				this._super();
			}
		});
	});
})(jQuery);
