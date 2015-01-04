title: Introduction to the Data Model and ORM
summary: Introduction to creating and querying a database records through the ORM (object-relational model)

# Introduction to the Data Model and ORM

SilverStripe uses an [object-relational model](http://en.wikipedia.org/wiki/Object-relational_model) to represent its
information.

*  Each database table maps to a PHP class.
*  Each database row maps to a PHP object.
*  Each database column maps to a property on a PHP object.

All data tables in SilverStripe are defined as subclasses of [api:DataObject]. The [api:DataObject] class represents a 
single row in a database table, following the ["Active Record"](http://en.wikipedia.org/wiki/Active_record_pattern) 
design pattern. Database Columns are is defined as [Data Types](data_types_and_casting) in the static `$db` variable 
along with any [relationships](../relations) defined as `$has_one`, `$has_many`, `$many_many` properties on the class.

Let's look at a simple example:

**mysite/code/Player.php**

	:::php
	<?php

	class Player extends DataObject {

		private static $db = array(
			'PlayerNumber' => 'Int',
			'FirstName' => 'Varchar(255)',
			'LastName' => 'Text',
			'Birthday' => 'Date'
		);
	}


This `Player` class definition will create a database table `Player` with columns for `PlayerNumber`, `FirstName` and 
so on. After writing this class, we need to regenerate the database schema.

## Generating the Database Schema

After adding, modifying or removing `DataObject` subclasses, make sure to rebuild your SilverStripe database. The 
database schema is generated automatically by visiting the URL http://www.yoursite.com/dev/build while authenticated as an administrator.

This script will analyze the existing schema, compare it to what's required by your data classes, and alter the schema 
as required.  

It will perform the following changes:

  * Create any missing tables
  * Create any missing fields
  * Create any missing indexes
  * Alter the field type of any existing fields
  * Rename any obsolete tables that it previously created to _obsolete_(tablename)

It **won't** do any of the following

  * Delete tables
  * Delete fields
  * Rename any tables that it doesn't recognize. This allows other applications to coexist in the same database, as long as 
  their table names don't match a SilverStripe data class.


<div class="notice" markdown='1'>
You need to be logged in as an administrator to perform this command, unless your site is in [dev mode](../debugging), 
or the command is run through [CLI](../cli).
</div>

When rebuilding the database schema through the [api:SS_ClassLoader] the following additional properties are 
automatically set on the `DataObject`.

*  ID: Primary Key. When a new record is created, SilverStripe does not use the database's built-in auto-numbering 
system. Instead, it will generate a new `ID` by adding 1 to the current maximum ID.
*  ClassName: An enumeration listing this data-class and all of its subclasses.
*  Created: A date/time field set to the creation date of this record
*  LastEdited: A date/time field set to the date this record was last edited through `write()`

**mysite/code/Player.php**

	:::php
	<?php

	class Player extends DataObject {

		private static $db = array(
			'PlayerNumber' => 'Int',
			'FirstName' => 'Varchar(255)',
			'LastName' => 'Text',
			'Birthday' => 'Date'
		);
	}

Generates the following `SQL`.

	CREATE TABLE `Player` (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`ClassName` enum('Player') DEFAULT 'Player',
		`LastEdited` datetime DEFAULT NULL,
		`Created` datetime DEFAULT NULL,
  		`PlayerNumber` int(11) NOT NULL DEFAULT '0',
		`FirstName` varchar(255) DEFAULT NULL,
  		`LastName` mediumtext,
  		`Birthday` datetime DEFAULT NULL,
  		
  		PRIMARY KEY (`ID`),
		KEY `ClassName` (`ClassName`)
	);

## Creating Data Records

A new instance of a [api:DataObject] can be created using the `new` syntax.
	
	:::php
	$player = new Player();

Or, a better way is to use the `create` method.

	:::php
	$player = Player::create();

<div class="notice" markdown='1'>
Using the `create()` method provides chainability, which can add elegance and brevity to your code, e.g. `Player::create()->write()`. More importantly, however, it will look up the class in the [Injector](../extending/injector) so that the class can be overriden by [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection).
</div>


Database columns and properties can be set as class properties on the object. The SilverStripe ORM handles the saving
of the values through a custom `__set()` method.

	:::php
	$player->FirstName = "Sam";
	$player->PlayerNumber = 07;

To save the `DataObject` to the database, use the `write()` method. The first time `write()` is called, an `ID` will be
set.
	
	:::php
	$player->write();

For convenience, the `write()` method returns the record's ID. This is particularly useful when creating new records.

	:::php
	$player = Player::create();
	$id = $player->write();

## Querying Data

With the `Player` class defined we can query our data using the `ORM` or Object-Relational Model. The `ORM` provides 
shortcuts and methods for fetching, sorting and filtering data from our database.

	:::php
	$players = Player::get();
	// returns a `DataList` containing all the `Player` objects.

	$player = Player::get()->byId(2);
	// returns a single `Player` object instance that has the ID of 2.

	echo $player->ID;
	// returns the players 'ID' column value

	echo $player->dbObject('LastEdited')->Ago();
	// calls the `Ago` method on the `LastEdited` property.

The `ORM` uses a "fluent" syntax, where you specify a query by chaining together different methods.  Two common methods 
are `filter()` and `sort()`:

	:::php
	$members = Player::get()->filter(array(
		'FirstName' => 'Sam'
	))->sort('Surname');

	// returns a `DataList` containing all the `Player` records that have the `FirstName` of 'Sam'

<div class="info" markdown="1">
Provided `filter` values are automatically escaped and do not require any escaping.
</div>

## Lazy Loading

The `ORM` doesn't actually execute the [api:SQLQuery] until you iterate on the result with a `foreach()` or `<% loop %>`.

It's smart enough to generate a single efficient query at the last moment in time without needing to post-process the 
result set in PHP. In `MySQL` the query generated by the ORM may look something like this

	:::php
	$players = Player::get()->filter(array(
		'FirstName' => 'Sam'
	));

	$players = $players->sort('Surname');

	// executes the following single query
	// SELECT * FROM Player WHERE FirstName = 'Sam' ORDER BY Surname


This also means that getting the count of a list of objects will be done with a single, efficient query.

	:::php
	$players = Player::get()->filter(array(
		'FirstName' => 'Sam'
	))->sort('Surname');
	
	// This will create an single SELECT COUNT query
	// SELECT COUNT(*) FROM Player WHERE FirstName = 'Sam'
	echo $players->Count();
	
## Looping over a list of objects

`get()` returns a `DataList` instance. You can loop over `DataList` instances in both PHP and templates.
	
	:::php
	$players = Player::get();

	foreach($players as $player) {
		echo $player->FirstName;
	}

Notice that we can step into the loop safely without having to check if `$players` exists. The `get()` call is robust, and will at worst return an empty `DataList` object. If you do want to check if the query returned any records, you can use the `exists()` method, e.g.

	:::php
	$players = Player::get();

	if($players->exists()) {
		// do something here
	}

See the [Lists](../lists) documentation for more information on dealing with [api:SS_List] instances.

## Returning a single DataObject

There are a couple of ways of getting a single DataObject from the ORM. If you know the ID number of the object, you 
can use `byID($id)`:

	:::php
	$player = Player::get()->byID(5);

`get()` returns a [api:DataList] instance. You can use operations on that to get back a single record.

	:::php
	$players = Player::get();

	$first = $players->first();
	$last = $players->last();

## Sorting

If would like to sort the list by `FirstName` in a ascending way (from A to Z).

	:::php
	 // Sort can either be Ascending (ASC) or Descending (DESC)
	$players = Player::get()->sort('FirstName', 'ASC');

	 // Ascending is implied
	$players = Player::get()->sort('FirstName');

To reverse the sort

	:::php
	$players = Player::get()->sort('FirstName', 'DESC');

	// or..
	$players = Player::get()->sort('FirstName', 'ASC')->reverse();

However you might have several entries with the same `FirstName` and would like to sort them by `FirstName` and 
`LastName`

	:::php
	$players = Players::get()->sort(array(
		'FirstName' => 'ASC',
		'LastName'=>'ASC'
	));

You can also sort randomly.

	:::php
	$players = Player::get()->sort('RAND()')
	

## Filtering Results

The `filter()` method filters the list of objects that gets returned.

	:::php
	$players = Player::get()->filter(array(
		'FirstName' => 'Sam'
	));

Each element of the array specifies a filter.  You can specify as many filters as you like, and they **all** must be 
true for the record to be included in the result.

The key in the filter corresponds to the field that you want to filter and the value in the filter corresponds to the 
value that you want to filter to.

So, this would return only those players called "Sam Minnée".

	:::php
	$players = Player::get()->filter(array(
		'FirstName' => 'Sam',
		'LastName' => 'Minnée',
	));

	// SELECT * FROM Player WHERE FirstName = 'Sam' AND LastName = 'Minnée'

There is also a shorthand way of getting Players with the FirstName of Sam.

	:::php
	$players = Player::get()->filter('FirstName', 'Sam');

Or if you want to find both Sam and Sig.

	:::php
	$players = Player::get()->filter(
		'FirstName', array('Sam', 'Sig')
	);

	// SELECT * FROM Player WHERE FirstName IN ('Sam', 'Sig')

You can use [SearchFilters](searchfilters) to add additional behavior to your `filter` command rather than an 
exact match.

	:::php
	$players = Player::get()->filter(array(
		'FirstName:StartsWith' => 'S'
		'PlayerNumber:GreaterThan' => '10'
	));

### filterAny

Use the `filterAny()` method to match multiple criteria non-exclusively (with an "OR" disjunctive), 

	:::php
	$players = Player::get()->filterAny(array(
		'FirstName' => 'Sam',
		'Age' => 17,
	));

	// SELECT * FROM Player WHERE ("FirstName" = 'Sam' OR "Age" = '17')

You can combine both conjunctive ("AND") and disjunctive ("OR") statements.

	:::php
	$players = Player::get()
		->filter(array(
			'LastName' => 'Minnée'
		))
		->filterAny(array(
			'FirstName' => 'Sam',
			'Age' => 17,
		));
	// SELECT * FROM Player WHERE ("LastName" = 'Minnée' AND ("FirstName" = 'Sam' OR "Age" = '17'))

You can use [SearchFilters](searchfilters) to add additional behavior to your `filterAny` command.

	:::php
	$players = Player::get()->filterAny(array(
		'FirstName:StartsWith' => 'S'
		'PlayerNumber:GreaterThan' => '10'
	));


### filterByCallback

It is also possible to filter by a PHP callback, this will force the data model to fetch all records and loop them in 
PHP, thus `filter()` or `filterAny()` are to be preferred over `filterByCallback()`.    

<div class="notice" markdown="1">
Because `filterByCallback()` has to run in PHP, it has a significant performance tradeoff, and should not be used on large recordsets. 

`filterByCallback()` will always return  an `ArrayList`.
</div>

The first parameter to the callback is the item, the second parameter is the list itself. The callback will run once 
for each record, if the callback returns true, this record will be added to the list of returned items.    

The below example will get all `Players` aged over 10.

	:::php
	$players = Player::get()->filterByCallback(function($item, $list) {
		return ($item->Age() > 10);
	});

### Exclude

The `exclude()` method is the opposite to the filter in that it removes entries from a list.

	:::php
	$players = Player::get()->exclude('FirstName', 'Sam');

	// SELECT * FROM Player WHERE FirstName != 'Sam'

Remove both Sam and Sig..

	:::php
	$players = Player::get()->exclude(
		'FirstName', array('Sam','Sig')
	);

`Exclude` follows the same pattern as filter, so for removing only Sam Minnée from the list:

	:::php
	$players = Player::get()->exclude(array(
		'FirstName' => 'Sam',
		'Surname' => 'Minnée',
	));

And removing Sig and Sam with that are either age 17 or 74.

	:::php
	$players = Player::get()->exclude(array(
		'FirstName' => array('Sam', 'Sig'),
		'Age' => array(17, 43)
	));

	// SELECT * FROM Player WHERE ("FirstName" NOT IN ('Sam','Sig) OR "Age" NOT IN ('17', '74));

You can use [SearchFilters](searchfilters) to add additional behavior to your `exclude` command.

	:::php
	$players = Player::get()->exclude(array(
		'FirstName:EndsWith' => 'S'
		'PlayerNumber:LessThanOrEqual' => '10'
	));

### Subtract

You can subtract entries from a [api:DataList] by passing in another DataList to `subtract()`

	:::php
	$sam = Player::get()->filter('FirstName', 'Sam');
	$players = Player::get();

	$noSams = $players->subtract($sam);

Though for the above example it would probably be easier to use `filter()` and `exclude()`. A better use case could be 
when you want to find all the members that does not exist in a Group.

	:::php
	// ... Finding all members that does not belong to $group.
	$otherMembers = Member::get()->subtract($group->Members());

### Limit

You can limit the amount of records returned in a DataList by using the `limit()` method.

	:::php
	$members = Member::get()->limit(5);
	
`limit()` accepts two arguments, the first being the amount of results you want returned, with an optional second 
parameter to specify the offset, which allows you to tell the system where to start getting the results from. The 
offset, if not provided as an argument, will default to 0.

	:::php
	// Return 10 members with an offset of 4 (starting from the 5th result).
	$members = Member::get()->sort('Surname')->limit(10, 4);

<div class="alert">
Note that the `limit` argument order is different from a MySQL LIMIT clause.
</div>

### Raw SQL

Occasionally, the system described above won't let you do exactly what you need to do. In these situations, we have 
methods that manipulate the SQL query at a lower level.  When using these, please ensure that all table and field names 
are escaped with double quotes, otherwise some DB backends (e.g. PostgreSQL) won't work.

Under the hood, query generation is handled by the `[api:DataQuery]` class. This class does provide more direct access 
to certain SQL features that `DataList` abstracts away from you.

In general, we advise against using these methods unless it's absolutely necessary. If the ORM doesn't do quite what 
you need it to, you may also consider extending the ORM with new data types or filter modifiers 

#### Where clauses

You can specify a WHERE clause fragment (that will be combined with other filters using AND) with the `where()` method:

	:::php
	$members = Member::get()->where("\"FirstName\" = 'Sam'")

#### Joining Tables

You can specify a join with the `innerJoin` and `leftJoin` methods.  Both of these methods have the same arguments:

 * The name of the table to join to.
 * The filter clause for the join.
 * An optional alias.

	:::php
	// Without an alias
	$members = Member::get()
		->leftJoin("Group_Members", "\"Group_Members\".\"MemberID\" = \"Member\".\"ID\"");

	$members = Member::get()
		->innerJoin("Group_Members", "\"Rel\".\"MemberID\" = \"Member\".\"ID\"", "Rel");
	
<div class="alert" markdown="1">
Passing a *$join* statement to will filter results further by the JOINs performed against the foreign table. It will 
**not** return the additionally joined data.
</div>

### Default Values

Define the default values for all the `$db` fields. This example sets the "Status"-column on Player to "Active" 
whenever a new object is created.

	:::php
	<?php

	class Player extends DataObject {

		private static $defaults = array(
			"Status" => 'Active',
		);
	}

<div class="notice" markdown='1'>
Note: Alternatively you can set defaults directly in the database-schema (rather than the object-model). See 
[Data Types and Casting](data-types) for details.
</div>

## Subclasses


Inheritance is supported in the data model: separate tables will be linked together, the data spread across these 
tables. The mapping and saving logic is handled by SilverStripe, you don't need to worry about writing SQL most of the 
time.

For example, suppose we have the following set of classes:

	:::php
	<?php

	class Page extends SiteTree {

	}

	class NewsPage extends Page {

		private static $db = array(
			'Summary' => 'Text'
		);
	}

The data for the following classes would be stored across the following tables:

	:::yml
	SiteTree:
		- ID: Int
		- ClassName: Enum('SiteTree', 'Page', 'NewsPage')
		- Created: Datetime
		- LastEdited: Datetime
		- Title: Varchar
		- Content: Text
	NewsArticle:
		- ID: Int
		- Summary: Text

Accessing the data is transparent to the developer.

	:::php
	$news = NewsPage::get();

	foreach($news as $article) {
		echo $news->Title;
	}

The way the ORM stores the data is this:

*  "Base classes" are direct sub-classes of [api:DataObject].  They are always given a table, whether or not they have
special fields.  This is called the "base table". In our case, `SiteTree` is the base table.

*  The base table's ClassName field is set to class of the given record.  It's an enumeration of all possible
sub-classes of the base class (including the base class itself).

*  Each sub-class of the base object will also be given its own table, *as long as it has custom fields*.  In the
example above, NewsSection didn't have its own data, so an extra table would be redundant.

*  In all the tables, ID is the primary key.  A matching ID number is used for all parts of a particular record: 
record #2 in Page refers to the same object as record #2 in `[api:SiteTree]`.

To retrieve a news article, SilverStripe joins the [api:SiteTree], [api:Page] and NewsArticle tables by their ID fields. 

## Related Documentation

* [Data Types and Casting](../data_types_and_casting)

## API Documentation

* [api:DataObject]
* [api:DataList]
* [api:DataQuery]
