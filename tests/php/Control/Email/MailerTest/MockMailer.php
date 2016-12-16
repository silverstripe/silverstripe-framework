<?php

namespace SilverStripe\Control\Tests\Email\MailerTest;

use SilverStripe\Control\Email\Mailer;
use SilverStripe\Dev\TestOnly;

/**
 * Mocks the sending of emails without actually sending anything
 */
class MockMailer extends Mailer implements TestOnly
{
    protected function email($to, $subjectEncoded, $fullBody, $headersEncoded, $bounceAddress)
    {
        return array($to, $subjectEncoded, $fullBody, $headersEncoded, $bounceAddress);
    }
}
