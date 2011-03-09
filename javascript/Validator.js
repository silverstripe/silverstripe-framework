var _CURRENT_FORM;
var _FIRST_ERRORED_FIELD = null;
var _VALIDATIONS_REF = new Array();

function initialiseForm(form, fromAnOnBlur) {
	_CURRENT_FORM = form;
	_FIRST_ERRORED_FIELD = null;

	if(fromAnOnBlur) {
		limitValidationErrorsTo(fromAnOnBlur);
	} else {
		clearValidationErrorLimit();
	}

	_HAS_HAD_FORM_ERROR = false;
	clearValidationErrorCache();
}

function hasHadFormError() {
	return _HAS_HAD_FORM_ERROR || !_ERROR_CACHE;
}

function focusOnFirstErroredField() {
    try {
        _FIRST_ERRORED_FIELD.focus();
    } catch(er) {
    }
}

/**
 * Returns group with the correct classname
 */
function findIndexOf(group,index) {
	var i;
	for(i = 0; i < group.length; i++) {
		if(group[i].className.indexOf(index) > -1) {
			return group[i];
		}
	}
	return null;
}

function clearErrorMessage(holderDiv){
	//merged by nlou 23/08/2007, r#40674
	if(holderDiv.tagName == 'TD'){//for tablefield. 
		$$('span.message', holderDiv).each(function(el){ 
			Element.hide(el); 
		} 
		); 
	}else{ 
		$$('span.message', holderDiv.parentNode).each(function(el) { 
			Element.hide(el); 
		}); 
	} 
	$$('div.validationError', holderDiv.parentNode).each(function(el) {
		Element.removeClassName(el,'validationError');
	});
}

function clearAllErrorMessages() {
	$$('span.message').each(function(el) {
		Element.hide(el);
	});
	$$('div.validationError').each(function(el) {
		Element.removeClassName(el,'validationError');
	});
}

function require(fieldName,cachedError) {
	el = _CURRENT_FORM.elements[fieldName];

	// see if the field is an optionset
	if(el == null) {

		var descendants = _CURRENT_FORM.getElementsByTagName('*');

		el = $(fieldName);

		if(el == null)
			return true;

		if(Element.hasClassName(el, 'optionset')) {
			el.type = 'optionset';

			var options = el.getElementsByTagName('input');

			for(var i = 0; i < options.length; i++) {
				if(options[i].checked)
					if(el.value != null)
						el.value += ',' + options[i].value;
					else
						el.value = options[i].value;
			}
		}

	}


	if(el != null) {
		// Sets up radio and checkbox validation
		if(el.type == 'checkbox' || el.type == 'radio') {
			var set = el.checked;
		}//merged by nlou 23/08/2007, r#40674
		else if(el.type == 'select-one'){ 
			if(el.value == ''||el.value == '0'){ 
				var set = ''; 
			}else{ 
				var set = el.value; 
			} 
		}else{
			var set = el.value;
		}

		var baseEl;
		var fieldHolder = el;

		// Sometimes require events are triggered of
		// associative elements like labels ;-p
		if(el.type) {
			if(el.parentNode.className.indexOf('form') != -1) set = true;
			baseEl = el;

		} else {
			if(_CURRENT_FORM.elements[fieldName]) {
			//Some elements are nested and need to be "got"
				var i, hasValue = false;
				if(_CURRENT_FORM.elements[fieldName].length > 1) {
					for(i=0; i < el.length; i++) {
						if(el[i].checked && el[i].value) {
							hasValue = true;
							break;
						}
					}

					if(hasValue) set = true;
					else set = "";
					baseEl = el[0].parentNode.parentNode;

				} else {
					set = "";
					baseEl = el.parentNode;
				}

			} else {
				set = true;
			}
		}

		// This checks to see if the input has a value, and the field is not a readonly.
		if( ( typeof set == 'undefined' || (typeof(set) == 'string' && set.match(/^\s*$/)) ) ) {
			//fieldgroup validation
			var fieldLabel = findParentLabel(baseEl);

			// Some fields do-not have labels, in
			// which case we need a blank one
			if(fieldLabel == null || fieldLabel == "") {
				fieldlabel = "this field";
			}

			var errorMessage = ss.i18n.sprintf(ss.i18n._t('VALIDATOR.FIELDREQUIRED', 'Please fill out "%s", it is required.'), fieldLabel);
			if(baseEl.requiredErrorMsg) errorMessage = baseEl.requiredErrorMsg;
			else if(_CURRENT_FORM.requiredErrorMsg) errorMessage = _CURRENT_FORM.requiredErrorMsg;

			validationError(baseEl, errorMessage.replace('$FieldLabel', fieldLabel),"required",cachedError);
			return false;

		} else {
			if(!hasHadFormError()) {
				if(baseEl) fieldHolder = baseEl.parentNode;
				clearErrorMessage(fieldHolder);
			}
			return true;
		}
	}

	return true;
}

