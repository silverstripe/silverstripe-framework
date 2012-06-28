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

### Searchable Fields

The `$searchable_fields` property uses a mixed array format that can be used to further customize your generated admin
system. The default is a set of array values listing the fields.

Example: Getting predefined searchable fields

	:::php
	$fields = singleton('MyDataObject')->searchableFields();


Example: Simple Definition

	:::php
	class MyDataObject extends DataObject {
	   static $searchable_fields = array(
	      'Name',
	      'ProductCode'
	   );
	}


Searchable fields will be appear in the search interface with a default form field (usually a `[api:TextField]`) and a default
search filter assigned (usually an `[api:ExactMatchFilter]`). To override these defaults, you can specify additional information
on `$searchable_fields`:

	:::php
	class MyDataObject extends DataObject {
	   static $searchable_fields = array(
	       'Name' => 'PartialMatchFilter',
	       'ProductCode' => 'NumericField'
	   );
	}


If you assign a single string value, you can set it to be either a `[api:FormField]` or `[api:SearchFilter]`. To specify both, you can
assign an array:

	:::php
	class MyDataObject extends DataObject {
	   static $searchable_fields = array(
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
	  static $db = array(
	    'Title' => 'Varchar'
	  );
	  static $many_many = array(
	    'Players' => 'Player'
	  );
	  static $searchable_fields = array(
	      'Title',
	      'Players.Name',
	   );
	}
	class Player extends DataObject {
	  static $db = array(
	    'Name' => 'Varchar',
	    'Birthday' => 'Date'
	  );
	  static $belongs_many_many = array(
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
	  static $db = array(
	    'Name' => 'Text',
	    'OtherProperty' => 'Text',
	    'ProductCode' => 'Int',
	  ); 
	  static $summary_fields = array(
	      'Name',
	      'ProductCode'
	   );
	}


To include relations in your summaries, you can use a dot-notation.

	:::php
	class OtherObject extends DataObject {
	  static $db = array(
	    'Title' => 'Varchar'
	  );
	}
	class MyDataObject extends DataObject {
	  static $db = array(
	    'Name' => 'Text'
	  );
	  static $has_one = array(
	    'OtherObject' => 'OtherObject'
	  );
	   static $summary_fields = array(
	      'Name',
	      'OtherObject.Title'
	   );
	}


## API Documentation

`[api:DataObject]`
