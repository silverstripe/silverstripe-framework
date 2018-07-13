title: Form Validation
summary: Validate form data through the server side validation API.

# Form Validation

SilverStripe provides server-side form validation out of the box through the [Validator](api:SilverStripe\Forms\Validator) class and its' child class
[RequiredFields](api:SilverStripe\Forms\RequiredFields). A single `Validator` instance is set on each `Form`. Validators are implemented as an argument to 
the [Form](api:SilverStripe\Forms\Form) constructor or through the function `setValidator`.

```php
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\RequiredFields;

class PageController extends ContentController
{
    private static $allowed_actions = [
        'MyForm'
    ];

    public function MyForm()
    {
        $fields = new FieldList(
            TextField::create('Name'),
            EmailField::create('Email')
        );

        $actions = new FieldList(
            FormAction::create('doSubmitForm', 'Submit')
        );

        // the fields 'Name' and 'Email' are required.
        $required = new RequiredFields([
            'Name', 'Email'
        ]);

        // $required can be set as an argument
        $form = new Form($controller, 'MyForm', $fields, $actions, $required);

        // Or, through a setter.
        $form->setValidator($required);

        return $form;
    }

    public function doSubmitForm($data, $form)
    {
        //..
    }
}

```

In this example we will be required to input a value for `Name` and a valid email address for `Email` before the 
`doSubmitForm` method is called.

<div class="info" markdown="1">
Each individual [FormField](api:SilverStripe\Forms\FormField) instance is responsible for validating the submitted content through the 
[FormField::validate()](api:SilverStripe\Forms\FormField::validate()) method. By default, this just checks the value exists. Fields like `EmailField` override 
`validate` to check for a specific format.
</div>

Subclasses of `FormField` can define their own version of `validate` to provide custom validation rules such as the 
above example with the `Email` validation. The `validate` method on `FormField` takes a single argument of the current 
`Validator` instance.

```php
public function validate($validator)
{
    if ((int) $this->Value() === 10) {
        $validator->validationError($this->Name(), 'This value cannot be 10');
        return false;
    }

    return true;
}
```

The `validate` method should return `true` if the value passes any validation and `false` if SilverStripe should trigger
a validation error on the page. In addition a useful error message must be set on the given validator.

<div class="notice" markdown="1">
You can also override the entire `Form` validation by subclassing `Form` and defining a `validate` method on the form.
</div>

Say we need a custom `FormField` which requires the user input a value in a `TextField` between 2 and 5. There would be
two ways to go about this:

A custom `FormField` which handles the validation. This means the `FormField` can be reused throughout the site and have
the same validation logic applied to it throughout.

**app/code/CustomNumberField.php**

```php
use SilverStripe\Forms\TextField;

class CustomNumberField extends TextField
{
    public function validate($validator)
    {
        if (!is_numeric($this->value)) {
            $validator->validationError(
                $this->name, 'Not a number. This must be between 2 and 5', 'validation', false
            );
            
            return false;
        } elseif ($this->value > 5 || $this->value < 2) {
            $validator->validationError(
                $this->name, 'Your number must be between 2 and 5', 'validation', false
            );

            return false;
        }

        return true;
    }
}
```

Or, an alternative approach to the custom class is to define the behavior inside the Form's action method. This is less
reusable and would not be possible within the `CMS` or other automated `UI` but does not rely on creating custom 
`FormField` classes.
    
```php
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Member;

class Page_Controller extends ContentController
{
    private static $allowed_actions = [
        'MyForm'
    ];

    public function MyForm()
    {
        $fields = new FieldList(
            TextField::create('Name'),
            EmailField::create('Email')
        );

        $actions = new FieldList(
            FormAction::create('doSubmitForm', 'Submit')
        );

        $form = new Form($controller, 'MyForm', $fields, $actions);

        return $form;
    }

    public function doSubmitForm($data, $form)
    {
        // At this point, RequiredFields->isValid() will have been called already,
        // so we can assume that the values exist. Say we want to make sure that email hasn't already been used.
        
        $check = Member::get()->filter('Email', $data['Email'])->first();

        if ($check) {
            $form->addErrorMessage('Email', 'This email already exists', 'bad');

            return $this->redirectBack();
        }


        $form->sessionMessage('You have been added to our mailing list', 'good');
        
        return $this->redirectBack();
    }
}
```

