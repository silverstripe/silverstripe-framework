ComplexTableFieldPopupForm = Class.create();
ComplexTableFieldPopupForm.prototype = {
	
	errorMessage: "Error talking to server",
	
	initialize: function() {
		Behaviour.register({
			"form#ComplexTableField_Popup_DetailForm .Actions input.action": {
				onclick: this.submitForm.bind(this)
			}
		});
	},
	
	submitForm : function(e) {
		// if custom validation implementation (extend class to implement)
		if(this.validate) {
			if(!this.validate()) {
				Event.stop(e);
				return false;
			}
		}
		
		// only do ajaxy stuff for content loaded in an iframe
		if(window != top && parent.parent.GB_hide) {
			var theForm = Event.findElement(e,"form");
			if(parent.parent.statusMessage != undefined) parent.parent.statusMessage('saving');
			var submitButton = document.getElementsBySelector("input.action",theForm)[0];
			if(typeof submitButton != 'undefined') {
				submitButton.disabled = true;
				Element.addClassName(submitButton,'loading');
			}

			new parent.parent.Ajax.Request(
				theForm.getAttribute("action"),
				{
					parameters: Form.serialize(theForm)+"&ajax=1",
					onComplete: this.updateTableAfterSave.bind(this),
					onFailure: this.ajaxErrorHandler.bind(this)
				}
			);
			Event.stop(e);
			return false;
		} else {
			return true;
		}
	},
	
	updateTableAfterSave : function(response) {
		eval(response.responseText);

		var theForm = document.getElementsByTagName("form")[0];

		// don't update when validation is present and failed
		if(!this.validate || (this.validate && !hasHadFormError())) {
			new parent.parent.Ajax.Request(
				parent.parent.GB_RefreshLink,
				{
					onComplete: this.updateAndHide.bind(parent.parent),
					onFailure :  this.ajaxErrorHandler
				}
			);
		} else {
			var submitButton = document.getElementsBySelector("input.action",theForm)[0];
			if(typeof submitButton != 'undefined') {
				submitButton.disabled = false;
				Element.removeClassName(submitButton,'loading');
			}
		}
	},
	
	ajaxErrorHandler: function(response) {
		var submitButton = document.getElementsBySelector("input.action",theForm)[0];
		if(typeof submitButton != 'undefined') {
			submitButton.disabled = false;
			Element.removeClassName(submitButton,'loading');
		}
		
		// TODO does not work due to sandbox-iframe restrictions?
		if(typeof(parent.parent.ajaxErrorHandler) == 'function') {
			parent.parent.ajaxErrorHandler();
		} else {
			alert(this.errorMessage);
		}
	},
	
	updateAndHide: function(response) {
		var theForm =document.getElementsByTagName("form")[0];
		
		var submitButton = document.getElementsBySelector("input.action",theForm)[0];
		if(typeof submitButton != 'undefined') {
			submitButton.disabled = false;
			Element.removeClassName(submitButton,'loading');
		}
		
		onload_init_tabstrip();
		
		// TODO Fix DOM-relation after pagination inside popup
		if(this.GB_OpenerObj) {
			// apparently firefox doesn't remember its DOM after innerHTML, so we help out here...
			var cachedObj = this.GB_OpenerObj;
			var cachedParentObj = this.GB_OpenerObj.parentNode;
			Element.replace(this.GB_OpenerObj, response.responseText);
			this.Behaviour.apply(cachedParentObj);
			cachedObj = null;
			this.GB_OpenerObj = null;
		}
		
		// causes IE6 to go nuts
		//this.GB_hide();
		
	}
}
ComplexTableFieldPopupForm.applyTo('form#ComplexTableField_Popup_DetailForm');