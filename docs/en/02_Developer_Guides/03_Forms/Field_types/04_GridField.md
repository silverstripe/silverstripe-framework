title: GridField
summary: How to use the GridField class for managing tabular data.

# GridField

[GridField](api:SilverStripe\Forms\GridField\GridField) is SilverStripe's implementation of data grids. The main purpose of the `FormField` is to display 
tabular data in a format that is easy to view and modify. It can be thought of as a HTML table with some tricks.


```php
use SilverStripe\Forms\GridField\GridField;

$field = new GridField($name, $title, $list);
```

<div class="hint" markdown='1'>
GridField can only be used with `$list` data sets that are of the type `SS_List` such as `DataList` or `ArrayList`.
</div>

<div class="notice" markdown="1">
[GridField](api:SilverStripe\Forms\GridField\GridField) powers the automated data UI of [ModelAdmin](api:SilverStripe\Admin\ModelAdmin). For more information about `ModelAdmin` see the 
[Customizing the CMS](/developer_guides/customising_the_admin_interface) guide.
</div>

Each `GridField` is built from a number of components grouped into the [GridFieldConfig](api:SilverStripe\Forms\GridField\GridFieldConfig). Without any components, 
a `GridField` has almost no functionality. The `GridFieldConfig` instance and the attached [GridFieldComponent](api:SilverStripe\Forms\GridField\GridFieldComponent) are 
responsible for all the user interactions including formatting data to be readable, modifying data and performing any 
actions such as deleting records.

**app/code/Page.php**


```php
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\CMS\Model\SiteTree;

class Page extends SiteTree 
{
    
    public function getCMSFields() 
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Pages', 
            new GridField('Pages', 'All pages', SiteTree::get())
        ); 

        return $fields;
    }
}
```

This will display a bare bones `GridField` instance under `Pages` tab in the CMS. As we have not specified the 
`GridField` configuration, the default configuration is an instance of [GridFieldConfig_Base](api:SilverStripe\Forms\GridField\GridFieldConfig_Base) which provides:

 * [GridFieldToolbarHeader](api:SilverStripe\Forms\GridField\GridFieldToolbarHeader)
 * [GridFieldSortableHeader](api:SilverStripe\Forms\GridField\GridFieldSortableHeader)
 * [GridFieldFilterHeader](api:SilverStripe\Forms\GridField\GridFieldFilterHeader)
 * [GridFieldDataColumns](api:SilverStripe\Forms\GridField\GridFieldDataColumns)
 * [GridFieldPageCount](api:SilverStripe\Forms\GridField\GridFieldPageCount)
 * [GridFieldPaginator](api:SilverStripe\Forms\GridField\GridFieldPaginator)

The configuration of those `GridFieldComponent` instances and the addition or subtraction of components is done through 
the `getConfig()` method on `GridField`.

**app/code/Page.php**


```php
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\CMS\Model\SiteTree;

class Page extends SiteTree 
{
    
    public function getCMSFields() 
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Pages', 
            $grid = new GridField('Pages', 'All pages', SiteTree::get())
        );

        // GridField configuration
        $config = $grid->getConfig();

        //
        // Modification of existing components can be done by fetching that component.
        // Consult the API documentation for each component to determine the configuration
        // you can do.
        //
        $dataColumns = $config->getComponentByType(GridFieldDataColumns::class);
        
        $dataColumns->setDisplayFields([
            'Title' => 'Title',
            'Link'=> 'URL',
            'LastEdited' => 'Changed'
        ]);

        return $fields;
    }
}

```

With the `GridFieldConfig` instance, we can modify the behavior of the `GridField`.
```php
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;

// `GridFieldConfig::create()` will create an empty configuration (no components).
$config = GridFieldConfig::create();

// add a component
$config->addComponent(new GridFieldDataColumns());

// Update the GridField with our custom configuration
$gridField->setConfig($config);
```

