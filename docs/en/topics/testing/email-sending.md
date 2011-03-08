# Email Sending

SilverStripe's test system has built-in support for testing emails sent using the Email class.

## How it works

For this to work, you need to send emails using the `Email` class, which is generally the way that we recommend you
send emails in your SilverStripe application.  Here is a simple example of how you might do this:

	:::php
	$e = new Email();
	$e->To = "someone@example.com";
	$e->Subject = "Hi there";
	$e->Body = "I just really wanted to email you and say hi.";
	$e->send();


Normally, the send() method would send an email using PHP's mail() function.  However, if you are running a `[api:SapphireTest]`
test, then it holds off actually sending the email, and instead lets you assert that an email was sent using this method.

	:::php
	$this->assertEmailSent("someone@example.com", null, "/th.*e$/");


The arguments are `$to`, `$from`, `$subject`, `$body`, and can be take one of the following three forms:

*  A string: match exactly that string
*  `null/false`: match anything
*  A PERL regular expression (starting with '/'): match that regular expression

## How to use it

Given all of that, there is not a lot that you have to do in order to test emailing functionality in your application.

*  Write your SilverStripe application, using the Email class to send emails.
*  Write tests that trigger the email sending functionality.
*  Include appropriate `$this->assertEmailSent()` calls in those tests.

That's it!

## What isn't tested

It's important to realise that this email testing doesn't actually test everything that there is to do with email.  The
focus of this email testing system is testing that your application is triggering emails correctly.  It doesn't test
your email infrastructure outside of the webserver.  For example:

*  It won't test that email is correctly configured on your webserver
*  It won't test whether your emails are going to be lost in someone's spam filter
*  It won't test bounce-handling or any other auxiliary services of email

## How it's built

For those of you who want to dig a little deeper, here's a quick run-through of how the system has been built.  As well
as explaining how we built the email test, this is a good design pattern for making other "tricky external systems"
testable:

1.  The `Email::send()` method makes uses of a static object, `Email::$mailer`, to do the dirty work of calling
mail().  The default mailer is an object of type `Mailer`, which performs a normal send.
2.  `Email::set_mailer()` can be called to load in a new mailer object.
3.  `SapphireTest::setUp()` method calls `Email::set_mailer(new TestMailer())` to replace the default mailer with a `TestMailer` object.  This replacement mailer doesn't actually do anything when it is asked to send an email; it just
records the details of the email in an internal field that can be searched with `TestMailer::findEmails()`.
4.  `SapphireTest::assertEmailSent()` calls `TestMailer::findEmails()` to see if a mail-send was requested by the
application.

