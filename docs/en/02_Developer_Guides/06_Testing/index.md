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

You should also read over [the PHPUnit manual](http://www.phpunit.de/manual/current/en/). It provides a lot of
fundamental concepts that we build on in this documentation.

Unit tests are not included in the zip/tar.gz SilverStripe [downloads](http://www.silverstripe.org/software/download/) so to get them, install SilverStripe [with composer](/getting_started/composer).

## Invoking phpunit

Once you have used composer to create your project, `cd` to your project root. Composer will have installed PHPUnit alongside the required PHP classes into the `vendor/bin/` directory.

If you don't want to invoke PHPUnit through its full path (`vendor/bin/phpunit`), add `./vendor/bin` to your $PATH, or symlink phpunit into the root directory of your website:

- `PATH=./vendor/bin:$PATH` in your shell's profile script; **or**
- `ln -s vendor/bin/phpunit phpunit` at the command prompt in your project root

## Configuration

### phpunit.xml

The `phpunit` executable can be configured by command line arguments or through an XML file. File-based configuration has
the advantage of enforcing certain rules across test executions (e.g. excluding files from code coverage reports), and
of course this information can be version controlled and shared with other team members.

**Note: This doesn't apply for running tests through the "sake" wrapper**

SilverStripe comes with a default `phpunit.xml.dist` that you can use as a starting point. Copy the file into a new
`phpunit.xml` and customize to your needs - PHPUnit will auto-detect its existence, and prioritize it over the default
file.

There's nothing stopping you from creating multiple XML files (see the `--configuration` flag in
[PHPUnit documentation](http://www.phpunit.de/manual/current/en/textui.html)). For example, you could have a
`phpunit-unit-tests.xml` and `phpunit-functional-tests.xml` file (see below).

### Database Permissions

SilverStripe tests create thier own database when they are run. Because of this the database user in your config file
should have the appropriate permissions to create new databases on your server, otherwise tests will not run.

## Writing Tests

Tests are written by creating subclasses of `[api:SapphireTest]`.  You should put tests for your site in the
`mysite/tests` directory.  If you are writing tests for a module, put them in the `(modulename)/tests` directory.

Generally speaking, there should be one test class for each application class.  The name of the test class should be the
application class, with "Test" as a suffix.  For instance, we have all the tests for `SiteTree` in
`framework/tests/SiteTreeTest.php`

You will generally write two different kinds of test classes.

*  **Unit Test:** Test the behaviour of one of your DataObjects.
*  **Functional Test:** Test the behaviour of one of your controllers.

Tutorials and recipes for creating tests using the SilverStripe framework:

* [Creating a SilverStripe test](how_tos/write_a_sapphiretest): Writing tests to check core data objects
* [Creating a functional test](how_tos/write_a_functionaltest): An overview of functional tests and how to write a functional test
* [Testing Outgoing Email](how_tos/testing_email): An overview of the built-in email testing code

## Running Tests

### Via the "phpunit" Binary on Command Line

The `phpunit` binary should be used from the root directory of your website.

	# Runs all tests defined in phpunit.xml
	phpunit

	# Run all tests of a specific module
	phpunit framework/tests/

	# Run specific tests within a specific module
	phpunit framework/tests/filesystem

	# Run a specific test
	phpunit framework/tests/filesystem/FolderTest.php

	# Run tests with optional `$_GET` parameters (you need an empty second argument)
	phpunit framework/tests '' flush=all

All command-line arguments are documented on
[phpunit.de](http://www.phpunit.de/manual/current/en/textui.html).

### Via the "sake" Wrapper on Command Line

The [sake](/developer_guides/cli/) executable that comes with SilverStripe can trigger a customized
`[api:TestRunner]` class that handles the PHPUnit configuration and output formatting.
While the custom test runner a handy tool, its also more limited than using `phpunit` directly,
particularly around formatting test output.

	# Run all tests
	sake dev/tests/all

	# Run all tests of a specific module (comma-separated)
	sake dev/tests/module/framework,cms

	# Run specific tests (comma-separated)
	sake dev/tests/FolderTest,OtherTest

	# Run tests with optional `$_GET` parameters
	sake dev/tests/all flush=all

	# Skip some tests
	sake dev/tests/all SkipTests=MySkippedTest

### Via Web Browser

Executing tests from the command line is recommended, since it most closely reflects
test runs in any automated testing environments. However, you can also run tests through the browser:

	http://localhost/dev/tests
