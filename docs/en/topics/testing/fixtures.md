# Fixtures

## Overview

You will often find the need to test your functionality with some consistent 
data. If we are testing our code with the same data each time, we can trust our 
tests to yield reliable results.

In Silverstripe we define this data via 'fixtures' (so called because of their 
fixed nature). The `[api:SapphireTest]` class takes care of populating a test 
database with data from these fixtures - all we have to do is define them, and 
we have a few ways in which we can do this.

## YAML Fixtures

YAML is a markup language which is deliberately simple and easy to read, so it 
is ideal for fixture generation.

Say we have the following two DataObjects:

	:::php
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

Our `YAML` is broken up into three levels, signified by the indentation of each 
line. In the first level of indentation, `Player` and `Team`, represent the 
class names of the objects we want to be created for the test.

The second level, `john`/`joe`/`jack` & `hurricanes`/`crusaders`, are 
identifiers. These are what you pass as the second argument of 
`SapphireTest::objFromFixture()`. Each identifier you specify represents a new 
object.

The third and final level represents each individual object's fields.

A field can either be provided with raw data (such as the names for our 
Players), or we can define a relationship, as seen by the fields prefixed with 
`=>`.

Each one of our Players has a relationship to a Team, this is shown with the 
`Team` field for each `Player` being set to `=>Team.` followed by a team name.

Take the player John for example, his team is the Hurricanes which is 
represented by `=>Team.hurricanes`.

This is tells the system that we want to set up a relationship for the `Player` 
object `john` with the `Team` object `hurricanes`.

It will populate the `Player` object's `TeamID` with the ID of `hurricanes`,
just like how a relationship is always set up.

<div class="hint" markdown='1'>
Note that we use the name of the relationship (Team), and not the name of the 
database field (TeamID).
</div>

This style of relationship declaration can be used for both a `has-one` and a 
`many-many` relationship. For `many-many` relationships, we specify a comma 
separated list of values.

For example we could just as easily write the above as:

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
			Origin: Wellington
			Players: =>Player.john
		crusaders:
			Name: The Crusaders
			Origin: Bay of Plenty
			Players: =>Player.joe,=>Player.jack

A crucial thing to note is that **the YAML file specifies DataObjects, not 
database records**.

The database is populated by instantiating DataObject objects and setting the 
fields declared in the YML, then calling write() on those objects. This means 
that any `onBeforeWrite()` or default value logic will be executed as part of 
the test. The reasoning behind this is to allow us to test the `onBeforeWrite` 
functionality of our objects.

You can see this kind of testing in action in the `testURLGeneration()` test 
from the example in [Creating a SilverStripe Test](creating-a-silverstripe-test).

### Defining many_many_extraFields

`many_many` relations can have additional database fields attached to the 
relationship. For example we may want to declare the role each player has in the
team.

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
			);
		);	
	}

To provide the value for the many_many_extraField use the YAML list syntax.

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

## Test Class Definition

### Manual Object Creation

Sometimes statically defined fixtures don't suffice. This could be because of 
the complexity of the tested model, or because the YAML format doesn't allow you 
to modify all of a model's state.

One common example here is publishing pages (page fixtures aren't published by 
default).

You can always resort to creating objects manually in the test setup phase.

Since the test database is cleared on every test method, you'll get a fresh set 
of test instances every time.

	:::php
	class SiteTreeTest extends SapphireTest {

		function setUp() {
			parent::setUp();

			for($i=0; $i<100; $i++) {
				$page = new Page(array('Title' => "Page $i"));
				$page->write();
				$page->publish('Stage', 'Live');
			}
		}
	}

## Fixture Factories

### Why Factories?

While manually defined fixtures provide full flexibility, they offer very little 
in terms of structure and convention. Alternatively, you can use the 
`[api:FixtureFactory]` class, which allows you to set default values, callbacks 
on object creation, and dynamic/lazy value setting.

<div class="hint" markdown='1'>
SapphireTest uses FixtureFactory under the hood when it is provided with YAML 
based fixtures.
</div>

The idea is that rather than instantiating objects directly, we'll have a 
factory class for them. This factory can have so called "blueprints" defined on 
it, which tells the factory how to instantiate an object of a specific type. 
Blueprints need a name, which is usually set to the class it creates.

### Usage

Since blueprints are auto-created for all available DataObject subclasses,
you only need to instantiate a factory to start using it.

	:::php
	$factory = Injector::inst()->create('FixtureFactory');
	$obj = $factory->createObject('MyClass', 'myobj1');

It is important to remember that fixtures are referenced by arbitrary
identifiers ('myobj1'). These are internally mapped to their database identifiers.

	:::php
	$databaseId = $factory->getId('MyClass', 'myobj1');

In order to create an object with certain properties, just add a second argument:

	:::php
	$obj = $factory->createObject('MyClass', 'myobj1', array('MyProperty' => 'My Value'));

#### Default Properties

Blueprints can be overwritten in order to customize their behaviour,
for example with default properties in case none are passed into `createObject()`.

	:::php
	$factory->define('MyObject', array(
		'MyProperty' => 'My Default Value'
	));

#### Dependent Properties

Values can be set on demand through anonymous functions, which can either generate random defaults,
or create composite values based on other fixture data.

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

#### Relations

Model relations can be expressed through the same notation as in the YAML fixture format
described earlier, through the `=>` prefix on data values.

	:::php
	$obj = $factory->createObject('MyObject', 'myobj1', array(
		'MyHasManyRelation' => '=>MyOtherObject.obj1,=>MyOtherObject.obj2'
	));

#### Callbacks

Sometimes new model instances need to be modified in ways which can't be expressed
in their properties, for example to publish a page, which requires a method call.

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

Data of the same type can have variations, for example forum members vs.
CMS admins could both inherit from the `Member` class, but have completely
different properties. This is where named blueprints come in.
By default, blueprint names equal the class names they manage.

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

### Full Test Example

	:::php
	class MyObjectTest extends SapphireTest {

		protected $factory;

		function __construct() {
			parent::__construct();

			$factory = Injector::inst()->create('FixtureFactory');
			// Defines a "blueprint" for new objects
			$factory->define('MyObject', array(
				'MyProperty' => 'My Default Value'
			));
			$this->factory = $factory;
		}

		function testSomething() {
			$MyObjectObj = $this->factory->createObject(
				'MyObject',
				array('MyOtherProperty' => 'My Custom Value')
			);
			// $myPageObj->MyProperty = My Default Value
			// $myPageObj->MyOtherProperty = My Custom Value
		}
	}
