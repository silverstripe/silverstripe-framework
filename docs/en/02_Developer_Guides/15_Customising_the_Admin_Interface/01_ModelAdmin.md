---
title: ModelAdmin
summary: Create admin UI's for managing your data records.
---

# ModelAdmin

[ModelAdmin](api:SilverStripe\Admin\ModelAdmin) provides a simple way to utilize the SilverStripe Admin UI with your own data models. It can create
searchables list and edit views of [DataObject](api:SilverStripe\ORM\DataObject) subclasses, and even provides import and export of your data.

It uses the framework's knowledge about the model to provide sensible defaults, allowing you to get started in a couple
of lines of code, while still providing a solid base for customization.

[info]
The interface is mainly powered by the [GridField](api:SilverStripe\Forms\GridField\GridField) class ([documentation](../forms/field_types/gridfield)), which can
also be used in other areas of your application.
[/info]

Let's assume we want to manage a simple product listing as a sample data model: A product can have a name, price, and
a category.

**app/code/Product.php**


```php
use SilverStripe\ORM\DataObject;

class Product extends DataObject 
{

    private static $db = [
        'Name' => 'Varchar',
        'ProductCode' => 'Varchar',
        'Price' => 'Currency'
    ];

    private static $has_one = [
        'Category' => 'Category'
    ];
}
```

**app/code/Category.php**


```php
use SilverStripe\ORM\DataObject;

class Category extends DataObject 
{

    private static $db = [
        'Title' => 'Text'
    ];

    private static $has_many = [
        'Products' => 'Product'
    ];
}
```

To create your own `ModelAdmin`, simply extend the base class, and edit the `$managed_models` property with the list of
DataObject's you want to scaffold an interface for. The class can manage multiple models in parallel, if required.

We'll name it `MyAdmin`, but the class name can be anything you want.

**app/code/MyAdmin.php**


```php
use SilverStripe\Admin\ModelAdmin;

class MyAdmin extends ModelAdmin 
{

    private static $managed_models = [
        'Product',
        'Category'
    ];

    private static $url_segment = 'products';

    private static $menu_title = 'My Product Admin';
}
```

This will automatically add a new menu entry to the SilverStripe Admin UI entitled `My Product Admin` and logged in
users will be able to upload and manage `Product` and `Category` instances through http://yoursite.com/admin/products.

[alert]
After defining these classes, make sure you have rebuilt your SilverStripe database and flushed your cache.
[/alert]

## Permissions

Each new `ModelAdmin` subclass creates its' own [permission code](../security), for the example above this would be
`CMS_ACCESS_MyAdmin`. Users with access to the Admin UI will need to have this permission assigned through
`admin/security/` or have the `ADMIN` permission code in order to gain access to the controller.

[notice]
For more information on the security and permission system see the [Security Documentation](../security)
[/notice]

The [DataObject](api:SilverStripe\ORM\DataObject) API has more granular permission control, which is enforced in [ModelAdmin](api:SilverStripe\Admin\ModelAdmin) by default.
Available checks are `canEdit()`, `canCreate()`, `canView()` and `canDelete()`. Models check for administrator
permissions by default. For most cases, less restrictive checks make sense, e.g. checking for general CMS access rights.

**app/code/Category.php**


```php
use SilverStripe\Security\Permission;
use SilverStripe\ORM\DataObject;

class Category extends DataObject 
{
    public function canView($member = null) 
    {
        return Permission::check('CMS_ACCESS_Company\Website\MyAdmin', 'any', $member);
    }

    public function canEdit($member = null) 
    {
        return Permission::check('CMS_ACCESS_Company\Website\MyAdmin', 'any', $member);
    }

    public function canDelete($member = null) 
    {
        return Permission::check('CMS_ACCESS_Company\Website\MyAdmin', 'any', $member);
    }

    public function canCreate($member = null) 
    {
        return Permission::check('CMS_ACCESS_Company\Website\MyAdmin', 'any', $member);
    }
}
```
## Custom ModelAdmin CSS menu icons using built in icon font

An extended ModelAdmin class supports adding a custom menu icon to the CMS.

