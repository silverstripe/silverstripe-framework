# Forms

HTML forms are in practice the most used way to interact with a user.
SilverStripe provides classes to generate and handle the actions and data from a
form.

## Overview

A fully implemented form in SilverStripe includes a couple of classes that
individually have separate concerns.

* Controller — Takes care of assembling the form and receiving data from it.
* Form — Holds sets of fields, actions and validators.
* FormField — Fields that receive data or displays them, e.g input fields.
* FormActions — Buttons that execute actions.
* Validators — Validate the whole form.

Depending on your needs you can customize and override any of the above classes;
the defaults, however, are often sufficient.

## The Controller

Forms start at the controller. Here is a simple example on how to set up a form
in a controller.

**Page.php**

	:::php
	class Page_Controller extends ContentController {
		
		private static $allowed_actions = array(
			'HelloForm'
		);
		
		// Template method
		public function HelloForm() {
			$fields = new FieldList();
			$actions = new FieldList(
				FormAction::create("doSayHello")->setTitle("Say hello")
			);
			$form = new Form($this, 'HelloForm', $fields, $actions);
			// Load the form with previously sent data
			$form->loadDataFrom($this->request->postVars());
			return $form;
		}
		
		public function doSayHello($data, Form $form) {
			// Do something with $data
			return $this->render();
		}
	}

The name of the form ("HelloForm") is passed into the `Form` constructor as a
second argument. It needs to match the method name.

Because forms need a URL, the `HelloForm()` method needs to be handled like any
other controller action. To grant it access through URLs, we add it to the
`$allowed_actions` array.

Form actions ("doSayHello"), on the other hand, should _not_ be included in
`$allowed_actions`; these are handled separately through
`Form->httpSubmission()`.

You can control access on form actions either by conditionally removing a
`FormAction` from the form construction, or by defining `$allowed_actions` in
your own `Form` class (more information in the
["controllers" topic](/topics/controllers)).
	
**Page.ss**

	:::ss
	<%-- place where you would like the form to show up --%>
	<div>$HelloForm</div>

<div class="warning" markdown='1'>
Be sure to add the Form name 'HelloForm' to your controller's $allowed_actions
array to enable form submissions.
</div>

<div class="notice" markdown='1'>
You'll notice that we've used a new notation for creating form fields, using
`create()` instead of the `new` operator. These are functionally equivalent, but
allows PHP to chain operations like `setTitle()` without assigning the field
instance to a temporary variable. For in-depth information on the create syntax,
see the [Injector](/reference/injector) documentation or the API documentation
for `[api:Object]`::create().
</div>

## The Form

Form is the base class of all forms in a SilverStripe application. Forms in your
application can be created either by instantiating the Form class itself, or by
subclassing it.

### Instantiating a form

Creating a form is a matter of defining a method to represent that form. This
method should return a form object. The constructor takes the following
arguments:

* `$controller`: This must be an instance of the controller that contains the
	form, often `$this`.
* `$name`: This must be the name of the method on that controller that is
	called to return the form. The first two arguments allow the form object
	to be re-created after submission. **It's vital that they be properly
	set—if you ever have problems with a form action handler not working,
	check that these values are correct.**
* `$fields`: A `[api:FieldList]` containing `[api:FormField]` instances make
	up fields in the form.
* `$actions`: A `[api:FieldList]` containing the `[api:FormAction]` objects -
	the buttons at the bottom.
* `$validator`: An optional `[api:Validator]` for validation of the form.

Example: 

	:::php
	// Controller action
	public function MyCustomForm() {
		$fields = new FieldList(
			EmailField::create("Email"),
			PasswordField::create("Password")
		);
		$actions = new FieldList(FormAction::create("login")->setTitle("Log in"));
		return new Form($this, "MyCustomForm", $fields, $actions);
	}


## Subclassing a form

It's the responsibility of your subclass's constructor to call 

	:::php
	parent::__construct()

