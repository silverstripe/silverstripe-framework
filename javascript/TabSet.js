(function($) {
	$.entwine('ss', function($) {
		/**
		 * Lightweight wrapper around jQuery UI tabs for generic tab set-up
		 */
		$('.ss-tabset').entwine({
			IgnoreTabState: false,

			onadd: function() {
				var hash = window.location.hash;

				// Can't name redraw() as it clashes with other CMS entwine classes
				this.redrawTabs();

				if (hash !== '') {
					this.openTabFromURL(hash);
				}

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
			 * @func openTabFromURL
			 * @param {string} hash
			 * @desc Allows linking to a specific tab.
			 */
			openTabFromURL: function(hash) {
				var $trigger;

				// Make sure the hash relates to a valid tab.
				$.each(this.find('.ui-tabs-anchor'), function() {
					// The hash in in the button's href and there is exactly one tab with that id.
					if (this.href.indexOf(hash) !== -1 && $(hash).length === 1) {
						$trigger = $(this);
						return false; // break the loop
					}
				});

				// If there's no tab, it means the hash is invalid, so do nothing.
				if ($trigger === void 0) {
					return;
				}

				// Switch to the correct tab when AJAX loading completes.
				$(document).ready(function() {
					$trigger.click();
				});
			},

			/**
			 * @func rewriteHashlinks
			 * @desc Ensure hash links are prefixed with the current page URL, otherwise jQuery interprets them as being external.
			 */
			rewriteHashlinks: function() {
				$(this).find('ul a').each(function() {
					if (!$(this).attr('href')) return;

					var matches = $(this).attr('href').match(/#.*/);
					if (!matches) return;
					$(this).attr('href', document.location.href.replace(/#.*/, '') + matches[0]);
				});
			}
		});
	});
})(jQuery);
