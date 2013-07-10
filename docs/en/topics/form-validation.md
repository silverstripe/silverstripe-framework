# Form Validation

SilverStripe provides PHP form validation out of the box, but doesn't come with any built-in JavaScript validation
(the previously used `Validator.js` approach has been deprecated).

## ValidationException and ValidationResult

A the core of the validation system in SilverStripe 3.1 is the `ValidationException`.  Any time you want to trigger
a validation error, you can throw one of these.

	:::php
	// General error
	throw new ValidationException("I don't like this form");

	// Field-specific error
	throw ValidationException::create_for_field("Content", "I don't like your Content");

You can throw these in anywhere that would be called during a form submission.  For example:

 * `onBeforeWrite()` methods on your DataObjects
 * Action handlers for your forms
 * Custom classes that connect to 3rd party APIs
 
Internally, a ValidationResult object is created to actually store the validation information, and if you have more
complex needs (for example, if you need to return errors against multiple fields), you can create a `ValidationResult`
object yourself and pass is to the `ValidationException` constructor.

	:::php
	$result = new ValidationResult;

	// General error
	$result->addError("I don't like this form");	

	// Field-specific error
	$result->addFieldError("Content", "I don't like your Content");

	// Non-error message
	$result->addFieldMessage("Title", "But I do like your Title", "good");

	throw new ValidationException($result);

## Required Fields

In addition to throwing ValidationExceptions, each Form can have a Validator attached.

Validators are implemented as an argument to the `[api:Form]` constructor,
and are subclasses of the abstract `[api:Validator]` base class.
The only implementation which comes with SilverStripe is
the `[api:RequiredFields]` class, which ensures fields are filled out
when the form is submitted.

	:::php
	public function Form() {
		$form = new Form($this, 'Form',
			new FieldList(
				new TextField('MyRequiredField'),
				new TextField('MyOptionalField')
			),
			new FieldList(
				new FormAction('submit', 'Submit form')
			),
			new RequiredFields(array('MyRequiredField'))
		);
		// Optional: Add a CSS class for custom styling
		$form->dataFieldByName('MyRequiredField')->addExtraClass('required');
		return $form;
	}

## Form Field Validation

Form fields are responsible for validating the data they process,
through the `[api:FormField->validate()] method. There are many fields
for different purposes (see ["form field types"](/reference/form-field-types) for a full list).

## Adding your own validation messages

In many cases, you want to add PHP validation which is more complex than
validating the format or existence of a single form field input.
For example, you might want to have dependent validation on
a postcode which depends on the country you've selected in a different field.

There's two ways to go about this: Either you can attach a custom error message
to a specific field, or a generic message for the whole form.

In both cases, you achieve this by throwing a ValidationException.

Example: Validate postcodes based on the selected country (on the controller).

	:::php
	class MyController extends Controller {
		private static $allowed_actions = array('Form');
		public function Form() {
			return Form::create($this, 'Form',
				new FieldList(
					new NumericField('Postcode'),
					new CountryDropdownField('Country')
				),
				new FieldList(
					new FormAction('submit', 'Submit form')
				),
				new RequiredFields(array('Country'))
			);
		}
		public function submit($data, $form) {
			// At this point, RequiredFields->validate() will have been called already,
			// so we can assume that the values exist.
			
			// German postcodes need to be five digits
			if($data['Country'] == 'de' && isset($data['Postcode']) && strlen($data['Postcode']) != 5) {
				throw ValidationException::create_for_field("Postcode", "Need five digits for German postcodes");
			}
			
			// Global validation error (not specific to form field)
			if($data['Country'] == 'IR' && isset($data['Postcode']) && $data['Postcode']) {
				throw new ValidationException("Ireland doesn't have postcodes!");
			}
			
			// continue normal processing...
		}
	}

## JavaScript Validation

While there are no built-in JavaScript validation handlers in SilverStripe,
the `FormField` API is flexible enough to provide the information required
in order to plug in custom libraries.

### HTML5 attributes

HTML5 specifies some built-in form validations ([source](http://www.w3.org/wiki/HTML5_form_additions)),
which are evaluated by modern browsers without any need for JavaScript.
SilverStripe supports this by allowing to set custom attributes on fields.

	:::php
	// Markup contains <input type="text" required />
	TextField::create('MyText')->setAttribute('required', true);
	
	// Markup contains <input type="url" pattern="https?://.+" />
	TextField::create('MyText')
		->setAttribute('type', 'url')
		->setAttribute('pattern', 'https?://.+')

### HTML5 metadata

In addition, HTML5 elements can contain custom data attributes with the `data-` prefix.
These are general purpose attributes, but can be used to hook in your own validation.

	:::php
	// Validate a specific date format (in PHP)
	// Markup contains <input type="text" data-dateformat="dd.MM.yyyy" />
	DateField::create('MyDate')->setConfig('dateformat', 'dd.MM.yyyy');
	
	// Limit extensions on upload (in PHP)
	// Markup contains <input type="file" data-allowed-extensions="jpg,jpeg,gif" />
	$exts = array('jpg', 'jpeg', 'gif');
	$validator = new Upload_Validator();
	$validator->setAllowedExtensions($exts);
	$upload = Upload::create()->setValidator($validator);
	$fileField = FileField::create('MyFile')->setUpload(new);
	$fileField->setAttribute('data-allowed-extensions', implode(',', $exts));

Note that these examples don't have any effect on the client as such,
but are just a starting point for custom validation with JavaScript.

## Model Validation

An alternative (or additional) approach to validation is to place it directly
on the model. SilverStripe provides a `[api:DataObject->validate()]` method for this purpose.
Refer to the ["datamodel" topic](/topics/datamodel#validation-and-constraints) for more information.

## Subclassing Validator

To create your own validator, you need to subclass validator and define one methods:

 *  **php($data)** Should return true if the given data is valid, and call $this->validationError() if there were any
errors.

## Handling errors yourself

If you wish to handle the errors yourself in some way, you can catch the ValidationException.  Here is a simple
example:

	:::php
	try {
		// Do something that might have a validation error

	} catch(ValidationException $e) {
		// Extract the ValidationResult form the 
		$result = $e->getResult();
		// One possible way of showing the errors contained within the ValidationResult
		echo "There were the following problems:\n" . $result->starredList();
		
	}

## Related

 * Model Validation with [api:DataObject->validate()]
