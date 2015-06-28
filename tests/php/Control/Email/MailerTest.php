<?php

namespace SilverStripe\Control\Tests\Email;

use SilverStripe\Control\Email\Email;
use SilverStripe\Control\Email\Mailer;
use SilverStripe\Dev\SapphireTest;

class MailerTest extends SapphireTest
{

    public function testGetTransport()
    {
        $mailer = new Mailer();
        $this->assertInstanceOf(\Swift_Transport::class, $mailer->getTransport());
        $this->assertNotInstanceOf(\Swift_SendmailTransport::class, $mailer->getTransport());

        Mailer::config()->update('swift_transport', \Swift_SendmailTransport::class);

        $mailer = new Mailer();
        $this->assertInstanceOf(\Swift_SendmailTransport::class, $mailer->getTransport());
    }

    public function testSetTransport()
    {
        $mailer = new Mailer();
        $this->assertNotInstanceOf(\Swift_SendmailTransport::class, $mailer->getTransport());
        $mailer->setTransport(new \Swift_SendmailTransport());
        $this->assertInstanceOf(\Swift_SendmailTransport::class, $mailer->getTransport());
    }

    public function testGetSwiftMailer()
    {
        $mailer = new Mailer();
        $this->assertInstanceOf(\Swift_Mailer::class, $mailer->getSwiftMailer());
    }

    public function testSetSwiftMailer()
    {
        $mailer = new Mailer();

        $this->assertEquals($mailer->getTransport(), $mailer->getSwiftMailer()->getTransport());

        Mailer::config()->remove('swift_plugins');
        Mailer::config()->update('swift_plugins', array(\Swift_Plugins_AntiFloodPlugin::class));

        /** @var \Swift_MailTransport $transport */
        $transport = $this->getMockBuilder(\Swift_MailTransport::class)->getMock();
        $transport
            ->expects($this->once())
            ->method('registerPlugin')
            ->willReturnCallback(function ($plugin) {
                $this->assertInstanceOf(\Swift_Plugins_AntiFloodPlugin::class, $plugin);
            });

        /** @var \Swift_Mailer $swift */
        $swift = $this->getMockBuilder(\Swift_Mailer::class)->disableOriginalConstructor()->getMock();
        $swift
            ->expects($this->once())
            ->method('registerPlugin')
            ->willReturnCallback(function ($plugin) use ($transport) {
                $transport->registerPlugin($plugin);
            });

        $mailer->setSwiftMailer($swift);
    }

    public function testGetFailedRecipients()
    {
        $mailer = new Mailer();
        $transport = $this->getMockBuilder(\Swift_NullTransport::class)->getMock();
        $transport->expects($this->once())
            ->method('send')
            ->willThrowException(new \Swift_RfcComplianceException('Bad email'));
        $mailer->setTransport($transport);
        $swiftMessage = new \Swift_Message('Test', 'Body');
        $swiftMessage->setTo('to@example.com');
        $swiftMessage->setFrom('from@example.com');
        $mailer->sendSwift($swiftMessage);
        $this->assertCount(1, $mailer->getFailedRecipients());
    }

    public function testSend()
    {
        $email = Email::create_from_callback('SilverStripe\\Email\\Email', null, function ($message) {
            $message->setTo('to@example.com');
            $message->setFrom('from@example.com');
            $message->setSubject('Subject');
        });

        $mailer = $this->getMock(Mailer::class, array('sendSwift'));
        $mailer->expects($this->once())->method('sendSwift')->willReturnCallback(function ($message) {
            $this->assertInstanceOf(\Swift_Message::class, $message);
        });

        $mailer->send($email);
    }

    public function testSendSwift()
    {
        $mailer = new Mailer();
        $transport = $this->getMockBuilder(\Swift_NullTransport::class)->getMock();
        $transport->expects($this->once())
            ->method('send');
        $mailer->setTransport($transport);
        $swiftMessage = new \Swift_Message('Test', 'Body');
        $swiftMessage->setTo('to@example.com');
        $swiftMessage->setFrom('from@example.com');
        $mailer->sendSwift($swiftMessage);
    }
}
