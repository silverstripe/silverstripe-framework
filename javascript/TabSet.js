jQuery(document).ready(function () {
	/**
	 * Replace prefixes for all hashlinks in tabs.
	 * SSViewer rewrites them from "#Root_MyTab" to
	 * e.g. "/admin/#Root_MyTab" which makes them
	 * unusable for jQuery UI.
	 */
	jQuery('.ss-tabset > ul a').each(function() {
		var href = jQuery(this).attr('href').replace(/.*(#.*)/, '$1');
		jQuery(this).attr('href', href);
	})
	
	// Initialize tabset
	jQuery('.ss-tabset').tabs();
	
	// if tab has no nested tabs, set overflow to auto
	jQuery('.ss-tabset .tab').not(':has(.tab)').css('overflow', 'auto');
});