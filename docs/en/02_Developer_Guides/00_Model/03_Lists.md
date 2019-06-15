---
title: Managing Lists
summary: The SS_List interface allows you to iterate through and manipulate a list of objects.
icon: list
---

# Managing Lists

Whenever using the ORM to fetch records or navigate relationships you will receive an [SS_List](api:SilverStripe\ORM\SS_List) instance commonly as
either [DataList](api:SilverStripe\ORM\DataList) or [RelationList](api:SilverStripe\ORM\RelationList). This object gives you the ability to iterate over each of the results or
modify.

## Iterating over the list

[SS_List](api:SilverStripe\ORM\SS_List) implements `IteratorAggregate`, allowing you to loop over the instance.

```php
use SilverStripe\Security\Member;

$members = Member::get();

foreach($members as $member) {
    echo $member->Name;
}
```

Or in the template engine:

```ss
<% loop $Members %>
    <!-- -->
<% end_loop %>
```

## Finding an item by value

```php
// $list->find($key, $value);

//
$members = Member::get();

echo $members->find('ID', 4)->FirstName;
// returns 'Sam'
```

## Maps

A map is an array where the array indexes contain data as well as the values. You can build a map from any list

```php
$members = Member::get()->map('ID', 'FirstName');

// $members = [
//    1 => 'Sam'
//    2 => 'Sig'
//    3 => 'Will'
// ];
```

This functionality is provided by the [Map](api:SilverStripe\ORM\Map) class, which can be used to build a map around any `SS_List`.

```php
$members = Member::get();
$map = new Map($members, 'ID', 'FirstName');
```

## Column

```php
$members = Member::get();

echo $members->column('Email');

// returns [
//    'sam@silverstripe.com',
//    'sig@silverstripe.com',
//    'will@silverstripe.com'
// ];
```

## Iterating over a large list {#chunk}

When iterating over a DataList, all DataObjects in the list will be loaded in memory. This can consume a lot of memory when working with a large data set.

To limit the number of DataObjects loaded in memory, you can use the `chunk()` method on your DataList. In most cases, you can iterate over the results of `chunk()` the same way you would iterate over your DataList. Internally, `chunk()` will split your DataList query into smaller queries and keep running through them until it runs out of results.

```php
$members = Member::get();
foreach ($members as $member) {
    echo $member->Email;
}

// This call will produce the same output, but it will use less memory and run more queries against the database
$members = Member::get()->chunk();
foreach ($members as $member) {
    echo $member->Email;
}
```

`chunk()` will respect any filter or sort condition applied to the DataList. By default, chunk will limit each query to 100 results. You can explicitly set this limit by passing an integer to `chunk()`.

```php
$members = Member::get()
    ->filter('Email:PartialMatch', 'silverstripe.com')
    ->sort('Email')
    ->chunk(10);
foreach ($members as $member) {
    echo $member->Email;
}
```

They are some limitations:
* `chunk()` will ignore any limit or offset you have applied to your DataList
* you can not "count" a chunked list or do any other call against it aside from iterating it
* while iterating over a chunked list, you can not perform any operation that would alter the order of the items.

## ArrayList

[ArrayList](api:SilverStripe\ORM\ArrayList) exists to wrap a standard PHP array in the same API as a database backed list.

```php
$sam = Member::get()->byId(5);
$sig = Member::get()->byId(6);

$list = new ArrayList();
$list->push($sam);
$list->push($sig);

echo $list->Count();
// returns '2'
```

## Related Lessons
* [Lists and pagination](https://www.silverstripe.org/learn/lessons/v4/lists-and-pagination-1)

## API Documentation

* [SS_List](api:SilverStripe\ORM\SS_List)
* [RelationList](api:SilverStripe\ORM\RelationList)
* [DataList](api:SilverStripe\ORM\DataList)
* [ArrayList](api:SilverStripe\ORM\ArrayList)
* [Map](api:SilverStripe\ORM\Map)
