title: Fixtures
summary: Populate test databases with fake seed data.

# Fixtures

To test functionality correctly, we must use consistent data. If we are testing our code with the same data each 
time, we can trust our tests to yield reliable results and to identify when the logic changes. Each test run in 
SilverStripe starts with a fresh database containing no records. `Fixtures` provide a way to describe the initial data
to load into the database. The `[api:SapphireTest]` class takes care of populating a test database with data from 
fixtures - all we have to do is define them.

Fixtures are defined in `YAML`. `YAML` is a markup language which is deliberately simple and easy to read, so it is 
ideal for fixture generation. Say we have the following two DataObjects:

	:::php
	<?php

	class Player extends DataObject {
		
		private static $db = array (
			'Name' => 'Varchar(255)'
		);

		private static $has_one = array(
			'Team' => 'Team'
		);
	}

	class Team extends DataObject {

		private static $db = array (
			'Name' => 'Varchar(255)',
			'Origin' => 'Varchar(255)'
		);

		private static $has_many = array(
			'Players' => 'Player'
		);
	}

We can represent multiple instances of them in `YAML` as follows:

**mysite/tests/fixtures.yml**

	:::yml
	Player:
		john:
			Name: John
			Team: =>Team.hurricanes
		joe:
			Name: Joe
			Team: =>Team.crusaders
		jack:
			Name: Jack
			Team: =>Team.crusaders
	Team:
		hurricanes:
			Name: The Hurricanes
			Origin: Wellington
		crusaders:
			Name: The Crusaders
			Origin: Bay of Plenty

This `YAML` is broken up into three levels, signified by the indentation of each line. In the first level of 
indentation, `Player` and `Team`, represent the class names of the objects we want to be created.

The second level, `john`/`joe`/`jack` & `hurricanes`/`crusaders`, are **identifiers**. Each identifier you specify 
represents a new object and can be referenced in the PHP using `objFromFixture`

	:::php
	$player = $this->objFromFixture('Player', 'jack');

The third and final level represents each individual object's fields.

A field can either be provided with raw data (such as the names for our Players), or we can define a relationship, as 
seen by the fields prefixed with `=>`.

Each one of our Players has a relationship to a Team, this is shown with the `Team` field for each `Player` being set 
to `=>Team.` followed by a team name.

<div class="info" markdown="1">
Take the player John in our example YAML, his team is the Hurricanes which is represented by `=>Team.hurricanes`. This
sets the `has_one` relationship for John with with the `Team` object `hurricanes`.
</div>

<div class="hint" markdown='1'>
Note that we use the name of the relationship (Team), and not the name of the 
database field (TeamID).
</div>

This style of relationship declaration can be used for any type of relationship (i.e `has_one`, `has_many`, `many_many`).

We can also declare the relationships conversely. Another way we could write the previous example is:

	:::yml
	Player:
		john:
			Name: John
		joe:
			Name: Joe
		jack:
			Name: Jack
	Team:
		hurricanes:
			Name: Hurricanes
			Origin: Wellington
			Players: =>Player.john
		crusaders:
			Name: Crusaders
			Origin: Bay of Plenty
			Players: =>Player.joe,=>Player.jack

The database is populated by instantiating `DataObject` objects and setting the fields declared in the `YAML`, then 
calling `write()` on those objects. Take for instance the `hurricances` record in the `YAML`. It is equivalent to 
writing:

	:::php
	$team = new Team(array(
		'Name' => 'Hurricanes',
		'Origin' => 'Wellington'
	));

	$team->write();

	$team->Players()->add($john);

<div class="notice" markdown="1">
As the YAML fixtures will call `write`, any `onBeforeWrite()` or default value logic will be executed as part of the 
test.
</div>

### Defining many_many_extraFields

`many_many` relations can have additional database fields attached to the relationship. For example we may want to 
declare the role each player has in the team.

	:::php
	class Player extends DataObject {
		
		private static $db = array (
			'Name' => 'Varchar(255)'
		);

		private static $belongs_many_many = array(
			'Teams' => 'Team'
		);
	}

	class Team extends DataObject {

		private static $db = array (
			'Name' => 'Varchar(255)'
		);

		private static $many_many = array(
			'Players' => 'Player'
		);

		private static $many_many_extraFields = array(
			"Players" => array(
				"Role" => "Varchar"
			)
		);	
	}

