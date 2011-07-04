# DataObject

## Introduction

A single database record & abstract class for the data-access-model. 

## Usage

*  [datamodel](/topics/datamodel): The basic pricinples
*  [data-types](/topics/data-types): Casting and special property-parsing
*  `[api:DataObject]`: A "container" for DataObjects

## Basics

The call to `DataObject->getCMSFields()` is the centerpiece of every data administration interface in SilverStripe,
which returns a `[api:FieldSet]`''.

	:::php
	class MyPage extends Page {
	  function getCMSFields() {
	    $fields = parent::getCMSFields();
	    $fields->addFieldToTab('Root.Content',new CheckboxField('CustomProperty'));
	    return $fields;
	  }
	}


## Scaffolding Formfields

These calls retrieve a `[api:FieldSet]` for the area where you intend to work with the scaffolded form.

### For the CMS

 * Requirements: SilverStripe 2.3.*

	:::php
	$fields = singleton('MyDataObject')->getCMSFields();


### For the Frontend

Used for simple frontend forms without relation editing or `[api:TabSet] behaviour. Uses `scaffoldFormFields()` by
default. To customize, either overload this method in your subclass, or decorate it by `DataObjectDecorator->updateFormFields()`.

* Requirements: SilverStripe 2.3.*

	:::php
	$fields = singleton('MyDataObject')->getFrontEndFields();


## Customizing Scaffolded Fields

 * Requirements: SilverStripe 2.3.*

This section covers how to enhance the default scaffolded form fields from above.  It is particularly useful when used
in conjunction with the `[api:ModelAdmin]` in the CMS to make relevant data administration interfaces.


### Searchable Fields

* Requirements: SilverStripe 2.3.*

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

* Requirements: SilverStripe 2.3.*

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
