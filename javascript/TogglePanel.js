Behaviour.register({
	'h2.TogglePanelHeader' : {
		onclick : function() {
			var contentDiv = $('panel_' + this.id);
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