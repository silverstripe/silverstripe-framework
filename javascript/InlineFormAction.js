/**
 * Javascript-Template, needs to be evaluated by Requirements::javascriptTemplate
 */
Behaviour.register({
	'div.inlineformaction input#$ID': {
		onclick: function() {
			var url = baseHref() + 'admin-custom/' + this.name.substring(7) + '?ID=' + $('Form_EditForm_ID').value + '&ajax=1';
			
			new Ajax.Request( url, {
				onSuccess: Ajax.Evaluator,
				onFailure: Ajax.Evaluator
			});
			
			return false;
		}
	}
});