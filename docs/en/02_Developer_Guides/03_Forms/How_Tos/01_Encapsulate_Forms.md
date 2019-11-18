---
title: How to Encapsulate Forms
summary: Learn how to move a form from a controller into its own class definition.
iconBrand: wpforms
---

# How to Encapsulate Forms

Form definitions can often get long, complex and often end up cluttering up a `Controller` definition. We may also want
to reuse the `Form` across multiple `Controller` classes rather than just one. A nice way to encapsulate the logic and 
code for a `Form` is to create it as a subclass to `Form`. Let's look at a example of a `Form` which is on our 
`Controller` but would be better written as a subclass.

**app/code/Page.php**


```php
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\FormAction;
use SilverStripe\CMS\Controllers\ContentController;

class PageController extends ContentController 
{
    
    public function SearchForm() 
    {
        $fields = new FieldList(
            HeaderField::create('Header', 'Step 1. Basics'),
            OptionsetField::create('Type', '', [
                'foo' => 'Search Foo',
                'bar' => 'Search Bar',
                'baz' => 'Search Baz'
            ]),

            CompositeField::create(
                HeaderField::create('Header2', 'Step 2. Advanced '),
                CheckboxSetField::create('Foo', 'Select Option', [
                    'qux' => 'Search Qux'
                ]),

                CheckboxSetField::create('Category', 'Category', [
                    'Foo' => 'Foo',
                    'Bar' => 'Bar'
                ]),

                NumericField::create('Minimum', 'Minimum'),
                NumericField::create('Maximum', 'Maximum')
            )
        );
        
        $actions = new FieldList(
            FormAction::create('doSearchForm', 'Search')
        );
        
        $required = new RequiredFields([
            'Type'
        ]);

        $form = new Form($this, 'SearchForm', $fields, $actions, $required);
        $form->setFormMethod('GET');
        
        $form->addExtraClass('no-action-styles');
        $form->disableSecurityToken();
        $form->loadDataFrom($_REQUEST);
    
        return $form;
    }
}

```

Now that is a bit of code to include on our controller and generally makes the file look much more complex than it 
should be. Good practice would be to move this to a subclass and create a new instance for your particular controller.

**app/code/forms/SearchForm.php**

```php
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\Form;

class SearchForm extends Form 
{

    /**
     * Our constructor only requires the controller and the name of the form
     * method. We'll create the fields and actions in here.
     *
     */
    public function __construct($controller, $name) 
    {
        $fields = new FieldList(
            HeaderField::create('Header', 'Step 1. Basics'),
            OptionsetField::create('Type', '', [
                'foo' => 'Search Foo',
                'bar' => 'Search Bar',
                'baz' => 'Search Baz'
            ]),

            CompositeField::create(
                HeaderField::create('Header2', 'Step 2. Advanced '),
                CheckboxSetField::create('Foo', 'Select Option', [
                    'qux' => 'Search Qux'
                ]),

                CheckboxSetField::create('Category', 'Category', [
                    'Foo' => 'Foo',
                    'Bar' => 'Bar'
                ]),

                NumericField::create('Minimum', 'Minimum'),
                NumericField::create('Maximum', 'Maximum')
            )
        );
        
        $actions = new FieldList(
            FormAction::create('doSearchForm', 'Search')
        );
        
        $required = new RequiredFields([
            'Type'
        ]);

        // now we create the actual form with our fields and actions defined
        // within this class
        parent::__construct($controller, $name, $fields, $actions, $required);

        // any modifications we need to make to the form.
        $this->setFormMethod('GET');
    
        $this->addExtraClass('no-action-styles');
        $this->disableSecurityToken();
        $this->loadDataFrom($_REQUEST);
    }
}

```

Our controller will now just have to create a new instance of this form object. Keeping the file light and easy to read.

**app/code/Page.php**


```php
use SearchForm;
use SilverStripe\CMS\Controllers\ContentController;

class PageController extends ContentController 
{
    
    private static $allowed_actions = [
        'SearchForm',
    ];
    
    public function SearchForm() 
    {
        return new SearchForm($this, 'SearchForm');
    }
}
```

Form actions can also be defined within your `Form` subclass to keep the entire form logic encapsulated.

## Related Documentation

* [Introduction to Forms](../introduction)

## API Documentation

* [Form](api:SilverStripe\Forms\Form)

