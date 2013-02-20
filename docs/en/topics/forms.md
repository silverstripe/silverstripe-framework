# Forms

## Introduction

Form is the base class of all forms in a sapphire application. Forms in your application can be created either by
instantiating the Form class itself, or by subclassing it. 

## Instantiating a form

Creating a form is a matter of defining a method to represent that form. This method should return a form object. The
constructor takes the following arguments:

*  `$controller`: This must be the controller that contains the form.
*  `$name`: This must be the name of the method on that controller that is called to return the form.  The first two
fields allow the form object to be re-created after submission.  **It's vital that they are properly set - if you ever
have problems with form action handler not working, check that these values are correct.**
*  `$fields`: A `[api:FieldSet]`s that make up the editable portion of the form.
*  `$actions`: A `[api:FieldSet]`s that make up the control portion of the form - the buttons at the bottom.
*  `$validator`: An optional `[api:Validator]` for more information.

Example: 

	:::php
	function MyCustomForm() {
		$fields = new FieldSet(
			new EmailField("Email"),
			new EncryptField("Password")
		);
		$actions = new FieldSet(new FormAction("login", "Log in"));
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
		function __construct($controller, $name) {
			$fields = new FieldSet(
				new EmailField("Email"),
				new EncryptedField("Password")
			);
			$actions = new FieldSet(new FormAction("login", "Log in"));
			
			parent::__construct($controller, $name, $fields, $actions);
		}
	}


The real difference, however, is that you can then define your controller methods within the form class itself.


## Form Field Types
		
There are many classes extending `[api:FormField]`. Some examples:
		
*  `[api:TextField]`
*  `[api:EmailField]`
*  `[api:NumericField]`
*  `[api:DateField]`
*  `[api:CheckboxField]`
*  `[api:DropdownField]`
*  `[api:OptionsetField]`
*  `[api:CheckboxSetField]`

Full overview at [form-field-types](/reference/form-field-types)

	
### Using Form Fields
			
To get these fields automatically rendered into a form element, all you need to do is create a new instance of the
class, and add it to the fieldset of the form. 
		
	:::php
	$form = new Form(
	        $controller = $this,
	        $name = "SignupForm",
	        $fields = new FieldSet(
	            new TextField(
	                $name = "FirstName", 
	                $title = "First name"
	            ),
	            new TextField("Surname"),
	            new EmailField("Email", "Email address"),
	         ), 
	        $actions = new FieldSet(
	            // List the action buttons here
	            new FormAction("signup", "Sign up")
	        ), 
	        $requiredFields = new RequiredFields(
	            // List the required fields here: "Email", "FirstName"
	        )
	);


You'll note some of the fields are optional.

Implementing the more complex fields requires extra arguments.

	:::php
	$form = new Form(
	        $controller = $this,
	        $name = "SignupForm",
	        $fields = new FieldSet(
	            // List the your fields here
	            new TextField(
	                $name = "FirstName", 
	                $title = "First name"
		), 
	            new TextField("Surname"),
	            new EmailField("Email", "Email address")
	            new DropdownField(
	                 $name = "Country",
	                 $title = "Country (if outside nz)",
	                 $source = Geoip::getCountryDropDown(),
	                 $value = Geoip::visitor_country()
		)
	        ), new FieldSet(
	            // List the action buttons here
	            new FormAction("signup", "Sign up")
	 
	        ), new RequiredFields(
	            // List the required fields here: "Email", "FirstName"
	        )
	);


##  Readonly

Readonly on a Form

	:::php
	$myForm->makeReadonly();


Readonly on a FieldSet

	:::php
	$myFieldSet->makeReadonly();


Readonly on a FormField

	:::php
	$myReadonlyField = $myField->transform(new ReadonlyTransformation());
	// shortcut
	$myReadonlyField = $myField->performReadonlyTransformation();


## Using a custom template

*Required Silverstripe 2.3 for some displayed functionality*

You can use a custom form template to render with, instead of *Form.ss*

It's recommended you only do this if you've got a lot of presentation text, graphics that surround the form fields. This
is better than defining those as *LiteralField* objects, as it doesn't clutter the data layer with presentation junk.

First of all, you need to create your form on it's own class, that way you can define a custom template using a `forTemplate()` method on your Form class.

	:::php
	class MyForm extends Form {
	
	   function __construct($controller, $name) {
	      $fields = new FieldSet(
	         new TextField('FirstName', 'First name'),
	         new EmailField('Email', 'Email address')
			);
	
	      $actions = new FieldSet(
	         new FormAction('submit', 'Submit')
	      );
	
			parent::__construct($controller, $name, $fields, $actions);
		}
		
	   function forTemplate() {
	      return $this->renderWith(array(
	         $this->class,
	         'Form'
	      ));
		}
		
	   function submit($data, $form) {
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
	         <label class="left" for="$FormName_FirstName">First name</label>
	         $dataFieldByName(FirstName)
			</div>
			
	      <div id="Email" class="field email">
	         <label class="left" for="$FormName_Email">Email</label>
	         $dataFieldByName(Email)
			</div>
			
	      $dataFieldByName(SecurityID)
		</fieldset>
		
		<% if Actions %>
		<div class="Actions">
	         <% control Actions %>$Field<% end_control %>
		</div>
		<% end_if %>
	</form>

 `$dataFieldByName(FirstName)` will return the form control contents of `Field()` for the particular field object, in
this case `TextField->Field()` or `EmailField->Field()` which returns an `<input>` element with specific markup
for the type of field. Pass in the name of the field as the first parameter, as done above, to render it into the 
template.

To find more methods, have a look at the `[api:Form]` class, as there is a lot of different methods of customising the form
templates, for example, you could use `<% control Fields %>` instead of specifying each field manually, as we've done
above.

### Securing forms against Cross-Site Request Forgery (CSRF)

SilverStripe tries to protect users against *Cross-Site Request Forgery (CSRF)* by adding a hidden *SecurityID*
parameter to each form. See [secure-development](/topics/security) for details.

### Remove existing fields

If you want to remove certain fields from your subclass:

	:::php
	class MyCustomForm extends MyForm {
		function __construct($controller, $name) {
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
	$fields->addFieldToTab('Root.Content.Main', new TextField('FavouriteColour'), 'Content');



## Related

*  [form-field-types](/reference/form-field-types)
*  `[api:FormField]` class
*  [multiform module](http://silverstripe.org/multi-form-module)

##  API Documentation

`[api:Form]`