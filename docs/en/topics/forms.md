# Forms

HTML forms are in practice the most used way to communicate with a browser.
SilverStripe provides classes to generate and handle the actions and data from a
form.

## Overview

A fully implemented form in SilverStripe includes a couple of classes that
individually have separate concerns.

 * Controller - Takes care of assembling the form and receiving data from it.
 * Form - Holds sets of fields, actions and validators.
 * FormField  - Fields that receive data or displays them, e.g input fields.
 * FormActions - Often submit buttons that executes actions.
 * Validators - Validate the whole form, see [Form validation](form-validation.md) topic for more information.

Depending on your needs you can customize and override any of the above classes,
however the defaults are often sufficient.

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

Since forms need a URL, the `HelloForm()` method needs to be handled like any
other controller action. In order to whitelist its access through URLs, we add
it to the `$allowed_actions` array.

Form actions ("doSayHello") on the other hand should NOT be included here, these
are handled separately through `Form->httpSubmission()`.

You can control access on form actions either by conditionally removing a
`FormAction` from the form construction, or by defining `$allowed_actions` in
your own `Form` class (more information in the
["controllers" topic](/topics/controllers)).
	
**Page.ss**

	:::ss
	<!-- place where you would like the form to show up -->
	<div>$HelloForm</div>

<div class="warning" markdown='1'>
Be sure to add the Form name 'HelloForm' to the Controller::$allowed_actions()
to be sure that form submissions get through to the correct action.
</div>

<div class="notice" markdown='1'>
You'll notice that we've used a new notation for creating form fields, using `create()` instead of the `new` operator. 
These are functionally equivalent, but allows PHP to chain operations like `setTitle()` without assigning the field 
instance to a temporary variable. For in-depth information on the create syntax, see the [Injector](/reference/injector) 
documentation or the API documentation for `[api:Object]`::create().
</div>

## The Form

Form is the base class of all forms in a SilverStripe application. Forms in your
application can be created either by instantiating the Form class itself, or by
subclassing it.

### Instantiating a form

Creating a form is a matter of defining a method to represent that form. This
method should return a form object. The constructor takes the following
arguments:

*  `$controller`: This must be and instance of the controller that contains the form, often `$this`.
*  `$name`: This must be the name of the method on that controller that is called to return the form.  The first two
fields allow the form object to be re-created after submission.  **It's vital that they are properly set - if you ever
have problems with form action handler not working, check that these values are correct.**
*  `$fields`: A `[api:FieldList]` containing `[api:FormField]` instances make up fields in the form.
*  `$actions`: A `[api:FieldList]` containing the `[api:FormAction]` objects - the buttons at the bottom.
*  `$validator`: An optional `[api:Validator]` for validation of the form.

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

It's the responsibility of your subclass' constructor to call 

	:::php
	parent::__construct()

with the right parameters.  You may choose to take $fields and $actions as arguments if you wish, but $controller and
$name must be passed - their values depend on where the form is instantiated.

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


The real difference, however, is that you can then define your controller methods within the form class itself. This 
means that the form takes responsibilities from the controller and manage how to parse and use the form 
data.

**Page.php**

	:::php
	class Page_Controller extends ContentController {
		
		private static $allowed_actions = array(
			'HelloForm',
		);
		
		// Template method
		public function HelloForm() {
			return new MyForm($this, 'MyCustomForm');
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

##  Readonly

You can turn a form or individual fields into a readonly version. This is handy
in the case of confirmation pages or when certain fields can be edited due to
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

It's recommended you only do this if you've got a lot of presentation text, graphics that surround the form fields. This
is better than defining those as *LiteralField* objects, as it doesn't clutter the data layer with presentation junk.

First of all, you need to create your form on it's own class, that way you can define a custom template using a `forTemplate()` method on your Form class.

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

*MyForm.ss* should then be placed into your *templates/Includes* directory for
your project. Here is an example of basic customization:

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
			</div>
			
			<div id="Email" class="field password">
				<label class="left" for="{$FormName}_Password">Password</label>
				$Fields.dataFieldByName(Password)
			</div>
			
			$Fields.dataFieldByName(SecurityID)
		</fieldset>
		
		<% if $Actions %>
		<div class="Actions">
			<% loop $Actions %>$Field<% end_loop %>
		</div>
		<% end_if %>
	</form>

`$Fields.dataFieldByName(FirstName)` will return the form control contents of `Field()` for the particular field object,
in this case `EmailField->Field()` or `PasswordField->Field()` which returns an `<input>` element with specific markup 
for the type of field. Pass in the name of the field as the first parameter, as done above, to render it into the 
template.

To find more methods, have a look at the `[api:Form]` class and `[api:FieldList]` class as there is a lot of different 
methods of customising the form templates. An example is that you could use `<% loop $Fields %>` instead of specifying 
each field manually, as we've done above.

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
`[FormField->Field()](api:FormField)` method. In order to influence the form
rendering, overloading these two methods is a good start.

In addition, most form fields are rendered through SilverStripe templates, e.g.
`TextareaField` is rendered via `framework/templates/forms/TextareaField.ss`.

These templates can be overwritten globally by placing a template with the same
name in your `mysite` directory, or set on a form field instance via anyone of
these methods:

 - FormField->setTemplate()
 - FormField->setFieldHolderTemplate()
 - FormField->getSmallFieldHolderTemplate()
 
<div class="hint" markdown='1'>
Caution: Not all FormFields consistently uses templates set by the above methods.
</div>

### Securing forms against Cross-Site Request Forgery (CSRF)

SilverStripe tries to protect users against *Cross-Site Request Forgery (CSRF)*
by adding a hidden *SecurityID* parameter to each form. See
[secure-development](/topics/security) for details.

In addition, you should limit forms to the intended HTTP verb (mostly `GET` or `POST`)
to further reduce attack surface, by using `[api:Form->setStrictFormMethodCheck()]`.

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

## Related

*  [Form Field Types](/reference/form-field-types)
*  [MultiForm Module](http://silverstripe.org/multi-form-module)

##  API Documentation

* `[api:Form]`
* `[api:FormField]`
* `[api:FieldList]`
* `[api:FormAction]`
