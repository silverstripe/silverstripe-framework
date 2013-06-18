# Unit and Integration Testing

The SilverStripe core contains various features designed to simplify the process of creating and managing automated tests.

* [Create a unit test](create-silverstripe-test): Writing tests to check core data objects
* [Creating a functional test](create-functional-test): An overview of functional tests and how to write a functional test
* [Email Sending](email-sending): An overview of the built-in email testing code
* [Troubleshooting](testing-guide-troubleshooting): Frequently asked questions list for testing issues
* [Why Unit Test?](why-test): Why should you test and how to start testing

If you are familiar with PHP coding but new to unit testing, you should read the [Introduction](/topics/testing) and
check out Mark's presentation [Getting to Grips with SilverStripe Testing](http://www.slideshare.net/maetl/getting-to-grips-with-silverstripe-testing).

You should also read over [the PHPUnit manual](http://www.phpunit.de/manual/current/en/). It provides a lot of
fundamental concepts that we build on in this documentation.

If you're more familiar with unit testing, but want a refresher of some of the concepts and terminology, you can browse
the [Testing Glossary](#glossary).
To get started now, follow the installation instructions below, and check
[Troubleshooting](/topics/testing/testing-guide-troubleshooting) in case you run into any problems.

## Installation

### Via Composer

Unit tests are not included in the normal SilverStripe downloads,
you are expected to work with local git repositories 
([installation instructions](/topics/installation/composer)).

Once you've got the project up and running,
check out the additional requirements to run unit tests:

	composer update --dev

The will install (among other things) the [PHPUnit](http://www.phpunit.de/) dependency,
which is the framework we're building our unit tests on.
Composer installs it alongside the required PHP classes into the `vendor/bin/` directory.
You can either use it through its full path (`vendor/bin/phpunit`), or symlink it
into the root directory of your website:

	ln -s vendor/bin/phpunit phpunit

### Via PEAR

Alternatively, you can check out phpunit globally via the PEAR packanage manager
([instructions](https://github.com/sebastianbergmann/phpunit/)).

	pear config-set auto_discover 1
	pear install pear.phpunit.de/PHPUnit

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

## Configuration

### phpunit.xml

The `phpunit` executable can be configured by commandline arguments or through an XML file.
File-based configuration has the advantage of enforcing certain rules across
test executions (e.g. excluding files from code coverage reports), and of course this
information can be version controlled and shared with other team members.

**Note: This doesn't apply for running tests through the "sake" wrapper**

SilverStripe comes with a default `phpunit.xml.dist` that you can use as a starting point.
Copy the file into a new `phpunit.xml` and customize to your needs - PHPUnit will auto-detect
its existence, and prioritize it over the default file.

There's nothing stopping you from creating multiple XML files (see the `--configuration` flag in [PHPUnit documentation](http://www.phpunit.de/manual/current/en/textui.html)).
For example, you could have a `phpunit-unit-tests.xml` and `phpunit-functional-tests.xml` file (see below).

## Glossary {#glossary}

**Assertion:** A predicate statement that must be true when a test runs.

**Behat:** A behaviour-driven testing library used with SilverStripe as a higher-level
alternative to the `FunctionalTest` API, see [http://behat.org](http://behat.org).

**Test Case:** The atomic class type in most unit test frameworks. New unit tests are created by inheriting from the
base test case.

**Test Suite:** Also known as a 'test group', a composite of test cases, used to collect individual unit tests into
packages, allowing all tests to be run at once.

**Fixture:** Usually refers to the runtime context of a unit test - the environment and data prerequisites that must be
in place in order to run the test and expect a particular outcome. Most unit test frameworks provide methods that can be
used to create fixtures for the duration of a test - `setUp` - and clean them up after the test is done - `tearDown'.

**Refactoring:** A behavior preserving transformation of code. If you change the code, while keeping the actual
functionality the same, it is refactoring. If you change the behavior or add new functionality it's not.

**Smell:** A code smell is a symptom of a problem. Usually refers to code that is structured in a way that will lead to
problems with maintenance or understanding.

**Spike:** A limited and throwaway sketch of code or experiment to get a feel for how long it will take to implement a
certain feature, or a possible direction for how that feature might work.

**Test Double:** Also known as a 'Substitute'. A general term for a dummy object that replaces a real object with the
same interface. Substituting objects is useful when a real object is difficult or impossible to incorporate into a unit
test.

**Fake Object**: A substitute object that simply replaces a real object with the same interface, and returns a
pre-determined (usually fixed) value from each method.

**Mock Object:** A substitute object that mimicks the same behavior as a real object (some people think of mocks as
"crash test dummy" objects). Mocks differ from other kinds of substitute objects in that they must understand the
context of each call to them, setting expectations of which, and what order, methods will be invoked and what parameters
will be passed.

**Test-Driven Development (TDD):** A style of programming where tests for a new feature are constructed before any code
is written. Code to implement the feature is then written with the aim of making the tests pass. Testing is used to
understand the problem space and discover suitable APIs for performing specific actions.

**Behavior Driven Development (BDD):** An extension of the test-driven programming style, where tests are used primarily
for describing the specification of how code should perform. In practice, there's little or no technical difference - it
all comes down to language. In BDD, the usual terminology is changed to reflect this change of focus, so *Specification*
is used in place of *Test Case*, and *should* is used in place of *expect* and *assert*.
