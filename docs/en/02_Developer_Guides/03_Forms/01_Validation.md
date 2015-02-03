title: Form Validation
summary: Validate form data through the server side validation API.

# Form Validation

SilverStripe provides server-side form validation out of the box through the [api:Validator] class and its' child class
[api:RequiredFields]. A single `Validator` instance is set on each `Form`. Validators are implemented as an argument to 
the `[api:Form]` constructor or through the function `setValidator`.

	:::php
	<?php

	class Page_Controller extends ContentController {

		private static $allowed_actions = array(
			'MyForm'
		);

		public function MyForm() {
			$fields = new FieldList(
				TextField::create('Name'),
				EmailField::create('Email')
			);

			$actions = new FieldList(
				FormAction::create('doSubmitForm', 'Submit')
			);

			// the fields 'Name' and 'Email' are required.
			$required = new RequiredFields(array(
				'Name', 'Email'
			));

			// $required can be set as an argument
			$form = new Form($controller, 'MyForm', $fields, $actions, $required);

			// Or, through a setter.
			$form->setValidator($required);

			return $form;
		}

		public function doSubmitForm($data, $form) {
			//..
		}
	}

In this example we will be required to input a value for `Name` and a valid email address for `Email` before the 
`doSubmitForm` method is called.

<div class="info" markdown="1">
Each individual [api:FormField] instance is responsible for validating the submitted content through the 
[api:FormField::validate] method. By default, this just checks the value exists. Fields like `EmailField` override 
`validate` to check for a specific format.
</div>

Subclasses of `FormField` can define their own version of `validate` to provide custom validation rules such as the 
above example with the `Email` validation. The `validate` method on `FormField` takes a single argument of the current 
`Validator` instance. 

<div class="notice" markdown="1">
The data value of the `FormField` submitted is not passed into validate. It is stored in the `value` property through 
the `setValue` method.
</div>

	:::php
	public function validate($validator) {
		if($this->value == 10) {
			return false;
		}

		return true;
	}

The `validate` method should return `true` if the value passes any validation and `false` if SilverStripe should trigger
a validation error on the page.

<div class="notice" markdown="1">
You can also override the entire `Form` validation by subclassing `Form` and defining a `validate` method on the form.
</div>

Say we need a custom `FormField` which requires the user input a value in a `TextField` between 2 and 5. There would be
two ways to go about this:

A custom `FormField` which handles the validation. This means the `FormField` can be reused throughout the site and have
the same validation logic applied to it throughout.

**mysite/code/formfields/CustomNumberField.php**

	:::php
	<?php

	class CustomNumberField extends TextField {

		public function validate($validator) {
			if(!is_numeric($this->value)) {
				$validator->validationError(
					$this->name, "Not a number. This must be between 2 and 5", "validation", false
				);
				
				return false;
			}
			else if($this->value > 5 || $this->value < 2) {
				$validator->validationError(
					$this->name, "Your number must be between 2 and 5", "validation", false
				);

				return false;
			}

			return true;
		}
	}

Or, an alternative approach to the custom class is to define the behavior inside the Form's action method. This is less
reusable and would not be possible within the `CMS` or other automated `UI` but does not rely on creating custom 
`FormField` classes.
	
	:::php
	<?php

	class Page_Controller extends ContentController {

		private static $allowed_actions = array(
			'MyForm'
		);

		public function MyForm() {
			$fields = new FieldList(
				TextField::create('Name'),
				EmailField::create('Email')
			);

			$actions = new FieldList(
				FormAction::create('doSubmitForm', 'Submit')
			);

			$form = new Form($controller, 'MyForm', $fields, $actions);

			return $form
		}

		public function doSubmitForm($data, $form) {
			// At this point, RequiredFields->validate() will have been called already,
			// so we can assume that the values exist. Say we want to make sure that email hasn't already been used.
			
			$check = Member::get()->filter('Email', $data['Email'])->first();

			if($check) {
				$form->addErrorMessage('Email', 'This email already exists', 'bad');

				return $this->redirectBack();
			}


			$form->sessionMessage("You have been added to our mailing list", 'good');
			
			return $this->redirectBack();
		}
	}

## Server-side validation messages

If a `FormField` fails to pass `validate()` the default error message is returned.

	:::php
	'$Name' is required

Use `setCustomValidationMessage` to provide a custom message.

	:::php
	$field = new TextField(..);
	$field->setCustomValidationMessage('Whoops, looks like you have missed me!');

## JavaScript validation

Although there are no built-in JavaScript validation handlers in SilverStripe, the `FormField` API is flexible enough 
to provide the information required in order to plug in custom libraries like [Parsley.js](http://parsleyjs.org/) or 
[jQuery.Validate](http://jqueryvalidation.org/). Most of these libraries work on HTML `data-` attributes or special 
classes added to each input. For Parsley we can structure the form like.

	:::php
	$form = new Form(..);
	$form->setAttribute('data-parsley-validate', true);

	$field = $fields->dataFieldByName('Name');

	$field->setAttribute('required', true);
	$field->setAttribute('data-parsley-mincheck', '2');


## Model Validation

An alternative (or additional) approach to validation is to place it directly on the database model. SilverStripe 
provides a `[api:DataObject->validate]` method to validate data at the model level. See 
[Data Model Validation](../model/validation). 

### Validation in the CMS

In the CMS, we're not creating the forms for editing CMS records. The `Form` instance is generated for us so we cannot
call `setValidator` easily. However, a `DataObject` can provide its' own `Validator` instance through the 
`getCMSValidator()` method. The CMS interfaces such as [api:LeftAndMain], [api:ModelAdmin] and [api:GridField] will 
respect the provided `Validator` and handle displaying error and success responses to the user. 

<div class="info" markdown="1">
Again, custom error messages can be provided through the `FormField`
</div>

	:::php
	<?php

	class Page extends SiteTree {

		private static $db = array(
			'MyRequiredField' => 'Text'
		);

		public function getCMSFields() {
			$fields = parent::getCMSFields();

			$fields->addFieldToTab('Root.Main', 
				TextField::create('MyRequiredField')->setCustomValidationMessage('You missed me.')
			);
		}
		
		public function getCMSValidator() {
			return new RequiredFields(array(
				'MyRequiredField'
			));
		}

## API Documentation

 * [api:RequiredFields]
 * [api:Validator]
