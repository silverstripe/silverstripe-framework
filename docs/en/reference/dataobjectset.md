# DataObjectSet

## Introduction

This class represents a set of `[api:DataObject]`s, such as the results of a query. It is the base for all
[datamodel](/topics/datamodel)-related querying. It implements the [Iterator
interface](http://php.net/manual/en/language.oop5.iterations.php) introduced in PHP5.

Relations (`has_many`/`many_many`) are described in `[api:ComponentSet]`, a subclass of `[api:DataObjectSet]`.

## Usage

### Getting the size

	:::php
	$mySet->Count();

### Getting an single element

	:::php
	$myFirstDataObject = $mySet->First();
	$myLastDataObject = $mySet->Last();


### Getting multiple elements

	:::php
	$mySpecialDataObjects = $mySet->find('Status', 'special');
	$startingFromTen = $mySet->getOffset(10);
	$tenToTwenty = $mySet->getRange(10, 10);


### Getting one property

	:::php
	$myIDArray = $mySet->column('ID');

### Grouping

You can group a set by a specific column. Consider using `[api:SQLQuery]` with a *GROUP BY* statement for enhanced
performance.

	:::php
	$groupedSet = $mySet->groupBy('Lastname');

### Sorting

Sort a set by a specific column. 

	:::php
	$mySet->sort('Lastname'); //ascending
	$mySet->sort('Lastname', 'DESC'); //descending

This works on the object itself, so do NOT do something like this:

	:::php
	$sortedSet = $mySet->sort('Lastname'); //ascending

## Merge with other `[api:DataObjectSet]`s

	:::php
	$myFirstSet->merge($mySecondSet);
	// $myFirstSet now contains all combined values


### Mapping for Dropdowns

When using `[api:DropdownField]` and its numerous subclasses to select a value from a set, you can easily map
the records to a compatible array:

	:::php
	$map = $mySet->toDropDownMap('ID', 'Title');
	$dropdownField = new DropdownField('myField', 'my label', $map);


### Converting to array

	:::php
	$myArray = $mySet->toArray();

### Checking for existence

It is good practice to check for empty sets before doing any iteration.

	:::php
	$mySet = DataObject::get('Players');
	if($mySet->exists()) foreach($mySet as $player)

### Paging

`[api:DataObject]`s have native support for dealing with **pagination**.
See *setPageLimits*, *setPageLength*, etc.

FIXME Complete pagination documentation


## API Documentation
`[api:DataObjectSet]`
