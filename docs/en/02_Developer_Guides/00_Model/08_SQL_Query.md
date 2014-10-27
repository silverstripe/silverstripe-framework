title: SQLQuery
summary: Write and modify direct database queries through SQLQuery.

# SQLQuery

A [api:SQLQuery] object represents a SQL query, which can be serialized into a SQL statement. Dealing with low-level 
SQL such as `mysql_query()` is not encouraged, since the ORM provides powerful abstraction API's.

For example, if you want to run a simple `COUNT` SQL statement, the following three statements are functionally 
equivalent:

	:::php
	// Through raw SQL.
	$count = DB::query('SELECT COUNT(*) FROM "Member"')->value();

	// Through SQLQuery abstraction layer.
	$query = new SQLQuery();
	$count = $query->setFrom('Member')->setSelect('COUNT(*)')->value();

	// Through the ORM.
	$count = Member::get()->count();


<div class="info">
The SQLQuery object is used by the SilverStripe ORM internally. By understanding SQLQuery, you can modify the SQL that 
the ORM creates.
</div>

## Usage

### Select

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

The `$result` is an array lightly wrapped in a database-specific subclass of `[api:Query]`. This class implements the 
*Iterator*-interface, and provides convenience-methods for accessing the data.

### Delete

	:::php
	$sqlQuery->setDelete(true);

### Insert / Update

<div class="alert" markdown="1">
Currently not supported through the `SQLQuery` class, please use raw `DB::query()` calls instead.
</div>

	:::php
	DB::query('UPDATE "Player" SET "Status"=\'Active\'');

### Joins

	:::php
	$sqlQuery = new SQLQuery();
	$sqlQuery->setFrom('Player');
	$sqlQuery->addSelect('COUNT("Player"."ID")');
	$sqlQuery->addWhere('"Team"."ID" = 99');
	$sqlQuery->addLeftJoin('Team', '"Team"."ID" = "Player"."TeamID"');
	
	$count = $sqlQuery->execute()->value();

### Mapping

Creates a map based on the first two columns of the query result. 

	:::php
	$sqlQuery = new SQLQuery();
	$sqlQuery->setFrom('Player');
	$sqlQuery->setSelect('ID');
	$sqlQuery->selectField('CONCAT("Name", ' - ', YEAR("Birthdate")', 'NameWithBirthyear');
	$map = $sqlQuery->execute()->map();

	echo $map;

	// returns array(
	// 	1 => "Foo - 1920",
	//	2 => "Bar - 1936"
	// );

## Related Documentation

* [Introduction to the Data Model and ORM](../data_model_and_orm)

## API Documentation

* [api:DataObject]
* [api:SQLQuery]
* [api:DB]
* [api:Query]
* [api:Database]