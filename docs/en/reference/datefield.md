# DateField

## Introduction

The DateField is a FormField that lets you display an editable date, either in a single text input field, or in three separate fields for day, month and year. It also provides a calendar datepicker.

## Adding a DateField 

The following example will add a simple DateField to your Page, allowing you to enter a date manually. 

	:::php
	class Page extends SiteTree {
		static $db = array(
			'MyDate' => 'Date',
		);
	
		public function getCMSFields() {
			$fields = parent::getCMSFields();
			
			$fields->AddFieldToTab(
				'Root.Main',
				$myDate = new DateField('MyDate', 'Enter a date')
			);
			
			return $fields;
		} 
	}	

## Custom dateformat

You can define a custom dateformat for your Datefield based on Zend_Date constants. Find a list of valid constants [here](http://framework.zend.com/manual/1.12/en/zend.date.constants.html).

	:::php
		...
		$fields->AddFieldToTab(
			'Root.Main',
			$myDate = new DateField('MyDate', 'Enter a date')
		); 
		
		// will display a date in the following format: 31-06-2012
		$myDate->setConfig('dateformat', 'dd-MM-yyyy'); 
 

## Min and max dates

Set the minimum and maximum allowed datevalues using the `min` and `max` configuration settings (in ISO format or strtotime() compatible). Example: 

	:::php
		...
		$myDate->setConfig('min', '-7 days');
		$myDate->setConfig('max', '2012-12-31');

		
## Dmy fields

The following setting will display your DateField as `three input fields` for day, month and year separately. Any custom dateformat settings will be ignored.

	:::php
		...
		$myDate->setConfig('dmyfields', true);
		
		// set the separator
		$dateOfBirth->setConfig('dmyseparator', '/');

		// enable HTML 5 Placeholders (day, month year). Enabled by default.
		$dateOfBirth->setConfig('dmyplaceholders', 'true');

## Calendar field
 
The following setting will add a Calendar to a single DateField, using the `jQuery UI DatePicker widget`

	:::php
		...
		$myDate->setConfig('showcalendar', true);


### 'Safe' dateformats to use with the calendar

The jQuery DatePicker doesn't support every constant available for Zend_Date. If you choose to use the calendar, the following constants should at least be safe:

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

### Calendar localization issues

Unfortunately the day- and monthname values in Zend Date do not always match those in the existing jQuery UI locale files, so constants like `EEE` or `MMM`, for day and monthnames could breakl validation. To fix this we had to slightly alter the jQuery locale files, situated in */framework/thirdparty/jquery-ui/datepicker/i18n/*, to match Zend_Date. 

At this moment not all locale files may be present. If a locale file is missing, the DatePicker calendar will fallback to 'yyyy-MM-dd' whenever day- and/or monthnames are used. After saving, the correct format will be displayed.  

## Contributing jQuery locale files

If you find the jQuery locale file for your chosen locale is missing, the following section will explain how to create one. If you wish to contribute your file to the SilverStripe core, please check out ['Contributing'](http://doc.silverstripe.org/framework/en/trunk/misc/contributing).

### 1. Get your localefile from google

You can find a list of localefiles for the jQuery UI DatePicker [here on Google](http://jquery-ui.googlecode.com/svn/trunk/ui/i18n/m "jQuery UI DatePicker locale files").

### 2. Find your Zend Locale file

The Zend locale files are located in */framework/thirdparty/Zend/Locale/Data/*. Find the one that has the information for your locale. 

### 3. Find the date values

You're looking for the `Gregorian` date values for monthnames and daynames in the Zend locale file. Edit the DatePicker locale File so your *full day- and monthnames* and *short monthnames* match. For your *short daynames*, use the first three characters of the full name. Note that Zend dates are `case sensitive`!

### 4. Filename

Use the original jQuery UI filename 'jquery.ui.datepicker-xx.js', where xx stands for the locale.