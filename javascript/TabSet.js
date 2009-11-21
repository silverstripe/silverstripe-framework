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
	
	/**
	 * Adjust height of nested tabset panels contained
	 * in jQuery.layout panels to allow scrolling.
	 */
	var ss_tabset_fixHeight = function(e) {
		console.debug(jQuery('.ss-tabset .tab'));
		jQuery('.ss-tabset .tab').each(function() {
			console.debug(this);
			var $tabPane = jQuery(this);
			var $layoutPane = $tabPane.parents('.ui-layout-pane:first');
			
			// don't apply resizing if tabset is not contained in a layout pane
			if(!$layoutPane) return;
			
			// substract heights of unrelated tab elements
			var $tabSets = $tabPane.parents('.ss-tabset');
			var $tabBars = $tabSets.children('.ui-tabs-nav');
			var tabPaneHeight = $layoutPane.height();
			console.log('total', tabPaneHeight);
			// each tabset has certain padding and borders
			$tabSets.each(function() {
				console.log('tabset',jQuery(this).outerHeight(true) - jQuery(this).innerHeight());
				tabPaneHeight -= jQuery(this).outerHeight(true) - jQuery(this).innerHeight();
			});
			// get all "parent" tab navigation bars to substract their heights
			// from the total panel height
			$tabBars.each(function() {
				console.log('tabbar', jQuery(this).outerHeight(true));
				// substract height of every tab bar from the total panel height
				tabPaneHeight -= jQuery(this).outerHeight(true);
			});
			// Remove any margins from the tab pane
			console.log('tabpane', $tabPane.outerHeight(true) - $tabPane.innerHeight());
			tabPaneHeight -= $tabPane.outerHeight(true) - $tabPane.innerHeight();
			console.log('final', tabPaneHeight);
			$tabPane.height(tabPaneHeight);
			
			// if tab has no nested tabs, set overflow to auto
			if(!$tabPane.find('.tab').length) {
				$tabPane.css('overflow', 'auto');	
			}
		});
	}
	
	ss_tabset_fixHeight();
	
	jQuery(window).bind('resize', ss_tabset_fixHeight);
});