`GridFieldConfig` provides a number of methods to make setting the configuration easier. We can insert a component 
before another component by passing the second parameter.

```php
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldDataColumns;

$config->addComponent(new GridFieldFilterHeader(), GridFieldDataColumns::class);
```

We can add multiple components in one call.


```php
$config->addComponents(
    new GridFieldDataColumns(), 
    new GridFieldToolbarHeader()
);
```

Or, remove a component.


```php
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
$config->removeComponentsByType(GridFieldDeleteAction::class);
```

Fetch a component to modify it later on.


```php
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
$component = $config->getComponentByType(GridFieldFilterHeader::class)
```

Here is a list of components for use bundled with the core framework. Many more components are provided by third-party
modules and extensions.

 - [GridField_ActionMenu](api:SilverStripe\Forms\GridField\GridField_ActionMenu)
 - [GridFieldToolbarHeader](api:SilverStripe\Forms\GridField\GridFieldToolbarHeader)
 - [GridFieldSortableHeader](api:SilverStripe\Forms\GridField\GridFieldSortableHeader)
 - [GridFieldFilterHeader](api:SilverStripe\Forms\GridField\GridFieldFilterHeader)
 - [GridFieldDataColumns](api:SilverStripe\Forms\GridField\GridFieldDataColumns)
 - [GridFieldDeleteAction](api:SilverStripe\Forms\GridField\GridFieldDeleteAction)
 - [GridFieldViewButton](api:SilverStripe\Forms\GridField\GridFieldViewButton)
 - [GridFieldEditButton](api:SilverStripe\Forms\GridField\GridFieldEditButton)
 - [GridFieldExportButton](api:SilverStripe\Forms\GridField\GridFieldExportButton)
 - [GridFieldPrintButton](api:SilverStripe\Forms\GridField\GridFieldPrintButton)
 - [GridFieldPaginator](api:SilverStripe\Forms\GridField\GridFieldPaginator)
 - [GridFieldDetailForm](api:SilverStripe\Forms\GridField\GridFieldDetailForm)

## Bundled GridFieldConfig

As a shortcut, `GridFieldConfig` subclasses can define a list of `GridFieldComponent` objects to use. This saves 
developers manually adding each component. 

### GridFieldConfig_Base

A simple read-only and paginated view of records with sortable and searchable headers.


```php
use SilverStripe\Forms\GridField\GridFieldConfig_Base;

$config = GridFieldConfig_Base::create();

$gridField->setConfig($config);

// Is the same as adding the following components..
// .. new GridFieldToolbarHeader()
// .. new GridFieldSortableHeader()
// .. new GridFieldFilterHeader()
// .. new GridFieldDataColumns()
// .. new GridFieldPageCount('toolbar-header-right')
// .. new GridFieldPaginator($itemsPerPage)
```

### GridFieldConfig_RecordViewer

Similar to `GridFieldConfig_Base` with the addition support of the ability to view a `GridFieldDetailForm` containing
a read-only view of the data record.

<div class="info" markdown="1">
The data row show must be a `DataObject` subclass. The fields displayed in the read-only view come from 
`DataObject::getCMSFields()`.
</div>

<div class="alert" markdown="1">
The `DataObject` class displayed must define a `canView()` method that returns a boolean on whether the user can view 
this record.
</div>


```php
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;

$config = GridFieldConfig_RecordViewer::create();

$gridField->setConfig($config);

// Same as GridFieldConfig_Base with the addition of
// .. new GridFieldViewButton(),
// .. new GridFieldDetailForm()
```

### GridFieldConfig_RecordEditor

Similar to `GridFieldConfig_RecordViewer` with the addition support to edit or delete each of the records.

<div class="info" markdown="1">
The data row show must be a `DataObject` subclass. The fields displayed in the edit view come from 
`DataObject::getCMSFields()`.
</div>

<div class="alert" markdown="1">
Permission control for editing and deleting the record uses the `canEdit()` and `canDelete()` methods on the 
`DataObject` object.
</div>


