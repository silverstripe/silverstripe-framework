title: ModelAdmin
summary: Create admin UI's for managing your data records.

# ModelAdmin

[api:ModelAdmin] provides a simple way to utilize the SilverStripe Admin UI with your own data models. It can create
searchables list and edit views of [api:DataObject] subclasses, and even provides import and export of your data.

It uses the framework's knowledge about the model to provide sensible defaults, allowing you to get started in a couple
of lines of code, while still providing a solid base for customization.

<div class="info" markdown="1">
The interface is mainly powered by the [api:GridField] class ([documentation](../forms/fields/gridfield)), which can
also be used in other areas of your application.
</div>

Let's assume we want to manage a simple product listing as a sample data model: A product can have a name, price, and
a category.

**mysite/code/Product.php**

	:::php
	<?php

	class Product extends DataObject {

		private static $db = array(
			'Name' => 'Varchar',
			'ProductCode' => 'Varchar',
			'Price' => 'Currency'
		);

		private static $has_one = array(
			'Category' => 'Category'
		);
	}

**mysite/code/Category.php**

	:::php
	<?php

	class Category extends DataObject {

		private static $db = array(
			'Title' => 'Text'
		);

		private static $has_many = array(
			'Products' => 'Product'
		);
	}

To create your own `ModelAdmin`, simply extend the base class, and edit the `$managed_models` property with the list of
DataObject's you want to scaffold an interface for. The class can manage multiple models in parallel, if required.

We'll name it `MyAdmin`, but the class name can be anything you want.

**mysite/code/MyAdmin.php**

	:::php
	<?php

	class MyAdmin extends ModelAdmin {

		private static $managed_models = array(
			'Product',
			'Category'
		);

		private static $url_segment = 'products';

		private static $menu_title = 'My Product Admin';
	}

This will automatically add a new menu entry to the SilverStripe Admin UI entitled `My Product Admin` and logged in
users will be able to upload and manage `Product` and `Category` instances through http://yoursite.com/admin/products.

<div class="alert" markdown="1">
After defining these classes, make sure you have rebuilt your SilverStripe database and flushed your cache.
</div>

## Permissions

Each new `ModelAdmin` subclass creates its' own [permission code](../security), for the example above this would be
`CMS_ACCESS_MyAdmin`. Users with access to the Admin UI will need to have this permission assigned through
`admin/security/` or have the `ADMIN` permission code in order to gain access to the controller.

<div class="notice" markdown="1">
For more information on the security and permission system see the [Security Documentation](../security)
</div>

The [api:DataObject] API has more granular permission control, which is enforced in [api:ModelAdmin] by default.
Available checks are `canEdit()`, `canCreate()`, `canView()` and `canDelete()`. Models check for administrator
permissions by default. For most cases, less restrictive checks make sense, e.g. checking for general CMS access rights.

**mysite/code/Category.php**

	:::php
	<?php

	class Category extends DataObject {
	  // ...
		public function canView($member = null) {
			return Permission::check('CMS_ACCESS_MyAdmin', 'any', $member);
		}

		public function canEdit($member = null) {
			return Permission::check('CMS_ACCESS_MyAdmin', 'any', $member);
		}

		public function canDelete($member = null) {
			return Permission::check('CMS_ACCESS_MyAdmin', 'any', $member);
		}

		public function canCreate($member = null) {
			return Permission::check('CMS_ACCESS_MyAdmin', 'any', $member);
		}

## Searching Records

[api:ModelAdmin] uses the [SearchContext](../search/searchcontext) class to provide a search form, as well as get the
searched results. Every [api:DataObject] can have its own context, based on the fields which should be searchable. The
class makes a guess at how those fields should be searched, e.g. showing a checkbox for any boolean fields in your
`$db` definition.

To remove, add or modify searchable fields, define a new `[api:DataObject::$searchable_fields]` static on your model
class (see [SearchContext](../search/searchcontext) docs for details).

**mysite/code/Product.php**

	:::php
	<?php

	class Product extends DataObject {

	   private static $searchable_fields = array(
	      'Name',
	      'ProductCode'
	   );
	}

<div class="hint" markdown="1">
[SearchContext](../search/searchcontext) documentation has more information on providing the search functionality.
</div>

## Displaying Results

The results are shown in a tabular listing, powered by the [GridField](../forms/fields/gridfield), more specifically
the [api:GridFieldDataColumns] component. This component looks for a [api:DataObject::$summary_fields] static on your
model class, where you can add or remove columns. To change the title, use [api:DataObject::$field_labels].

**mysite/code/Product.php**

	:::php
	<?php

	class Product extends DataObject {

	   private static $field_labels = array(
	      'Price' => 'Cost' // renames the column to "Cost"
	   );

	   private static $summary_fields = array(
	      'Name',
	      'Price'
	   );
	}

The results list are retrieved from [api:SearchContext->getResults], based on the parameters passed through the search
form. If no search parameters are given, the results will show every record. Results are a [api:DataList] instance, so
can be customized by additional SQL filters, joins.

For example, we might want to exclude all products without prices in our sample `MyAdmin` implementation.

**mysite/code/MyAdmin.php**

	:::php
	<?php

	class MyAdmin extends ModelAdmin {

		public function getList() {
			$list = parent::getList();

			// Always limit by model class, in case you're managing multiple
			if($this->modelClass == 'Product') {
				$list = $list->exclude('Price', '0');
			}

			return $list;
		}
	}

You can also customize the search behavior directly on your `ModelAdmin` instance. For example, we might want to have a
checkbox which limits search results to expensive products (over $100).

**mysite/code/MyAdmin.php**

	:::php
	<?php

	class MyAdmin extends ModelAdmin {

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

To alter how the results are displayed (via `[api:GridField]`), you can also overload the `getEditForm()` method. For
example, to add a new component.

**mysite/code/MyAdmin.php**

	:::php
	<?php

	class MyAdmin extends ModelAdmin {

		private static $managed_models = array(
			'Product',
			'Category'
		);

		// ...
		public function getEditForm($id = null, $fields = null) {
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

The above example will add the component to all `GridField`s (of all managed models). Alternatively we can also add it
to only one specific `GridField`:

**mysite/code/MyAdmin.php**

	:::php
	<?php

	class MyAdmin extends ModelAdmin {

		private static $managed_models = array(
			'Product',
			'Category'
		);

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

## Data Import

The `ModelAdmin` class provides import of CSV files through the [api:CsvBulkLoader] API. which has support for column
mapping, updating existing records, and identifying relationships - so its a powerful tool to get your data into a
SilverStripe database.

By default, each model management interface allows uploading a CSV file with all columns auto detected. To override
with a more specific importer implementation, use the [api:ModelAdmin::$model_importers] static.

## Data Export

Export is available as a CSV format through a button at the end of a results list. You can also export search results.
This is handled through the [api:GridFieldExportButton] component.

To customize the exported columns, create a new method called `getExportFields` in your `ModelAdmin`:

	:::php
	<?php

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


## Related Documentation

* [GridField](../forms/fields/gridfield)
* [Permissions](../security/permissions)
* [SeachContext](../search/seachcontext)

## API Documentation

* [api:ModelAdmin]
* [api:LeftAndMain]
* [api:GridField]
* [api:DataList]
* [api:CsvBulkLoader]
