# SQL Query

## Introduction

An object representing a SQL query. It is easier to deal with object-wrappers than string-parsing a raw SQL-query. This
object is used by `[api:DataObject]`, though...

A word of caution: Dealing with low-level SQL is not encouraged in the SilverStripe [datamodel](/topics/datamodel) for various
reasons. You'll break the behaviour of:

*  Custom getters/setters
*  DataObject::onBeforeWrite/onBeforeDelete
*  Automatic casting
*  Default-setting through object-model
*  `[api:DataObject]`
*  Database abstraction

We'll explain some ways to use *SELECT* with the full power of SQL, but still maintain a connection to the SilverStripe
[datamodel](/topics/datamodel).

## Usage


### SELECT

	:::php
	$sqlQuery = new SQLQuery();
	$sqlQuery->select = array(
	  'Firstname AS Name',
	  'YEAR(Birthday) AS BirthYear'
	);
	$sqlQuery->from = "
	  Player
	  LEFT JOIN Team ON Player.TeamID = Team.ID
	";
	$sqlQuery->where = "
	  YEAR(Birthday) = 1982
	";
	// $sqlQuery->orderby = "";
	// $sqlQuery->groupby = "";
	// $sqlQuery->having = "";
	// $sqlQuery->limit = "";
	// $sqlQuery->distinct = true;
	
	// get the raw SQL
	$rawSQL = $sqlQuery->sql();
	
	// execute and return a Query-object
	$result = $sqlQuery->execute();


### DELETE

	:::php
	// ...
	$sqlQuery->delete = true;


### INSERT/UPDATE

(currently not supported -see below for alternative solutions)

## Working with results

The result is an array lightly wrapped in a database-specific subclass of `[api:Query]`. This class implements the
*Iterator*-interface defined in PHP5, and provides convenience-methods for accessing the data.

### Iterating

	:::php
	foreach($result as $row) {
	  echo $row['BirthYear'];
	}


### Quick value checking

Raw SQL is handy for performance-optimized calls. 

	:::php
	class Team extends DataObject {
	  function getPlayerCount() {
	    $sqlQuery = new SQLQuery(
	      "COUNT(Player.ID)",
	      "Team LEFT JOIN Player ON Team.ID = Player.TeamID"
	    );
	    return $sqlQuery->execute()->value();
	}

Way faster than dealing with `[api:DataObject]`s, but watch out for premature optimisation:

	:::php
	$players = $myTeam->Players();
	echo $players->Count();


### Mapping

Useful for creating dropdowns.

	:::php
	$sqlQuery = new SQLQuery(
	  array('YEAR(Birthdate)', 'Birthdate'),
	  'Player'
	);
	$map = $sqlQuery->execute()->map();
	$field = new DropdownField('Birthdates', 'Birthdates', $map);


### "Raw" SQL with DB::query()

This is not recommended for most cases, but you can also use the SilverStripe database-layer to fire off a raw query:

	:::php
	DB::query("UPDATE Player SET Status='Active'");

One example for using a raw DB::query is when you are wanting to order twice in the database:

	:::php
	$records = DB::query('SELECT *, CASE WHEN "ThumbnailID" = 0 THEN 2 ELSE 1 END AS "HasThumbnail" FROM "TempDoc" ORDER BY "HasThumbnail", "Name" ASC');
	$items = singleton('TempDoc')->buildDataObjectSet($records);

This CASE SQL creates a second field "HasThumbnail" depending if "ThumbnailID" exists in the database which you can then
order by "HasThumbnail" to make sure the thumbnails are at the top of the list and then order by another field "Name"
separately for both the items that have a thumbnail and then for those that don't have thumbnails.

### "Semi-raw" SQL with buildSQL()

You can gain some ground on the datamodel-side when involving the selected class for querying. You don't necessarily
need to call *buildSQL* from a specific object-instance, a *singleton* will do just fine.

	:::php
	$sqlQuery = singleton('Player')->buildSQL(
	  'YEAR(Birthdate) = 1982'
	);


This form of building a query has the following advantages:

*  Respects DataObject::$default_sort
*  Automatically LEFT JOIN on all base-tables (see [database-structure](database-structure))
*  Selection of *ID*, *ClassName*, *RecordClassName*, which are necessary to use *buildDataObjectSet* later on
*  Filtering records for correct *ClassName*

### Transforming a result to `[api:DataObjectSet]`

This is a commonly used technique inside SilverStripe: Use raw SQL, but transfer the resulting rows back into
`[api:DataObject]`s.

	:::php
	$sqlQuery = new SQLQuery();
	$sqlQuery->select = array(
	  'Firstname AS Name',
	  'YEAR(Birthday) AS BirthYear',
	  // IMPORTANT: Needs to be set after other selects to avoid overlays
	  'Player.ClassName AS ClassName',
	  'Player.ClassName AS RecordClassName',
	  'Player.ID AS ID'
	);
	$sqlQuery->from = array(
	  "Player",
	  "LEFT JOIN Team ON Player.TeamID = Team.ID"
	);
	$sqlQuery->where = array(
	  "YEAR(Player.Birthday) = 1982"
	);
	
	$result = $sqlQuery->execute();
	var_dump($result->first()); // array
	
	// let Silverstripe work the magic
	$myDataObjectSet = singleton('Player')->buildDataObjectSet($result);
	var_dump($myDataObjectSet->First()); // DataObject
	
	// this is where it gets tricky
	$myFirstPlayer = $myDataObjectSet->First();
	var_dump($myFirstPlayer->Name); // 'John'
	var_dump($myFirstPlayer->Firstname); // undefined, as it was not part of the SELECT-clause;
	var_dump($myFirstPlayer->Surname); // undefined, as it was not part of the SELECT-clause
	
	// lets assume that class Player extends BasePlayer,
	// and BasePlayer has a database-column "Status"
	var_dump($myFirstPlayer->Status); // undefined, as we didn't LEFT JOIN the BasePlayer-table


**CAUTION:** Depending on the selected columns in your query, you might get into one of the following scenarios:

*  Not all object-properties accessible: You need to take care of selecting the right stuff yourself
*  Overlayed object-properties: If you *LEFT JOIN* a table which also has a column 'Birthdate' and do a global select on
this table, you might not be able to access original object-properties.
*  You can't create `[api:DataObject]`s where no scalar record-data is available, e.g. when using *GROUP BY*
*  Naming conflicts with custom getters: A getter like Player->getName() will overlay the column-data selected in the
above example

Be careful when saving back `[api:DataObject]`s created through *buildDataObjectSet*, you might get strange side-effects due to
the issues noted above.
## Using FormFields with custom SQL

Some subclasses of `[api:FormField]` for ways to create sophisticated report-tables based on SQL.

## Related

*  [datamodel](/topics/datamodel)
*  `[api:DataObject]`
*  [database-structure](database-structure)

## API Documentation
`[api:SQLQuery]`