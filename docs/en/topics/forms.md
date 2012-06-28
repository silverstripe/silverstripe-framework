# Forms

## Introduction

Form is the base class of all forms in a SilverStripe application. Forms in your application can be created either by
instantiating the Form class itself, or by subclassing it. 

## Instantiating a form

Creating a form is a matter of defining a method to represent that form.  This method should return a form object.  The
constructor takes the following arguments:

*  `$controller`: This must be the controller that contains the form.
*  `$name`: This must be the name of the method on that controller that is called to return the form.  The first two
fields allow the form object to be re-created after submission.  **It's vital that they are properly set - if you ever
have problems with form action handler not working, check that these values are correct.**
*  `$fields`: A `[api:FieldList]` containing `[api:FormField]` instances make up fields in the form.
*  `$actions`: A `[api:FieldList]` containing the `[api:FormAction]` objects - the buttons at the bottom.
*  `$validator`: An optional `[api:Validator]` for more information.

Example: 

	:::php
	public function MyCustomForm() {
		$fields = new FieldList(
			new EmailField("Email"),
			new EncryptField("Password")
		);
		$actions = new FieldList(new FormAction("login", "Log in"));
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
				new EmailField("Email"),
				new EncryptedField("Password")
			);
			$actions = new FieldList(new FormAction("login", "Log in"));
			
			parent::__construct($controller, $name, $fields, $actions);
		}
	}


The real difference, however, is that you can then define your controller methods within the form class itself.


## Form Field Types

There are many classes extending `[api:FormField]`,
there's a full overview at [form-field-types](/reference/form-field-types)


### Using Form Fields

To get these fields automatically rendered into a form element, 
all you need to do is create a new instance of the
class, and add it to the fieldlist of the form. 

	:::php
	$form = new Form(
		$this, // controller
		"SignupForm", // form name
		new FieldList( // fields
			TextField::create("FirstName")
				->setTitle('First name')
			TextField::create("Surname")
				->setTitle('Last name')
				->setMaxLength(50),
			EmailField::create("Email")
				->setTitle("Email address")
				->setAttribute('type', 'email')
		), 
		new FieldList( // actions
			FormAction::create("signup")->setTitle("Sign up")
		), 
		new RequiredFields( // validation
			"Email", "FirstName"
		)
	);

You'll notice that we've used a new notation for creating form fields,
using `create()` instead of the `new` operator. These are functionally equivalent,
but allows PHP to chain operations like `setTitle()` without assigning
the field instance to a temporary variable.

##  Readonly

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
					 new TextField('FirstName', 'First name'),
					 new EmailField('Email', 'Email address')
				);
	
				$actions = new FieldList(
					 new FormAction('submit', 'Submit')
				);
	
				parent::__construct($controller, $name, $fields, $actions);
		 }
	
		 public function forTemplate() {
				return $this->renderWith(array(
					 $this->class,
					 'Form'
				));
		 }
	
		 public function submit($data, $form) {
				// do stuff here
		 }
	
	}

`forTemplate()` tells the `[api:Form]` class to render with a template of return value of `$this->class`, which in this case
is *MyForm*, the name of the class. If the template doesn't exist, then it falls back to using Form.ss.

*MyForm.ss* should then be placed into your *templates/Includes* directory for your project. Here is an example of
basic customisation:

	:::ss
	<form $FormAttributes>
		 <% if Message %>
				<p id="{$FormName}_error" class="message $MessageType">$Message</p>
		 <% else %>
				<p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
		 <% end_if %>
		 
		 <fieldset>
				<div id="FirstName" class="field text">
					 <label class="left" for="{$FormName}_FirstName">First name</label>
					 $dataFieldByName(FirstName)
				</div>
	
				<div id="Email" class="field email">
					 <label class="left" for="{$FormName}_Email">Email</label>
					 $dataFieldByName(Email)
				</div>
	
				$dataFieldByName(SecurityID)
		 </fieldset>
	
		 <% if Actions %>
				<div class="Actions">
					 <% loop Actions %>$Field<% end_loop %>
				</div>
		 <% end_if %>
	</form>

 `$dataFieldByName(FirstName)` will return the form control contents of `Field()` for the particular field object, in
this case `TextField->Field()` or `EmailField->Field()` which returns an `<input>` element with specific markup
for the type of field. Pass in the name of the field as the first parameter, as done above, to render it into the
template.

To find more methods, have a look at the `[api:Form]` class, as there is a lot of different methods of customising the form
templates, for example, you could use `<% loop Fields %>` instead of specifying each field manually, as we've done
above.

### Custom form field templates

The easiest way to customize form fields is adding CSS classes and additional attributes.

	:::php
	$field = new TextField('MyText');
	$field->addExtraClass('largeText');
	$field->setAttribute('data-validation-regex', '[\d]*');

	// Field() renders as:
	// <input type="text" class="largeText" id="Form_Form_TextField" name="TextField" data-validation-regex="[\d]*">

Each form field is rendered into a form via the `[FieldHolder()](api:FormField->FieldHolder())` method,
which includes a container `<div>` as well as a `<label>` element (if applicable).
You can also render each field without these structural elements through the `[Field()](api:FormField->Field())` method.
In order to influence the form rendering, overloading these two methods is a good start.

In addition, most form fields are rendered through SilverStripe templates, e.g. `TextareaField` is rendered via `framework/templates/forms/TextareaField.ss`.
These templates can be overwritten globally by placing a template with the same name in your `mysite` directory,
or set on a form field instance via `[setTemplate()](api:FormField->setTemplate())` and `[setFieldHolderTemplate()](api:FormField->setFieldHolderTemplate())`.

### Securing forms against Cross-Site Request Forgery (CSRF)

SilverStripe tries to protect users against *Cross-Site Request Forgery (CSRF)* by adding a hidden *SecurityID*
parameter to each form. See [secure-development](/topics/security) for details.

### Remove existing fields

If you want to remove certain fields from your subclass:

	:::php
	class MyCustomForm extends MyForm {
		public function __construct($controller, $name) {
			parent::__construct($controller, $name);
			
			// remove a normal field
			$this->fields->removeByName('MyFieldName');
			
			// remove a field from a tab
			$this->fields->removeFieldFromTab('TabName', 'MyFieldName');
		}
	}


### Working with tabs

Adds a new text field called FavouriteColour next to the Content field in the CMS

	:::php
	$fields->addFieldToTab('Root.Content', new TextField('FavouriteColour'), 'Content');



## Related

*  [Form Field Types](/reference/form-field-types)
*  [MultiForm Module](http://silverstripe.org/multi-form-module)

##  API Documentation

* `[api:Form]`
* `[api:FormField]`
* `[api:FieldList]`
* `[api:FormAction]`
