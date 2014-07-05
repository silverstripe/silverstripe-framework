# Creating a SilverStripe Test

A test is created by extending one of two classes, SapphireTest and FunctionalTest.
You would subclass `[api:SapphireTest]` to test your application logic,
for example testing the behaviour of one of your `[DataObjects](api:DataObject)`,
whereas `[api:FunctionalTest]` is extended when you want to test your application's functionality,
such as testing the results of GET and POST requests,
and validating the content of a page. FunctionalTest is a subclass of SapphireTest.

## Creating a test from SapphireTest

Here is an example of a test which extends SapphireTest:

	:::php
	<?php
	class SiteTreeTest extends SapphireTest {

		// Define the fixture file to use for this test class
		protected static $fixture_file = 'SiteTreeTest.yml';

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
				'staffduplicate' => 'my-staff-2'
			);

			foreach($expectedURLs as $fixture => $urlSegment) {
				$obj = $this->objFromFixture('Page', $fixture);
				$this->assertEquals($urlSegment, $obj->URLSegment);
			}
		}
	}

Firstly we define a static member `$fixture_file`, this should point to a file that represents the data we want to test,
represented in YAML. When our test is run, the data from this file will be loaded into a test database for our test to use.
This property can be an array of strings pointing to many .yml files, but for our test we are just using a string on its
own. For more detail on fixtures, see [this page](fixtures).

The second part of our class is the `testURLGeneration` method. This method is our test. You can asign many tests, but
again for our purposes there is just the one. When the test is executed, methods prefixed with the word `test` will be
run. The test database is rebuilt every time one of these methods is run.

Inside our test method is the `objFromFixture` method that will generate an object for us based on data from our fixture
file. To identify to the object, we provide a class name and an identifier. The identifier is specified in the YAML file
but not saved in the database anywhere, `objFromFixture` looks the `[api:DataObject]` up in memory rather than using the
database. This means that you can use it to test the functions responsible for looking up content in the database.

The final part of our test is an assertion command, `assertEquals`. An assertion command allows us to test for something
in our test methods (in this case we are testing if two values are equal). A test method can have more than one assertion
command, and if any one of these assertions fail, so will the test method.

For more information on PHPUnit's assertions see the [PHPUnit manual](http://www.phpunit.de/manual/current/en/api.html#api.assert).

The `[api:SapphireTest]` class comes with additional assertions which are more specific to Sapphire, for example the
`assertEmailSent` method, which simulates sending emails through the `Email->send()`
API without actually using a mail server. For more details on this see the [testing emails](testing-email) guide.
