/**
 * Fits an element's height to its parent by substracting
 * all (visible) siblings heights from the element.
 * Caution: This will set overflow: hidden on the parent
 *
 * Copyright 2009 Ingo Schommer, SilverStripe Ltd.
 * Licensed under MIT License: http://www.opensource.org/licenses/mit-license.php
 *
 * @todo Implement selectors to ignore certain elements
 *
 * @author Ingo Schommer, SilverStripe Ltd.
 * @version 0.1
 */
jQuery.fn.extend({
	fitHeightToParent: function() {
		return jQuery(this).each(function() {
			var $this = jQuery(this);

			var boxmodel = ['marginTop','marginBottom','paddingTop','paddingBottom','borderBottomWidth','borderTopWidth'];
		
			// don't bother if element or parent arent visible,
			// we won't get height readings
			if($this.is(':visible') && $this.parent().is(':visible')) {
				
				// we set overflow = hidden so that large children don't muck things up in IE6 box model
				var origParentOverflow = $this.parent().css('overflow');
				$this.parent().css('overflow', 'hidden');
				
				// get height from parent without any margins as a starting point,
				// and reduce any top/bottom paddings
				var height = $this.parent().innerHeight() 
					- parseFloat($this.parent().css('paddingTop'))
					- parseFloat($this.parent().css('paddingBottom'));

				// substract height of any siblings of the current element
				// including their margins/paddings/borders
				$this.siblings(':visible').filter(function() {
					// remove all absolutely positioned elements
					return (jQuery(this).css('position') != 'absolute');
				}).each(function() {
					height -= jQuery(this).outerHeight(true);
				});

				// remove margins/paddings/borders on inner element
				jQuery.each(boxmodel, function(i, name) {
					height -= parseFloat($this.css(name)) || 0;
				});

				// set new height
				$this.height(height);
				
				// Reset overflow
				$this.parent().css('overflow', origParentOverflow);
			}
		});
	}
});