# Datamodel

SilverStripe uses an [object-relational model](http://en.wikipedia.org/wiki/Object-relational_model) that assumes the
following connections:

*  Each database-table maps to a PHP class
*  Each database-row maps to a PHP object
*  Each database-column maps to a property on a PHP object
 
All data tables in SilverStripe are defined as subclasses of `[api:DataObject]`. Inheritance is supported in the data
model: seperate tables will be linked together, the data spread across these tables. The mapping and saving/loading
logic is handled by SilverStripe, you don't need to worry about writing SQL most of the time. 

Most of the ORM customizations are possible through [PHP5 Object
Overloading](http://www.onlamp.com/pub/a/php/2005/06/16/overloading.html) handled in the `[api:Object]`-class.

See [database-structure](/reference/database-structure) for in-depth information on the database-schema,
and the ["sql queries" topic](/reference/sqlquery) in case you need to drop down to the bare metal.

## Generating the Database Schema

The SilverStripe database-schema is generated automatically by visiting the URL.
`http://<mysite>/dev/build`

<div class="notice" markdown='1'>
Note: You need to be logged in as an administrator to perform this command.
</div>

## Querying Data

Every query to data starts with a `DataList::create(<class>)` or `<class>::get()` call. For example, this query would return all of the `Member` objects:

	:::php
	$members = Member::get();

The ORM uses a "fluent" syntax, where you specify a query by chaining together different methods.  Two common methods 
are `filter()` and `sort()`:

	:::php
	$members = Member::get()->filter(array('FirstName' => 'Sam'))->sort('Surname');
	
Those of you who know a bit about SQL might be thinking "it looks like you're querying all members, and then filtering
to those with a first name of 'Sam'. Isn't this very slow?"  Is isn't, because the ORM doesn't actually execute the 
query until you iterate on the result with a `foreach()` or `<% loop %>`.

	:::php
	// The SQL query isn't executed here...
	$members = Member::get();
	// ...or here
	$members = $members->filter(array('FirstName' => 'Sam'));
	// ...or even here
	$members = $members->sort('Surname');
	
	// *This* is where the query is executed
	foreach($members as $member) {
		echo "<p>$member->FirstName $member->Surname</p>";
	}

This also means that getting the count of a list of objects will be done with a single, efficient query.

	:::php
	$members = Member::get()->filter(array('FirstName' => 'Sam'))->sort('Surname');
	// This will create an single SELECT COUNT query.
	echo $members->Count();
	
All of this lets you focus on writing your application, and not worrying too much about whether or not your queries are efficient.

### Returning a single DataObject

There are a couple of ways of getting a single DataObject from the ORM.  If you know the ID number of the object, you can use `byID($id)`:

	:::php
	$member = Member::get()->byID(5);

If you have constructed a query that you know should return a single record, you can call `First()`:

	:::php
	$member = Member::get()->filter(array('FirstName' => 'Sam', 'Surname' => 'Minnee'))->First();


### Sort

Quiet often you would like to sort a list. Doing this on a list could be done in a few ways.

If would like to sort the list by `FirstName` in a ascending way (from A to Z).

	:::php
	$member = Member::get()->sort('FirstName', 'ASC');
	$member = Member::get()->sort('FirstName'); // Ascending is implied

To reverse the sort

	:::php
	$member = Member::get()->sort('FirstName', 'DESC');

However you might have several entries with the same `FirstName` and would like to sort them by `FirstName` and `LastName`

	:::php
	$member = Member::get()->sort(array(
		'FirstName' => 'ASC',
		'LastName'=>'ASC'
	));

### Filter

As you might expect, the `filter()` method filters the list of objects that gets returned.  The previous example 
included this filter, which returns all Members with a first name of "Sam".

	:::php
	$members = Member::get()->filter(array('FirstName' => 'Sam'));

In SilverStripe 2, we would have passed `"\"FirstName\" = 'Sam'` to make this query.  Now, we pass an  array, 
`array('FirstName' => 'Sam')`, to minimise the risk of SQL injection bugs.  The format of this array follows a few 
rules:

 * Each element of the array specifies a filter.  You can specify as many filters as you like, and they **all** must be 
true.
 * The key in the filter corresponds to the field that you want to filter by.
 * The value in the filter corresponds to the value that you want to filter to.

So, this would return only those members called "Sam Minnée".

	:::php
	$members = Member::get()->filter(array(
		'FirstName' => 'Sam',
		'Surname' => 'Minnée',
	));

There are also a short hand way of getting Members with the FirstName of Sam.

	:::php
	$members = Member::get()->filter('FirstName', 'Sam');

Or if you want to find both Sam and Sig.

	:::php
	$members = Member::get()->filter(
		'FirstName', array('Sam', 'Sig')
	);


Then there is the most complex task when you want to find Sam and Sig that has either Age 17 or 74.

	:::php
	$members = Member::get()->filter(array(
		'FirstName' => array('Sam', 'Sig'),
		'Age' => array(17, 74)
	));

This would be equivalent to a SQL query of

	:::
	... WHERE ("FirstName" IN ('Sam', 'Sig) AND "Age" IN ('17', '74));


### Exclude

The `exclude()` method is the opposite to the filter in that it removes entries from a list.

If we would like to remove all members from the list with the FirstName of Sam.

	:::php
	$members = Member::get()->exclude('FirstName', 'Sam');

Remove both Sam and Sig is as easy as.

	:::php
	$members = Member::get()->exclude('FirstName', array('Sam','Sig'));

As you can see it follows the same pattern as filter, so for removing only Sam Minnée from the list

	:::php
	$members = Member::get()->exclude(array(
		'FirstName' => 'Sam',
		'Surname' => 'Minnée',
	));

And removing Sig and Sam with that are either age 17 or 74.

	:::php
	$members = Member::get()->exclude(array(
		'FirstName' => array('Sam', 'Sig'),
		'Age' => array(17, 43)
	));

This would be equivalent to a SQL query of

	:::
	... WHERE ("FirstName" NOT IN ('Sam','Sig) OR "Age" NOT IN ('17', '74));

By default, these filters specify case-insensitive exact matches.  There are a number of suffixes that you can put on 
field names to change this: `":StartsWith"`, `":EndsWith"`, `":PartialMatch"`, `":GreaterThan"`, `":LessThan"`, `":Negation"`.

This query will return everyone whose first name doesn't start with S, who have logged on since 1/1/2011.

	:::php
	$members = Member::get()->filter(array(
		'FirstName:StartsWith:Not' => 'S'
		'LastVisited:GreaterThan' => '2011-01-01'
	));

If you wish to match against any of a number of columns, you can list several field names, separated by commas.  This 
will return all members whose first name or surname contain the string 'sam'.

	:::php
	$members = Member::get()->filter(array(
		'FirstName,Surname:PartialMatch' => 'sam'
	));

If you wish to match against any of a number of values, you can pass an array as the value.  This will return all 
members whose first name is either Sam or Ingo.

	:::php
	$members = Member::get()->filter(array(
		'FirstName' => array('sam', 'ingo'),
	));

### Subtract

You can subtract entries from a DataList by passing in another DataList to `subtract()`

	:::php
	$allSams = Member::get()->filter('FirstName', 'Sam');
	$allMembers = Member::get();
	$noSams = $allMembers->subtract($allSams);

Though for the above example it would probably be easier to use `filter()` and `exclude()`. A better
use case could be when you want to find all the members that does not exist in a Group. 

	:::php
	// ... Finding all members that does not belong to $group.
	$otherMembers = Member::get()->subtract($group->Members());


### Relation filters

So far we have only filtered a data list by fields on the object that you're requesting.  For simple cases, this might 
be okay, but often, a data model is made up of a number of related objects.  For example, in SilverStripe each member 
can be placed in a number of groups, and each group has a number of permissions.

For this, the SilverStripe ORM supports **Relation Filters**.  Any ORM request can be filtered by fields on a related object by 
specifying the filter key as `<relation-name>.<field-in-related-object>`.  You can chain relations together as many 
times as is necessary.

For example, this will return all members assigned ot a group that has a permission record with the code "ADMIN".  In other words, it will return all administrators.

	:::php
	$members = Member::get()->filter(array(
		'Groups.Permissions.Code' => 'ADMIN',
	));

Note that we are just joining to these tables to filter the records.  Even if a member is in more than 1 administrator group, unique members will still be returned by this query.

The other features of filters can be applied to relation filters as well.  This will return all members in groups whose
names start with 'A' or 'B'.

	:::php
	$members = Member::get()->filter(array(
		'Groups.Title:StartsWith' => array('A', 'B'),
	));

You can even follow a relation back to the original model class!  This will return all members are in at least 1 group that also has a member called Sam.

	:::php
	$members = Member::get()->filter(array(
		'Groups.Members.FirstName' => 'Sam'
	));

### Raw SQL options for advanced users

Occasionally, the system described above won't let you do exactly what you need to do.  In these situtations, we have 
methods that manipulate the SQL query at a lower level.  When using these, please ensure that all table & field names 
are escaped with double quotes, otherwise some DB back-ends (e.g. PostgreSQL) won't work.

In general, we advise against using these methods unless it's absolutely necessary.  If the ORM doesn't do quite what 
you need it to, you may also consider extending the ORM with new data types or filter modifiers (that documentation still needs to be written)

#### Where clauses

You can specify a WHERE clause fragment (that will be combined with other filters using AND) with the `where()` method:

	:: php
	$members = Member::get()->where("\"FirstName\" = 'Sam'")

#### Joining 

You can specify a join with the innerJoin and leftJoin methods.  Both of these methods have the same arguments:

 * The name of the table to join to
 * The filter clause for the join
 * An optional alias

For example:

	:: php
	// Without an alias
	$members = Member::get()->leftJoin("Group_Members", "\"Group_Members\".\"MemberID\" = \"Member\".\"ID\"");

	$members = Member::get()->innerJoin("Group_Members", "\"Rel\".\"MemberID\" = \"Member\".\"ID\"", "REl");
	
Passing a *$join* statement to DataObject::get will filter results further by the JOINs performed against the foreign
table. **It will NOT return the additionally joined data.**  The returned *$records* will always be a
`[api:DataObject]`.

## Properties


### Definition

Data is defined in the static variable $db on each class, in the format:
`<property-name>` => "data-type"

	:::php
	class Player extends DataObject {
	  static $db = array(
	    "FirstName" => "Varchar",
	    "Surname" => "Varchar",
	    "Description" => "Text",
	    "Status" => "Enum('Active, Injured, Retired')",
	    "Birthday" => "Date"
	  );
	}

See [data-types](data-types) for all available types.

### Overloading

"Getters" and "Setters" are functions that help us save fields to our data objects. By default, the methods getField()
and setField() are used to set data object fields.  They save to the protected array, $obj->record. We can overload the
default behaviour by making a function called "get`<fieldname>`" or "set`<fieldname>`". 

	:::php
	class Player extends DataObject {
	  static $db = array(
	    "Status" => "Enum('Active, Injured, Retired')"
	  );
	
	  // access through $myPlayer->Status
	  public function getStatus() {
	      // check if the Player is actually... born already!
	      return (!$this->obj("Birthday")->InPast()) ? "Unborn" : $this->Status;
	  }


### Customizing

We can create new "virtual properties" which are not actually listed in *static $db* or stored in the database-row.
Here we combined a Player's first name and surname, accessible through $myPlayer->Title.

	:::php
	class Player extends DataObject {
	  public function getTitle() {
	    return "{$this->FirstName} {$this->Surname}";
	  }
	
	  // access through $myPlayer->Title = "John Doe";
	  // just saves data on the object, please use $myPlayer->write() to save the database-row
	  public function setTitle($title) {
	    list($firstName, $surName) = explode(' ', $title);
	    $this->FirstName = $firstName;
	    $this->Surname = $surName;
	  }
	}

<div class="warning" markdown='1'>
**CAUTION:** It is common practice to make sure that pairs of custom getters/setter deal with the same data, in a consistent
format.
</div>

<div class="warning" markdown='1'>
**CAUTION:** Custom setters can be hard to debug: Please double check if you could transform your data in more
straight-forward logic embedded to your custom controller or form-saving.
</div>

### Default Values

Define the default values for all the $db fields. This example sets the "Status"-column on Player to "Active" whenever a
new object is created.

	:::php
	class Player extends DataObject {
	  static $defaults = array(
	    "Status" => 'Active',
	  );
	}

<div class="notice" markdown='1'>
Note: Alternatively you can set defaults directly in the database-schema (rather than the object-model). See
[data-types](data-types) for details.
</div>

### Casting

Properties defined in *static $db* are automatically casted to their [data-types](data-types) when used in templates. 
You can also cast the return-values of your custom functions (e.g. your "virtual properties").
Calling those functions directly will still return whatever type your PHP code generates,
but using the *obj()*-method or accessing through a template will cast the value according to the $casting-definition.

	:::php
	class Player extends DataObject {
	  static $casting = array(
	    "MembershipFee" => 'Currency',
	  );
	
	  // $myPlayer->MembershipFee() returns a float (e.g. 123.45)
	  // $myPlayer->obj('MembershipFee') returns a object of type Currency
	  // In a template: <% loop MyPlayer %>MembershipFee.Nice<% end_loop %> returns a casted string (e.g. "$123.45")
	  public function getMembershipFee() {
	    return $this->Team()->BaseFee * $this->MembershipYears;
	  }
	}


## Relations

Relations are built through static array definitions on a class, in the format `<relationship-name> => <classname>`

### has_one

A 1-to-1 relation creates a database-column called "`<relationship-name>`ID", in the example below this would be "TeamID"
on the "Player"-table.

	:::php
	// access with $myPlayer->Team()
	class Player extends DataObject {
	  static $has_one = array(
	    "Team" => "Team",
	  );
	}

SilverStripe's `[api:SiteTree]` base-class for content-pages uses a 1-to-1 relationship to link to its
parent element in the tree:

	:::php
	// access with $mySiteTree->Parent()
	class SiteTree extends DataObject {
	  static $has_one = array(
	    "Parent" => "SiteTree",
	  );
	}

### has_many

Defines 1-to-many joins. A database-column named ""`<relationship-name>`ID"" will to be created in the child-class.

<div class="warning" markdown='1'>
**CAUTION:** Please specify a $has_one-relationship on the related child-class as well, in order to have the necessary
accessors available on both ends.
</div>

	:::php
	// access with $myTeam->Players() or $player->Team()
	class Team extends DataObject {
	  static $has_many = array(
	    "Players" => "Player",
	  );
	}
	class Player extends DataObject {
	  static $has_one = array(
	    "Team" => "Team",
	  );
	}


To specify multiple $has_manys to the same object you can use dot notation to distinguish them like below

	:::php
	class Person {
		static $has_many = array(
			"Managing" => "Company.Manager",
			"Cleaning" => "Company.Cleaner",
		);
	}
	
	class Company {
		static $has_one = array(
			"Manager" => "Person",
			"Cleaner" => "Person"
		);
	}


Multiple $has_one relationships are okay if they aren't linking to the same object type.

	:::php
	/**
	 * THIS IS BAD
	 */
	class Team extends DataObject {
	  static $has_many = array(
	    "Players" => "Player",
	  );
	}
	class Player extends DataObject {
	  static $has_one = array(
	    "Team" => "Team",
	    "AnotherTeam" => "Team",
	  );
	}


### many_many

Defines many-to-many joins. A new table, (this-class)_(relationship-name), will be created with a pair of ID fields.

<div class="warning" markdown='1'>
**CAUTION:** Please specify a $belongs_many_many-relationship on the related class as well, in order to have the necessary
accessors available on both ends.
</div>

	:::php
	// access with $myTeam->Categories() or $myCategory->Teams()
	class Team extends DataObject {
	  static $many_many = array(
	    "Categories" => "Category",
	  );
	}
	class Category extends DataObject {
	  static $belongs_many_many = array(
	    "Teams" => "Team",
	  );
	}


### Adding relations

Adding new items to a relations works the same,
regardless if you're editing a *has_many*- or a *many_many*. 
They are encapsulated by `[api:HasManyList]` and `[api:ManyManyList]`,
both of which provide very similar APIs, e.g. an `add()` and `remove()` method.

	:::php
	class Team extends DataObject {
	  // see "many_many"-description for a sample definition of class "Category"
	  static $many_many = array(
	    "Categories" => "Category",
	  );
		
	  public function addCategories(SS_List $cats) {
	    foreach($cats as $cat) $this->Categories()->add($cat);
	  }
	}


### Custom Relations

You can use the flexible datamodel to get a filtered result-list without writing any SQL. For example, this snippet gets
you the "Players"-relation on a team, but only containing active players. 
See `[api:DataObject::$has_many]` for more info on the described relations.

	:::php
	class Team extends DataObject {
	  static $has_many = array(
	    "Players" => "Player"
	  );
	
	  // can be accessed by $myTeam->ActivePlayers()
	  public function ActivePlayers() {
	    return $this->Players("Status='Active'");
	  }
	}

Note: Adding new records to a filtered `RelationList` like in the example above
doesn't automatically set the filtered criteria on the added record.

## Validation and Constraints

Traditionally, validation in SilverStripe has been mostly handled on the controller
through [form validation](/topics/form-validation).
While this is a useful approach, it can lead to data inconsistencies if the
record is modified outside of the controller and form context.
Most validation constraints are actually data constraints which belong on the model.
SilverStripe provides the `[api:DataObject->validate()]` method for this purpose.

By default, there is no validation - objects are always valid!  
However, you can overload this method in your
DataObject sub-classes to specify custom validation, 
or use the hook through `[api:DataExtension]`.

Invalid objects won't be able to be written - a [api:ValidationException]` 
will be thrown and no write will occur.
It is expected that you call validate() in your own application to test that an object 
is valid before attempting a write, and respond appropriately if it isn't.

The return value of `validate()` is a `[api:ValidationResult]` object.
You can append your own errors in there.

Example: Validate postcodes based on the selected country

	:::php
	class MyObject extends DataObject {
		static $db = array(
			'Country' => 'Varchar',
			'Postcode' => 'Varchar'
		);
		public function validate() {
			$result = parent::validate();
			if($this->Country == 'DE' && $this->Postcode && strlen($this->Postcode) != 5) {
				$result->error('Need five digits for German postcodes');
			}
			return $result;
		}
	}

## Maps

A map is an array where the array indexes contain data as well as the values.  You can build a map
from any DataList like this:

	:::php
	$members = Member::get()->map('ID', 'FirstName');
	
This will return a map where the keys are Member IDs, and the values are the corresponding FirstName
values.  Like everything else in the ORM, these maps are lazy loaded, so the following code will only
query a single record from the database:

	:::php
	$members = Member::get()->map('ID', 'FirstName');
	echo $member[5];
	
This functionality is provided by the `SS_Map` class, which can be used to build a map around any `SS_List`.

	:::php
	$members = Member::get();
	$map = new SS_Map($members, 'ID', 'FirstName');

Note: You can also retrieve a single property from all contained records
through `[api:SS_List->column()]`.

## Data Handling

When saving data through the object model, you don't have to manually escape strings to create SQL-safe commands.
You have to make sure though that certain properties are not overwritten, e.g. *ID* or *ClassName*.

### Creation

	:::php
	$myPlayer = new Player();
	$myPlayer->Firstname = "John"; // sets property on object
	$myPlayer->write(); // writes row to database


### Update

	:::php
	$myPlayer = DataObject::get_by_id('Player',99);
	if($myPlayer) {
	  $myPlayer->Firstname = "John"; // sets property on object
	  $myPlayer->write(); // writes row to database
	}


### Batch Update

	:::php
	$myPlayer->update(
	  ArrayLib::filter_keys(
	    $_REQUEST, 
	    array('Birthday', 'Firstname')
	  )
	);


Alternatively you can use *castedUpdate()* to respect the [data-types](/topics/data-types). This is preferred to manually
casting data before saving.

	:::php
	$myPlayer->castedUpdate(
	  ArrayLib::filter_keys(
	    $_REQUEST, 
	    array('Birthday', 'Firstname')
	  )
	);


### onBeforeWrite

You can customize saving-behaviour for each DataObject, e.g. for adding security. These functions are private, obviously
it wouldn't make sense to call them externally on the object. They are triggered when calling *write()*.

Example: Disallow creation of new players if the currently logged-in player is not a team-manager.

	:::php
	class Player extends DataObject {
	  static $has_many = array(
	    "Teams"=>"Team"
	  );
	
	  public function onBeforeWrite() {
	    // check on first write action, aka "database row creation" (ID-property is not set)
	    if(!$this->ID) {
	      $currentPlayer = Member::currentUser();
	      if(!$currentPlayer->IsTeamManager()) {
	        user_error('Player-creation not allowed', E_USER_ERROR);
	        exit();
	      }
	    }
	
	    // check on every write action
	    if(!$this->record['TeamID']) {
	        user_error('Cannot save player without a valid team-connection', E_USER_ERROR);
	        exit();
	    }
	
	    // CAUTION: You are required to call the parent-function, otherwise SilverStripe will not execute the request.
	    parent::onBeforeWrite();
	  }
	}


<div class="notice" markdown='1'>
Note: There are no separate methods for *onBeforeCreate* and *onBeforeUpdate*. Please check for the existence of
$this->ID to toggle these two modes, as shown in the example above.
</div>

### onBeforeDelete

Triggered before executing *delete()* on an existing object.

Example: Checking for a specific [permission](/reference/permission) to delete this type of object.
It checks if a member is logged in who belongs to a group containing the permission "PLAYER_DELETE".

	:::php
	class Player extends DataObject {
	  static $has_many = array(
	    "Teams"=>"Team"
	  );
	
	  public function onBeforeDelete() {
	    if(!Permission::check('PLAYER_DELETE')) {
	      Security::permissionFailure($this);
	      exit();
	    }
	
	    parent::onBeforeDelete();
	  }
	}

### Saving data with forms

See [forms](/topics/forms).

### Saving data with custom SQL

See the ["sql queries" topic](/reference/sqlquery) for custom *INSERT*, *UPDATE*, *DELETE* queries.

## Extending DataObjects

You can add properties and methods to existing `[api:DataObjects]`s like `[api:Member]` (a core class) without hacking core
code or subclassing.
Please see `[api:DataExtension]` for a general description, and `[api:Hierarchy]` for our most
popular examples.

## FAQ

### Whats the difference between DataObject::get() and a relation-getter?

You can work with both in pretty much the same way, 
but relationship-getters return a special type of collection: 
A `[api:HasManyList]` or a `[api:ManyManyList]` with relation-specific functionality.

	:::php
	$myTeams = $myPlayer->Team(); // returns HasManyList
	$myTeam->add($myOtherPlayer);