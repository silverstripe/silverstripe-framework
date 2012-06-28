# Complex Table Field

## Introduction

<div class="warning" markdown="1">
	This field is deprecated in favour of the new [GridField](/topics/grid-field) API.
</div>

Shows a group of DataObjects as a (readonly) tabular list (similiar to `[api:TableListField]`.)

You can specify limits and filters for the resultset by customizing query-settings (mostly the ID-field on the other
side of a one-to-many-relationship).

See `[api:TableListField]` for more documentation on the base-class

## Source Input

See `[api:TableListField]`.

## Setting Parent/Child-Relations

`[api:ComplexTableField]` tries to determine the parent-relation automatically by looking at the $has_one property on the listed
child, or the record loaded into the surrounding form (see getParentClass() and getParentIdName()). You can force a
specific parent relation:

	:::php
	$myCTF->setParentClass('ProductGroup');


## Customizing Popup

By default, getCMSFields() is called on the listed DataObject.
You can override this behaviour in various ways:

	:::php
	// option 1: implicit (left out of the constructor), chooses based on Object::useCustomClass or specific instance
	$myCTF = new ComplexTableField(
	  $this,
	  'MyName',
	  'Product',
	  array('Price','Code')
	)
	
	// option 2: constructor
	$myCTF = new ComplexTableField(
	  $this,
	  'MyName',
	  'Product',
	  array('Price','Code'),
	  new FieldList(
	    new TextField('Price')
	  )
	)
	
	// option 3: constructor function
	$myCTF = new ComplexTableField(
	  $this,
	  'MyName',
	  'Product',
	  array('Price','Code'),
	  'getCustomCMSFields'
	)


## Customizing Display & Functionality

If you don't want several functions to appear (e.g. no add-link), there's several ways:

*  Use `ComplexTableField->setPermissions(array("show","edit"))` to limit the functionality without touching the template
(more secure). Possible values are "show","edit", "delete" and "add".  

*  Subclass `[api:ComplexTableField]` and override the rendering-mechanism
*  Use `ComplexTableField->setTemplate()` and `ComplexTableField->setTemplatePopup()` to provide custom templates

### Customising fields and Requirements in the popup

There are several ways to customise the fields in the popup. Often you would want to display more information in the
popup as there is more real-estate for you to play with. 

`[api:ComplexTableField]` gives you several options to do this. You can either

*  Pass a FieldList in the constructor.
*  Pass a String in the constructor. 

The first will simply add the fieldlist to the form, and populate it with the source class. 
The second will call the String as a method on the source class (Which should return a FieldList) of fields for the
Popup. 

You can also customise Javascript which is loaded for the Lightbox. As Requirements::clear() is called when the popup is
instantiated, `[api:ComplexTableField]` will look for a function to gather any specific requirements that you might need on your
source class. (e.g. Inline Javascript or styling).

For this, create a function called "getRequirementsForPopup". 

## Getting it working on the front end (not the CMS)

Sometimes you'll want to have a nice table on the front end, so you can move away from relying on the CMS for maintaing
parts of your site.

You'll have to do something like this in your form:

	:::php
	$tableField = new ComplexTableField(
	   $controller,
	   'Works',
	   'Work',
	   array(
	      'MyField' => 'My awesome field name'
	   ),
	   'getPopupFields'
	);
	
	$tableField->setParentClass(false);
			
	$fields = new FieldList(
	   new HiddenField('ID', ''),
	   $tableField
	);


You have to hack in an ID on the form, as the CMS forms have this, and front end forms usually do not.

It's not a perfect solution, but it works relatively well to get a simple `[api:ComplexTableField]` up and running on the front
end.

To come: Make it a lot more flexible so tables can be easily used on the front end. It also needs to be flexible enough
to use a popup as well, out of the box.

## Subclassing

Most of the time, you need to override the following methods:

*  ComplexTableField->sourceItems() - querying
*  ComplexTableField->DetailForm() - form output
*  ComplexTableField_Popup->saveComplexTableField() - saving