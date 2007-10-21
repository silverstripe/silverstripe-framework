var ToggleField = Class.create();
ToggleField.prototype = {
	initialize: function() {
		var rules = {};
		rules['#' + this.id + ' .triggerMore'] = {
			onclick: function(e) {
				this.toggle();
				Event.stop(e); return false;
			}.bind(this)
		};
		rules['#' + this.id + ' .triggerLess'] = {
			onclick: function(e) {
				this.toggle();
				Event.stop(e); return false;
			}.bind(this)
		};
		Behaviour.register(rules);
		
		if(Element.hasClassName(this, 'startClosed')) {
			this.toggle();
		}
	},
	
	toggle: function() {
		Element.toggle($$('#' + this.id + ' .contentLess')[0]);
		Element.toggle($$('#' + this.id + ' .contentMore')[0]);
	}
}
ToggleField.applyTo('div.toggleField');