var ToggleCompositeField = Class.create();
ToggleCompositeField.prototype = {
	initialize: function() {
		var rules = {};
		rules['#' + this.id + ' .trigger'] = {
			onclick: function(e) {
				this.toggle();
				Event.stop(e); return false;
			}.bind(this)
		};
		Behaviour.register(rules);
		
		// close content by default
		if(Element.hasClassName(this, 'startClosed')) {
			Element.toggle($$('#' + this.id + ' .contentMore')[0]);
		}
		Element.toggle($$('#' + this.id + ' .triggerClosed')[0]);
	},
	
	toggle: function() {
		Element.toggle($$('#' + this.id + ' .contentMore')[0]);
		Element.toggle($$('#' + this.id + ' .triggerClosed')[0]);
		Element.toggle($$('#' + this.id + ' .triggerOpened')[0]);
	}
}
ToggleCompositeField.applyTo('div.toggleCompositeField');