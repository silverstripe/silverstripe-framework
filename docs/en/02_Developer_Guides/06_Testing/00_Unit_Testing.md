title: Unit and Integration Testing
summary: Test models, database logic and your object methods.

# Unit and Integration Testing

A Unit Test is an automated piece of code that invokes a unit of work in the application and then checks the behavior 
to ensure that it works as it should. A simple example would be to test the result of a PHP method.

**mysite/code/Page.php**

	:::php
	<?php

	class Page extends SiteTree {

		public static function MyMethod() {
			return (1 + 1);
		}
	}

**mysite/tests/PageTest.php**

	:::php
	<?php

	class PageTest extends SapphireTest {

		public function testMyMethod() {
			$this->assertEquals(2, Page::MyMethod());
		}
	}

<div class="info" markdown="1">
Tests for your application should be stored in the `mysite/tests` directory. Test cases for add-ons should be stored in 
the `(modulename)/tests` directory. 

Test case classes should end with `Test` (e.g PageTest) and test methods must start with `test` (e.g testMyMethod).
</div>

A SilverStripe unit test is created by extending one of two classes, [api:SapphireTest] or [api:FunctionalTest]. 

[api:SapphireTest] is used to test your model logic (such as a `DataObject`), and [api:FunctionalTest] is used when 
you want to test a `Controller`, `Form` or anything that requires a web page.

<div class="info" markdown="1">
`FunctionalTest` is a subclass of `SapphireTest` so will inherit all of the behaviors. By subclassing `FunctionalTest`
you gain the ability to load and test web pages on the site. 

