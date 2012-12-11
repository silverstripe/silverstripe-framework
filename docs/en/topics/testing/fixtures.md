# Fixtures

## Overview

Often you need to test your functionality with some existing data, so called "fixtures".
The `[api:SapphireTest]` class already prepares an empty database for you,
and you have various ways to define those fixtures.

## YAML Fixtures

YAML is a markup language which is deliberately simple and easy to read,
so ideal for our fixture generation. 

We will begin with a sample file and talk our way through it.

	Page:
	    home:
	        Title: Home
	    about:
	        Title: About Us
	    staff:
	        Title: Staff
	        URLSegment: my-staff
	        Parent: =>Page.about
	        
	RedirectorPage:
	    redirect_home:
	        RedirectionType: Internal
	        LinkTo: =>Page.home


The contents of the YAML file are broken into three levels.

*  **Top level: class names** - `Page` and `RedirectorPage`.  This is the name of the dataobject class that should be created. 
The fact that `RedirectorPage` is actually a subclass is irrelevant to the system populating the database.  It just
instantiates the object you specify.
*  **Second level: identifiers** - `home`, `about`, etc.  These are the identifiers that you pass as
the second argument of SapphireTest::objFromFixture().  Each identifier you specify delimits a new database record. 
This means that every record needs to have an identifier, whether you use it or not.
*  **Third level: fields** - each field for the record is listed as a 3rd level entry.  In most cases, the field's raw
content is provided.  However, if you want to define a relationship, you can do so using "=>".

There are a couple of lines like this:

	Parent: =>Page.about

This will tell the system to set the ParentID database field to the ID of the Page object with the identifier "about". 
This can be used on any has-one or many-many relationship.  Note that we use the name of the relationship (Parent), and
not the name of the database field (ParentID)

On many-many relationships, you should specify a comma separated list of values.

	MyRelation: =>Class.inst1,=>Class.inst2,=>Class.inst3

An crucial thing to note is that **the YAML file specifies DataObjects, not database records**.  The database is
populated by instantiating DataObject objects, setting the fields listed, and calling write().  This means that any
onBeforeWrite() or default value logic will be executed as part of the test.  This forms the basis of our
testURLGeneration() test above.

For example, the URLSegment value of Page.staffduplicate is the same as the URLSegment value of Page.staff.  When the
fixture is set up, the URLSegment value of Page.staffduplicate will actually be my-staff-2.

Finally, be aware that requireDefaultRecords() is **not** called by the database populator - so you will need to specify
standard pages such as 404 and home in your YAML file.

## Test Class Definition



## Manual Object Creation

Sometimes statically defined fixtures don't suffice, because of the complexity of the tested model, 
or because the YAML format doesn't allow you to modify all model state.
One common example here is publishing pages (page fixtures aren't published by default).

You can always resort to creating objects manually in the test setup phase.
Since the test database is cleared on every test method, you'll get a fresh
set of test instances every time.

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

Manually defined fixture provide full flexibility, but very little in terms of structure and convention.
Alternatively, you can use the `[api:FixtureFactory]` class, which allows you
to set default values, callbacks on object creation, and dynamic/lazy value setting.
By the way, the `SapphireTest` YAML fixtures rely on internally on this class as well.

The idea is that rather than instanciating objects directly, we'll have a factory class for them.
This factory can have so called "blueprints" defined on it, which tells the factory
how to instanciate an object of a specific type. Blueprints need a name,
which is usually set to the class it creates. 

### Usage

Since blueprints are auto-created for all available DataObject subclasses,
you only need to instanciate a factory to start using it.

	:::php
	$factory = Injector::inst()->create('FixtureFactory');
	$obj = $factory->createObject('MyClass', 'myobj1');

It is important to remember that fixtures are referenced by arbitrary
identifiers ('myobj1'). These are internally mapped to their database identifiers.

	:::
	$databaseId = $factory->getId('MyClass', 'myobj1');

In order to create an object with certain properties, just add a second argument:

	:::php
	$obj = $factory->createObject('MyClass', 'myobj1', array('MyProperty' => 'My Value'));

### Default Properties

Blueprints can be overwritten in order to customize their behaviour,
for example with default properties in case none are passed into `createObject()`.

	:::php
	$factory->define('MyObject', array(
		'MyProperty' => 'My Default Value'
	));

### Dependent Properties

Values can be set on demand through anonymous functions,
which can either generate random defaults, or create
composite values based on other fixture data.

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

Model relations can be expressed through the same notation as in the YAML fixture format
described earlier, through the `=>` prefix on data values.

	:::php
	$obj = $factory->createObject('MyObject', array(
		'MyHasManyRelation' => '=>MyOtherObject.obj1,=>MyOtherObject.obj2'
	));

### Callbacks

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