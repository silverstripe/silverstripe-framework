title: How to test emails within unit tests

# Testing Email within Unit Tests

SilverStripe's test system has built-in support for testing emails sent using the `[api:Email]` class. If you are 
running a `[api:SapphireTest]` test, then it holds off actually sending the email, and instead lets you assert that an 
email was sent using this method.

	:::php
	public function MyMethod() {
		$e = new Email();
		$e->To = "someone@example.com";
		$e->Subject = "Hi there";
		$e->Body = "I just really wanted to email you and say hi.";
		$e->send();
	}

To test that `MyMethod` sends the correct email, use the [api:Email::assertEmailSent] method.

	:::php
	$this->assertEmailSend($to, $from, $subject, $body);

	// to assert that the email is sent to the correct person
	$this->assertEmailSent("someone@example.com", null, "/th.*e$/");


Each of the arguments (`$to`, `$from`, `$subject` and `$body`) can be either one of the following.

* A string: match exactly that string
* `null/false`: match anything
* A PERL regular expression (starting with '/')

## Related Documentation

* [Email](../../email)

## API Documentation

* [api:Email]

