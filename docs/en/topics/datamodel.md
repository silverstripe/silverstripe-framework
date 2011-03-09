# Datamodel

SilverStripe uses an [object-relational model](http://en.wikipedia.org/wiki/Object-relational_model) that assumes the
following connections:

*  Each database-table maps to a php-class
*  Each database-row maps to a php-object
*  Each database-column maps to a property on a php-object
 
All data tables in SilverStripe are defined as subclasses of `[api:DataObject]`. Inheritance is supported in the data
model: seperate tables will be linked together, the data spread across these tables. The mapping and saving/loading
logic is handled by sapphire, you don't need to worry about writing SQL most of the time. 

The advanced object-relational layer in SilverStripe is one of the main reasons for requiring PHP5. Most of its
customizations are possible through [PHP5 Object
Overloading](http://www.onlamp.com/pub/a/php/2005/06/16/overloading.html) handled in the `[api:Object]`-class.

See [database-structure](/reference/database-structure) for in-depth information on the database-schema.

## Generating the database-schema

The SilverStripe database-schema is generated automatically by visiting the URL.
`http://<mysite>/dev/build`

<div class="notice" markdown='1'>
Note: You need to be logged in as an administrator to perform this command.
</div>

## Querying Data

There are static methods available for querying data. They automatically compile the necessary SQL to query the database
so they are very helpful. In case you need to fall back to plain-jane SQL, have a look at `[api:SQLQuery]`.

	:::php
	$records = DataObject::get($obj, $filter, $sort, $join, $limit);

	:::php
	$record = DataObject::get_one($obj, $filter);

	:::php
	$record = DataObject::get_by_id($obj, $id);

**CAUTION: Please make sure to properly escape your SQL-snippets (see [security](/topics/security).**

## Joining 

Passing a *$join* statement to DataObject::get will filter results further by the JOINs performed against the foreign
table. **It will NOT return the additionally joined data.**  The returned *$records* will always be a
`[api:DataObject]`.

When using *$join* statements be sure the string is in the proper format for the respective database engine. In MySQL
the use of back-ticks may be necessary when referring Table Names and potentially Columns. (see [MySQL
Identifiers](http://dev.mysql.com/doc/refman/5.0/en/identifiers.html)):

	:::php
	// Example from the forums: http://www.silverstripe.org/archive/show/79865#post79865
	// Note the use of backticks on table names
	$links = DataObject::get("SiteTree", 
	          "ShowInMenus = 1 AND ParentID = 23",
	          "", 
	          "LEFT JOIN `ConsultationPaperHolder` ON `ConsultationPaperHolder`.ID = `SiteTree`.ID",
	          "0, 10"); 


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
	  function getStatus() {
	      // check if the Player is actually... born already!
	      return (!$this->obj("Birthday")->InPast()) ? "Unborn" : $this->Status;
	  }


### Customizing

We can create new "virtual properties" which are not actually listed in *static $db* or stored in the database-row.
Here we combined a Player's first name and surname, accessible through $myPlayer->Title.

	:::php
	class Player extends DataObject {
	  function getTitle() {
	    return "{$this->FirstName} {$this->Surname}";
	  }
	
	  // access through $myPlayer->Title = "John Doe";
	  // just saves data on the object, please use $myPlayer->write() to save the database-row
	  function setTitle($title) {
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
Calling those functions directly will still return whatever type your php-code generates,
but using the *obj()*-method or accessing through a template will cast the value according to the $casting-definition.

	:::php
	class Player extends DataObject {
	  static $casting = array(
	    "MembershipFee" => 'Currency',
	  );
	
	  // $myPlayer->MembershipFee() returns a float (e.g. 123.45)
	  // $myPlayer->obj('MembershipFee') returns a object of type Currency
	  // In a template: <% control MyPlayer %>MembershipFee.Nice<% end_control %> returns a casted string (e.g. "$123.45")
	  function getMembershipFee() {
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

Inside sapphire it doesn't matter if you're editing a *has_many*- or a *many_many*-relationship. You need to get a
`[api:ComponentSet]`.

	:::php
	class Team extends DataObject {
	  // see "many_many"-description for a sample definition of class "Category"
	  static $many_many = array(
	    "Categories" => "Category",
	  );
		
	  /**
	
	   * @param DataObjectSet
	   */
	  function addCategories($additionalCategories) {
	    $existingCategories = $this->Categories();
	    
	    // method 1: Add many by iteration
	    foreach($additionalCategories as $category) {
	      $existingCategories->add($category);
	    }
	
	    // method 2: Add many by ID-List
	    $existingCategories->addMany(array(1,2,45,745));
	  }
	}


### Custom Relation Getters

You can use the flexible datamodel to get a filtered result-list without writing any SQL. For example, this snippet gets
you the "Players"-relation on a team, but only containing active players. (See `[api:DataObject::$has_many]` for more info on
the described relations).

	:::php
	class Team extends DataObject {
	  static $has_many = array(
	    "Players" => "Player"
	  );
	
	  // can be accessed by $myTeam->ActivePlayers
	  function getActivePlayers() {
	    return $this->Players("Status='Active'");
	  }
	}

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
	
	  function onBeforeWrite() {
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
	
	    // CAUTION: You are required to call the parent-function, otherwise sapphire will not execute the request.
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
	
	  function onBeforeDelete() {
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

See `[api:SQLQuery]` for custom *INSERT*, *UPDATE*, *DELETE* queries.




## Decorating DataObjects

You can add properties and methods to existing `[api:DataObjects]`s like `[api:Member]` (a core class) without hacking core
code or subclassing.
Please see `[api:DataObjectDecorator]` for a general description, and `[api:Hierarchy]` for our most
popular examples.



## FAQ

### Whats the difference between DataObject::get() and a relation-getter?
You can work with both in pretty much the same way, but relationship-getters return a special type of collection: 
A `[api:ComponentSet]` with relation-specific functionality.

	:::php
	$myTeam = DataObject::get_by_id('Team',$myPlayer->TeamID); // returns DataObject
	$myTeam->add(new Player()); // fails
	
	$myTeam = $myPlayer->Team(); // returns Componentset
	$myTeam->add(new Player()); // works

