title: How to write a SapphireTest

# How to write a SapphireTest

Here is an example of a test which extends [api:SapphireTest] to test the URL generation of the page. It also showcases
how you can load default records into the test database.

**mysite/tests/PageTest.php**

	:::php
	<?php

	class PageTest extends SapphireTest {

		/** 
		 * Defines the fixture file to use for this test class
		 *
		 /
		protected static $fixture_file = 'SiteTreeTest.yml';

		/**
		 * Test generation of the URLSegment values.
		 *
		 * Makes sure to:
		 *  - Turn things into lowercase-hyphen-format
		 *  - Generates from Title by default, unless URLSegment is explicitly set
		 *  - Resolves duplicates by appending a number
		 */
		public function testURLGeneration() {
			$expectedURLs = array(
				'home' => 'home',
				'staff' => 'my-staff',
				'about' => 'about-us',
				'staffduplicate' => 'my-staff-2'
			);

			foreach($expectedURLs as $fixture => $urlSegment) {
				$obj = $this->objFromFixture('Page', $fixture);

				$this->assertEquals($urlSegment, $obj->URLSegment);
			}
		}
	}

Firstly we define a static `$fixture_file`, this should point to a file that represents the data we want to test,
represented as a YAML [Fixture](../fixtures). When our test is run, the data from this file will be loaded into a test 
database and discarded at the end of the test.

<div class="notice" markdown="1">
The `fixture_file` property can be path to a file, or an array of strings pointing to many files. The path must be 
absolute from your website's root folder.
</div>

The second part of our class is the `testURLGeneration` method. This method is our test. When the test is executed, 
methods prefixed with the word `test` will be run. 

<div class="notice" markdown="1">
The test database is rebuilt every time one of these methods is run.
</div>

Inside our test method is the `objFromFixture` method that will generate an object for us based on data from our fixture
file. To identify to the object, we provide a class name and an identifier. The identifier is specified in the YAML file
but not saved in the database anywhere, `objFromFixture` looks the `[api:DataObject]` up in memory rather than using the
database. This means that you can use it to test the functions responsible for looking up content in the database.

The final part of our test is an assertion command, `assertEquals`. An assertion command allows us to test for something
in our test methods (in this case we are testing if two values are equal). A test method can have more than one 
assertion command, and if any one of these assertions fail, so will the test method.

<div class="info" markdown="1">
For more information on PHPUnit's assertions see the [PHPUnit manual](http://www.phpunit.de/manual/current/en/api.html#api.assert).
</div>

## Related Documentation

* [Unit Testing](../unit_testing)
* [Fixtures](../fixtures)

## API Documentation

* [api:SapphireTest]
* [api:FunctionalTest]