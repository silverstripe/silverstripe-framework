# Installing and Configuring PHPUnit

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
