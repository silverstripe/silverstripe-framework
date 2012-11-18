/**
 * File: Tooltip.js
 */
(function($) {
		/**
		 * Class: ss-tooltip.
		 * Setup jquery ui tooltips
		 */
		$('.ss-tooltip').entwine({
			onmousedown : function() {
				this.tooltip({disabled: false});
				this.tooltip({
					show: { effect: "fadeIn", duration: 300, delay: 300 },
					hide: { effect: "fadeOut", duration: 300, delay: 0 },
					position: { my: "left+15 top", at: "right top", collision: "flipfit"}
				});
				this.tooltip('open');
			},
			onmouseup : function() {
				this.tooltip('close');
				this.tooltip({disabled: true});
			}
		});
}(jQuery));
