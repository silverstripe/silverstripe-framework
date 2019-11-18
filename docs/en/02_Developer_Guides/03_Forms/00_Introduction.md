---
title: Introduction to Forms
summary: An introduction to creating a Form instance and handling submissions.
iconBrand: wpforms
---

# Forms

The HTML `Form` is the most used way to interact with a user. SilverStripe provides classes to generate forms through 
the [Form](api:SilverStripe\Forms\Form) class, [FormField](api:SilverStripe\Forms\FormField) instances to capture data and submissions through [FormAction](api:SilverStripe\Forms\FormAction).

[notice]
See the [Introduction to frontend forms](https://www.silverstripe.org/learn/lessons/v4/introduction-to-frontend-forms-1) lesson for a step by step process of creating a `Form`
[/notice]

## Creating a Form

Creating a [Form](api:SilverStripe\Forms\Form) has the following signature.


```php
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;

$form = new Form(
    $controller, // the Controller to render this form on 
    $name, // name of the method that returns this form on the controller
    FieldList $fields, // list of FormField instances 
    FieldList $actions, // list of FormAction instances
    $required // optional use of RequiredFields object
);
```

In practice, this looks like:

**app/code/PageController.php**

```php
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;

class PageController extends ContentController
{   
    private static $allowed_actions = [
        'HelloForm'
    ];
    
    public function HelloForm()
    {
        $fields = new FieldList(
            TextField::create('Name', 'Your Name')
        );

        $actions = new FieldList(
            FormAction::create('doSayHello')->setTitle('Say hello')
        );

        $required = new RequiredFields('Name');

        $form = new Form($this, 'HelloForm', $fields, $actions, $required);

        return $form;
    }

    public function doSayHello($data, Form $form)
    {
        $form->sessionMessage('Hello ' . $data['Name'], 'success');

        return $this->redirectBack();
    }
}

```

**app/templates/Page.ss**

```ss
$HelloForm
```

[info]
The examples above use `FormField::create()` instead of the  `new` operator (`new FormField()`). These are functionally 
equivalent, but allows PHP to chain operations like `setTitle()` without assigning the field instance to a temporary 
variable.
[/info]

When constructing the `Form` instance (`new Form($controller, $name)`) both controller and name are required. The
`$controller` and `$name` are used to allow SilverStripe to calculate the origin of the `Form object`. When a user 
submits the `HelloForm` from your `contact-us` page the form submission will go to `contact-us/HelloForm` before any of
the [FormAction](api:SilverStripe\Forms\FormAction). The URL is known as the `$controller` instance will know the 'contact-us' link and we provide 
`HelloForm` as the `$name` of the form. `$name` **needs** to match the method name.

Because the `HelloForm()` method will be the location the user is taken to, it needs to be handled like any other 
controller action. To grant it access through URLs, we add it to the `$allowed_actions` array.

```php
private static $allowed_actions = [
    'HelloForm'
];

```

[notice]
Form actions (`doSayHello`), on the other hand, should _not_ be included in `$allowed_actions`; these are handled 
separately through [Form::httpSubmission()](api:SilverStripe\Forms\Form::httpSubmission()).
[/notice]


## Adding FormFields

Fields in a [Form](api:SilverStripe\Forms\Form) are represented as a single [FieldList](api:SilverStripe\Forms\FieldList) instance containing subclasses of [FormField](api:SilverStripe\Forms\FormField). 
Some common examples are [TextField](api:SilverStripe\Forms\TextField) or [DropdownField](api:SilverStripe\Forms\DropdownField). 

```php
SilverStripe\Forms\TextField::create($name, $title, $value);
```

[info]
A list of the common FormField subclasses is available on the [Common Subclasses](field_types/common_subclasses/) page.
[/info]

The fields are added to the [FieldList](api:SilverStripe\Forms\FieldList) `fields` property on the `Form` and can be modified at up to the point the 
`Form` is rendered.

```php
$fields = new FieldList(
    TextField::create('Name'),
    EmailField::create('Email')
);

$form = new Form($controller, 'MethodName', $fields, ...);

// or use `setFields`
$form->setFields($fields);

// to fetch the current fields..
$fields = $form->getFields();
```

A field can be appended to the [FieldList](api:SilverStripe\Forms\FieldList).

```php
$fields = $form->Fields();

// add a field
$fields->push(TextField::create(/* ... */));

// insert a field before another one
$fields->insertBefore(TextField::create(/* ... */), 'Email');

// insert a field after another one
$fields->insertAfter(TextField::create(/* ... */), 'Name');

// insert a tab before the main content tab (used to position tabs in the CMS)
$fields->insertBefore(Tab::create(/* ... */), 'Main');
// Note: you need to create and position the new tab prior to adding fields via addFieldToTab()
```

Fields can be fetched after they have been added in.

```php
$email = $form->Fields()->dataFieldByName('Email');
$email->setTitle('Your Email Address');
```

Fields can be removed from the form.
    
```php
$form->getFields()->removeByName('Email');
```

[alert]
Forms can be tabbed (such as the CMS interface). In these cases, there are additional functions such as `addFieldToTab`
and `removeFieldByTab` to ensure the fields are on the correct interface. See [Tabbed Forms](tabbed_forms) for more 
information on the CMS interface.
[/alert]

## Modifying FormFields

Each [FormField](api:SilverStripe\Forms\FormField) subclass has a number of methods you can call on it to customise its' behavior or HTML markup. The
default `FormField` object has several methods for doing common operations. 

[notice]
Most of the `set` operations will return the object back so methods can be chained.
[/notice]

```php
$field = new TextField(..);

$field
    ->setMaxLength(100)
    ->setAttribute('placeholder', 'Enter a value..')
    ->setTitle('');
```

### Custom Templates

The [Form](api:SilverStripe\Forms\Form) HTML markup and each of the [FormField](api:SilverStripe\Forms\FormField) instances are rendered into templates. You can provide custom
templates by using the `setTemplate` method on either the `Form` or `FormField`. For more details on providing custom 
templates see [Form Templates](form_templates)

```php
$form = new Form(..);

$form->setTemplate('CustomForm');

// or, for a FormField
$field = new TextField(..);

$field->setTemplate('CustomTextField');
$field->setFieldHolderTemplate('CustomTextField_Holder');
```

## Adding FormActions

[FormAction](api:SilverStripe\Forms\FormAction) objects are displayed at the bottom of the `Form` in the form of a `button` or `input` tag. When a
user presses the button, the form is submitted to the corresponding method.

```php
FormAction::create($action, $title);
```

As with [FormField](api:SilverStripe\Forms\FormField), the actions for a `Form` are stored within a [FieldList](api:SilverStripe\Forms\FieldList) instance in the `actions` property
on the form.
    
```php
public function MyForm()
{
    $fields = new FieldList(/* .. */);

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

public function doSubmitForm($data, $form)
{
    //
}

public function doSecondaryFormAction($data, $form)
{
    //
}
```

The first `$action` argument for creating a `FormAction` is the name of the method to invoke when submitting the form 
with the particular button. In the previous example, clicking the 'Another Button' would invoke the 
`doSecondaryFormAction` method. This action can be defined (in order) on either:

 * One of the `FormField` instances.
 * The `Form` instance.
 * The `Controller` instance.

[notice]
If the `$action` method cannot be found on any of those or is marked as `private` or `protected`, an error will be 
thrown.
[/notice]

The `$action` method takes two arguments:

 * `$data` an array containing the values of the form mapped from `$name => $value`
 * `$form` the submitted [Form](api:SilverStripe\Forms\Form) instance.

```php
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;

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

        $form = new Form($controller, 'MyForm', $fields, $actions);

        return $form
    }

    public function doSubmitForm($data, $form)
    {
        // Submitted data is available as a map.
        echo $data['Name'];
        echo $data['Email'];

        // You can also fetch the value from the field.
        echo $form->Fields()->dataFieldByName('Email')->Value();

        // Using the Form instance you can get / set status such as error messages.
        $form->sessionMessage('Successful!', 'good');

        // After dealing with the data you can redirect the user back.
        return $this->redirectBack();
    }
}

```

## Validation

Form validation is handled by the [Validator](api:SilverStripe\Forms\Validator) class and the `validator` property on the `Form` object. The validator 
is provided with a name of each of the [FormField](api:SilverStripe\Forms\FormField)s to validate and each `FormField` instance is responsible for 
validating its' own data value. 

For more information, see the [Form Validation](validation) documentation.

```php
$validator = new SilverStripe\Forms\RequiredFields([
    'Name',
    'Email'
]);

$form = new Form($this, 'MyForm', $fields, $actions, $validator);
```

## Related Lessons
* [Introduction to frontend forms](https://www.silverstripe.org/learn/lessons/v4/introduction-to-frontend-forms-1)

## API Documentation

* [Form](api:SilverStripe\Forms\Form)
* [FormField](api:SilverStripe\Forms\FormField)
* [FieldList](api:SilverStripe\Forms\FieldList)
* [FormAction](api:SilverStripe\Forms\FormAction)
