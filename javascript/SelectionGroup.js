(function($) {
$(document).ready(function() {
	$('ul.SelectionGroup input.selector').livequery('click', function(){
		var prnt = this.parentNodeWithTag('ul');
		var li = this.parentNodeWithTag('li');
		var i, item, allItems = prnt.childNodes;
		for(i=0;item=allItems[i];i++) if(item.tagName) {
			if(item == li) {
				Element.addClassName(item, 'selected')
			} else {
				Element.removeClassName(item, 'selected')
			}
		}
	});
})
})(jQuery);

SelectionGroupSelector = Class.create();
SelectionGroupSelector.prototype = {
	parentNodeWithTag: function(tagName){
		var el = this.parentNode;
		while(el.parentNode && el.tagName.toLowerCase() != tagName) el = el.parentNode;
		return el;
	}
}

SelectionGroupSelector.applyTo('ul.SelectionGroup input.selector');