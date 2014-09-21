summary: Send HTML and plain text email from your SilverStripe application.

# Email

SilverStripe has emailing functionality using the built-in mail() function in PHP.
Features include sending plaintext- and HTML emails, sending bulk emails, subscription, handling bounced back emails.

## Configuration

Your PHP configuration needs to include the SMTP module for sending emails.
If you are not running an SMTP server together with your webserver, you might need to setup PHP with the credentials for
an external SMTP server (see [PHP documentation for mail()](http://php.net/mail)).

## Usage

### Sending combined HTML and Plaintext

By default, emails are sent in both HTML and Plaintext format.
A plaintext representation is automatically generated from the system
by stripping HTML markup, or transforming it where possible
(e.g. `<strong>text</strong>` is converted to `*text*`).

	:::php
	$email = new Email($from, $to, $subject, $body);
	$email->send();


The default HTML template is located in `framework/templates/email/GenericEmail.ss`.

### Sending Plaintext only

	:::php
	$email = new Email($from, $to, $subject, $body);
	$email->sendPlain();

### Templates

*  Create a SS-template file called, in this example we will use 'MyEmail.ss' inside `mysite/templates/email`.
*  Fill this out with the body text for your email. You can use any [SS-template syntax](/topics/templates) (e.g. `<% loop %>`,
`<% if %>`, $FirstName etc)
*  Choose your template with **setTemplate()**
*  Populate any custom data into the template before sending with **populateTemplate()**

Example:

	:::php
	$email = new Email($from, $to, $subject, $body);
	$email->setTemplate('MyEmail');

	// You can call this multiple times or bundle everything into an array, including DataSetObjects
	$email->populateTemplate(Member::currentUser());

	$welcomeMsg = 'Thank you for joining on '.date('Y-m-d'.'!');

	$email->populateTemplate(array(
		'WelcomeMessage' => $welcomeMsg, // Accessible in template via $WelcomeMessage
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
	<?php
	$email = new MyEmail();
	$email->populateTemplate(Member::currentUser()); // This will populate the template, $to, $from etc variables if they exist
	$email->send(); // Will immediately send an HTML email with appropriate plain-text content


### Administrator Emails

You can influence the default sender address of emails through the `Email.admin_email`
[configuration setting](/topics/configuration). This address is used if the `from` field is empty.

### Redirecting Emails

Further [configuration settings](/topics/configuration) relating to email rewriting:

*  `Email.send_all_emails_to` will redirect all emails sent to the given address.  Handy for testing!
*  `Email.cc_all_emails_to` and `Email.bcc_all_emails_to` will keep the email going to its original recipients, but
add an additional recipient in the BCC/CC header.  Good for monitoring system-generated correspondence on the live
systems.

	:::php
	if(Director::isLive()) Config::inst()->update('Email', 'bcc_all_emails_to', "client@example.com");
	else Config::inst()->update('Email', 'send_all_emails_to', "developer@example.com");


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
