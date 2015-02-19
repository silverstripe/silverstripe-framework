title: Introduction to Forms
summary: An introduction to creating a Form instance and handling submissions.

# Forms

The HTML `Form` is the most used way to interact with a user. SilverStripe provides classes to generate forms through 
the [api:Form] class, [api:FormField] instances to capture data and submissions through [api:FormAction].

<div class="notice" markdown="1">
See the [Forms Tutorial](../../tutorials/forms/) for a step by step process of creating a `Form`
</div>

## Creating a Form

Creating a [api:Form] has the following signature.

	:::php
	$form = new Form(
		$controller, // the Controller to render this form on 
		$name, // name of the method that returns this form on the controller
		FieldList $fields, // list of FormField instances 
		FieldList $actions, // list of FormAction instances
		$required // optional use of RequiredFields object
	);

In practice, this looks like:

**mysite/code/Page.php**

	:::php
	<?php

	class Page_Controller extends ContentController {
		
		private static $allowed_actions = array(
			'HelloForm'
		);
		
		public function HelloForm() {
			$fields = new FieldList(
				TextField::create('Name', 'Your Name')
			);

			$actions = new FieldList(
				FormAction::create("doSayHello")->setTitle("Say hello")
			);

			$required = new RequiredFields('Name')

			$form = new Form($this, 'HelloForm', $fields, $actions, $required);
	
			return $form;
		}
	
		public function doSayHello($data, Form $form) {
			$form->sessionMessage('Hello '. $data['Name'], 'success');

			return $this->redirectBack();
		}
	}

**mysite/templates/Page.ss**

	:::ss
	$HelloForm


<div class="info" markdown="1">
The examples above use `FormField::create()` instead of the  `new` operator (`new FormField()`). These are functionally 
equivalent, but allows PHP to chain operations like `setTitle()` without assigning the field instance to a temporary 
variable.
</div>

When constructing the `Form` instance (`new Form($controller, $name)`) both controller and name are required. The
`$controller` and `$name` are used to allow SilverStripe to calculate the origin of the `Form object`. When a user 
submits the `HelloForm` from your `contact-us` page the form submission will go to `contact-us/HelloForm` before any of
the [api:FormActions]. The URL is known as the `$controller` instance will know the 'contact-us' link and we provide 
`HelloForm` as the `$name` of the form. `$name` **needs** to match the method name.

Because the `HelloForm()` method will be the location the user is taken to, it needs to be handled like any other 
controller action. To grant it access through URLs, we add it to the `$allowed_actions` array.

	:::php
	private static $allowed_actions = array(
		'HelloForm'
	);

<div class="notice" markdown="1">
Form actions (`doSayHello`), on the other hand, should _not_ be included in `$allowed_actions`; these are handled 
separately through [api:Form::httpSubmission].
</div>


## Adding FormFields

Fields in a [api:Form] are represented as a single [api:FieldList] instance containing subclasses of [api:FormField]. 
Some common examples are [api:TextField] or [api:DropdownField]. 

	:::php
	TextField::create($name, $title, $value);

<div class="info" markdown='1'>
A list of the common FormField subclasses is available on the [Common Subclasses](fields/common_subclasses) page.
</div>

The fields are added to the [api:FieldList] `fields` property on the `Form` and can be modified at up to the point the 
`Form` is rendered.

	:::php
	$fields = new FieldList(
		TextField::create('Name'),
		EmailField::create('Email')
	);

	$form = new Form($controller, 'MethodName', $fields, ...);

	// or use `setFields`
	$form->setFields($fields);

	// to fetch the current fields..
	$fields = $form->getFields();

A field can be appended to the [api:FieldList].

	:::php
	$fields = $form->Fields();

	// add a field
	$fields->push(new TextField(..));

	// insert a field before another one
	$fields->insertBefore(new TextField(..), 'Email');

	// insert a field after another one
	$fields->insertAfter(new TextField(..), 'Name');

Fields can be fetched after they have been added in.
	
	:::php
	$email = $form->Fields()->dataFieldByName('Email');
	$email->setTitle('Your Email Address');

