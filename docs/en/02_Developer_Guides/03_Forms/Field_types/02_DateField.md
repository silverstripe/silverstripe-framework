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
The formats are based on [CLDR format](http://userguide.icu-project.org/formatparse/datetime).
</div>
 

## Min and Max Dates

Sets the minimum and maximum allowed date values using the `min` and `max` configuration settings (in ISO format or 
strtotime()).

	:::php
	DateField::create('MyDate')
		->setMinDate('-7 days')
		->setMaxDate'2012-12-31')
		
## Separate Day / Month / Year Fields

To display separate input fields for day, month and year separately you can use the `SeparatedDateField` subclass`.
HTML5 placeholders 'day', 'month' and 'year' are enabled by default. 

	:::php
	SeparatedDateField::create('MyDate');

<div class="alert" markdown="1">
Any custom date format settings will be ignored. 
</div>

## Calendar Picker
 
The following setting will add a Calendar to a single DateField, using the jQuery UI DatePicker widget.

	:::php
	DateField::create('MyDate')
		->setShowCalendar(true);

The jQuery date picker will support most custom locale formats (if left as default).
If setting an explicit date format via setDateFormat() then the below table of supported
characters should be used.

It is recommended to use numeric format, as `MMM` or `MMMM` month names may not always pass validation.

Constant | xxxxx
-------- | -----
d        | numeric day of the month (without leading zero)
dd       | numeric day of the month (with leading zero)
EEE      | dayname, abbreviated
EEEE     | dayname
M        | numeric month of the year (without leading zero)
MM       | numeric month of the year (with leading zero)
MMM	     | monthname, abbreviated	
MMMM     | monthname
y        | year (4 digits)
yy       | year (2 digits)
yyyy     | year (4 digits)

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