To provide the value for the `many_many_extraField` use the YAML list syntax.

	:::yml
	Player:
	  john:
	    Name: John
	  joe:
	    Name: Joe
	  jack:
	    Name: Jack
	Team:
	  hurricanes:
	    Name: The Hurricanes
	    Players: 
	      - =>Player.john:
 	        Role: Captain

	  crusaders:
	    Name: The Crusaders
	    Players: 
	      - =>Player.joe:
	        Role: Captain
	      - =>Player.jack:
	        Role: Winger

## Fixture Factories

While manually defined fixtures provide full flexibility, they offer very little in terms of structure and convention. 

Alternatively, you can use the `[api:FixtureFactory]` class, which allows you to set default values, callbacks on object 
creation, and dynamic/lazy value setting.

<div class="hint" markdown='1'>
SapphireTest uses FixtureFactory under the hood when it is provided with YAML based fixtures.
</div>

The idea is that rather than instantiating objects directly, we'll have a factory class for them. This factory can have 
*blueprints* defined on it, which tells the factory how to instantiate an object of a specific type. Blueprints need a 
name, which is usually set to the class it creates such as `Member` or `Page`.

Blueprints are auto-created for all available DataObject subclasses, you only need to instantiate a factory to start 
using them.

	:::php
	$factory = Injector::inst()->create('FixtureFactory');

	$obj = $factory->createObject('Team', 'hurricanes');

In order to create an object with certain properties, just add a third argument:

	:::php
	$obj = $factory->createObject('Team', 'hurricanes', array(
		'Name' => 'My Value'
	));

<div class="warning" markdown="1">
It is important to remember that fixtures are referenced by arbitrary identifiers ('hurricanes'). These are internally 
mapped to their database identifiers.
</div>

After we've created this object in the factory, `getId` is used to retrieve it by the identifier.

	:::php
	$databaseId = $factory->getId('Team', 'hurricanes');


### Default Properties

Blueprints can be overwritten in order to customize their behavior. For example, if a Fixture does not provide a Team
name, we can set the default to be `Unknown Team`.

	:::php
	$factory->define('Team', array(
		'Name' => 'Unknown Team'
	));

### Dependent Properties

Values can be set on demand through anonymous functions, which can either generate random defaults, or create composite 
values based on other fixture data.

	:::php
	$factory->define('Member', array(
		'Email' => function($obj, $data, $fixtures) {
			if(isset($data['FirstName']) {
				$obj->Email = strtolower($data['FirstName']) . '@example.org';
			}
		},
		'Score' => function($obj, $data, $fixtures) {
			$obj->Score = rand(0,10);
		}
	));

### Relations

Model relations can be expressed through the same notation as in the YAML fixture format described earlier, through the 
`=>` prefix on data values.

	:::php
	$obj = $factory->createObject('Team', 'hurricanes', array(
		'MyHasManyRelation' => '=>Player.john,=>Player.joe'
	));

#### Callbacks

Sometimes new model instances need to be modified in ways which can't be expressed in their properties, for example to 
publish a page, which requires a method call.

	:::php
	$blueprint = Injector::inst()->create('FixtureBlueprint', 'Member');

	$blueprint->addCallback('afterCreate', function($obj, $identifier, $data, $fixtures) {
		$obj->publish('Stage', 'Live');
	});

	$page = $factory->define('Page', $blueprint);

Available callbacks:

 * `beforeCreate($identifier, $data, $fixtures)`
 * `afterCreate($obj, $identifier, $data, $fixtures)`

### Multiple Blueprints

Data of the same type can have variations, for example forum members vs. CMS admins could both inherit from the `Member` 
class, but have completely different properties. This is where named blueprints come in. By default, blueprint names 
equal the class names they manage.

	:::php
	$memberBlueprint = Injector::inst()->create('FixtureBlueprint', 'Member', 'Member');

	$adminBlueprint = Injector::inst()->create('FixtureBlueprint', 'AdminMember', 'Member');

	$adminBlueprint->addCallback('afterCreate', function($obj, $identifier, $data, $fixtures) {
		if(isset($fixtures['Group']['admin'])) {
			$adminGroup = Group::get()->byId($fixtures['Group']['admin']);
			$obj->Groups()->add($adminGroup);
		}
	});

	$member = $factory->createObject('Member'); // not in admin group

	$admin = $factory->createObject('AdminMember'); // in admin group

## Related Documentation

* [How to use a FixtureFactory](how_to/fixturefactories/)

## API Documentation

* [api:FixtureFactory]
* [api:FixtureBlueprint]

