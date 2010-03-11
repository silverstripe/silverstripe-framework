(function($) {	
	$(document).ready(function() {
    $("input.rollover").live('mouseover', function(){
			if(!this.overSrc) {
				var srcParts = $(this).attr('src').match( /(.*)\.([a-zA-Z]+)$/ );
				var fileName = srcParts[1];
				var extension = srcParts[2];
				this.overSrc = fileName + '_over.' + extension;
				this.outSrc = $(this).attr('src');
			}
			$(this).attr('src', this.overSrc);
		});
		
    $("input.rollover").live('mouseout', function(){
			$(this).attr('src', this.outSrc);
		});
	});
})(jQuery);