```php
$config = GridFieldConfig_RecordEditor::create();

$gridField->setConfig($config);

// Same as GridFieldConfig_RecordViewer with the addition of
// .. new GridFieldAddNewButton(),
// .. new GridFieldEditButton(),
// .. new GridFieldDeleteAction()
```

### GridFieldConfig_RelationEditor

Similar to `GridFieldConfig_RecordEditor`, but adds features to work on a record's has-many or many-many relationships. 
As such, it expects the list used with the `GridField` to be a instance of `RelationList`.

```php
$config = GridFieldConfig_RelationEditor::create();

$gridField->setConfig($config);
```

This configuration adds the ability to searched for existing records and add a relationship 
(`GridFieldAddExistingAutocompleter`).

Records created or deleted through the `GridFieldConfig_RelationEditor` automatically update the relationship in the
database.

## GridField_ActionMenu

The `GridField_ActionMenu` component provides a dropdown menu which automatically bundles GridField actions into a react based dropdown. It is included by default on `GridFieldConfig_RecordEditor` and `GridFieldConfig_RelationEditor` configs.

To add it to a GridField, add the `GridField_ActionMenu` component and any action(s) that implement `GridField_ActionMenuItem` (such as `GridFieldEditButton` or `GridFieldDeleteAction`) to the `GridFieldConfig`.

```php
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;

// `GridFieldConfig::create()` will create an empty configuration (no components).
$config = GridFieldConfig::create();

// add a component
$config->addComponent();

$config->addComponents(
    new GridFieldDataColumns(),
    new GridFieldEditButton(), 
    new GridField_ActionMenu()
);

// Update the GridField with our custom configuration
$gridField->setConfig($config);

```

## GridFieldDetailForm

The `GridFieldDetailForm` component drives the record viewing and editing form. It takes its' fields from 
`DataObject->getCMSFields()` method but can be customised to accept different fields via the 
[GridFieldDetailForm::setFields()](api:SilverStripe\Forms\GridField\GridFieldDetailForm::setFields()) method.


```php
use SilverStripe\Forms\GridField\GridFieldDetailForm;

$form = $gridField->getConfig()->getComponentByType(GridFieldDetailForm::class);
$form->setFields(new FieldList(
    new TextField('Title')
));
```

### many_many_extraFields

The component also has the ability to load and save data stored on join tables when two records are related via a 
"many_many" relationship, as defined through [DataObject::$many_many_extraFields](api:SilverStripe\ORM\DataObject::$many_many_extraFields). While loading and saving works 
transparently, you need to add the necessary fields manually, they're not included in the `getCMSFields()` scaffolding.

These extra fields act like usual form fields, but need to be "namespaced" in order for the `GridField` logic to detect 
them as fields for relation extra data, and to avoid clashes with the other form fields.

The namespace notation is `ManyMany[<extradata-field-name>]`, so for example `ManyMany[MyExtraField]`.


```php
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\ORM\DataObject;

class Team extends DataObject 
{
    
    private static $db = [
        'Name' => 'Text'
    ];

    public static $many_many = [
        'Players' => 'Player'
    ];
}
class Player extends DataObject 
{

    private static $db = [
        'Name' => 'Text'
    ];
    
    public static $many_many = [
        'Teams' => 'Team'
    ];
    
    public static $many_many_extraFields = [
        'Teams' => [
            'Position' => 'Text'
        ]
    ];

    public function getCMSFields() 
    {
        $fields = parent::getCMSFields();

        if($this->ID) {
            $teamFields = singleton('Team')->getCMSFields();
            $teamFields->addFieldToTab(
                'Root.Main',
                // The "ManyMany[<extradata-name>]" convention
                new TextField('ManyMany[Position]', 'Current Position')
            );

            $config = GridFieldConfig_RelationEditor::create();
            $config->getComponentByType('GridFieldDetailForm')->setFields($teamFields);

            $gridField = new GridField('Teams', 'Teams', $this->Teams(), $config);
            $fields->findOrMakeTab('Root.Teams')->replaceField('Teams', $gridField);
        }

        return $fields;
    }
}
```

