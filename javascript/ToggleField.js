var ToggleField = Class.create();
ToggleField.prototype = {
	initialize: function() {
		var rules = {};
		rules['#' + this.id + ' .triggerMore'] = {
			onclick: function(e) {
				Element.toggle(this);
				Event.stop(e); return false;
			}.bind(this)
		};
		rules['#' + this.id + ' .triggerLess'] = {
			onclick: function(e) {
				Element.toggle(this);
				Event.stop(e); return false;
			}.bind(this)
		};
		Behaviour.register(rules);
		
		if(Element.hasClassName(this, 'startClosed')) {
			Element.toggle(this);
		}
	},
	
	toggle: function() {
		var lessDivs = $$('#' + this.id + ' .contentLess');
		if(lessDivs) Element.toggle(lessDivs[0]);
		
		var moreDivs = $$('#' + this.id + ' .contentMore');
		if(moreDivs) Element.toggle(moreDivs[0]);
	}
}
ToggleField.applyTo('div.toggleField');