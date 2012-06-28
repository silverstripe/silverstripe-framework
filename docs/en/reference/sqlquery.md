# SQL Query

## Introduction

An object representing a SQL query, which can be serialized into a SQL statement. 
It is easier to deal with object-wrappers than string-parsing a raw SQL-query. 
This object is used by the SilverStripe ORM internally.

Dealing with low-level SQL is not encouraged, since the ORM provides
powerful abstraction APIs (see [datamodel](/topics/datamodel). 
Starting with SilverStripe 3, records in collections are lazy loaded,
and these collections have the ability to run efficient SQL
such as counts or returning a single column.

For example, if you want to run a simple `COUNT` SQL statement,
the following three statements are functionally equivalent:

	:::php
	// Through raw SQL
	$count = DB::query('SELECT COUNT(*) FROM "Member"')->value();
	// Through SQLQuery abstraction layer
	$query = new SQLQuery();
	$count = $query->setFrom('Member')->setSelect('COUNT(*)')->value();
	// Through the ORM
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
	Please read our ["security" topic](/topics/security) to find out
	how to sanitize user input before using it in SQL queries.
</div>

## Usage

### SELECT

	:::php
	$sqlQuery = new SQLQuery();
	$sqlQuery->setFrom('Player');
	$sqlQuery->selectField('FieldName', 'Name');
	$sqlQuery->selectField('YEAR("Birthday")', 'Birthyear');
	$sqlQuery->addLeftJoin('Team','"Player"."TeamID" = "Team"."ID"');
	$sqlQuery->addWhere('YEAR("Birthday") = 1982');
	// $sqlQuery->setOrderBy(...);
	// $sqlQuery->setGroupBy(...);
	// $sqlQuery->setHaving(...);
	// $sqlQuery->setLimit(...);
	// $sqlQuery->setDistinct(true);
	
	// Get the raw SQL (optional)
	$rawSQL = $sqlQuery->sql();
	
	// Execute and return a Query object
	$result = $sqlQuery->execute();

	// Iterate over results
	foreach($result as $row) {
	  echo $row['BirthYear'];
	}

The result is an array lightly wrapped in a database-specific subclass of `[api:Query]`. 
This class implements the *Iterator*-interface, and provides convenience-methods for accessing the data.

### DELETE

	:::php
	$sqlQuery->setDelete(true);

### INSERT/UPDATE

Currently not supported through the `SQLQuery` class, please use raw `DB::query()` calls instead.

	:::php
	DB::query('UPDATE "Player" SET "Status"=\'Active\'');

### Value Checks

Raw SQL is handy for performance-optimized calls,
e.g. when you want a single column rather than a full-blown object representation.

Example: Get the count from a relationship.

	:::php
	$sqlQuery = new SQLQuery();
  $sqlQuery->setFrom('Player');
  $sqlQuery->addSelect('COUNT("Player"."ID")');
  $sqlQuery->addWhere('"Team"."ID" = 99');
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
	$sqlQuery = new SQLQuery();
	$sqlQuery->setFrom('Player');
	$sqlQuery->setSelect('Birthdate');
	$sqlQuery->selectField('CONCAT("Name", ' - ', YEAR("Birthdate")', 'NameWithBirthyear');
	$map = $sqlQuery->execute()->map();
	$field = new DropdownField('Birthdates', 'Birthdates', $map);

Note that going through SQLQuery is just necessary here 
because of the custom SQL value transformation (`YEAR()`). 
An alternative approach would be a custom getter in the object definition.

	:::php
	class Player extends DataObject {
		static $db = array(
			'Name' => 
			'Birthdate' => 'Date'
		);
		function getNameWithBirthyear() {
			return date('y', $this->Birthdate);
		}
	}
	$players = Player::get();
	$map = $players->map('Name', 'NameWithBirthyear');

## Related

*  [datamodel](/topics/datamodel)
*  `[api:DataObject]`
*  [database-structure](database-structure)