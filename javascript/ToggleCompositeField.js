(function($){
	$.entwine('ss', function($){
		$('.ss-toggle').entwine({
			onadd: function() {
				this.accordion({
					collapsible: true,
					active: (this.hasClass("ss-toggle-start-closed")) ? false : 0
				});

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