/**
 * Returns the label of the blockset which contains the classname left
 */
function findParentLabel(el) {
	// If the el's type is HTML then were at the uppermost parent, so return
	// null. its handled by the validator function anyway :-)
	if(el) {
		if(el.className == "undefined") {
			return null;
		} else {
			if(el.className) {
				if(el.className.indexOf('field') == 0) {
					labels = el.getElementsByTagName('label');
					if(labels){
						var left = findIndexOf(labels,'left');
						var right = findIndexOf(labels,'right');
						if(left) {
							return strip_tags(left.innerHTML);
						} else if(right) {
							return strip_tags(right.innerHTML);
						} else {
							return findParentLabel(el.parentNode);
						}
					}
				}//merged by nlou 23/08/2007, r#40674
				else if(el.className.indexOf('tablecolumn') != -1){ 
					return el.className.substring(0, el.className.indexOf('tablecolumn')-1); 
				}else{
					return findParentLabel(el.parentNode);
				}
			} else {
				// Try to find a label with a for value of this field.
				if(el.id) {
					var labels = $$('label[for=' + el.id + ']');
					if(labels && labels.length > 0) return labels[0].innerHTML;
				}
			
				return findParentLabel(el.parentNode);
			}
		}
	}
	// backup
	return "this";
}

/**
 * Adds a validation error to an element
 */
function validationError(field,message, messageClass, cacheError) {
	if(typeof(field) == 'string') {
		field = $(field);
	}

	if(cacheError) {
		_ERROR_CACHE[_ERROR_CACHE.length] = {
			"field": field,
			"message": message,
			"messageClass": messageClass
		}
		return;
	}

	// The validation function should only be called if you've just left a field,
	// or the field is being validated on final submission
	if(_LIMIT_VALIDATION_ERRORS && _LIMIT_VALIDATION_ERRORS != field) {
		// clearErrorMessage(field.parentNode);
		return;
	}

	_HAS_HAD_FORM_ERROR = true;

	// See if the tag has a reference to the validationMessage (quicker than the one below)
	var validationMessage = (typeof(_VALIDATIONS_REF[field.id]) != 'undefined')? _VALIDATIONS_REF[field.id] : null;

	// Cycle through the elements to see if it has a span
	// (for a validation or required messages)
	if(!validationMessage) {

		//Get the parent holder of the element
		var FieldHolder = field.parentNode;
		var allSpans = FieldHolder.getElementsByTagName('span');
		validationMessage = findIndexOf(allSpans,'message');
	}

	// If we didn't find it, create it
	if(!validationMessage) {
		validationMessage = document.createElement('span');
		FieldHolder.appendChild(validationMessage);
	}

	// Keep a reference to it
	_VALIDATIONS_REF[field.id] = validationMessage;

    // Keep a reference to the first errored field
    if(field && !_FIRST_ERRORED_FIELD) _FIRST_ERRORED_FIELD = field;
    
	// Set the attributes
	validationMessage.className = "message " + messageClass;
	validationMessage.innerHTML = message;
	validationMessage.style.display = "block";
	
	// Set Classname on holder
	var holder = document.getParentOfElement(field,'div','field');
	Element.addClassName(holder, 'validationError');
}

/**
 * Set a limitation so that only validation errors for the given element will actually be shown
 */

var _LIMIT_VALIDATION_ERRORS = null;
function limitValidationErrorsTo(field) {
	_LIMIT_VALIDATION_ERRORS = field;
}

function clearValidationErrorLimit() {
	_LIMIT_VALIDATION_ERRORS = null;
}

function clearValidationErrorCache() {
	_ERROR_CACHE = new Array();
}

function showCachedValidationErrors() {
	for(i = 0; i < _ERROR_CACHE.length; i++) {
		validationError(_ERROR_CACHE[i]["field"],
		_ERROR_CACHE[i]["message"],
		_ERROR_CACHE[i]["messageClass"],
		false);
	}
}

function strip_tags(text) {
	return text.replace(/<[^>]+>/g,'');
}