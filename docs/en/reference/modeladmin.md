# ModelAdmin

## Introduction

Provides a simple way to utilize the SilverStripe CMS UI with your own data models,
and create searchable list and edit views of them, and even providing import and export of your data.

It uses the framework's knowledge about the model to provide sensible defaults,
allowing you to get started in a couple of lines of code,
while still providing a solid base for customization.

The interface is mainly powered by the [GridField](/reference/grid-field) class,
which can also be used in other CMS areas (e.g. to manage a relation on a `SiteTree`
record in the standard CMS interface).

## Setup

Let's assume we want to manage a simple product listing as a sample data model:
A product can have a name, price, and a category.
	
	:::php
	class Product extends DataObject {
	   private static $db = array('Name' => 'Varchar', 'ProductCode' => 'Varchar', 'Price' => 'Currency');
	   private static $has_one = array('Category' => 'Category');
	}
	class Category extends DataObject {
	   private static $db = array('Title' => 'Text');
	   private static $has_many = array('Products' => 'Product');
	}

To create your own `ModelAdmin`, simply extend the base class,
and edit the `$managed_models` property with the list of
data objects you want to scaffold an interface for.
The class can manage multiple models in parallel, if required.
We'll name it `MyAdmin`, but the class name can be anything you want.

	:::php
	class MyAdmin extends ModelAdmin {
	  private static $managed_models = array('Product','Category'); // Can manage multiple models
	  private static $url_segment = 'products'; // Linked as /admin/products/
	  private static $menu_title = 'My Product Admin';
	}

This will automatically add a new menu entry to the CMS, and you're ready to go!
Try opening http://localhost/admin/products/?flush=all.

## Permissions

