title: SQL Queries
summary: Write and modify direct database queries through SQLExpression subclasses.

# SQLSelect

## Introduction

An object representing a SQL select query, which can be serialized into a SQL statement. 
It is easier to deal with object-wrappers than string-parsing a raw SQL-query. 
This object is used by the SilverStripe ORM internally.

Dealing with low-level SQL is not encouraged, since the ORM provides
powerful abstraction APIs (see [datamodel](/developer_guides/data_model_and_orm). 
Starting with SilverStripe 3, records in collections are lazy loaded,
and these collections have the ability to run efficient SQL
such as counts or returning a single column.

For example, if you want to run a simple `COUNT` SQL statement,
the following three statements are functionally equivalent:

	:::php
	// Through raw SQL.
	$count = DB::query('SELECT COUNT(*) FROM "Member"')->value();

	// Through SQLQuery abstraction layer.
	$query = new SQLQuery();
	$count = $query->setFrom('Member')->setSelect('COUNT(*)')->value();

	// Through the ORM.
	$count = Member::get()->count();

If you do use raw SQL, you'll run the risk of breaking 
various assumptions the ORM and code based on it have:

*  Custom getters/setters (object property can differ from database column)
*  DataObject hooks like onBeforeWrite() and onBeforeDelete()
*  Automatic casting
*  Default values set through objects
*  Database abstraction

We'll explain some ways to use *SELECT* with the full power of SQL, 
but still maintain a connection to the ORM where possible.

<div class="warning" markdown="1">
Please read our [security topic](/developer_guides/security) to find out
how to properly prepare user input and variables for use in queries
</div>

## Usage

### SELECT

Selection can be done by creating an instance of `SQLSelect`, which allows
management of all elements of a SQL SELECT query, including columns, joined tables,
conditional filters, grouping, limiting, and sorting.

E.g.

	:::php
	<?php

	$sqlQuery = new SQLSelect();
	$sqlQuery->setFrom('Player');
	$sqlQuery->selectField('FieldName', 'Name');
	$sqlQuery->selectField('YEAR("Birthday")', 'Birthyear');
	$sqlQuery->addLeftJoin('Team','"Player"."TeamID" = "Team"."ID"');
	$sqlQuery->addWhere(array('YEAR("Birthday") = ?' => 1982));
	// $sqlQuery->setOrderBy(...);
	// $sqlQuery->setGroupBy(...);
	// $sqlQuery->setHaving(...);
	// $sqlQuery->setLimit(...);
	// $sqlQuery->setDistinct(true);
	
	// Get the raw SQL (optional) and parameters
	$rawSQL = $sqlQuery->sql($parameters);
	
	// Execute and return a Query object
	$result = $sqlQuery->execute();

	// Iterate over results
	foreach($result as $row) {
	  echo $row['BirthYear'];
	}

The result of `SQLSelect::execute()` is an array lightly wrapped in a database-specific subclass of [api:SS_Query]. 
This class implements the *Iterator*-interface, and provides convenience-methods for accessing the data.

### DELETE

Deletion can be done either by calling `DB::query`/`DB::prepared_query` directly,
by creating a `SQLDelete` object, or by transforming a `SQLQuery` into a `SQLDelete`
object instead.

For example, creating a `SQLDelete` object

	:::php
	<?php

	$query = SQLDelete::create()
		->setFrom('"SiteTree"')
		->setWhere(array('"SiteTree"."ShowInMenus"' => 0));
	$query->execute();

Alternatively, turning an existing `SQLQuery` into a delete

	:::php
	<?php

	$query = SQLQuery::create()
		->setFrom('"SiteTree"')
		->setWhere(array('"SiteTree"."ShowInMenus"' => 0))
		->toDelete();
	$query->execute();

Directly querying the database

	:::php
	<?php

	DB::prepared_query('DELETE FROM "SiteTree" WHERE "SiteTree"."ShowInMenus" = ?', array(0));

### INSERT/UPDATE

INSERT and UPDATE can be performed using the `SQLInsert` and `SQLUpdate` classes.
These both have similar aspects in that they can modify content in
the database, but each are different in the way in which they behave.

Previously, similar operations could be performed by using the `DB::manipulate`
function which would build the INSERT and UPDATE queries on the fly. This method
still exists, but internally uses `SQLUpdate` / `SQLInsert`, although the actual
query construction is now done by the `DBQueryBuilder` object.

Each of these classes implements the interface `SQLWriteExpression`, noting that each
accepts write key/value pairs in a number of similar ways. These include the following
api methods:

 * `addAssignments` - Takes a list of assignments as an associative array of key -> value pairs,
   but also supports SQL expressions as values if necessary.
 * `setAssignments` - Replaces all existing assignments with the specified list
 * `getAssignments` - Returns all currently given assignments, as an associative array
   in the format `array('Column' => array('SQL' => array('parameters)))`
 * `assign` - Singular form of addAssignments, but only assigns a single column value.
 * `assignSQL` - Assigns a column the value of a specified SQL expression without parameters
   `assignSQL('Column', 'SQL)` is shorthand for `assign('Column', array('SQL' => array()))`

SQLUpdate also includes the following api methods:

 * `clear` - Clears all assignments
 * `getTable` - Gets the table to update
 * `setTable` - Sets the table to update. This should be ANSI quoted.
   E.g. `$query->setTable('"SiteTree"');`

SQLInsert also includes the following api methods:
 * `clear` - Clears all rows
 * `clearRow` - Clears all assignments on the current row
 * `addRow` - Adds another row of assignments, and sets the current row to the new row
 * `addRows` - Adds a number of arrays, each representing a list of assignment rows,
   and sets the current row to the last one.
 * `getColumns` - Gets the names of all distinct columns assigned
 * `getInto` - Gets the table to insert into
 * `setInto` - Sets the table to insert into. This should be ANSI quoted.
   E.g. `$query->setInto('"SiteTree"');`

E.g.

	:::php
	<?php
	$update = SQLUpdate::create('"SiteTree"')->addWhere(array('ID' => 3));

	// assigning a list of items
	$update->addAssignments(array(
		'"Title"' => 'Our Products',
		'"MenuTitle"' => 'Products'
	));

	// Assigning a single value
	$update->assign('"MenuTitle"', 'Products');

	// Assigning a value using parameterised expression
	$title = 'Products';
	$update->assign('"MenuTitle"', array(
		'CASE WHEN LENGTH("MenuTitle") > LENGTH(?) THEN "MenuTitle" ELSE ? END' =>
			array($title, $title)
	));

	// Assigning a value using a pure SQL expression
	$update->assignSQL('"Date"', 'NOW()');

	// Perform the update
	$update->execute();

In addition to assigning values, the SQLInsert object also supports multi-row 
inserts. For database connectors and API that don't have multi-row insert support
these are translated internally as multiple single row inserts.

For example,

	:::php
	<?php
	$insert = SQLInsert::create('"SiteTree"');

	// Add multiple rows in a single call. Note that column names do not need 
	// to be symmetric
	$insert->addRows(array(
		array('"Title"' => 'Home', '"Content"' => '<p>This is our home page</p>'),
		array('"Title"' => 'About Us', '"ClassName"' => 'AboutPage')
	));

	// Adjust an assignment on the last row
	$insert->assign('"Content"', '<p>This is about us</p>');

	// Add another row
	$insert->addRow(array('"Title"' => 'Contact Us'));

	$columns = $insert->getColumns();
	// $columns will be array('"Title"', '"Content"', '"ClassName"');

	$insert->execute();

### Value Checks

Raw SQL is handy for performance-optimized calls,
e.g. when you want a single column rather than a full-blown object representation.

Example: Get the count from a relationship.

	:::php
	$sqlQuery = new SQLQuery();
	$sqlQuery->setFrom('Player');
	$sqlQuery->addSelect('COUNT("Player"."ID")');
	$sqlQuery->addWhere(array('"Team"."ID"' => 99));
	$sqlQuery->addLeftJoin('Team', '"Team"."ID" = "Player"."TeamID"');
	$count = $sqlQuery->execute()->value();

Note that in the ORM, this call would be executed in an efficient manner as well:

	:::php
	$count = $myTeam->Players()->count();

### Mapping

Creates a map based on the first two columns of the query result. 
This can be useful for creating dropdowns.

Example: Show player names with their birth year, but set their birth dates as values.

	:::php
	$sqlQuery = new SQLSelect();
	$sqlQuery->setFrom('Player');
	$sqlQuery->setSelect('Birthdate');
	$sqlQuery->selectField('CONCAT("Name", ' - ', YEAR("Birthdate")', 'NameWithBirthyear');
	$map = $sqlQuery->execute()->map();
	$field = new DropdownField('Birthdates', 'Birthdates', $map);

Note that going through SQLSelect is just necessary here 
because of the custom SQL value transformation (`YEAR()`). 
An alternative approach would be a custom getter in the object definition.

	:::php
	class Player extends DataObject {
		private static $db = array(
			'Name' =>  'Varchar',
			'Birthdate' => 'Date'
		);
		function getNameWithBirthyear() {
			return date('y', $this->Birthdate);
		}
	}
	$players = Player::get();
	$map = $players->map('Name', 'NameWithBirthyear');

## Related

* [Introduction to the Data Model and ORM](data_model_and_orm)

## API Documentation

* [api:DataObject]
* [api:SQLSelect]
* [api:DB]
* [api:Query]
* [api:Database]
