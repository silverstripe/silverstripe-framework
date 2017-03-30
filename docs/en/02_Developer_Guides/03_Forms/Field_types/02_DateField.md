title: DateField
summary: How to format and use the DateField class.

# DateField

This `FormField` subclass lets you display an editable date, in a single text input field.
It also provides a calendar date picker.

The following example will add a simple DateField to your Page, allowing you to enter a date manually. 

**mysite/code/Page.php**

	:::php
	<?php

	class Page extends SiteTree {

		private static $db = array(
			'MyDate' => 'Date',
		);
	
		public function getCMSFields() {
			$fields = parent::getCMSFields();
			
			$fields->addFieldToTab(
				'Root.Main',
				DateField::create('MyDate', 'Enter a date')
			);
			
			return $fields;
		} 
	}	

## Custom Date Format

A custom date format for a [api:DateField] can be provided through `setDateFormat`.

	:::php
	// will display a date in the following format: 31-06-2012
	DateField::create('MyDate')->setDateFormat('dd-MM-yyyy'); 

<div class="info" markdown="1">
The formats are based on [ICU format](http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details).
</div>
 

## Min and Max Dates

Sets the minimum and maximum allowed date values using the `min` and `max` configuration settings (in ISO format or 
`strtotime()`).

	:::php
	DateField::create('MyDate')
		->setMinDate('-7 days')
		->setMaxDate('2012-12-31')
		
## Separate Day / Month / Year Fields

To display separate input fields for day, month and year separately you can use the `SeparatedDateField` subclass`.
HTML5 placeholders 'day', 'month' and 'year' are enabled by default. 

	:::php
	SeparatedDateField::create('MyDate');

<div class="alert" markdown="1">
Any custom date format settings will be ignored. 
</div>

## Date Picker and HTML5 support
 
The field can be used as a [HTML5 input date type](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/date)
(with `type=date`) by calling `setHTML5(true)`.

	:::php
	DateField::create('MyDate')
		->setHTML5(true);

In browsers [supporting HTML5 date inputs](caniuse.com/#feat=input-datetime),
this will cause a localised date picker to appear for users.
In this mode, the field will be forced to present and save ISO 8601 date formats (`y-MM-dd`),
since the browser takes care of converting to/from a localised presentation.

Browsers without support receive an `<input type=text>` based polyfill.

## Formatting Hints

It's often not immediate apparent which format a field accepts, and showing the technical format (e.g. `HH:mm:ss`) is 
of limited use to the average user. An alternative is to show the current date in the desired format alongside the 
field description as an example.

	:::php
	$dateField = DateField::create('MyDate');

	// Show long format as text below the field
	$dateField->setDescription(_t(
	    'FormField.Example',
	     'e.g. {format}',
	     [ 'format' =>  $dateField->getDateFormat() ]
	));

	// Alternatively, set short format as a placeholder in the field
	$dateField->setAttribute('placeholder', $dateField->getDateFormat());

<div class="notice" markdown="1">
Fields scaffolded through [api:DataObject::scaffoldCMSFields()] automatically have a description attached to them.
</div>

## API Documentation

* [api:DateField]
