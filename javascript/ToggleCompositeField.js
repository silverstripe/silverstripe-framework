(function($){
	$.entwine('ss', function($){
		$('.ss-toggle').entwine({
			onadd: function() {
				opts = {collapsible: true};
				if (this.hasClass("ss-toggle-start-closed")) opts.active = false;

				this.accordion({ collapsible: true });

				this._super();
			},
			onremove: function() {
				this.accordion('destroy');
			},

			getTabSet: function() {
				return this.closest(".ss-tabset");
			},

			fromTabSet: {
				ontabsshow: function() {
					this.accordion("resize");
				}
			}
		});
	});
})(jQuery);
