# Troubleshooting

Part of the [SilverStripe Testing Guide](testing-guide).

## I can't run my new test class

If you've just added a test class, but you can't see it via the web interface, chances are, you haven't flushed your
manifest cache - append `?flush=1` to the end of your URL querystring.

## Class 'PHPUnit_Framework_MockObject_Generator' not found

This is due to an upgrade in PHPUnit 3.5 which PEAR doesn't handle correctly.<br>
It can be fixed by running the following commands:

	pear install -f phpunit/DbUnit
	pear install -f phpunit/PHPUnit_MockObject
	pear install -f phpunit/PHPUnit_Selenium

## My tests fail seemingly random when comparing database IDs

When defining fixtures in the YML format, you only assign aliases
for them, not direct database IDs. Even if you insert only one record
on a clean database, it is not guaranteed to produce ID=1 on every run.
So to make your tests more robust, use the aliases rather than hardcoded IDs.

Also, some databases don't return records in a consistent sort order
unless you explicitly tell them to. If you don't want to test sort order
but rather just the returned collection, 

	:::php
	$myPage = $this->objFromFixture('Page', 'mypage');
	$myOtherPage = $this->objFromFixture('Page', 'myotherpage');
	$pages = Page::get();
	// Bad: Assumptions about IDs and their order
	$this->assertEquals(array(1,2), $pages->column('ID'));
	// Good: Uses actually created IDs, independent of their order
	$this->assertContains($myPage->ID, $pages->column('ID'));
	$this->assertContains($myOtherPage->ID, $pages->column('ID'));

## My fixtures are getting complicated, how do I inspect their database state?

Fixtures are great because they're easy to define through YML,
but sometimes can be a bit of a blackbox when it comes to the actual
database state they create. These are temporary databases, which are 
destructed directly after the test run - which is intentional,
but not very helpful if you want to verify that your fixtures have been created correctly.

SilverStripe comes with a URL action called `dev/tests/startsession`.
When called through a web browser, it prompts for a fixture file
which it creates a new database for, and sets it as the current database
in this browser session until you call `dev/tests/endsession`.

For more advanced users, you can also have a look in the `[api:YamlFixture]`
class to see what's going on behind the scenes.

## My database server is cluttered with `tmpdb...` databases

This is a common problem due to aborted test runs,
which don't clean up after themselves correctly
(mostly because of a fatal PHP error in the tests).
The easiest way to get rid of them is a call to `dev/tests/cleanupdb`.