Fields can be removed from the form.
	
	:::php
	$form->getFields()->removeByName('Email');

<div class="alert" markdown="1">
Forms can be tabbed (such as the CMS interface). In these cases, there are additional functions such as `addFieldToTab`
and `removeFieldByTab` to ensure the fields are on the correct interface. See [Tabbed Forms](tabbed_forms) for more 
information on the CMS interface.
</div>

## Modifying FormFields

Each [api:FormField] subclass has a number of methods you can call on it to customize its' behavior or HTML markup. The
default `FormField` object has several methods for doing common operations. 

<div class="notice" markdown="1">
Most of the `set` operations will return the object back so methods can be chained.
</div>

	:::php
	$field = new TextField(..);

	$field
		->setMaxLength(100)
		->setAttribute('placeholder', 'Enter a value..')
		->setTitle('');

### Custom Templates

The [api:Form] HTML markup and each of the [api:FormField] instances are rendered into templates. You can provide custom
templates by using the `setTemplate` method on either the `Form` or `FormField`. For more details on providing custom 
templates see [Form Templates](form_templates)

	:::php
	$form = new Form(..);

	$form->setTemplate('CustomForm');

	// or, for a FormField
	$field = new TextField(..);

	$field->setTemplate('CustomTextField');
	$field->setFieldHolderTemplate('CustomTextField_Holder');

## Adding FormActions

[api:FormAction] objects are displayed at the bottom of the `Form` in the form of a `button` or `input` tag. When a
user presses the button, the form is submitted to the corresponding method.

	:::php
	FormAction::create($action, $title);

As with [api:FormField], the actions for a `Form` are stored within a [api:FieldList] instance in the `actions` property
on the form.
	
	:::php
	public function MyForm() {
		$fields = new FieldList(..);

		$actions = new FieldList(
			FormAction::create('doSubmitForm', 'Submit')
		);

		$form = new Form($controller, 'MyForm', $fields, $actions);

		// Get the actions
		$actions = $form->Actions();

		// As actions is a FieldList, push, insertBefore, removeByName and other
		// methods described for `Fields` also work for actions.

		$actions->push(
			FormAction::create('doSecondaryFormAction', 'Another Button')
		);

		$actions->removeByName('doSubmitForm');
		$form->setActions($actions);

		return $form
	}

	public function doSubmitForm($data, $form) {
		//
	}

	public function doSecondaryFormAction($data, $form) {
		//
	}

The first `$action` argument for creating a `FormAction` is the name of the method to invoke when submitting the form 
with the particular button. In the previous example, clicking the 'Another Button' would invoke the 
`doSecondaryFormAction` method. This action can be defined (in order) on either:

 * One of the `FormField` instances.
 * The `Form` instance.
 * The `Controller` instance.

<div class="notice" markdown="1">
If the `$action` method cannot be found on any of those or is marked as `private` or `protected`, an error will be 
thrown.
</div>

The `$action` method takes two arguments:

 * `$data` an array containing the values of the form mapped from `$name => $value`
 * `$form` the submitted [api:Form] instance.

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
			// Submitted data is available as a map.
			echo $data['Name'];
			echo $data['Email'];

			// You can also fetch the value from the field.
			echo $form->Fields()->dataFieldByName('Email')->Value();

			// Using the Form instance you can get / set status such as error messages.
			$form->sessionMessage("Successful!", 'good');

			// After dealing with the data you can redirect the user back.
			return $this->redirectBack();
		}
	}

## Validation

Form validation is handled by the [api:Validator] class and the `validator` property on the `Form` object. The validator 
is provided with a name of each of the [api:FormField]s to validate and each `FormField` instance is responsible for 
validating its' own data value. 

For more information, see the [Form Validation](validation) documentation.

	:::php
	$validator = new RequiredFields(array(
		'Name', 'Email'
	));

	$form = new Form($this, 'MyForm', $fields, $actions, $validator);

## API Documentation

* [api:Form]
* [api:FormField]
* [api:FieldList]
* [api:FormAction]
