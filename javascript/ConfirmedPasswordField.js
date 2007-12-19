Behaviour.register({
	'div.confirmedpassword' : {
		initialize: function() {
			var rules = {};
			rules['#'+this.id+' .showOnClick a'] = {onclick: function(e) {
				this.toggle();
				Event.stop(e);
			}.bind(this)};
			
			Behaviour.register(rules);
			
			this.toggle();
		},
		
		toggle: function() {
			var containers = $$('.showOnClickContainer', this);
			if(!containers.length) return false;
			
			var container = containers[0];
			Element.toggle(container);
			var hiddenField = $$('input.hidden', this)[0];
			hiddenField.value = (Element.visible(container));
		}
		
	}
});