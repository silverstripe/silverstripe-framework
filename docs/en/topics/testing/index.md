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

The framework has a required dependency on [PHPUnit](http://www.phpunit.de/) and an optional dependency on
[SimpleTest](http://simpletest.org/), the two premiere PHP testing frameworks.

To run SilverStripe tests, you'll need to be able to access PHPUnit on your include path. First, you'll need to make sure
that you have the PEAR command line client installed. To test this out, type `pear help` at your prompt. You should
see a bunch of generic PEAR info. If it's not installed, you'll need to set it up first (see: [Getting Started with
PEAR](http://www.sitepoint.com/article/getting-started-with-pear/)) or else manually install PHPUnit (see: [Installation
instructions](http://www.phpunit.de/pocket_guide/3.3/en/installation.html)).

The PHPUnit installation via PEAR is very straightforward.
You might have to perform the following commands as root or super user (sudo).

<del>We need a specific version of PHPUnit (3.3.x), as 3.4 or higher breaks our test runner (see [#4573](http://open.silverstripe.com/ticket/4573))</del>

At your prompt, type the following commands:

	pear channel-discover pear.phpunit.de 
	pear channel-discover pear.symfony-project.com
	pear install phpunit/PHPUnit

## Running Tests

### Via Web Browser

Go to the main test URL which will give you options for running various available test suites or individual tests on
their own:

	 http://localhost/dev/tests

### Via Command Line

`cd` to the root level of your project and run [sake](/topics/commandline) (SilverStripe Make) to execute the tests:

	/path/to/project$ sake dev/tests/all


### Partial Test Runs


Run specific tests:

	dev/tests/MyTest,MyOtherTest


Run all tests in a module folder, e.g. "framework"

	dev/tests/module/<modulename>


Skip certain tests

	dev/tests/all SkipTests=MySkippedTest


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

## How To

Tutorials and recipes for creating tests using the SilverStripe framework:

*  **[Create a SilverStripe Test](/topics/testing/create-silverstripe-test)**
*  **[Create a Functional Test](/topics/testing/create-functional-test)**
*  **[Test Outgoing Email Sending](/topics/testing/email-sending)**

## Glossary {#glossary}

**Assertion:** A predicate statement that must be true when a test runs.

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