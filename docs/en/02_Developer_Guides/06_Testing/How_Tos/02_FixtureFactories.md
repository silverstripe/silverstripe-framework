title: How to use a FixtureFactory

# How to use a FixtureFactory

The [api:FixtureFactory] is used to manually create data structures for use with tests. For more information on fixtures
see the [Fixtures](../fixtures) documentation.

In this how to we'll use a `FixtureFactory` and a custom blue print for giving us a shortcut for creating new objects
with information that we need.

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

			echo $MyObjectObj->MyProperty;
			// returns "My Default Value"

			echo $myPageObj->MyOtherProperty;
			// returns "My Custom Value"
		}
	}

## Related Documentation

* [Fixtures](../fixtures)

## API Documentation

* [api:FixtureFactory]
* [api:FixtureBlueprint]