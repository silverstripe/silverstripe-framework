---
title: Email
summary: Send HTML and plain text email from your Silverstripe CMS application.
icon: envelope-open
---

# Email

Creating and sending email in Silverstripe CMS is done through the [Email](api:SilverStripe\Control\Email\Email) and [Mailer](api:SilverStripe\Control\Email\Mailer) classes. This document covers how to create an `Email` instance, customise it with a HTML template, then send it through a custom `Mailer`.

## Configuration

Silverstripe CMS provides an API over the top of the [SwiftMailer](http://swiftmailer.org/) PHP library which comes with an extensive list of "transports" for sending mail via different services. 

For legacy reasons, Silverstripe CMS 4 defaults to using the built-in PHP `mail()` command via a deprecated class `Swift_MailTransport`. However, using this layer is less secure and is strongly discouraged.

It's highly recommended you upgrade to a more robust transport for additional security. The Sendmail transport is the most common one. The `sendmail` binary is widely available across most Linux/Unix servers.

You can use any of the Transport classes provided natively by SwiftMailer. There are also countless PHP libraries offering custom Transports to integrate with third party mailing service:
- read the [SwiftMailer Transport Types documentation](https://swiftmailer.symfony.com/docs/sending.html#transport-types) for a full list of native Transport
- search [Packagist for SwiftMailer Transport](https://packagist.org/?query=SwiftMailer+Transport) to discover additional third party integrations

To swap out the transport used by the `Mailer`, create a file `app/_config/email.yml`

To use a `sendmail` binary:

```yml
---
Name: myemailconfig
After:
  - '#emailconfig'
---
SilverStripe\Core\Injector\Injector:
  Swift_Transport:
    class: Swift_SendmailTransport
```

To use SMTP:

```yml
---
Name: myemailconfig
After:
  - '#emailconfig'
---
SilverStripe\Core\Injector\Injector:
  Swift_Transport:
    class: Swift_SmtpTransport
    properties:
      Host: smtp.host.com
      Port: <port>
      Encryption: tls
    calls:
      Username: [ setUsername, ['`APP_SMTP_USERNAME`'] ]
      Password: [ setPassword, ['`APP_SMTP_PASSWORD`'] ]
      AuthMode: [ setAuthMode, ['login'] ]
```

Note the usage of backticks to designate environment variables for the credentials - ensure you set these in your `.env` file or in your webserver configuration.

### Mailer Configuration for dev environments

You may wish to use a different mailer configuration in your development environment. This can be used to suppress outgoing messages or to capture them for debugging purposes in a service like [MailCatcher](https://mailcatcher.me/).

You can suppress all emails by using the [`Swift_Transport_NullTransport`](https://github.com/swiftmailer/swiftmailer/blob/master/lib/classes/Swift/Transport/NullTransport.php).

```yml
---
Name: mydevemailconfig
After:
  - '#emailconfig'
Only:
  environment: dev
---
SilverStripe\Core\Injector\Injector:
  Swift_Transport:
    class: Swift_Transport_NullTransport
```

If you're using MailCatcher, or a similar tool, you can tell `Swift_SendmailTransport` to use a different binary.

```yml
---
Name: mydevemailconfig
After:
  - '#emailconfig'
Only:
  environment: dev
---
SilverStripe\Core\Injector\Injector:
  Swift_Transport:
    class: Swift_SendmailTransport
    constructor:
      0: '/usr/bin/env catchmail -t'
```

### Testing that email works

You _must_ ensure emails are being sent from your _production_ environment. You can do this by testing that the
***Lost password*** form available at `/Security/lostpassword` sends an email to your inbox, or with the following code snippet that can be run via a `SilverStripe\Dev\BuildTask`:

```php
$email = new Email('no-reply@mydomain.com', 'myuser@gmail.com', 'My test subject', 'My email body text');
$email->send();
```

Using the code snippet above also tests that the ability to set the "from" address is working correctly.

## Usage

### Sending plain text only


```php
use SilverStripe\Control\Email\Email;

$email = new Email($from, $to, $subject, $body);
$email->sendPlain();
```

### Sending combined HTML and plain text

By default, emails are sent in both HTML and Plaintext format. A plaintext representation is automatically generated 
from the system by stripping HTML markup, or transforming it where possible (e.g. `<strong>text</strong>` is converted 
to `*text*`).


```php
$email = new Email($from, $to, $subject, $body);
$email->send();
```

[info]
The default HTML template for emails is named `GenericEmail` and is located in `vendor/silverstripe/framework/templates/SilverStripe/Email/`.
To customise this template, copy it to the `app/templates/Email/` folder or use `setHTMLTemplate` when you create the 
`Email` instance.
[/info]


### Templates

HTML emails can use custom templates using the same template language as your website template. You can also pass the
email object additional information using the `setData` and `addData` methods. 

**app/templates/Email/MyCustomEmail.ss**


```ss
<h1>Hi $Member.FirstName</h1>
<p>You can go to $Link.</p>
```

The PHP Logic..

```php
$email = SilverStripe\Control\Email\Email::create()
    ->setHTMLTemplate('Email\\MyCustomEmail') 
    ->setData([
        'Member' => Security::getCurrentUser(),
        'Link'=> $link,
    ])
    ->setFrom($from)
    ->setTo($to)
    ->setSubject($subject);

if ($email->send()) {
    //email sent successfully
} else {
    // there may have been 1 or more failures
}

```

[alert]
As we've added a new template file (`MyCustomEmail`) make sure you clear the Silverstripe CMS cache for your changes to
take affect.
[/alert]

#### Custom plain templates

By default Silverstripe CMS will generate a plain text representation of the email from the HTML body. However if you'd like
to specify your own own plaintext version/template you can use `$email->setPlainTemplate()` to render a custom view of
the plain email:

```php
$email = new SilverStripe\Control\Email\Email();
$email->setPlainTemplate('MyPlanTemplate');
$this->send();
```

## Administrator Emails

You can set the default sender address of emails through the `Email.admin_email` [configuration setting](/developer_guides/configuration).

**app/_config/app.yml**


```yaml
SilverStripe\Control\Email\Email:
  admin_email: support@example.com
```

To add a display name, set `admin_email` as follow.

```yaml
SilverStripe\Control\Email\Email:
  admin_email:
    support@example.com: 'Support team'
```

[alert]
Remember, setting a `from` address that doesn't come from your domain (such as the users email) will likely see your
email marked as spam. If you want to send from another address think about using the `setReplyTo` method.
[/alert]

You will also have to remove the `SS_SEND_ALL_EMAILS_FROM` environment variable if it is present.

## Redirecting Emails

There are several other [configuration settings](/developer_guides/configuration) to manipulate the email server.

*  `SilverStripe\Control\Email\Email.send_all_emails_to` will redirect all emails sent to the given address.
All recipients will be removed (including CC and BCC addresses). This is useful for testing and staging servers where 
you do not wish to send emails out. For debugging the original addresses are added as `X-Original-*` headers on the email.
*  `SilverStripe\Control\Email\Email.cc_all_emails_to` and `SilverStripe\Control\Email\Email.bcc_all_emails_to` will add
an additional recipient in the BCC / CC header. These are good for monitoring system-generated correspondence on the 
live systems.

Configuration of those properties looks like the following:

**app/_config.php**

```php
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Config\Config;
if(Director::isLive()) {
    Config::modify()->set(Email::class, 'bcc_all_emails_to', "client@example.com");
} else {
    Config::modify()->set(Email::class, 'send_all_emails_to', "developer@example.com");
}
```

### Setting custom "Reply To" email address.

For email messages that should have an email address which is replied to that actually differs from the original "from" 
email, do the following. This is encouraged especially when the domain responsible for sending the message isn't
necessarily the same which should be used for return correspondence and should help prevent your message from being 
marked as spam.

```php
$email = new Email(..);
$email->setReplyTo('reply@example.com');
```

### Setting Custom Headers

For email headers which do not have getters or setters (like setTo(), setFrom()) you can manipulate the underlying
`Swift_Message` that we provide a wrapper for.


```php
$email = new Email(...);
$email->getSwiftMessage()->getHeaders()->addTextHeader('HeaderName', 'HeaderValue');
```

[info]
See this [Wikipedia](http://en.wikipedia.org/wiki/E-mail#Message_header) entry for a list of header names.
[/info]

## Disabling Emails

If required, you can also disable email sending entirely. This is useful for testing and staging servers where
you do not wish to send emails out.

```yaml
---
Name: myemailconfig
Only:
  Environment: dev
---
SilverStripe\Core\Injector\Injector:
  Swift_Transport:
    class: Swift_NullTransport
```

## SwiftMailer Documentation

For further information on SwiftMailer, consult their docs: http://swiftmailer.org/docs/introduction.html


## API Documentation

* [Email](api:SilverStripe\Control\Email\Email)
