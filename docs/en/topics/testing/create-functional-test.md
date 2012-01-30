# Writing functional tests

Functional tests test your controllers.  The core of these are the same as unit tests:

*  Create a subclass of `[api:SapphireTest]` in the `mysite/tests` or `(module)/tests` folder.
*  Define static $fixture_file to point to a database YAML file.
*  Create methods that start with "test" to create your tests.
*  Assertions are used to work out if a test passed or failed.

The code of the tests is a little different.  Instead of examining the behaviour of objects, we example the results of
URLs.  Here is an example from the subsites module:

	:::php
	class SubsiteAdminTest extends SapphireTest {
		static $fixture_file = 'subsites/tests/SubsiteTest.yml';
	
		/**
		 * Return a session that has a user logged in as an administrator
		 */
		public function adminLoggedInSession() {
			return new Session(array(
				'loggedInAs' => $this->idFromFixture('Member', 'admin')
			));
		}
	
		/**
		 * Test generation of the view
		 */
		public function testBasicView() {
			// Open the admin area logged in as admin
			$response1 = Director::test('admin/subsites/', null, $this->adminLoggedInSession());
	
			// Confirm that this URL gets you the entire page, with the edit form loaded
			$response2 = Director::test('admin/subsites/show/1', null, $this->adminLoggedInSession());
			$this->assertTrue(strpos($response2->getBody(), 'id="Root_Configuration"') !== false);
			$this->assertTrue(strpos($response2->getBody(), '<head') !== false);
	
			// Confirm that this URL gets you just the form content, with the edit form loaded
			$response3 = Director::test('admin/subsites/show/1', array('ajax' => 1), $this->adminLoggedInSession());
	
			$this->assertTrue(strpos($response3->getBody(), 'id="Root_Configuration"') !== false);
			$this->assertTrue(strpos($response3->getBody(), '<form') === false);
			$this->assertTrue(strpos($response3->getBody(), '<head') === false);
		}
	


We are using a new static method here: **Director::test($url, $postVars, $sessionObj)**

Director::test() lets us execute a URL and see what happens.  It bypasses HTTP, instead relying on the cleanly
encapsulated execution model of `[api:Controller]`.

It takes 3 arguments:

*  $url: The URL to execute
*  $postVars: Post variables to pass to the URL
*  $sessionObj: A Session object representing the current session.

And it returns an `[api:HTTPResponse]` object, which will give you the response headers (including redirection), status code,
and body.

We can use string processing on the body of the response to then see if it fits with our expectations.

If you're testing for natural language responses like error messages, make sure to use [i18n](/topics/i18n) translations through
the *_t()* method to avoid tests failing when i18n is enabled.