`SapphireTest` in turn, extends `PHPUnit_Framework_TestCase`. For more information on `PHPUnit_Framework_TestCase` see 
the [PHPUnit](http://www.phpunit.de) documentation. It provides a lot of fundamental concepts that we build on in this 
documentation.
</div>

## Running Tests

### PHPUnit Binary

The `phpunit` binary should be used from the root directory of your website.

	:::bash
	phpunit
	# Runs all tests
	
	phpunit framework/tests/
	# Run all tests of a specific module

	phpunit framework/tests/filesystem
	# Run specific tests within a specific module
	
	phpunit framework/tests/filesystem/FolderTest.php
	# Run a specific test
	
	phpunit framework/tests '' flush=all
	# Run tests with optional `$_GET` parameters (you need an empty second argument)

<div class="alert" markdown="1">
If phpunit is not installed globally on your machine, you may need to replace the above usage of `phpunit` with the full
path (e.g `vendor/bin/phpunit framework/tests`)
</div>

<div class="info" markdown="1">
All command-line arguments are documented on [phpunit.de](http://www.phpunit.de/manual/current/en/textui.html).
</div>
	
### Via a Web Browser

Executing tests from the command line is recommended, since it most closely reflects test runs in any automated testing 
environments. If for some reason you don't have access to the command line, you can also run tests through the browser.
	
	http://yoursite.com/dev/tests


### Via the CLI

The [sake](../cli) executable that comes with SilverStripe can trigger a customized `[api:TestRunner]` class that 
handles the PHPUnit configuration and output formatting. While the custom test runner a handy tool, it's also more 
limited than using `phpunit` directly, particularly around formatting test output.

	:::bash
	sake dev/tests/all
	# Run all tests

	sake dev/tests/module/framework,cms
	# Run all tests of a specific module (comma-separated)

	sake dev/tests/FolderTest,OtherTest
	# Run specific tests (comma-separated)

	sake dev/tests/all "flush=all&foo=bar"
	# Run tests with optional `$_GET` parameters

	sake dev/tests/all SkipTests=MySkippedTest
	# Skip some tests


## Test Databases and Fixtures

SilverStripe tests create their own database when the test starts. New `ss_tmp` databases are created using the same 
connection details you provide for the main website. The new `ss_tmp` database does not copy what is currently in your 
application database. To provide seed data use a [Fixture](fixtures) file.

<div class="alert" markdown="1">
As the test runner will create new databases for the tests to run, the database user should have the appropriate 
permissions to create new databases on your server.
</div>

<div class="notice" markdown="1">
The test database is rebuilt every time one of the test methods is run. Over time, you may have several hundred test 
databases on your machine. To get rid of them is a call to `http://yoursite.com/dev/tests/cleanupdb`
</div>

## Custom PHPUnit Configuration

The `phpunit` executable can be configured by command line arguments or through an XML file. SilverStripe comes with a 
default `phpunit.xml.dist` that you can use as a starting point. Copy the file into `phpunit.xml` and customize to your 
needs.

**phpunit.xml**

	:::xml
	<phpunit bootstrap="framework/tests/bootstrap.php" colors="true">
		<testsuite name="Default">
			<directory>mysite/tests</directory>
			<directory>cms/tests</directory>
			<directory>framework/tests</directory>
		</testsuite>
		
		<listeners>
			<listener class="SS_TestListener" file="framework/dev/TestListener.php" />
		</listeners>
		
		<groups>
			<exclude>
				<group>sanitychecks</group>
			</exclude>
		</groups>
	</phpunit>

<div class="alert" markdown="1">
This configuration file doesn't apply for running tests through the "sake" wrapper
</div>


### setUp() and tearDown()

In addition to loading data through a [Fixture File](fixtures), a test case may require some additional setup work to be
run before each test method. For this, use the PHPUnit `setUp` and `tearDown` methods. These are run at the start and 
end of each test.

	:::php
	<?php

	class PageTest extends SapphireTest {

		function setUp() {
			parent::setUp();

			// create 100 pages
			for($i=0; $i<100; $i++) {
				$page = new Page(array('Title' => "Page $i"));
				$page->write();
				$page->publish('Stage', 'Live');
			}

			// reset configuration for the test.
			Config::nest();
			Config::inst()->update('Foo', 'bar', 'Hello!');
		}

		public function tearDown() {
			// restores the config variables
			Config::unnest();

			parent::tearDown();
		}

		public function testMyMethod() {
			// ..
		}

		public function testMySecondMethod() {
			// ..
		}
	}

`tearDownOnce` and `setUpOnce` can be used to run code just once for the file rather than before and after each 
individual test case.

	:::php
	<?php

	class PageTest extends SapphireTest {

		function setUpOnce() {
			parent::setUpOnce();

			// ..
		}

		public function tearDownOnce() {
			parent::tearDownOnce();

			// ..
		}
	}

## Generating a Coverage Report

PHPUnit can generate a code coverage report ([docs](http://www.phpunit.de/manual/current/en/code-coverage-analysis.html))
by executing the following commands.

	:::bash
	phpunit --coverage-html assets/coverage-report
	# Generate coverage report for the whole project

 	phpunit --coverage-html assets/coverage-report mysite/tests/
 	# Generate coverage report for the "mysite" module

<div class="notice" markdown="1">
These commands will output a report to the `assets/coverage-report/` folder. To view the report, open the `index.html`
file within a web browser.
</div>

Typically, only your own custom PHP code in your project should be regarded when producing these reports. To exclude 
some `thirdparty/` directories add the following to the `phpunit.xml` configuration file.

	:::xml
	<filter>
		<blacklist>
			<directory suffix=".php">framework/dev/</directory>
			<directory suffix=".php">framework/thirdparty/</directory>
			<directory suffix=".php">cms/thirdparty/</directory>
			
			<!-- Add your custom rules here -->
			<directory suffix=".php">mysite/thirdparty/</directory>
		</blacklist>
	</filter>

## Related Documentation

* [How to Write a SapphireTest](how_tos/write_a_sapphiretest)
* [How to Write a FunctionalTest](how_tos/write_a_functionaltest)
* [Fixtures](fixtures)

## API Documentation

* [api:TestRunner]
* [api:SapphireTest]
* [api:FunctionalTest]