/**
 * File: Tooltip.js
 */
(function($) {
		/**
		 * Class: ss-tooltip.
		 * Setup jquery ui tooltips
		 */
		var toggle = 'hide';
		$('.ss-tooltip').entwine({
			onmatch : function(e) {
				// take of the title attribute because the default title popup looks nasty
				$('.ss-tooltip').each(function() {
					var title = $(this).attr('title');
					$(this).data('title', title).removeAttr('title');
				});
			},
			onmousedown : function() {
				this.tooltiptoggle();
			},
			tooltiptoggle : function() {
				if (toggle == 'hide') {
					toggle = 'show';
					var title = $(this).data('title');
					this.attr('title', title);
					this.tooltip({disabled: false});
					this.tooltip({
						show: { effect: "fadeIn", duration: 300, delay: 300 },
						hide: { effect: "fadeOut", duration: 300, delay: 0 },
						position: { my: "left+15 top", at: "right top", collision: "flipfit"}
					});
					this.tooltip('open');
				} else {
					toggle = 'hide';
					$(this).data('title', title).removeAttr('title');
					this.tooltip('close');
				}
			},
			onmouseleave : function() {
				toggle = 'hide';
				this.tooltip('close');
				this.tooltip({disabled: true});
			},
			onmouseenter : function() {
				// handle touch screen devices
				if (window.Touch) {
					this.tooltiptoggle();
				}
			}
		});
}(jQuery));