## Flexible Area Assignment through Fragments

`GridField` layouts can contain many components other than the table itself, for example a search bar to find existing 
relations, a button to add those, and buttons to export and print the current data. The `GridField` has certain defined 
areas called `fragments` where these components can be placed. 

The goal is for multiple components to share the same space, for example a header row. The built-in components:

 - `header`/`footer`: Renders in a `<thead>`/`<tfoot>`, should contain table markup
 - `before`/`after`: Renders before/after the actual `<table>`
 - `buttons-before-left`/`buttons-before-right`/`buttons-after-left`/`buttons-after-right`: 
    Renders in a shared row before the table. Requires [GridFieldButtonRow](api:SilverStripe\Forms\GridField\GridFieldButtonRow).

These built-ins can be used by passing the fragment names into the constructor of various components. Note that some 
[GridFieldConfig](api:SilverStripe\Forms\GridField\GridFieldConfig) classes will already have rows added to them. The following example will add a print button at the 
bottom right of the table.

```php
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldPrintButton;

$config->addComponent(new GridFieldButtonRow('after'));
$config->addComponent(new GridFieldPrintButton('buttons-after-right'));
```

### Creating your own Fragments

Fragments are designated areas within a `GridField` which can be shared between component templates. You can define 
your own fragments by using a `\$DefineFragment' placeholder in your components' template. This example will simply 
create an area rendered before the table wrapped in a simple `<div>`.


```php
use SilverStripe\Forms\GridField\GridField_HTMLProvider;

class MyAreaComponent implements GridField_HTMLProvider 
{

    public function getHTMLFragments( $gridField) 
    {
        return [
            'before' => '<div class="my-area">$DefineFragment(my-area)</div>'
        ];
    }
}

```

<div class="notice" markdown="1">
Please note that in templates, you'll need to escape the dollar sign on `\$DefineFragment`. These are specially 
processed placeholders as opposed to native template syntax.
</div>

Now you can add other components into this area by returning them as an array from your 
[GridFieldComponent::getHTMLFragments()](api:SilverStripe\Forms\GridField\GridFieldComponent::getHTMLFragments()) implementation:


```php
use SilverStripe\Forms\GridField\GridField_HTMLProvider;

class MyShareLinkComponent implements GridField_HTMLProvider 
{

    public function getHTMLFragments( $gridField) 
    {        
        return [
            'my-area' => '<a href>...</a>'
        ];
    }
}

```

Your new area can also be used by existing components, e.g. the [GridFieldPrintButton](api:SilverStripe\Forms\GridField\GridFieldPrintButton)


```php
new GridFieldPrintButton('my-area');
```

## Creating a Custom GridFieldComponent

Customizing a `GridField` is easy, applications and modules can provide their own `GridFieldComponent` instances to add
functionality. See [How to Create a GridFieldComponent](../how_tos/create_a_gridfieldcomponent).

## Creating a Custom GridField_ActionProvider

[GridField_ActionProvider](api:SilverStripe\Forms\GridField\GridField_ActionProvider) provides row level actions such as deleting a record. See 
[How to Create a GridField_ActionProvider](../how_tos/create_a_gridfield_actionprovider).

## Saving the GridField State

`GridState` is a class that is used to contain the current state and actions on the `GridField`. It's transfered 
between page requests by being inserted as a hidden field in the form.

The `GridState_Component` sets and gets data from the `GridState`.

## API Documentation

 * [GridField](api:SilverStripe\Forms\GridField\GridField)
 * [GridFieldConfig](api:SilverStripe\Forms\GridField\GridFieldConfig)
 * [GridFieldComponent](api:SilverStripe\Forms\GridField\GridFieldComponent) 
