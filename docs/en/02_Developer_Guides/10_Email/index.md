summary: Send HTML and plain text email from your SilverStripe application.

# Email

Creating and sending email in SilverStripe is done through the [Email](api:SilverStripe\Control\Email\Email) and [Mailer](api:SilverStripe\Control\Email\Mailer) classes. This document 
covers how to create an `Email` instance, customise it with a HTML template, then send it through a custom `Mailer`.

## Configuration

SilverStripe provides an API over the top of the [SwiftMailer](http://swiftmailer.org/) PHP library which comes with an
extensive list of "transports" for sending mail via different services. 

Out of the box, SilverStripe will use the built-in PHP `mail()` command via the `Swift_MailTransport` class. If you'd
like to use a more robust transport to send mail you can swap out the transport used by the `Mailer` via config:

```yml
SilverStripe\Core\Injector\Injector:
  Swift_Transport: Swift_SendmailTransport
```

For example, to use SMTP, create a file app/_config/email.yml:

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
      Username: [ setUsername, ['<username>'] ]
      Password: [ setPassword, ['<password>'] ]
      AuthMode: [ setAuthMode, ['login'] ]
```

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

<div class="info" markdown="1">
The default HTML template for emails is named `GenericEmail` and is located in `vendor/silverstripe/framework/templates/SilverStripe/Email/`.
To customise this template, copy it to the `app/templates/Email/` folder or use `setHTMLTemplate` when you create the 
`Email` instance.
</div>


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

<div class="alert" markdown="1">
As we've added a new template file (`MyCustomEmail`) make sure you clear the SilverStripe cache for your changes to
take affect.
</div>

#### Custom plain templates

By default SilverStripe will generate a plain text representation of the email from the HTML body. However if you'd like
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
  admin_email: support@myapp.com
```

<div class="alert" markdown="1">
Remember, setting a `from` address that doesn't come from your domain (such as the users email) will likely see your
email marked as spam. If you want to send from another address think about using the `setReplyTo` method.
</div>

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
    Config::modify()->set(Email::class, 'bcc_all_emails_to', "client@myapp.com");
} else {
    Config::modify()->set(Email::class, 'send_all_emails_to', "developer@myapp.com");
}
```

### Setting custom "Reply To" email address.

For email messages that should have an email address which is replied to that actually differs from the original "from" 
email, do the following. This is encouraged especially when the domain responsible for sending the message isn't
necessarily the same which should be used for return correspondence and should help prevent your message from being 
marked as spam.

```php
$email = new Email(..);
$email->setReplyTo('reply@myapp.com');
```

### Setting Custom Headers

For email headers which do not have getters or setters (like setTo(), setFrom()) you can manipulate the underlying
`Swift_Message` that we provide a wrapper for.


```php
$email = new Email(...);
$email->getSwiftMessage()->getHeaders()->addTextHeader('HeaderName', 'HeaderValue');
```

<div class="info" markdown="1">
See this [Wikipedia](http://en.wikipedia.org/wiki/E-mail#Message_header) entry for a list of header names.
</div>

## SwiftMailer Documentation

For further information on SwiftMailer, consult their docs: http://swiftmailer.org/docs/introduction.html

## API Documentation

* [Email](api:SilverStripe\Control\Email\Email)
