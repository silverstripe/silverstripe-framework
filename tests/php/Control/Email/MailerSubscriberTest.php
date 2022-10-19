<?php

namespace SilverStripe\Control\Tests\Email;

use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestMailer;
use Symfony\Component\Mailer\MailerInterface;

class MailerSubscriberTest extends SapphireTest
{
    protected function setUp(): void
    {
        parent::setUp();
        Email::config()->remove('send_all_emails_to');
        Email::config()->remove('cc_all_emails_to');
        Email::config()->remove('bcc_all_emails_to');
        Email::config()->remove('send_all_emails_from');
    }

    private function getEmail(): Email
    {
        return (new Email())
            ->setTo('original-to@example.com')
            ->setCC('original-cc@example.com')
            ->setBCC('original-bcc@example.com')
            ->setFrom('original-from@example.com');
    }

    private function getMailer(): TestMailer
    {
        return Injector::inst()->get(MailerInterface::class);
    }

    private function getHeaderValue(Email $email, string $headerName): ?string
    {
        $headers = $email->getHeaders();
        if (!$headers->has($headerName)) {
            return null;
        }
        return $headers->getHeaderBody($headerName)[0]->getAddress();
    }

    public function testSendAllEmailsTo(): void
    {
        Email::config()->update('send_all_emails_to', 'to@example.com');
        $email = $this->getEmail();
        $email->send();

        $this->assertCount(1, $email->getTo());
        $this->assertSame('to@example.com', $email->getTo()[0]->getAddress());
        $this->assertCount(1, $email->getFrom());
        $this->assertSame('original-from@example.com', $email->getFrom()[0]->getAddress());

        $this->assertSame('original-to@example.com', $this->getHeaderValue($email, 'X-Original-To'));
        $this->assertSame('original-cc@example.com', $this->getHeaderValue($email, 'X-Original-Cc'));
        $this->assertSame('original-bcc@example.com', $this->getHeaderValue($email, 'X-Original-Bcc'));
        $this->assertSame(null, $this->getHeaderValue($email, 'X-Original-From'));
    }

    public function testSendAllEmailsFrom(): void
    {
        Email::config()->update('send_all_emails_from', 'from@example.com');
        $email = $this->getEmail();
        $email->send();

        $this->assertCount(1, $email->getTo());
        $this->assertSame('original-to@example.com', $email->getTo()[0]->getAddress());
        $this->assertCount(1, $email->getFrom());
        $this->assertSame('from@example.com', $email->getFrom()[0]->getAddress());

        $this->assertSame(null, $this->getHeaderValue($email, 'X-Original-To'));
        $this->assertSame(null, $this->getHeaderValue($email, 'X-Original-Cc'));
        $this->assertSame(null, $this->getHeaderValue($email, 'X-Original-Bcc'));
        $this->assertSame('original-from@example.com', $this->getHeaderValue($email, 'X-Original-From'));
    }

    public function testCCAllEmailsTo(): void
    {
        Email::config()->update('cc_all_emails_to', 'cc@example.com');
        $email = $this->getEmail();
        $email->send();

        $this->assertCount(2, $email->getCc());
        $this->assertSame('original-cc@example.com', $email->getCc()[0]->getAddress());
        $this->assertSame('cc@example.com', $email->getCc()[1]->getAddress());
    }

    public function testBCCAllEmailsTo(): void
    {
        Email::config()->update('bcc_all_emails_to', 'bcc@example.com');
        $email = $this->getEmail();
        $email->send();

        $this->assertCount(2, $email->getBcc());
        $this->assertSame('original-bcc@example.com', $email->getBcc()[0]->getAddress());
        $this->assertSame('bcc@example.com', $email->getBcc()[1]->getAddress());
    }
}
