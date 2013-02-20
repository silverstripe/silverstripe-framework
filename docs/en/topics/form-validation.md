# Form Validation

Form validation is a combination of PHP and JavaScript

## PHP

### Introduction

Validators are implemented as an argument to the `[api:Form]` constructor.  You create a required fields validator like
so.  In this case, we're creating a `[api:RequiredFields]` validator - the `[api:Validator]` class itself is an abstract
class.


	:::php
	function Form() {
		$form = new Form($this, 'Form',
			new FieldSet(
				new TextField('MyRequiredField'),
				new TextField('MyOptionalField')
			),
			new FieldSet(
				new FormAction('submit', 'Submit')
			),
			new RequiredFields(array('MyRequiredField'))
		);
		// Optional: Add a CSS class for custom styling
		$form->dataFieldByName('MyRequiredField)->addExtraClass('required');
		return $form;
	}

### Subclassing Validator

To create your own validator, you need to subclass validator and define two methods:

 *  **javascript()** Should output a snippet of JavaScript that will get called to perform javascript validation.
 *  **php($data)** Should return true if the given data is valid, and call $this->validationError() if there were any
errors.

## JavaScript

### Default validator.js implementation

TODO Describe behaviour.js solution easily, how to disable it

Setting fieldEl.requiredErrorMsg or formEl.requiredErrorMsg will override the default error message.  Both can include
the string '$FieldLabel', which will be replaced with the field's label. Otherwise, the message is "Please fill out
"$FieldLabel", it is required".
			
You can use Behaviour to load in the appropriate value:
			
	:::js
	Behaviour.register({
	'#Form_Form' : {
	   requiredErrorMsg: "Please complete this question before moving on.",
			}
	});
			
### Other validation libraries

By default, SilverStripe forms with an attached Validator instance use the custom Validator.js clientside logic. It is
quite hard to customize, and might not be appropriate for all use-cases. You can disable integrated clientside
validation, and use your own (e.g. [jquery.validate](http://docs.jquery.com/Plugins/Validation)).

Disable for all forms (in `mysite/_config.php`):

	:::php
	Validator::set_javascript_validation_handler('none');
	
Disable for a specific form:

	:::php
	$myForm->getValidator()->setJavascriptValidationHandler('none');
	

## Related

 * Model Validation with [api:DataObject->validate()]