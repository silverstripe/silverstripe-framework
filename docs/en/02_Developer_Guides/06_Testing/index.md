summary: Deploy robust applications by bundling Unit and Behavior tests with your application code and modules.

# Unit and Integration Testing

For behaviour testing in SilverStripe, check out [SilverStripe Behat Documentation](https://github.com/silverstripe-labs/silverstripe-behat-extension/).

## Introduction

The SilverStripe core contains various features designed to simplify the process of creating and managing automated tests.

SilverStripe uses [PHPUnit](http://www.phpunit.de) for unit tests, and the framework contains features to simplify the
process of creating and managing tests.

If you're more familiar with unit testing, but want a refresher of some of the concepts and terminology, you can browse
the [Testing Glossary](testing_glossary). To get started now, follow the installation instructions below.

If you are familiar with PHP coding but new to unit testing then check out Mark's presentation [Getting to Grips with SilverStripe Testing](http://www.slideshare.net/maetl/getting-to-grips-with-silverstripe-testing).

You should also read over the [PHPUnit manual](http://www.phpunit.de/manual/current/en/). It provides a lot of
fundamental concepts that we build on in this documentation.

## Running Tests

In order to run tests, you need to install SilverStripe using [/getting-started/composer](Composer),
which will pull in the required development dependencies to run tests.
These are not included in the standard archive downloads provided from silverstripe.org.

Tests are run from the commandline, in your webroot folder:

 * `vendor/bin/phpunit`: Runs all tests (as defined by `phpunit.xml`)
 * `vendor/bin/phpunit vendor/silverstripe/framework/tests/`: Run all tests of a specific module
 * `vendor/bin/phpunit vendor/silverstripe/framework/tests/filesystem`: Run specific tests within a specific module
 * `vendor/bin/phpunit vendor/silverstripe/framework/tests/filesystem/FolderTest.php`: Run a specific test 
 * `vendor/bin/phpunit vendor/silverstripe/framework/tests '' flush=all`: Run tests with optional request parameters (note the empty second argument)

Check the PHPUnit manual for all available [command line arguments](http://www.phpunit.de/manual/current/en/textui.html).

On Linux or OSX, you can avoid typing the full path on every invocation by adding `vendor/bin` 
to your `$PATH` definition in the shell profile (usually `~/.profile`): `PATH=./vendor/bin:$PATH`

## Generating a Coverage Report

PHPUnit can generate a code coverage report ([docs](http://www.phpunit.de/manual/current/en/code-coverage-analysis.html))
which shows you how much of your logic is executed by your tests. This is very useful to determine gaps in tests.

	:::bash
	vendor/bin/phpunit --coverage-html <output-folder> <optional-tests-folder>

To view the report, open the `index.html` in `<output-folder>` in a web browser.

Typically, only your own custom PHP code in your project should be regarded when producing these reports. To exclude 
some `thirdparty/` directories add the following to the `phpunit.xml` configuration file.

	:::xml
	<filter>
		<blacklist>
			<directory suffix=".php">vendor/silverstripe/framework/dev/</directory>
			<directory suffix=".php">vendor/silverstripe/framework/thirdparty/</directory>
			<directory suffix=".php">vendor/silverstripe/cms/thirdparty/</directory>

			<!-- Add your custom rules here -->
			<directory suffix=".php">app/thirdparty/</directory>
		</blacklist>
	</filter>

## Configuration

The `phpunit` executable can be configured by [command line arguments](http://www.phpunit.de/manual/current/en/textui.html) 
or through an XML file. File-based configuration has
the advantage of enforcing certain rules across test executions (e.g. excluding files from code coverage reports), and
of course this information can be version controlled and shared with other team members.

SilverStripe comes with a default `phpunit.xml.dist` that you can use as a starting point. Copy the file into a new
`phpunit.xml` and customize to your needs - PHPUnit will auto-detect its existence, and prioritize it over the default
file.

There's nothing stopping you from creating multiple XML files (see the `--configuration` flag in
[PHPUnit documentation](http://www.phpunit.de/manual/current/en/textui.html)). For example, you could have a
`phpunit-unit-tests.xml` and `phpunit-functional-tests.xml` file (see below).

### Database Permissions

SilverStripe tests create their own temporary database on every execution. Because of this the database user in your config file
should have the appropriate permissions to create new databases on your server, otherwise tests will not run.

## Writing Tests

Tests are written by creating subclasses of [SapphireTest](api:SilverStripe\Dev\SapphireTest).  You should put tests for your site in the
`app/tests` directory.  If you are writing tests for a module, put them in the `tests/` directory of your module (in `vendor/`).

Generally speaking, there should be one test class for each application class.  The name of the test class should be the
application class, with "Test" as a suffix.  For instance, we have all the tests for `SiteTree` in
`vendor/silverstripe/framework/tests/SiteTreeTest.php`

You will generally write two different kinds of test classes.

*  **Unit Test:** Test the behaviour of one of your DataObjects.
*  **Functional Test:** Test the behaviour of one of your controllers.

Tutorials and recipes for creating tests using the SilverStripe framework:

* [Creating a SilverStripe test](how_tos/write_a_sapphiretest): Writing tests to check core data objects
* [Creating a functional test](how_tos/write_a_functionaltest): An overview of functional tests and how to write a functional test
* [Testing Outgoing Email](how_tos/testing_email): An overview of the built-in email testing code
