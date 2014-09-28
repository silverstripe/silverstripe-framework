summary: Send HTML and plain text email from your SilverStripe application.

# Email

Creating and sending email in SilverStripe is done through the [api:Email] and [api:Mailer] classes. This document 
covers how to create an `Email` instance, customize it with a HTML template, then send it through a custom `Mailer`.

## Configuration

Out of the box, SilverStripe will use the built-in PHP `mail()` command. If you are not running an SMTP server, you 
will need to either configure PHP's SMTP settings (see [PHP documentation](http://php.net/mail) to include your mail 
server configuration or use one of the third party SMTP services like [Mandrill](https://github.com/lekoala/silverstripe-mandrill) 
and [Postmark](https://github.com/fullscreeninteractive/silverstripe-postmarkmailer).

## Usage

### Sending plain text only

	:::php
	$email = new Email($from, $to, $subject, $body);
	$email->sendPlain();

### Sending combined HTML and plain text

By default, emails are sent in both HTML and Plaintext format. A plaintext representation is automatically generated 
from the system by stripping HTML markup, or transforming it where possible (e.g. `<strong>text</strong>` is converted 
to `*text*`).

	:::php
	$email = new Email($from, $to, $subject, $body);
	$email->send();

<div class="info" markdown="1">
The default HTML template for emails is named `GenericEmail` and is located in `framework/templates/email/`. To 
customize this template, copy it to the `mysite/templates/Email/` folder or use `setTemplate` when you create the 
`Email` instance.
</div>


### Templates

HTML emails can use custom templates using the same template language as your website template. You can also pass the
email object additional information using the `populateTemplate` method. 

**mysite/templates/Email/MyCustomEmail.ss**

	:::ss
	<h1>Hi $Member.FirstName</h1>
	<p>You can go to $Link.</p>

The PHP Logic..

	:::php
	$email = new Email();
	$email
		->setFrom($from)
		->setTo($to)
		->setSubject($subject)
		->setTemplate('MyCustomEmail')
		->populateTemplate(new ArrayData(array(
			'Member' => Member::currentUser(),
			'Link' => $link
		)));

	$email->send();

<div class="alert" markdown="1">
As we've added a new template file (`MyCustomEmail`) make sure you clear the SilverStripe cache for your changes to
take affect.
</div>

## Sub classing

To keep your application code clean and your internal API clear, a better approach to generating an email is to create 
a new subclass of `Email` which takes the required dependencies and handles setting the properties itself.

**mysite/code/MyCustomEmail.php**

	:::php
	<?php

	class MyEmail extends Email {
		
		protected $ss_template = "MyEmail";

		public function __construct($member) {
			$from = 'no-reply@mysite.com';
			$to = $member->Email;
			$subject = "Welcome to our site.";
			$link = Director::absoluteBaseUrl();

			parent::__construct($from, $to, $subject);

			$this->populateTemplate(new ArrayData(array(
				'Member' => $member->Email,
				'Link' => $link
			)));
		}
	}

Then within your application, usage of the email is much clearer to follow.

	:::php
	<?php
	$member = Member::currentUser();

	$email = new MyEmail($member);
	$email->send();


## Administrator Emails

You can set the default sender address of emails through the `Email.admin_email` [configuration setting](/developer_guides/configuration).

**mysite/_config/app.yml**

	:::yaml
	Email:
	  admin_email: support@silverstripe.org
  

<div class="alert" markdown="1">
Remember, setting a `from` address that doesn't come from your domain (such as the users email) will likely see your
email marked as spam. If you want to send from another address think about using the `replyTo` method.
</div>

## Redirecting Emails

There are several other [configuration settings](/developer_guides/configuration) to manipulate the email server.

*  `Email.send_all_emails_to` will redirect all emails sent to the given address. This is useful for testing and staging
servers where you do not wish to send emails out.
*  `Email.cc_all_emails_to` and `Email.bcc_all_emails_to` will add an additional recipient in the BCC / CC header. 
These are good for monitoring system-generated correspondence on the live systems.

Configuration of those properties looks like the following:

**mysite/_config.php**

	:::php
	if(Director::isLive()) {
		Config::inst()->update('Email', 'bcc_all_emails_to', "client@example.com");
	} else {
		Config::inst()->update('Email', 'send_all_emails_to', "developer@example.com");
	}

### Setting custom replyTo

	:::php
	$email = new Email(..);
	$email->replyTo('me@address.com');

### Setting Custom Headers

For email headers which do not have getters or setters (like setTo(), setFrom()) you can use **addCustomHeader($header,
$value)**

	:::php
	$email = new Email(...);
	$email->addCustomHeader('HeaderName', 'HeaderValue');
	..

<div class="info" markdown="1">
See this [Wikipedia](http://en.wikipedia.org/wiki/E-mail#Message_header) entry for a list of header names.
</div>

## Newsletters

The [newsletter module](http://silverstripe.org/newsletter-module) provides a UI and logic to send batch emails.

## Custom Mailers

SilverStripe supports changing out the underlying web server SMTP mailer service through the `Email::set_mailer()` 
function. A `Mailer` subclass will commonly override the `sendPlain` and `sendHTML` methods to send emails through curl
or some other process that isn't the built in `mail()` command. 

<div class="info" markdown="1">
There are a number of custom mailer add-ons available like [Mandrill](https://github.com/lekoala/silverstripe-mandrill)
and [Postmark](https://github.com/fullscreeninteractive/silverstripe-postmarkmailer).
</div>

In this example, `LocalMailer` will take any email's going while the site is in Development mode and save it to the 
assets folder instead.

**mysite/code/LocalMailer.php**

	:::php
	<?php

	class LocalMailer extends Mailer {

		function sendHTML($to, $from, $subject, $htmlContent, $attachedFiles = false, $customheaders = false, $plainContent = false, $inlineImages = false) {
			$file = ASSETS_PATH . '/_mail_'. urlencode(sprintf("%s_%s", $subject, $to));

			file_put_contents($file, $htmlContent);
		}


		function sendPlain($to, $from, $subject, $htmlContent, $attachedFiles = false, $customheaders = false, $plainContent = false, $inlineImages = false) {
			$file = ASSETS_PATH . '/_mail_'. urlencode(sprintf("%s_%s", $subject, $to));

			file_put_contents($file, $htmlContent);
		}
	}

**mysite/_config.php**
	
	:::php
	if(Director::isLive()) {
		Email::set_mailer(new PostmarkMailer());
	} else {
		Email::set_mailer(new LocalMailer());
	}

## API Documentation

* [api:Email]
