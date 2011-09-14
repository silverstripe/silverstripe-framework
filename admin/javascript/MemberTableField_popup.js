/**
 * File: MemberTableField_popup.js
 */

/**
 * Class: MemberTableFieldPopupForm
 */
MemberTableFieldPopupForm = Class.extend("ComplexTableFieldPopupForm");
MemberTableFieldPopupForm.prototype = {
	initialize: function() {
		this.ComplexTableFieldPopupForm.initialize();
		
		Behaviour.register('MemberTableFieldPopupForm',{
			"div.MemberTableField_Popup .Actions input.action": {
				onclick: this.submitForm.bind(this)
			}
		});
	}
}

MemberTableFieldPopupForm.applyTo('div.MemberTableField_Popup .Actions');