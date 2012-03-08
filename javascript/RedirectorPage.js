Behaviour.register({
	'input#Form_EditForm_ExternalURL': {
		onclick: function() {
			document.getElementById('Form_EditForm_RedirectionType_External').checked = true;
		}
	},
	'#TreeDropdownField_Form_EditForm_LinkToID': {
		onclick: function() {
			document.getElementById('Form_EditForm_RedirectionType_Internal').checked = true;
		}
	}
});
