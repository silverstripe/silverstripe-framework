(function($){	
	
	/**
	 * Add focusin and focusout support to bind and live for browers other than IE. Designed to be usable in a delegated fashion (like $.live)
	 * Copyright (c) 2007 Jörn Zaefferer
	 */
	if ($.support.focusinBubbles === undefined)  {
		$.support.focusinBubbles = !!($.browser.msie);
	}

	if (!$.support.focusinBubbles && !$.event.special.focusin) {
		// Emulate focusin and focusout by binding focus and blur in capturing mode
		$.each({focus: 'focusin', blur: 'focusout'}, function(original, fix){
			$.event.special[fix] = {
				setup: function(){
					if (!this.addEventListener) return false;
					this.addEventListener(original, $.event.special[fix].handler, true);
				},
				teardown: function(){
					if (!this.removeEventListener) return false;
					this.removeEventListener(original, $.event.special[fix].handler, true);
				},
				handler: function(e){
					arguments[0] = $.event.fix(e);
					arguments[0].type = fix;
					return $.event.handle.apply(this, arguments);
				}
			};
		});
	}
		
	(function(){
		//IE has some trouble with focusout with select and keyboard navigation
		var activeFocus = null;
	
		$(document)
			.bind('focusin', function(e){
				var target = e.realTarget || e.target;
				if (activeFocus && activeFocus !== target) {
					e.type = 'focusout';
					$(activeFocus).trigger(e);
					e.type = 'focusin';
					e.target = target;
				}
				activeFocus = target;
			})
			.bind('focusout', function(e){
				activeFocus = null;
			});
	})();
	
})(jQuery);