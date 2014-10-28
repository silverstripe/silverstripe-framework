title: Managing Lists
summary: The SS_List interface allows you to iterate through and manipulate a list of objects.

# Managing Lists

Whenever using the ORM to fetch records or navigate relationships you will receive an [api:SS_List] instance commonly as
either [api:DataList] or [api:RelationList]. This object gives you the ability to iterate over each of the results or
modify.

## Iterating over the list.

[api:SS_List] implements `IteratorAggregate`, allowing you to loop over the instance.

	:::php
	$members = Member::get();

	foreach($members as $member) {
		echo $member->Name;
	}

Or in the template engine:

	:::ss
	<% loop $Members %>
		<!-- -->
	<% end_loop %>

## Finding an item by value.

	:::php
	// $list->find($key, $value);

	//
	$members = Member::get();

	echo $members->find('ID', 4)->FirstName;
	// returns 'Sam'


## Maps

A map is an array where the array indexes contain data as well as the values. You can build a map from any list

	:::php
	$members = Member::get()->map('ID', 'FirstName');
	
	// $members = array(
	//	1 => 'Sam'
	//	2 => 'Sig'
	//	3 => 'Will'
	// );
	
This functionality is provided by the [api:SS_Map] class, which can be used to build a map around any `SS_List`.

	:::php
	$members = Member::get();
	$map = new SS_Map($members, 'ID', 'FirstName');

## Column

	:::php
	$members = Member::get();

	echo $members->column('Email');

	// returns array(
	//	'sam@silverstripe.com',
	//	'sig@silverstripe.com',
	//	'will@silverstripe.com'
	// );

## ArrayList

[api:ArrayList] exists to wrap a standard PHP array in the same API as a database backed list.

	:::php
	$sam = Member::get()->byId(5);
	$sig = Member::get()->byId(6);

	$list = new ArrayList();
	$list->push($sam);
	$list->push($sig);

	echo $list->Count();
	// returns '2'


## API Documentation

* [api:SS_List]
* [api:RelationList]
* [api:DataList]
* [api:ArrayList]
* [api:SS_Map]