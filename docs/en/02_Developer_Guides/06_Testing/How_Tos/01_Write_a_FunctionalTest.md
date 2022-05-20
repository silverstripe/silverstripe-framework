---
title: How to write a FunctionalTest
summary: Expand your testing capabilities with integrations tests
---

# How to Write a FunctionalTest

[FunctionalTest](api:SilverStripe\Dev\FunctionalTest) test your applications `Controller` instances and anything else which requires a web request. The 
core of these tests are the same as `SapphireTest` unit tests but add several methods for creating [HTTPRequest](api:SilverStripe\Control\HTTPRequest)
and receiving [HTTPResponse](api:SilverStripe\Control\HTTPResponse) objects. In this How To, we'll see how to write a test to query a page, check the
response and modify the session within a test.

**app/tests/HomePageTest.php**


```php
use SilverStripe\Security\Member;

class HomePageTest extends FunctionalTest 
{

    /**
     * Test generation of the view
     */
    public function testViewHomePage() 
    {
        $page = $this->get('home/');

        // Home page should load..
        $this->assertEquals(200, $page->getStatusCode());

        // We should see a login form
        $login = $this->submitForm("LoginFormID", null, [
            'Email' => 'test@example.com',
            'Password' => 'wrongpassword'
        ]);

        // wrong details, should now see an error message
        $this->assertExactHTMLMatchBySelector("#LoginForm p.error", [
            "That email address is invalid."
        ]);

        // If we login as a user we should see a welcome message
        $me = Member::get()->first();

        $this->logInAs($me);
        $page = $this->get('home/');

        $this->assertExactHTMLMatchBySelector("#Welcome", [
            'Welcome back'
        ]);
    }
}
```

## Related Documentation

* [Functional Testing](../functional_testing)
* [Unit Testing](../unit_testing)

## API Documentation

* [FunctionalTest](api:SilverStripe\Dev\FunctionalTest)
