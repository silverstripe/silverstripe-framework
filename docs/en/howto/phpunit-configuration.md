# Configure PHPUnit for your project

This guide helps you to run [PHPUnit](http://phpunit.de) tests in your SilverStripe project.
See "[Testing](/topics/testing)" for an overview on how to create unit tests.

## Should I execute through "sake dev/tests" or "phpunit"?

Short answer: Both are valid ways.

The `sake` executable that comes with SilverStripe can trigger a customized
"[api:TestRunner]" class that handles the PHPUnit configuration and output formatting.
It's tyically invoked to run all tests through `sake dev/tests/all`,
a single test with `sake dev/tests/MyTestClass`, or tests for a module with `sake dev/tests/module/mymodulename`.
While the custom test runner a handy tool, its also more limited than using `phpunit` directly,
particularly around formatting test output.

The `phpunit` executable uses a SilverStripe bootstrapper to autoload classes, 
but handles its own test class retrieval, output formatting and other configuration. 
It can format output in common structured formats used by "continuous integration" servers.
If you're using [phpUnderControl](http://phpundercontrol.org/) or a similar tool,
you will most likely need the `--log-junit` and `--coverage-xml` flags that are not available through `sake`.

All command-line arguments are documented on [phpunit.de](http://www.phpunit.de/manual/current/en/textui.html).

## Usage of "phpunit" executable

 * `phpunit`: Runs all tests in all folders
 * `phpunit framework/tests/`: Run all tests of the framework module
 * `phpunit framework/tests/filesystem`: Run all filesystem tests within the framework module
 * `phpunit framework/tests/filesystem/FolderTest.php`: Run a single test
 * `phpunit framework/tests '' flush=all`: Run tests with optional `$_GET` parameters (you need an empty second argument)

## Coverage reports

 * `phpunit --coverage-html assets/coverage-report`: Generate coverage report for the whole project
 * `phpunit --coverage-html assets/coverage-report mysite/tests/`: Generate coverage report for the "mysite" module

## Customizing phpunit.xml.dist

The `phpunit` executable can be configured by commandline arguments or through an XML file.
File-based configuration has the advantage of enforcing certain rules across
test executions (e.g. excluding files from code coverage reports), and of course this
information can be version controlled and shared with other team members.

SilverStripe comes with a default `phpunit.xml.dist` that you can use as a starting point.
Copy the file into a new `phpunit.xml` and customize to your needs - PHPUnit will auto-detect
its existence, and prioritize it over the default file.

There's nothing stopping you from creating multiple XML files (see the `--configuration` flag in [PHPUnit documentation](http://www.phpunit.de/manual/current/en/textui.html)).
For example, you could have a `phpunit-unit-tests.xml` and `phpunit-functional-tests.xml` file (see below).

## Running unit and functional tests separately

You can use the filesystem structure of your unit tests to split
different aspects. In the simplest form, you can limit your test exeuction
to a specific directory by passing in a directory argument (`phpunit mymodule/tests`).
To specify multiple directories, you have to use the XML configuration file.
This can be useful to only run certain parts of your project
on continous integration, or produce coverage reports separately
for unit and functional tests.

Example `phpunit-unittests-only.xml`:

	<phpunit bootstrap="framework/tests/bootstrap.php" colors="true">
		<testsuites>
			<testsuite>
				<directory>mysite/tests/unit</directory>
				<directory>othermodule/tests/unit</directory>
				<!-- ... -->
			</testsuite>
		</testsuites>
		<!-- ... -->
	</phpunit>

You can run with this XML configuration simply by invoking `phpunit --configuration phpunit-unittests-only.xml`.

The same effect can be achieved with the `--group` argument and some PHPDoc (see [phpunit.de](http://www.phpunit.de/manual/current/en/appendixes.configuration.html#appendixes.configuration.groups)).

## Adding/removing files for code coverage reports

Not all PHP code in your project should be regarded when producing [code coverage reports](http://www.phpunit.de/manual/current/en/code-coverage-analysis.html).
This applies for all thirdparty code

	<filter>
		<blacklist>
			<directory suffix=".php">framework/dev/</directory>
			<directory suffix=".php">framework/thirdparty/</directory>
			<directory suffix=".php">cms/thirdparty/</directory>
			
			<!-- Add your custom rules here -->
			<directory suffix=".php">mysite/thirdparty/</directory>
		</blacklist>
	</filter>

See [phpunit.de](http://www.phpunit.de/manual/current/en/appendixes.configuration.html#appendixes.configuration.blacklist-whitelist) for more information.

## Speeding up your test execution with the SQLite3 module

Test execution can easily take a couple of minutes for a full run,
particularly if you have a lot of database write operations.
This is a problem when you're trying to to "[Test Driven Development](http://en.wikipedia.org/wiki/Test-driven_development)".

To speed things up a bit, you can simply use a faster database just for executing tests.
The SilverStripe database layer makes this relatively easy, most likely
you won't need to adjust any project code or alter SQL statements.

The [SQLite3 module](http://www.silverstripe.org/sqlite-database/) provides an interface
to a very fast database that requires minimal setup and is fully file-based.
It should give you up to 4x speed improvements over running tests in MySQL or other
more "heavy duty" relational databases.

Example `mysite/_config.php`:

	// Customized configuration for running with different database settings.
	// Ensure this code comes after ConfigureFromEnv.php
	if(Director::isDev()) {
		if($db = @$_GET['db']) {
			global $databaseConfig;
			if($db == 'sqlite3') $databaseConfig['type'] = 'SQLite3Database';
		}
	}
	
You can either use the database on a single invocation:

	phpunit framework/tests "" db=sqlite3
	
or through a `<php>` flag in your `phpunit.xml` (see [Appenix C: "Setting PHP INI settings"](http://www.phpunit.de/manual/current/en/appendixes.configuration.html)):

	<phpunit>
		<!-- ... -->
		<php>
			<var name="db" value="sqlite3"/>
		</php>
	</phpunit>

<div class="hint" markdown="1">
It is recommended that you still run your tests with the original database driver (at least on continuous integration)
to ensure a realistic test scenario.
</div>
