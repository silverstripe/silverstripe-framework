title: SQLSelect
summary: Write and modify direct database queries through SQLSelect.

# SQLSelect

A [api:SQLSelect] object represents a SQL query, which can be serialized into a SQL statement. Dealing with low-level 
SQL such as `mysql_query()` is not encouraged, since the ORM provides powerful abstraction API's.

For example, if you want to run a simple `COUNT` SQL statement, the following three statements are functionally 
equivalent:

	:::php
	// Through raw SQL.
	$count = DB::query('SELECT COUNT(*) FROM "Member"')->value();

	// Through SQLSelect abstraction layer.
	$query = new SQLSelect();
	$count = $query->setFrom('Member')->setSelect('COUNT(*)')->value();

	// Through the ORM.
	$count = Member::get()->count();


<div class="info">
The SQLSelect object is used by the SilverStripe ORM internally. By understanding SQLSelect, you can modify the SQL that 
the ORM creates.
</div>

## Usage

### Select

	:::php
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
Currently not supported through the `SQLSelect` class, please use raw `DB::query()` calls instead.
</div>

	:::php
	DB::query('UPDATE "Player" SET "Status"=\'Active\'');

### Joins

	:::php
	$sqlQuery = new SQLSelect();
	$sqlQuery->setFrom('Player');
	$sqlQuery->addSelect('COUNT("Player"."ID")');
	$sqlQuery->addWhere(array('"Team"."ID" => 99));
	$sqlQuery->addLeftJoin('Team', '"Team"."ID" = "Player"."TeamID"');
	
	$count = $sqlQuery->execute()->value();

### Mapping

Creates a map based on the first two columns of the query result. 

	:::php
	$sqlQuery = new SQLSelect();
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

* [Introduction to the Data Model and ORM](data_model_and_orm)

## API Documentation

* [api:DataObject]
* [api:SQLSelect]
* [api:DB]
* [api:Query]
* [api:Database]