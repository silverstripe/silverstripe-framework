Behaviour.register({
	'h2.TogglePanelHeader' : {
		onclick : function() {
			var contentDiv = $('panel_' + this.id);
			var toggleID = this.id.replace('panel_','') + '_toggle';
			Element.toggle(toggleID + '_closed');
			Element.toggle(toggleID + '_open');
			if(contentDiv.style.display == 'none') {
				contentDiv.style.display = '';
				Element.removeClassName(this, 'closed');
			} else {
				contentDiv.style.display = 'none';
				Element.addClassName(this, 'closed');
			}		
		}	
	}
});