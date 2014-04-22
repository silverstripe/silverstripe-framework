# DataObject

## Introduction

The `[api:DataObject]` class represents a single row in a database table,
following the ["Active Record"](http://en.wikipedia.org/wiki/Active_record_pattern) design pattern.

## Defining Properties

Properties defined through `DataObject::$db` map to table columns,
and can be declared as different [data-types](/topics/data-types).

## Loading and Saving Records

The basic principles around data persistence and querying for objects
is explained in the ["datamodel" topic](/topics/datamodel).

## Defining Form Fields

In addition to defining how data is persisted, the class can also
help with editing it by providing form fields through `DataObject->getCMSFields()`.
The resulting `[api:FieldList]` is the centrepiece of many data administration interfaces in SilverStripe.
Many customizations of the SilverStripe CMS interface start here,
by adding, removing or configuring fields.

	Example getCMSFields implementation

	:::php
	class MyDataObject extends DataObject {
		$db = array(
			'IsActive' => 'Boolean'
		);
	  public function getCMSFields() {
	    return new FieldList(
	    	new CheckboxField('IsActive')
	    );
	  }
	}

There's various [form field types](/references/form-field-types), for editing text, dates,
restricting input to numbers, and much more.

## Scaffolding Form Fields

The ORM already has a lot of information about the data represented by a `DataObject`
through its `$db` property, so why not use it to create form fields as well?
If you call the parent implementation, the class will use `[api:FormScaffolder]`
to provide reasonable defaults based on the property type (e.g. a checkbox field for booleans).
You can then further customize those fields as required.

	:::php
	class MyDataObject extends DataObject {
		// ...
	  public function getCMSFields() {
	    $fields = parent::getCMSFields();
	    $fields->fieldByName('IsActive')->setTitle('Is active?');
	    return $fields;
	  }
	}

The `[ModelAdmin](/reference/modeladmin)` class uses this approach to provide
data management interfaces with very little custom coding.

You can also alter the fields of built-in and module `DataObject` classes through
your own `[DataExtension](/reference/dataextension)`, and a call to `[api:DataExtension->updateCMSFields()]`.
`[api::DataObject->beforeUpdateCMSFields()]` can also be used to interact with and add to automatically
scaffolded fields prior to being passed to extensions (See `[DataExtension](/reference/dataextension)`).

### Searchable Fields

The `$searchable_fields` property uses a mixed array format that can be used to further customize your generated admin
system. The default is a set of array values listing the fields.

Example: Getting predefined searchable fields

	:::php
	$fields = singleton('MyDataObject')->searchableFields();


Example: Simple Definition

	:::php
	class MyDataObject extends DataObject {
	   private static $searchable_fields = array(
	      'Name',
	      'ProductCode'
	   );
	}


Searchable fields will be appear in the search interface with a default form field (usually a `[api:TextField]`) and a default
search filter assigned (usually an `[api:ExactMatchFilter]`). To override these defaults, you can specify additional information
on `$searchable_fields`:

	:::php
	class MyDataObject extends DataObject {
	   private static $searchable_fields = array(
	       'Name' => 'PartialMatchFilter',
	       'ProductCode' => 'NumericField'
	   );
	}


If you assign a single string value, you can set it to be either a `[api:FormField]` or `[api:SearchFilter]`. To specify both, you can
assign an array:

	:::php
	class MyDataObject extends DataObject {
	   private static $searchable_fields = array(
	       'Name' => array(
	          'field' => 'TextField',
	          'filter' => 'PartialMatchFilter',
	       ),
	       'ProductCode' => array(
	           'title' => 'Product code #',
	           'field' => 'NumericField',
	           'filter' => 'PartialMatchFilter',
	       ),
	   );
	}


To include relations (''$has_one'', `$has_many` and `$many_many`) in your search, you can use a dot-notation.

	:::php
	class Team extends DataObject {
	  private static $db = array(
	    'Title' => 'Varchar'
	  );
	  private static $many_many = array(
	    'Players' => 'Player'
	  );
	  private static $searchable_fields = array(
	      'Title',
	      'Players.Name',
	   );
	}
	class Player extends DataObject {
	  private static $db = array(
	    'Name' => 'Varchar',
	    'Birthday' => 'Date'
	  );
	  private static $belongs_many_many = array(
	    'Teams' => 'Team'
	  );
	}


### Summary Fields

Summary fields can be used to show a quick overview of the data for a specific `[api:DataObject]` record. Most common use is
their display as table columns, e.g. in the search results of a `[api:ModelAdmin]` CMS interface.

Example: Getting predefined summary fields

	:::php
	$fields = singleton('MyDataObject')->summaryFields();


Example: Simple Definition

	:::php
	class MyDataObject extends DataObject {
	  private static $db = array(
	    'Name' => 'Text',
	    'OtherProperty' => 'Text',
	    'ProductCode' => 'Int',
	  ); 
	  private static $summary_fields = array(
	    'Name',
	    'ProductCode'
	  );
	}


To include relations or field manipulations in your summaries, you can use a dot-notation.

	:::php
	class OtherObject extends DataObject {
	  private static $db = array(
	    'Title' => 'Varchar'
	  );
	}
	class MyDataObject extends DataObject {
	  private static $db = array(
	    'Name' => 'Text',
	    'Description' => 'HTMLText'
	  );
	  private static $has_one = array(
	    'OtherObject' => 'OtherObject'
	  );
	  private static $summary_fields = array(
	    'Name' => 'Name',
	    'Description.Summary' => 'Description (summary)',
	    'OtherObject.Title' => 'Other Object Title'
	  );
	}


Non-textual elements (such as images and their manipulations) can also be used in summaries.

	:::php
	class MyDataObject extends DataObject {
	  private static $db = array(
	    'Name' => 'Text'
	  );
	  private static $has_one = array(
	    'HeroImage' => 'Image'
	  );
	  private static $summary_fields = array(
	    'Name' => 'Name',
	    'HeroImage.CMSThumbnail' => 'Hero Image'
	  );
	}


## Permissions

Models can be modified in a variety of controllers and user interfaces,
all of which can implement their own security checks. But often it makes
sense to centralize those checks on the model, regardless of the used controller.

The API provides four methods for this purpose: 
`canEdit()`, `canCreate()`, `canView()` and `canDelete()`.
Since they're PHP methods, they can contain arbitrary logic
matching your own requirements. They can optionally receive a `$member` argument,
and default to the currently logged in member (through `Member::currentUser()`).

Example: Check for CMS access permissions

	class MyDataObject extends DataObject {
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
	}

**Important**: These checks are not enforced on low-level ORM operations
such as `write()` or `delete()`, but rather rely on being checked in the invoking code.
The CMS default sections as well as custom interfaces like
`[ModelAdmin](/reference/modeladmin)` or `[GridField](/reference/grid-field)`
already enforce these permissions.

## Indexes

It is sometimes desirable to add indexes to your data model, whether to
optimize queries or add a uniqueness constraint to a field. This is done
through the `DataObject::$indexes` map, which maps index names to descriptor
arrays that represent each index. There's several supported notations:

	:::php
	# Simple
	private static $indexes = array(
		'<column-name>' => true
	);

	# Advanced
	private static $indexes = array(
		'<index-name>' => array('type' => '<type>', 'value' => '"<column-name>"')
	);

	# SQL
	private static $indexes = array(
		'<index-name>' => 'unique("<column-name>")'
	);
	
The `<index-name>` can be an an arbitrary identifier in order to allow for more than one
index on a specific database column.
The "advanced" notation supports more `<type>` notations.
These vary between database drivers, but all of them support the following:

 * `index`: Standard index
 * `unique`: Index plus uniqueness constraint on the value
 * `fulltext`: Fulltext content index

In order to use more database specific or complex index notations,
we also support raw SQL for as a value in the `$indexes` definition.
Keep in mind this will likely make your code less portable between databases.

Example: A combined index on a two fields.

	:::php
	private static $db = array(
		'MyField' => 'Varchar',
		'MyOtherField' => 'Varchar',
	);
	private static $indexes = array(
		'MyIndexName' => array('type' => 'index', 'value' => '"MyField","MyOtherField"'),
	);

## API Documentation

`[api:DataObject]`
