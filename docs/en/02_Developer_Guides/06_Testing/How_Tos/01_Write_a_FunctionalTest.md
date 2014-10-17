title: How to write a FunctionalTest

# How to Write a FunctionalTest

[api:FunctionalTest] test your applications `Controller` instances and anything else which requires a web request. The 
core of these tests are the same as `SapphireTest` unit tests but add several methods for creating [api:SS_HTTPRequest]
and receiving [api:SS_HTTPResponse] objects. In this How To, we'll see how to write a test to query a page, check the
response and modify the session within a test.

**mysite/tests/HomePageTest.php**

	:::php
	<?php

	class HomePageTest extends SapphireTest {

		/**
		 * Test generation of the view
		 */
		public function testViewHomePage() {
			$page = $this->get('home/');

			// Home page should load..
			$this->assertEquals(200, $page->getStatusCode());

			// We should see a login form
			$login = $this->submitForm("#LoginForm", null, array(
				'Email' => 'test@test.com',
				'Password' => 'wrongpassword'
			));

			// wrong details, should now see an error message
			$this->assertExactHTMLMatchBySelector("#LoginForm p.error", array(
				"That email address is invalid."
			));

			// If we login as a user we should see a welcome message
			$me = Member::get()->first();

			$this->logInAs($me);
			$page = $this->get('home/');

			$this->assertExactHTMLMatchBySelector("#Welcome", array(
				'Welcome Back'
			));
		}
	}

## Related Documentation

* [Functional Testing](../functional_testing)
* [Unit Testing](../unit_testing)

## API Documentation

* [api:FunctionalTest]
