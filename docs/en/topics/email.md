# Email

SilverStripe has emailing functionality using the built-in mail() function in PHP.
Features include sending plaintext- and HTML emails, sending bulk emails, 
subscription, handling bounced back emails.

## Configuration

Your PHP configuration needs to include the SMTP module for sending emails.
If you are not running an SMTP server together with your webserver, you might 
need to setup PHP with the credentials for an external SMTP server 
(see [PHP documentation for mail()](http://php.net/mail)).

## Usage

### Sending combined HTML and Plaintext

By default, emails are sent in both HTML and Plaintext format.

A plaintext representation is automatically generated from the system by stripping 
HTML markup, or transforming it where possible (e.g. `<strong>text</strong>` is 
converted to `*text*`).

	:::php
	$email = new Email($from, $to, $subject, $body);
	$email->send();


The default HTML template is located in `sapphire/templates/email/GenericEmail.ss`.

### Sending Plaintext only

	:::php
	$email = new Email($from, $to, $subject, $body);
	$email->sendPlain();

### Custom Template

The emails you create may contain quite a bit of content and perhaps custom elements, 
background images and generally be too complex to write out in PHP code. 

To support this you can specify a [SilverStripe Template](/topics/templates) to be used
instead of the built in GenericEmail.ss file. 

* Create a 'MyEmailTemplate.ss' file inside `mysite/templates/email`.
* Fill MyEmailTemplate.ss with what you need to include. You can use any of the [SilverStripe Template syntax](/topics/templates) 
 (e.g. `<% control %>`, `<% if %>`, $FirstName etc). Such as the following:

	:::ss
	<h1>Hi <% if FirstName %>$FirstName<% end_if %></h1>
	<p>$WelcomeMessage</p>
	
The above is a simple hello email that prints out the first name of the user and
a welcome message. We're not finished quite yet, there is a couple things we need
to do:

*  Before you call `send()` on your email, set the template with `setTemplate()`
*  Populate any custom data into the template with `populateTemplate()` again,
you need to do this before you call `send()`. In this example above, we used 2 
fields - `$FirstName` and `$WelcomeMessage` so we need to ensure they are populated:

	:::php
	$email = new Email("from@example.com", "to@example.com", "Welcome to mysite");
	
	// sets the template to be used - MyEmail.ss.
	$email->setTemplate('MyEmailTemplate');
	
	// You can call populateTemplate multiple times or bundle everything into an array, 
	// including DataSetObjects.
	
	// Member::currentUser() includes $FirstName
	$email->populateTemplate(Member::currentUser());
	
	$welcomeMsg = 'Thank you for joining on '.date('Y-m-d').'!';
	
	$email->populateTemplate(array(
		'WelcomeMessage' => $welcomeMsg,
	));
	
	$email->send();



### Subclassing

Class definition:

	:::php
	<?php
	class MyEmail extends Email{
	  protected
	    $to = '$Email', // Be sure to encase this in single-quotes, as it is evaluated later by the template parser
	    $from = 'email@email.com',
	    $ss_template = 'MyEmail';
	}

Usage:

	:::php
	$email = new MyEmail();
	$email->send();


### Administrator Emails

The static function `Email::setAdminEmail()` can be called from a `_config.php` 
file to set the address that these emails should originate from. This address 
is used if the `from` field is empty.

### Redirecting Emails

`Email::send_all_emails_to($address)` will redirect all emails sent to the given 
address. We recommend using this in your local development environment. You can 
either configure this in your mysite/_config.php:

	:::php
	if(Director::isDev()) Email::send_all_emails_to("me@example.com");
	
If you use a [SilverStripe Environment](environment-management) file to manage your
configuration then you should include the following in your development copy:

	:::php
	define('SS_SEND_ALL_EMAILS_TO', 'me@example.com');
	
`Email::cc_all_emails_to()` and `Email::bcc_all_emails_to()` will keep the email 
going to  its original recipients, but adds an additional recipient in the BCC/CC 
header. 

	:::php
	if(Director::isLive()) Email::bcc_all_emails_to("client@example.com");
	else Email::send_all_emails_to("developer@example.com"); 


### Setting Custom Headers

For email headers which do not have getters or setters (like setTo(), setFrom()) you can use **addCustomHeader($header,
$value)**

	:::php
	$email = new Email(...);
	$email->addCustomHeader('HeaderName', 'HeaderValue');
	..


See [Wikipedia E-mail Message header](http://en.wikipedia.org/wiki/E-mail#Message_header) for a list of header names.

### Newsletters

The [newsletter module](http://silverstripe.org/newsletter-module) provides a UI and logic to send batch emails.

## API Documentation

`[api:Email]`