with the right parameters. You may choose to take $fields and $actions as
arguments if you wish, but $controller and $name must be passed—their values
depend on where the form is instantiated.

	:::php
	class MyForm extends Form {
		public function __construct($controller, $name) {
			$fields = new FieldList(
				EmailField::create("Email"),
				PasswordField::create("Password")
			);
			$actions = new FieldList(FormAction::create("login")->setTitle("Log in"));
			
			parent::__construct($controller, $name, $fields, $actions);
		}
	}


The real difference, however, is that you can then define your controller
methods within the form class itself. This means that the form takes
responsibilities from the controller and manage how to parse and use the form
data.

**Page.php**

	:::php
	class Page_Controller extends ContentController {
		
		private static $allowed_actions = array(
			'HelloForm',
		);
		
		// Template method
		public function HelloForm() {
			return new MyForm($this, 'HelloForm');
		}
	}

**MyForm.php**

	:::php
	class MyForm extends Form {
	
		public function __construct($controller, $name) {
			$fields = new FieldList(
				EmailField::create("Email"),
				PasswordField::create("Password")
			);

			$actions = new FieldList(FormAction::create("login")->setTitle("Log in"));
			
			parent::__construct($controller, $name, $fields, $actions);
		}
		
		public function login(array $data, Form $form) {
			// Authenticate the user and redirect the user somewhere
			Controller::curr()->redirectBack();
		}
	}

## The FormField classes

There are many classes extending `[api:FormField]`. There is a full overview at 
[form field types](/reference/form-field-types).


### Using Form Fields

To get these fields automatically rendered into a form element, all you need to
do is create a new instance of the class, and add it to the `FieldList` of the
form.

	:::php
	$form = new Form(
		$this, // controller
		"SignupForm", // form name
		new FieldList( // fields
			TextField::create("FirstName")->setTitle('First name'),
			TextField::create("Surname")->setTitle('Last name')->setMaxLength(50),
			EmailField::create("Email")->setTitle("Email address")->setAttribute('type', 'email')
		), 
		new FieldList( // actions
			FormAction::create("signup")->setTitle("Sign up")
		), 
		new RequiredFields( // validation
			"Email", "FirstName"
		)
	);

## Readonly

You can turn a form or individual fields into a readonly version. This is handy
in the case of confirmation pages or when certain fields cannot be edited due to
permissions.

Readonly on a Form

	:::php
	$myForm->makeReadonly();


Readonly on a FieldList

	:::php
	$myFieldList->makeReadonly();


Readonly on a FormField

	:::php
	$myReadonlyField = $myField->transform(new ReadonlyTransformation());
	// shortcut
	$myReadonlyField = $myField->performReadonlyTransformation();


## Custom form templates

You can use a custom form template to render with, instead of *Form.ss*

It's recommended you do this only if you have a lot of presentation text or
graphics that surround the form fields. This is better than defining those as
*LiteralField* objects, as it doesn't clutter the data layer with presentation
junk.

First you need to create your own form class extending Form; that way you can
define a custom template using a `forTemplate()` method on your Form class.

	:::php
	class MyForm extends Form {
	
		public function __construct($controller, $name) {
			$fields = new FieldList(
				EmailField::create("Email"),
				PasswordField::create("Password")
			);

			$actions = new FieldList(FormAction::create("login")->setTitle("Log in"));
			parent::__construct($controller, $name, $fields, $actions);
		}
		
		public function login(array $data, Form $form) {
			// Do something with $data
			Controller::curr()->redirectBack();
		}
		
		public function forTemplate() {
			return $this->renderWith(array($this->class, 'Form'));
		}
	}
	
`MyForm->forTemplate()` tells the `[api:Form]` class to render with a template
of return value of `$this->class`, which in this case is *MyForm*. If the
template doesn't exist, then it falls back to using Form.ss.

