Behaviour.register({
	'ul.SelectionGroup input.selector' : {
		onclick : function() {
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
		},
		parentNodeWithTag: function(tagName) {
			var el = this.parentNode;
			while(el.parentNode && el.tagName.toLowerCase() != tagName) el = el.parentNode;
			return el;
		}
	}
});