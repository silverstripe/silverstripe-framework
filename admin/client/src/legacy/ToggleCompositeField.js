import $ from 'jQuery';

// entwine also required, but can't be included more than once without error
require('../../../thirdparty/jquery-ui/jquery-ui.js');

// TODO Enable once https://github.com/webpack/extract-text-webpack-plugin/issues/179 is resolved. Included in bundle.scss for now.
// require('../../../thirdparty/jquery-ui-themes/smoothness/jquery-ui.css');

$.entwine('ss', function($){
	$('.ss-toggle').entwine({
		onadd: function() {
			this._super();

			this.accordion({
				heightStyle: "content",
				collapsible: true,
				active: (this.hasClass("ss-toggle-start-closed")) ? false : 0
			});
		},
		onremove: function() {
			if (this.data('accordion')) this.accordion('destroy');
			this._super();
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
