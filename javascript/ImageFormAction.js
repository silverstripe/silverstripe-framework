Behaviour.register({
	'input.rollover' : {
		initialize: function() {
			var srcParts = this.src.match( /(.*)\.([a-zA-Z]+)$/ );
			var fileName = srcParts[1];
			var extension = srcParts[2];
			
			this.overSrc = fileName + '_over.' + extension;
			this.outSrc = this.src;
		},
		
		onmouseover: function() {
			this.src = this.overSrc;
		},
		
		onmouseout: function() {
			this.src = this.outSrc;
		}
	}
});