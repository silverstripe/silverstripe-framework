/**
 * File: Tooltip.js
 */
(function($) {
		/**
		 * Class: ss-tooltip.
		 * Setup jquery ui tooltips
		 */
		$('.ss-tooltip').entwine({
			onmatch : function() {
				$('.ss-tooltip').tooltip();
			}
		});
}(jQuery));
