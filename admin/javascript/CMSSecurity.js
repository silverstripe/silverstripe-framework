jQuery.noConflict();

/**
 * File: LeftAndMain.js
 */
(function($) {
	// setup jquery.entwine
	$.entwine.warningLevel = $.entwine.WARN_LEVEL_BESTPRACTISE;
	$.entwine('ss', function($) {
		// Make all buttons "hoverable" with jQuery theming.
		$('.cms input[type="submit"], .cms button, .cms input[type="reset"], .cms .ss-ui-button').entwine({
			onadd: function() {
				this.addClass('ss-ui-button');
				if(!this.data('button')) this.button();
				this._super();
			},
			onremove: function() {
				if(this.data('button')) this.button('destroy');
				this._super();
			}
		});
	});
}(jQuery));
