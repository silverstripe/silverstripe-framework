(function($) {
	$(document).ready(function() {
		$("input.rollover").livequery(function(){
			var srcParts = jQuery(this).attr('src').match( /(.*)\.([a-zA-Z]+)$/ );
			var fileName = srcParts[1];
			var extension = srcParts[2];
			this.overSrc = fileName + '_over.' + extension;
			this.outSrc = jQuery(this).attr('src');
		});
    	$("input.rollover").livequery('mouseover', function(){
			jQuery(this).attr('src', this.overSrc);
		});
		
    	$("input.rollover").livequery('mouseout', function(){
			jQuery(this).attr('src', this.outSrc);
		});
	});
})(jQuery);