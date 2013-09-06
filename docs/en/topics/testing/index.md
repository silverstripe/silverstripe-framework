# Unit and Integration Testing

The SilverStripe core contains various features designed to simplify the process of creating and managing automated tests.

* [Installing and Configuring PHPUnit](installing-and-configuring-phpunit): Guide on setting up PHPUnit and configuring it with SilverStripe
* [Create a unit test](create-silverstripe-test): Writing tests to check core data objects
* [Creating a functional test](create-functional-test): An overview of functional tests and how to write a functional test
* [Email Sending](email-sending): An overview of the built-in email testing code
* [Troubleshooting](testing-guide-troubleshooting): Frequently asked questions list for testing issues
* [Why Unit Test?](why-test): Why should you test and how to start testing
* [Glossary](glossary): Definitions of testing terminology.

If you are familiar with PHP coding but new to unit testing, you should read the [Introduction](/topics/testing) and
check out Mark's presentation [Getting to Grips with SilverStripe Testing](http://www.slideshare.net/maetl/getting-to-grips-with-silverstripe-testing).

You should also read over [the PHPUnit manual](http://www.phpunit.de/manual/current/en/). It provides a lot of
fundamental concepts that we build on in this documentation.

If you're more familiar with unit testing, but want a refresher of some of the concepts and terminology, you can browse
the [Testing Glossary](#glossary).
To get started now, follow the installation instructions below, and check
[Troubleshooting](/topics/testing/testing-guide-troubleshooting) in case you run into any problems.

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

The [sake](/topics/commandline) executable that comes with SilverStripe can trigger a customized
"[api:TestRunner]" class that handles the PHPUnit configuration and output formatting.
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
test runs in any automated testing environments. If for some reason you don't have
access to the command line, you can also run tests through the browser.

	 http://localhost/dev/tests

## Writing Tests

Tests are written by creating subclasses of `[api:SapphireTest]`.  You should put tests for your site in the
`mysite/tests` directory.  If you are writing tests for a module, put them in the `(modulename)/tests` directory.

Generally speaking, there should be one test class for each application class.  The name of the test class should be the
application class, with "Test" as a suffix.  For instance, we have all the tests for `SiteTree` in
`framework/tests/SiteTreeTest.php`

You will generally write two different kinds of test classes.

*  **Unit Test:** Test the behaviour of one of your DataObjects.
*  **Functional Test:** Test the behaviour of one of your controllers.

Some people may note that we have used the same naming convention as Ruby on Rails.

Tutorials and recipes for creating tests using the SilverStripe framework:

*  **[Create a SilverStripe Test](/topics/testing/create-silverstripe-test)**
*  **[Create a Functional Test](/topics/testing/create-functional-test)**
*  **[Test Outgoing Email Sending](/topics/testing/email-sending)**