*MyForm.ss* should then be placed into your *templates/Includes* directory for your project. Here is an example of
basic customisation, with two ways of presenting the field and its inline validation:

	:::ss
	<form $FormAttributes>
		<% if $Message %>
			<p id="{$FormName}_error" class="message $MessageType">$Message</p>
		<% else %>
			<p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
		<% end_if %>
		
		<fieldset>
			<div id="Email" class="field email">
				<label class="left" for="{$FormName}_Email">Email</label>
				$Fields.dataFieldByName(Email)
				<span id="{$FormName}_error" class="message $Fields.dataFieldByName(Email).MessageType">
					$Fields.dataFieldByName(Email).Message
				</span>
			</div>
			
			<div id="Email" class="field password">
				<label class="left" for="{$FormName}_Password">Password</label>
				<% with $Fields.dataFieldByName(Password) %>
					$field
					<% if $Message %>
						<p id="{$FormName}_error" class="message $MessageType">$Message</p>
					<% end_if %>
				<% end_with %>
			</div>
			
			$Fields.dataFieldByName(SecurityID)
		</fieldset>
		
		<% if $Actions %>
		<div class="Actions">
			<% loop $Actions %>$Field<% end_loop %>
		</div>
		<% end_if %>
	</form>

`$Fields.dataFieldByName(FirstName)` will return the form control contents of
`Field()` for the particular field object, in this case `EmailField->Field()` or
`PasswordField->Field()` which returns an `<input>` element with specific markup
for the type of field. Pass in the name of the field as the first parameter, as
done above, to render it into the template.

To find more methods, have a look at the `[api:Form]` class and
`[api:FieldList]` class as there is a lot of different methods of customising
the form templates. An example is that you could use `<% loop $Fields %>`
instead of specifying each field manually, as we've done above.

### Custom form field templates

The easiest way to customize form fields is adding CSS classes and additional attributes.

	:::php
	$field = TextField::create('MyText')
		->addExtraClass('largeText');
		->setAttribute('data-validation-regex', '[\d]*');

Will be rendered as:

	:::html
	<input type="text" name="MyText" class="text largeText" id="MyForm_MyCustomForm_MyText" data-validation-regex="[\d]*">

Each form field is rendered into a form via the
`[FormField->FieldHolder()](api:FormField)` method, which includes a container
`<div>` as well as a `<label>` element (if applicable).

You can also render each field without these structural elements through the
`[FormField->Field()](api:FormField)` method. To influence form rendering,
overriding these two methods is a good start.

In addition, most form fields are rendered through SilverStripe templates; for
example, `TextareaField` is rendered via
`framework/templates/forms/TextareaField.ss`.

These templates can be overridden globally by placing a template with the same
name in your `mysite` directory, or set on a form field instance via any of
these methods:

- FormField->setTemplate()
- FormField->setFieldHolderTemplate()
- FormField->setSmallFieldHolderTemplate()
 
<div class="hint" markdown='1'>
Caution: Not all FormFields consistently uses templates set by the above methods.
</div>

### Securing forms against Cross-Site Request Forgery (CSRF)

SilverStripe tries to protect users against *Cross-Site Request Forgery (CSRF)*
by adding a hidden *SecurityID* parameter to each form. See
[secure-development](/topics/security) for details.

In addition, you should limit forms to the intended HTTP verb (mostly `GET` or `POST`)
to further reduce attack exposure, by using `[api:Form->setStrictFormMethodCheck()]`.

	:::php
	$myForm->setFormMethod('POST');
	$myForm->setStrictFormMethodCheck(true);
	$myForm->setFormMethod('POST', true); // alternative short notation

### Remove existing fields

If you want to remove certain fields from your subclass:

	:::php
	class MyCustomForm extends MyForm {

		public function __construct($controller, $name) {
			parent::__construct($controller, $name);
			
			// remove a normal field
			$this->Fields()->removeByName('MyFieldName');
			
			// remove a field from a tab
			$this->Fields()->removeFieldFromTab('TabName', 'MyFieldName');
		}
	}


### Working with tabs

Adds a new text field called FavouriteColour next to the Content field in the CMS

	:::php
	$this->Fields()->addFieldToTab('Root.Content', new TextField('FavouriteColour'), 'Content');

## Form Validation

SilverStripe provides PHP form validation out of the box, but doesn't come with
any built-in JavaScript validation (the previously used `Validator.js` approach
has been deprecated).

### Required Fields

