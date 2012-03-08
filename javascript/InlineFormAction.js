/**
 * Javascript-Template, needs to be evaluated by Requirements::javascriptTemplate
 */
Behaviour.register({
	'div.inlineformaction input#$ID': {
		onclick: function() {
			var url = jQuery('base').attr('href') + 'admin-custom/' + this.name.substring(7) + '?ID=' + document.getElementById('Form_EditForm_ID').value + '&ajax=1';
			
			jQuery.ajax({
				'url': url,
				success: Ajax.Evaluator,
				success: Ajax.Evaluator
			});
			
			return false;
		}
	}
});