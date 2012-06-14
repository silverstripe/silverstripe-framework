(function($){

	var getHTML = function(el) {
		var clone = el.cloneNode(true);

		var div = $('<div></div>');
		div.append(clone);

		return div.html();
	}

	$.leaktools = {

		logDuplicateElements: function(){
			var els = $('*');
			var dirty = false;

			els.each(function(i, a){
				els.not(a).each(function(j, b){
					if (getHTML(a) == getHTML(b)) {
						dirty = true;
						console.log(a, b);
					}
				})
			})

			if (!dirty) console.log('No duplicates found');
		},

		logUncleanedElements: function(clean){
			$.each($.cache, function(){
				var source = this.handle && this.handle.elem;
				if (!source) return;

				var parent = source;
				while (parent && parent.nodeType == 1) parent = parent.parentNode;

				if (!parent) {
					console.log('Unattached', source);
					console.log(this.events);
					if (clean) $(source).unbind().remove();
				}
				else if (parent !== document) console.log('Attached, but to', parent, 'not our document', source);
			})
		}
	};


})(jQuery);