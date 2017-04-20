<?php

namespace SilverStripe\Control\Tests\Email;

use SilverStripe\Control\Email\Email;
use SilverStripe\Control\Email\SwiftPlugin;
use SilverStripe\Dev\SapphireTest;

class SwiftPluginTest extends SapphireTest
{

    protected function setUp()
    {
        parent::setUp();

        //clean the config
        Email::config()->remove('send_all_emails_to');
        Email::config()->remove('cc_all_emails_to');
        Email::config()->remove('bcc_all_emails_to');
        Email::config()->remove('send_all_emails_from');
    }

    protected function getEmail()
    {
        return (new Email())
            ->setTo('original-to@example.com')
            ->setCC('original-cc@example.com')
            ->setBCC('original-bcc@example.com')
            ->setFrom('original-from@example.com');
    }

    protected function getMailer()
    {
        $mailer = new \Swift_Mailer(new \Swift_NullTransport());
        $mailer->registerPlugin(new SwiftPlugin());

        return $mailer;
    }

    public function testSendAllEmailsTo()
    {
        Email::config()->update('send_all_emails_to', 'to@example.com');
        $email = $this->getEmail();
        $this->getMailer()->send($email->getSwiftMessage());
        $headers = $email->getSwiftMessage()->getHeaders();

        $this->assertCount(1, $email->getTo());
        $this->assertContains('to@example.com', array_keys($email->getTo()));
        $this->assertCount(1, $email->getFrom());
        $this->assertContains('original-from@example.com', array_keys($email->getFrom()));

        $this->assertTrue($headers->has('X-Original-To'));
        $this->assertTrue($headers->has('X-Original-Cc'));
        $this->assertTrue($headers->has('X-Original-Bcc'));
        $this->assertFalse($headers->has('X-Original-From'));

        $originalTo = array_keys($headers->get('X-Original-To')->getFieldBodyModel());
        $originalCc = array_keys($headers->get('X-Original-Cc')->getFieldBodyModel());
        $originalBcc = array_keys($headers->get('X-Original-Bcc')->getFieldBodyModel());

        $this->assertCount(1, $originalTo);
        $this->assertContains('original-to@example.com', $originalTo);
        $this->assertCount(1, $originalCc);
        $this->assertContains('original-cc@example.com', $originalCc);
        $this->assertCount(1, $originalBcc);
        $this->assertContains('original-bcc@example.com', $originalBcc);
    }

    public function testSendAllEmailsFrom()
    {
        Email::config()->update('send_all_emails_from', 'from@example.com');
        $email = $this->getEmail();
        $this->getMailer()->send($email->getSwiftMessage());

        $headers = $email->getSwiftMessage()->getHeaders();

        $this->assertFalse($headers->has('X-Original-To'));
        $this->assertFalse($headers->has('X-Original-Cc'));
        $this->assertFalse($headers->has('X-Original-Bcc'));
        $this->assertTrue($headers->has('X-Original-From'));

        $this->assertCount(1, $email->getFrom());
        $this->assertContains('from@example.com', array_keys($email->getFrom()));

        $this->assertCount(1, $headers->get('X-Original-From')->getFieldBodyModel());
        $this->assertContains('original-from@example.com', array_keys($headers->get('X-Original-From')->getFieldBodyModel()));
    }

    public function testCCAllEmailsTo()
    {
        Email::config()->update('cc_all_emails_to', 'cc@example.com');
        $email = $this->getEmail();
        $this->getMailer()->send($email->getSwiftMessage());

        $this->assertCount(2, $email->getCC());
        $this->assertContains('cc@example.com', array_keys($email->getCC()));
        $this->assertContains('original-cc@example.com', array_keys($email->getCC()));
    }

    public function testBCCAllEmailsTo()
    {
        Email::config()->update('bcc_all_emails_to', 'bcc@example.com');
        $email = $this->getEmail();
        $this->getMailer()->send($email->getSwiftMessage());

        $this->assertCount(2, $email->getBCC());
        $this->assertContains('bcc@example.com', array_keys($email->getBCC()));
        $this->assertContains('original-bcc@example.com', array_keys($email->getBCC()));
    }
}
