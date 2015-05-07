title: Building Model and Search Interfaces around Scaffolding
summary: A Model-driven approach to defining your application UI.

# Scaffolding

The ORM already has a lot of information about the data represented by a `DataObject` through its `$db` property, so 
SilverStripe will use that information to provide scaffold some interfaces. This is done though [api:FormScaffolder]
to provide reasonable defaults based on the property type (e.g. a checkbox field for booleans). You can then further 
customise those fields as required.

## Form Fields

An example is `DataObject`, SilverStripe will automatically create your CMS interface so you can modify what you need.

	:::php
	<?php

	class MyDataObject extends DataObject {
		
		private static $db = array(
			'IsActive' => 'Boolean',
			'Title' => 'Varchar',
			'Content' => 'Text'
		);

		public function getCMSFields() {
			// parent::getCMSFields() does all the hard work and creates the fields for Title, IsActive and Content.
			$fields = parent::getCMSFields();
			$fields->dataFieldByName('IsActive')->setTitle('Is active?');
			
			return $fields;
		}
	}

To fully customise your form fields, start with an empty FieldList.

	:::php
	<?php

		public function getCMSFields() {
			$fields = FieldList::create(
				TabSet::create("Root.Main",
					CheckboxSetField::create('IsActive','Is active?'),
					TextField::create('Title'),
					TextareaField::create('Content')
						->setRows(5)
				)
			);
			
			return $fields;
		}



You can also alter the fields of built-in and module `DataObject` classes through your own 
[DataExtension](/developer_guides/extending/extensions), and a call to `DataExtension->updateCMSFields`.

## Searchable Fields

The `$searchable_fields` property uses a mixed array format that can be used to further customise your generated admin
system. The default is a set of array values listing the fields.

	:::php
	<?php

	class MyDataObject extends DataObject {
	
	   private static $searchable_fields = array(
	      'Name',
	      'ProductCode'
	   );
	}


Searchable fields will be appear in the search interface with a default form field (usually a [api:TextField]) and a 
default search filter assigned (usually an [api:ExactMatchFilter]). To override these defaults, you can specify 
additional information on `$searchable_fields`:

	:::php
	<?php

	class MyDataObject extends DataObject {

		private static $searchable_fields = array(
			'Name' => 'PartialMatchFilter',
			'ProductCode' => 'NumericField'
		);
	}

If you assign a single string value, you can set it to be either a [api:FormField] or [api:SearchFilter]. To specify 
both, you can assign an array:

	:::php
	<?php

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


To include relations (`$has_one`, `$has_many` and `$many_many`) in your search, you can use a dot-notation.

	:::php
	<?php

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

Summary fields can be used to show a quick overview of the data for a specific [api:DataObject] record. The most common use 
is their display as table columns, e.g. in the search results of a `[api:ModelAdmin]` CMS interface.

	:::php
	<?php

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
	<?php

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
	<?php

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

## Related Documentation

* [SearchFilters](searchfilters)

## API Documentation

* [api:FormScaffolder]
* [api:DataObject]