Validators are implemented as an argument to the `[api:Form]` constructor, and
are subclasses of the abstract `[api:Validator]` base class. The only
implementation that comes with SilverStripe is the `[api:RequiredFields]` class,
which ensures that fields are filled out when the form is submitted.

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

### Form Field Validation

Form fields are responsible for validating the data they process, through the
`[api:FormField->validate()]` method. There are many fields for different
purposes (see ["form field types"](/reference/form-field-types) for a full list).

### Adding your own validation messages

In many cases, you want to add PHP validation that is more complex than
validating the format or existence of a single form field input. For example,
you might want to have dependent validation on a postcode which depends on the
country you've selected in a different field.

There are two ways to go about this: attach a custom error message to a specific
field, or a generic message to the whole form.

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
				$form->addErrorMessage('Postcode', 'Need five digits for German postcodes', 'bad');
				return $this->redirectBack();
			}
			
			// Global validation error (not specific to form field)
			if($data['Country'] == 'IR' && isset($data['Postcode']) && $data['Postcode']) {
				$form->sessionMessage("Ireland doesn't have postcodes!", 'bad');
				return $this->redirectBack();
			}
			
			// continue normal processing...
		}
	}

### JavaScript Validation

Although there are no built-in JavaScript validation handlers in SilverStripe,
the `FormField` API is flexible enough to provide the information required in
order to plug in custom libraries.

#### HTML5 attributes

HTML5 specifies some built-in form validations
([source](http://www.w3.org/wiki/HTML5_form_additions)), which are evaluated by
modern browsers without any need for JavaScript. SilverStripe supports this by
allowing to set custom attributes on fields.

	:::php
	// Markup contains <input type="text" required />
	TextField::create('MyText')->setAttribute('required', true);
	
	// Markup contains <input type="url" pattern="https?://.+" />
	TextField::create('MyText')
		->setAttribute('type', 'url')
		->setAttribute('pattern', 'https?://.+')

#### HTML5 metadata

In addition, HTML5 elements can contain custom data attributes with the `data-`
prefix. These are general-purpose attributes, but can be used to hook in your
own validation.

	:::php
	// Validate a specific date format (in PHP)
	// Markup contains <input type="text" data-dateformat="dd.MM.yyyy" />
	DateField::create('MyDate')->setConfig('dateformat', 'dd.MM.yyyy');
	
	// Limit extensions on upload (in PHP)
	// Markup contains <input type="file" data-allowed-extensions="jpg,jpeg,gif" />
	$exts = array('jpg', 'jpeg', 'gif');
	$fileField = FileField::create('MyFile');
	$fileField->getValidator()->setAllowedExtensions($exts);
	$fileField->setAttribute('data-allowed-extensions', implode(',', $exts));

Note that these examples don't have any effect on the client as such, but are
just a starting point for custom validation with JavaScript.

### Model Validation

An alternative (or additional) approach to validation is to place it directly on
the model. SilverStripe provides a `[api:DataObject->validate()]` method for
this purpose. Refer to the
["datamodel" topic](/topics/datamodel#validation-and-constraints) for more information.

### Validation in the CMS

Since you're not creating the forms for editing CMS records, SilverStripe
provides you with a `getCMSValidator()` method on your models to return a
`[api:Validator]` instance.

	:::php
	class Page extends SiteTree {
		private static $db = array('MyRequiredField' => 'Text');
		
		public function getCMSValidator() {
			return new RequiredFields(array('MyRequiredField'));
		}
	}

### Subclassing Validator

To create your own validator, you need to subclass validator and define two methods:

* **javascript()** Should output a snippet of JavaScript that will get called
	to perform javascript validation.
* **php($data)** Should return true if the given data is valid, and call
	$this->validationError() if there were any errors.

## Related

* [Form Field Types](/reference/form-field-types)
* [MultiForm Module](http://silverstripe.org/multi-form-module)
* Model Validation with [api:DataObject->validate()]

## API Documentation

* `[api:Form]`
* `[api:FormField]`
* `[api:FieldList]`
* `[api:FormAction]`
