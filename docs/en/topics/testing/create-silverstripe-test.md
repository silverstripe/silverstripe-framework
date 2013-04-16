#  How To Create a SilverStripe Test

A unit test class will test the behaviour of one of your `[api:DataObjects]`.  This simple fragment of `[api:SiteTreeTest]`
provides us the basics of creating unit tests.

	:::php
	<?php
	class SiteTreeTest extends SapphireTest {
		
		// Define the fixture file to use for this test class
		private static $fixture_file = 'SiteTreeTest.yml';
	
		/**	
		 * Test generation of the URLSegment values.
		 *  - Turns things into lowercase-hyphen-format
		 *  - Generates from Title by default, unless URLSegment is explicitly set
		 *  - Resolves duplicates by appending a number
		 */
		public function testURLGeneration() {
			$expectedURLs = array(
				'home' => 'home',
				'staff' => 'my-staff',
				'about' => 'about-us',
				'staffduplicate' => 'my-staff-2',
				'product1' => '1-1-test-product',
				'product2' => 'another-product',
				'product3' => 'another-product-2',
				'product4' => 'another-product-3',
			);
			
			foreach($expectedURLs as $fixture => $urlSegment) {
				$obj = $this->objFromFixture('Page', $fixture);
				$this->assertEquals($urlSegment, $obj->URLSegment);
			}
		}
	}
	


There are a number of points to note in this code fragment:

*  Your test is a **subclass of SapphireTest**.  Both unit tests and functional tests are a subclass of `[api:SapphireTest]`.
*  **static $fixture_file** is defined.  The testing framework will automatically set up a new database for **each** of
your tests.  The initial database content will be sourced from the YML file that you list in $fixture_file. The property can take an array of fixture paths.
*  Each **method that starts with the word "test"** will be executed by the TestRunner.  Define as many as you like; the
database will be rebuilt for each of these.
*  **$this->objFromFixture($className, $identifier)** can be used to select one of the objects named in your fixture
file.  To identify to the object, we provide a class name and an identifier.  The identifier is specified in the YML
file but not saved in the database anywhere.  objFromFixture() looks the `[api:DataObject]` up in memory rather than using the
database.  This means that you can use it to test the functions responsible for looking up content in the database.

## Assertion commands

**$this->assertEquals()** is an example of an assertion function.  
These functions form the basis of our tests - a test
fails if and only if one or more of the assertions fail.  
See [the PHPUnit manual](http://www.phpunit.de/manual/current/en/api.html#api.assert)
for a listing of all PHPUnit's built-in assertions.

The `[api:SapphireTest]` class comes with additional assertions which are more
specific to the framework, e.g. `[assertEmailSent](api:SapphireTest->assertEmailSent())`
which can simulate sending emails through the `Email->send()` API without actually
using a mail server (see the [testing emails](email-sending)) guide.

## Fixtures

Often you need to test your functionality with some existing data, so called "fixtures".
These records are inserted on a fresh test database automatically. 
[Read more about fixtures](fixtures).