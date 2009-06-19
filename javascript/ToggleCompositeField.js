var ToggleCompositeField = Class.create();
ToggleCompositeField.prototype = {
	initialize: function() {
		var rules = {};
		rules['#' + this.id + ' .trigger'] = {
			onclick: function(e) {
				this.toggle();
				this.resetHiddenValue();
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
	},
	
	resetHiddenValue: function() {
		var hiddenValue = $$('#' + this.id + ' input.hidden.hiddenValue')[0];
		console.log(hiddenValue.value);
		if(hiddenValue.value == 1){
			hiddenValue.value = 0;
		}else if(hiddenValue.value == 0){
			hiddenValue.value = 1;
		}
	}
}
ToggleCompositeField.applyTo('div.toggleCompositeField');