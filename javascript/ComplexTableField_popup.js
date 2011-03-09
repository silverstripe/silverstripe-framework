ComplexTableFieldPopupForm = Class.create();
ComplexTableFieldPopupForm.prototype = {
	
	initialize: function() {
		var rules = {};
		
		Behaviour.register(rules);
	}
}
ComplexTableFieldPopupForm.applyTo('#ComplexTableField_Popup_DetailForm');
ComplexTableFieldPopupForm.applyTo('#ComplexTableField_Popup_AddForm');