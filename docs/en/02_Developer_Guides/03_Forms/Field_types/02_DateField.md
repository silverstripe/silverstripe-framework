title: DateField
summary: How to format and use the DateField class.

# DateField

This `FormField` subclass lets you display an editable date, either in a single text input field, or in three separate 
fields for day, month and year. It also provides a calendar date picker.

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

A custom date format for a [api:DateField] can be provided through `setConfig`.

	:::php
	// will display a date in the following format: 31-06-2012
	DateField::create('MyDate')->setConfig('dateformat', 'dd-MM-yyyy'); 

<div class="info" markdown="1">
The formats are based on [Zend_Date constants](http://framework.zend.com/manual/1.12/en/zend.date.constants.html).
</div>
 

## Min and Max Dates

Sets the minimum and maximum allowed date values using the `min` and `max` configuration settings (in ISO format or 
strtotime()).

	:::php
	DateField::create('MyDate')
		->setConfig('min', '-7 days')
		->setConfig('max', '2012-12-31')
		
## Separate Day / Month / Year Fields

The following setting will display your DateField as three input fields for day, month and year separately. HTML5 
placeholders 'day', 'month' and 'year' are enabled by default. 

	:::php
	DateField::create('MyDate')
		->setConfig('dmyfields', true)
		->setConfig('dmyseparator', '/') // set the separator
		->setConfig('dmyplaceholders', 'true'); // enable HTML 5 Placeholders

<div class="alert" markdown="1">
Any custom date format settings will be ignored. 
</div>

## Calendar Picker
 
The following setting will add a Calendar to a single DateField, using the jQuery UI DatePicker widget.

	:::php
	DateField::create('MyDate')
		->setConfig('showcalendar', true);

The jQuery DatePicker doesn't support every constant available for `Zend_Date`. If you choose to use the calendar, the 
following constants should at least be safe:

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

Unfortunately the day- and monthname values in Zend Date do not always match those in the existing jQuery UI locale 
files, so constants like `EEE` or `MMM`, for day and month names could break validation. To fix this we had to slightly 
alter the jQuery locale files, situated in */framework/thirdparty/jquery-ui/datepicker/i18n/*, to match Zend_Date. 

<div class="info">
At this moment not all locale files may be present. If a locale file is missing, the DatePicker calendar will fallback 
to 'yyyy-MM-dd' whenever day - and/or monthnames are used. After saving, the correct format will be displayed.  
</div>

## Formatting Hints

It's often not immediate apparent which format a field accepts, and showing the technical format (e.g. `HH:mm:ss`) is 
of limited use to the average user. An alternative is to show the current date in the desired format alongside the 
field description as an example.

	:::php
	$dateField = DateField::create('MyDate');

	// Show long format as text below the field
	$dateField->setDescription(sprintf(
		_t('FormField.Example', 'e.g. %s', 'Example format'),
		Convert::raw2xml(Zend_Date::now()->toString($dateField->getConfig('dateformat')))
	));

	// Alternatively, set short format as a placeholder in the field
	$dateField->setAttribute('placeholder', $dateField->getConfig('dateformat'));

<div class="notice" markdown="1">
Fields scaffolded through [api:DataObject::scaffoldCMSFields] automatically have a description attached to them.
</div>

## API Documentation

* [api:DateField]