## Exempt validation actions

In some cases you might need to disable validation for specific actions. E.g. actions which discard submitted
data may not need to check the validity of the posted content.

You can disable validation on individual using one of two methods:

```php
$actions = new SilverStripe\Forms\FieldList(
    $action = SilverStripe\Forms\FormAction::create('doSubmitForm', 'Submit')
);
$form = new SilverStripe\Forms\Form($controller, 'MyForm', $fields, $actions);

// Disable actions on the form action themselves
$action->setValidationExempt(true);

// Alternatively, you can whitelist individual actions on the form object by name
$form->setValidationExemptActions(['doSubmitForm']);
```

## Server-side validation messages

If a `FormField` fails to pass `validate()` the default error message is returned.

```
'$Name' is required
```

Use `setCustomValidationMessage` to provide a custom message.

```php
$field = new TextField(/* .. */);
$field->setCustomValidationMessage('Whoops, looks like you have missed me!');
```

## JavaScript validation

Although there are no built-in JavaScript validation handlers in SilverStripe, the `FormField` API is flexible enough 
to provide the information required in order to plug in custom libraries like [Parsley.js](http://parsleyjs.org/) or 
[jQuery.Validate](http://jqueryvalidation.org/). Most of these libraries work on HTML `data-` attributes or special 
classes added to each input. For Parsley we can structure the form like.

```php
$form = new SilverStripe\Forms\Form(/* .. */);
$form->setAttribute('data-parsley-validate', true);

$field = $fields->dataFieldByName('Name');

$field->setAttribute('required', true);
$field->setAttribute('data-parsley-mincheck', '2');
```

## Model Validation

An alternative (or additional) approach to validation is to place it directly on the database model. SilverStripe 
provides a [DataObject::validate()](api:SilverStripe\ORM\DataObject::validate()) method to validate data at the model level. See 
[Data Model Validation](../model/validation).

## Form action validation

At times it's not possible for all validation or recoverable errors to be pre-determined in advance of form
submission, such as those generated by the form [Validator](api:SilverStripe\Forms\Validator) object. Sometimes errors may occur within form
action methods, and it is necessary to display errors on the form after initial validation has been performed.

In this case you may throw a [ValidationException](api:SilverStripe\ORM\ValidationException) object within your handler, optionally passing it an
error message, or a [ValidationResult](api:SilverStripe\ORM\ValidationResult) object containing the list of errors you wish to display.

E.g.

```php
use SilverStripe\Control\Controller;
use SilverStripe\ORM\ValidationException;

class MyController extends Controller
{
    public function doSave($data, $form)
    {
        $success = $this->sendEmail($data);
        
        // Example error handling
        if (!$success) {
            throw new ValidationException('Sorry, we could not email to that address');
        }
        
        // If success
        return $this->redirect($this->Link('success'));
    }
}
```

### Validation in the CMS

In the CMS, we're not creating the forms for editing CMS records. The `Form` instance is generated for us so we cannot
call `setValidator` easily. However, a `DataObject` can provide its' own `Validator` instance through the 
`getCMSValidator()` method. The CMS interfaces such as [LeftAndMain](api:SilverStripe\Admin\LeftAndMain), [ModelAdmin](api:SilverStripe\Admin\ModelAdmin) and [GridField](api:SilverStripe\Forms\GridField\GridField) will 
respect the provided `Validator` and handle displaying error and success responses to the user. 

<div class="info" markdown="1">
Again, custom error messages can be provided through the `FormField`
</div>

```php
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\RequiredFields;

class Page extends SiteTree
{
    private static $db = [
        'MyRequiredField' => 'Text'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Main', 
            TextField::create('MyRequiredField')->setCustomValidationMessage('You missed me.')
        );
    }
    
    public function getCMSValidator()
    {
        return new RequiredFields([
            'MyRequiredField'
        ]);
    }
}

```

## Related Lessons
* [Intoduction to frontend forms](https://www.silverstripe.org/learn/lessons/v4/introduction-to-frontend-forms-1)


## API Documentation

 * [RequiredFields](api:SilverStripe\Forms\RequiredFields)
 * [Validator](api:SilverStripe\Forms\Validator)
