# Configure PHPUnit for your project

This guide helps you to run [PHPUnit](http://phpunit.de) tests in your SilverStripe project.
See "[Testing](/topics/testing)" for an overview on how to create unit tests.

## Coverage reports

PHPUnit can generate code coverage reports for you ([docs](http://www.phpunit.de/manual/current/en/code-coverage-analysis.html)):

 * `phpunit --coverage-html assets/coverage-report`: Generate coverage report for the whole project
 * `phpunit --coverage-html assets/coverage-report mysite/tests/`: Generate coverage report for the "mysite" module

Typically, only your own custom PHP code in your project should be regarded when 
producing these reports. Here's how you would exclude some `thirdparty/` directories:

	<filter>
		<blacklist>
			<directory suffix=".php">framework/dev/</directory>
			<directory suffix=".php">framework/thirdparty/</directory>
			<directory suffix=".php">cms/thirdparty/</directory>
			
			<!-- Add your custom rules here -->
			<directory suffix=".php">mysite/thirdparty/</directory>
		</blacklist>
	</filter>

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
		if(isset($_GET['db']) && ($db = $_GET['db'])) {
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