```
class NewsAdmin extends ModelAdmin
{
    ...
    private static $menu_icon_class = 'font-icon-news';
}
```
A complete list of supported font icons is available to view in the [SilverStripe Design System Manager](https://projects.invisionapp.com/dsm/silver-stripe/silver-stripe/section/icons/5a8b972d656c91001150f8b6)

## Searching Records

[ModelAdmin](api:SilverStripe\Admin\ModelAdmin) uses the [SearchContext](../search/searchcontext) class to provide a search form, as well as get the
searched results. Every [DataObject](api:SilverStripe\ORM\DataObject) can have its own context, based on the fields which should be searchable. The
class makes a guess at how those fields should be searched, e.g. showing a checkbox for any boolean fields in your
`$db` definition.

To remove, add or modify searchable fields, define a new [DataObject::$searchable_fields](api:SilverStripe\ORM\DataObject::$searchable_fields) static on your model
class (see [SearchContext](../search/searchcontext) docs for details).

**app/code/Product.php**


```php
use SilverStripe\ORM\DataObject;

class Product extends DataObject 
{

   private static $searchable_fields = [
      'Name',
      'ProductCode'
   ];
}
```

[hint]
[SearchContext](../search/searchcontext) documentation has more information on providing the search functionality.
[/hint]

## Displaying Results

The results are shown in a tabular listing, powered by the [GridField](../forms/field_types/gridfield), more specifically
the [GridFieldDataColumns](api:SilverStripe\Forms\GridField\GridFieldDataColumns) component. This component looks for a [DataObject::$summary_fields](api:SilverStripe\ORM\DataObject::$summary_fields) static on your
model class, where you can add or remove columns. To change the title, use [DataObject::$field_labels](api:SilverStripe\ORM\DataObject::$field_labels).

**app/code/Product.php**


```php
use SilverStripe\ORM\DataObject;

class Product extends DataObject 
{
   private static $field_labels = [
      'Price' => 'Cost' // renames the column to "Cost"
   ];

   private static $summary_fields = [
      'Name',
      'Price'
   ];
}
```

The results list are retrieved from [SearchContext::getResults()](api:SilverStripe\ORM\Search\SearchContext::getResults()), based on the parameters passed through the search
form. If no search parameters are given, the results will show every record. Results are a [DataList](api:SilverStripe\ORM\DataList) instance, so
can be customized by additional SQL filters, joins.

For example, we might want to exclude all products without prices in our sample `MyAdmin` implementation.

**app/code/MyAdmin.php**


```php
<?php
use SilverStripe\Admin\ModelAdmin;

class MyAdmin extends ModelAdmin 
{
    public function getList() 
    {
        $list = parent::getList();

        // Always limit by model class, in case you're managing multiple
        if($this->modelClass == 'Product') {
            $list = $list->exclude('Price', '0');
        }

        return $list;
    }
}
```

You can also customize the search behavior directly on your `ModelAdmin` instance. For example, we might want to have a
checkbox which limits search results to expensive products (over $100).

**app/code/MyAdmin.php**

```php
<?php
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Admin\ModelAdmin;

class MyAdmin extends ModelAdmin 
{
    public function getSearchContext() 
    {
        $context = parent::getSearchContext();

        if($this->modelClass == 'Product') {
            $context->getFields()->push(new CheckboxField('q[ExpensiveOnly]', 'Only expensive stuff'));
        }

        return $context;
    }

    public function getList() 
    {
        $list = parent::getList();

        $params = $this->getRequest()->requestVar('q'); // use this to access search parameters

        if($this->modelClass == 'Product' && isset($params['ExpensiveOnly']) && $params['ExpensiveOnly']) {
            $list = $list->exclude('Price:LessThan', '100');
        }

        return $list;
    }
}
```

## Altering the ModelAdmin GridField or Form

If you wish to provided a tailored esperience for CMS users, you can directly interact with the ModelAdmin form or gridfield. Override the following method:
* `getEditForm()` to alter the Form object
* `getGridField()` to alter the GridField field
* `getGridFieldConfig()` to alter the GridField configuration.

Extensions applied to a ModelAdmin can also use the `updateGridField` and `updateGridFieldConfig` hooks.

[hint]
`getGridField()`, `getGridFieldConfig()`, `updateGridField` and `updateGridFieldConfig` are only available on 
Silverstripe CMS 4.6 and above.
[/hint]

To alter how the results are displayed (via [GridField](api:SilverStripe\Forms\GridField\GridField)), you can also overload the `getEditForm()` method. For
example, to add a new component.

### Overriding the methods on ModelAdmin

**app/code/MyAdmin.php**


```php
<?php
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Admin\ModelAdmin;

class MyAdmin extends ModelAdmin 
{

    private static $managed_models = [
        Product::class,
        Category::class
    ];
    
    private static $url_segment = 'my-admin';

    protected function getGridFieldConfig(): GridFieldConfig
    {
        $config = parent::getGridFieldConfig();

        $config->addComponent(new GridFieldFilterHeader());

        return $config;
    }
}
```

The above example will add the component to all `GridField`s (of all managed models). Alternatively we can also add it
to only one specific `GridField`:

**app/code/MyAdmin.php**


```php
<?php
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Admin\ModelAdmin;

class MyAdmin extends ModelAdmin 
{

    private static $managed_models = [
        Product::class,
        Category::class
    ];
    
    private static $url_segment = 'my-admin';

    protected function getGridFieldConfig(): GridFieldConfig 
    {
        $config = parent::getGridFieldConfig();

        // modify the list view.
        if ($this->modelClass === Product::class) {
            $config->addComponent(new GridFieldFilterHeader());
        }

        return $config;
    }
}
```

### Using an extension to customise a ModelAdmin

You can use an Extension to achieve the same results. Extensions have the advantage of being reusable in many contexts.

**app/code/ModelAdminExtension.php**


```php
<?php
use SilverStripe\Core\Extension;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;

/**
 * You can apply this extension to a GridField.
 */
class ModelAdminExtension extends Extension
{
    public function updateGridFieldConfig(GridFieldConfig &$config)
    {
        $config->addComponent(new GridFieldFilterHeader());
    }
}
```

**app/_config/mysite.yml**

```yaml
MyAdmin:
  extensions:
    - ModelAdminExtension
```

### Altering a ModelAdmin using only `getEditForm()`

If you're developing against a version of Silverstripe CMS prior to 4.6, your only option is to override `getEditForm()`. This requires a bit more work to access the GridField and GridFieldConfig instances.

**app/code/MyAdmin.php**

```php
<?php

use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Admin\ModelAdmin;

class MyAdmin extends ModelAdmin 
{

    private static $managed_models = [
        Product::class,
        Category::class
    ];
    
    private static $url_segment = 'my-admin';

    public function getEditForm($id = null, $fields = null) 
    {
        $form = parent::getEditForm($id, $fields);

        // $gridFieldName is generated from the ModelClass, eg if the Class 'Product'
        // is managed by this ModelAdmin, the GridField for it will also be named 'Product'
        $gridFieldName = $this->sanitiseClassName($this->modelClass);
        $gridField = $form->Fields()->fieldByName($gridFieldName);

        // modify the list view.
        $gridField->getConfig()->addComponent(new GridFieldFilterHeader());

        return $form;
    }
}
```

## Data Import

The `ModelAdmin` class provides import of CSV files through the [CsvBulkLoader](api:SilverStripe\Dev\CsvBulkLoader) API. which has support for column
mapping, updating existing records, and identifying relationships - so its a powerful tool to get your data into a
SilverStripe database.

By default, each model management interface allows uploading a CSV file with all columns auto detected. To override
with a more specific importer implementation, use the [ModelAdmin::$model_importers](api:SilverStripe\Admin\ModelAdmin::$model_importers) static.

## Data Export

Export is available as a CSV format through a button at the end of a results list. You can also export search results.
This is handled through the [GridFieldExportButton](api:SilverStripe\Forms\GridField\GridFieldExportButton) component.

To customize the exported columns, create a new method called `getExportFields` in your `ModelAdmin`:


```php
use SilverStripe\Admin\ModelAdmin;

class MyAdmin extends ModelAdmin 
{
    // ...

    public function getExportFields() 
    {
        return [
            'Name' => 'Name',
            'ProductCode' => 'Product Code',
            'Category.Title' => 'Category'
        ];
    }
}
```

## Related Lessons
* [Intoduction to ModelAdmin](https://www.silverstripe.org/learn/lessons/v4/introduction-to-modeladmin-1)

## Related Documentation

* [GridField](../forms/field_types/gridfield)
* [Permissions](../security/permissions)
* [SearchContext](../search/searchcontext)

## API Documentation

* [ModelAdmin](api:SilverStripe\Admin\ModelAdmin)
* [LeftAndMain](api:SilverStripe\Admin\LeftAndMain)
* [GridField](api:SilverStripe\Forms\GridField\GridField)
* [DataList](api:SilverStripe\ORM\DataList)
* [CsvBulkLoader](api:SilverStripe\Dev\CsvBulkLoader)