Each new `ModelAdmin` subclass creates its own [permission code](/reference/permission),
for the example above this would be `CMS_ACCESS_MyAdmin`. Users with access to the CMS
need to have this permission assigned through `admin/security/` in order to gain
access to the controller (unless they're admins).

The `DataObject` API has more granular permission control, which is enforced in ModelAdmin by default. 
Available checks are `canEdit()`, `canCreate()`, `canView()` and `canDelete()`.
Models check for administrator permissions by default. For most cases,
less restrictive checks make sense, e.g. checking for general CMS access rights.

	:::php
	class Category extends DataObject {
	  // ...
		public function canView($member = null) {
			return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
		}
		public function canEdit($member = null) {
			return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
		}
		public function canDelete($member = null) {
			return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
		}
		public function canCreate($member = null) {
			return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
		}

## Search Fields

ModelAdmin uses the [SearchContext](/reference/searchcontext) class to provide
a search form, as well as get the searched results. Every DataObject can have its own context,
based on the fields which should be searchable. The class makes a guess at how those fields
should be searched, e.g. showing a checkbox for any boolean fields in your `$db` definition.

To remove, add or modify searchable fields, define a new `[api:DataObject::$searchable_fields]`
static on your model class (see [SearchContext](/reference/searchcontext) docs for details). 

	:::php
	class Product extends DataObject {
	   // ...
	   private static $searchable_fields = array(
	      'Name',
	      'ProductCode'
	      // leaves out the 'Price' field, removing it from the search
	   );
	}

For a more sophisticated customization, for example configuring the form fields
for the search form, override `DataObject->getCustomSearchContext()` on your model class.

## Result Columns

The results are shown in a tabular listing, powered by the [GridField](/reference/grid-field),
more specifically the `[api:GridFieldDataColumns]` component.
It looks for a `[api:DataObject::$summary_fields]` static on your model class,
where you can add or remove columns. To change the title, use `[api:DataObject::$field_labels]`.

	:::php
	class Product extends DataObject {
	   // ...
	   private static $field_labels = array(
	      'Price' => 'Cost' // renames the column to "Cost"
	   );
	   private static $summary_fields = array(
	      'Name',
	      'Price',
	      // leaves out the 'ProductCode' field, removing the column
	   );
	}

## Results Customization

The results are retrieved from `[api:SearchContext->getResults()]`,
based on the parameters passed through the search form.
If no search parameters are given, the results will show every record.
Results are a `[api:DataList]` instance, so can be customized by additional
SQL filters, joins, etc (see [datamodel](/topics/datamodel) for more info).

For example, we might want to exclude all products without prices in our sample `MyAdmin` implementation.

	:::php
	class MyAdmin extends ModelAdmin {
		// ...
		public function getList() {
			$list = parent::getList();
			// Always limit by model class, in case you're managing multiple
			if($this->modelClass == 'Product') {
				$list = $list->exclude('Price', '0');
			}
			return $list;
		}
	}

You can also customize the search behaviour directly on your `ModelAdmin` instance.
For example, we might want to have a checkbox which limits search results to expensive products (over $100).

	:::php
	class MyAdmin extends ModelAdmin {
		// ...
		public function getSearchContext() {
			$context = parent::getSearchContext();
			if($this->modelClass == 'Product') {
				$context->getFields()->push(new CheckboxField('q[ExpensiveOnly]', 'Only expensive stuff'));
			}
			return $context;
		}
		public function getList() {
			$list = parent::getList();
			$params = $this->request->requestVar('q'); // use this to access search parameters
			if($this->modelClass == 'Product' && isset($params['ExpensiveOnly']) && $params['ExpensiveOnly']) {
				$list = $list->exclude('Price:LessThan', '100');
			}
			return $list;
		}
	}
	
### GridField Customization	

To alter how the results are displayed (via `[api:GridField]`), you can also overload the `getEditForm()` method. For example, to add a new component.

	:::php
	class MyAdmin extends ModelAdmin {
		private static $managed_models = array('Product','Category');
		// ...
		public function getEditForm($id = null, $fields = null) {
			$form = parent::getEditForm($id, $fields);
			// $gridFieldName is generated from the ModelClass, eg if the Class 'Product'
			// is managed by this ModelAdmin, the GridField for it will also be named 'Product'
			$gridFieldName = $this->sanitiseClassName($this->modelClass);
			$gridField = $form->Fields()->fieldByName($gridFieldName);
			$gridField->getConfig()->addComponent(new GridFieldFilterHeader());
			return $form;
		}
	}
	
The above example will add the component to all `GridField`s (of all managed models). Alternatively we can also add it to only one specific `GridField`:

	:::php
	class MyAdmin extends ModelAdmin {
		private static $managed_models = array('Product','Category');
		// ...
		public function getEditForm($id = null, $fields = null) {
			$form = parent::getEditForm($id, $fields);
			$gridFieldName = 'Product';
			$gridField = $form->Fields()->fieldByName($gridFieldName);
			if ($gridField) {
				$gridField->getConfig()->addComponent(new GridFieldFilterHeader());
			}
			return $form;
		}
	}

## Managing Relationships

Has-one relationships are simply implemented as a `[api:DropdownField]` by default.
Consider replacing it with a more powerful interface in case you have many records
(through customizing `[api:DataObject->getCMSFields]`).

Has-many and many-many relationships are usually handled via the [GridField](/reference/grid-field) class,
more specifically the `[api:GridFieldAddExistingAutocompleter]` and `[api:GridFieldRelationDelete]` components.
They provide a list/detail interface within a single record edited in your ModelAdmin.
The [GridField](/reference/grid-field) docs also explain how to manage 
extra relation fields on join tables through its detail forms.
The autocompleter can also search attributes on relations,
based on the search fields defined through `[api:DataObject::searchableFields()]`.

## Permissions

`ModelAdmin` respects the permissions set on the model, through methods on your `DataObject` implementations:
`canView()`, `canEdit()`, `canDelete()`, and `canCreate`.

In terms of access control to the interface itself, every `ModelAdmin` subclass
creates its own "[permission code](/reference/permissions)", which can be assigned
to groups through the `admin/security` management interface. To further limit
permission, either override checks in `ModelAdmin->init()`, or define
more permission codes through the `ModelAdmin::$required_permission_codes` static.

## Data Import

The `ModelAdmin` class provides import of CSV files through the `[api:CsvBulkLoader]` API.
which has support for column mapping, updating existing records,
and identifying relationships - so its a powerful tool to get your data into a SilverStripe database.

By default, each model management interface allows uploading a CSV file
with all columns autodetected. To override with a more specific importer implementation,
use the `[api:ModelAdmin::$model_importers] static.

## Data Export

Export is also available, although at the moment only to the CSV format,
through a button at the end of a results list. You can also export search results.
It is handled through the `[api:GridFieldExportButton]` component.

To customize the exported columns, create a new method called `getExportFields` in your `ModelAdmin`:

	:::php
	class MyAdmin extends ModelAdmin {
		// ...
		public function getExportFields() {
			return array(
				'Name' => 'Name',
				'ProductCode' => 'Product Code',
				'Category.Title' => 'Category'
			);
		}
	}

Dot syntax support allows you to select a field on a related `has_one` object.

## Extending existing ModelAdmins

Sometimes you'll work with ModelAdmins from other modules, e.g. the product management
of an ecommerce module. To customize this, you can always subclass. But there's
also another tool at your disposal: The `[api:Extension]` API.

	:::php
	class MyAdminExtension extends Extension {
		// ...
		public function updateEditForm(&$form) {
			$form->Fields()->push(/* ... */)
		}
	}

Now enable this extension through your `[config.yml](/topics/configuration)` file.

	:::yml
	MyAdmin:
	  extensions:
	    - MyAdminExtension

The following extension points are available: `updateEditForm()`, `updateSearchContext()`,
`updateSearchForm()`, `updateList()`, `updateImportForm`.

## Customizing the interface

Interfaces like `ModelAdmin` can be customized in many ways:

 * JavaScript behaviour (e.g. overwritten jQuery.entwine rules)
 * CSS styles
 * HTML markup through templates

In general, use your `ModelAdmin->init()` method to add additional requirements
through the [Requirements](/reference/requirements) API.
For an introduction how to customize the CMS templates, see our [CMS Architecture Guide](/reference/cms-architecture).

## Related

* [GridField](../reference/grid-field): The UI component powering ModelAdmin
* [Tutorial 5: Dataobject Relationship Management](../tutorials/5-dataobject-relationship-management)
*  `[api:SearchContext]`
* [genericviews Module](http://silverstripe.org/generic-views-module)
* [Presentation about ModelAdmin at SupperHappyDevHouse Wellington](http://www.slideshare.net/chillu/modeladmin-in-silverstripe-23)
* [Reference: CMS Architecture](../reference/cms-architecture)
* [Howto: Extend the CMS Interface](../howto/extend-cms-interface)
