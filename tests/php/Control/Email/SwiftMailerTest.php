<?php

namespace SilverStripe\Control\Tests\Email;

use SilverStripe\Control\Email\Email;
use SilverStripe\Control\Email\SwiftMailer;
use SilverStripe\Dev\SapphireTest;
use Swift_Mailer;
use Swift_MailTransport;
use Swift_Message;
use Swift_NullTransport;
use Swift_Plugins_AntiFloodPlugin;

class SwiftMailerTest extends SapphireTest
{
    public function testSwiftMailer()
    {
        $mailer = new SwiftMailer();
        $mailer->setSwiftMailer($swift = new Swift_Mailer(new Swift_NullTransport()));

        $this->assertEquals($swift, $mailer->getSwiftMailer());

        SwiftMailer::config()->remove('swift_plugins');
        SwiftMailer::config()->update('swift_plugins', array(Swift_Plugins_AntiFloodPlugin::class));

        /** @var Swift_MailTransport $transport */
        $transport = $this->getMockBuilder(Swift_MailTransport::class)->getMock();
        $transport
            ->expects($this->once())
            ->method('registerPlugin')
            ->willReturnCallback(function ($plugin) {
                $this->assertInstanceOf(Swift_Plugins_AntiFloodPlugin::class, $plugin);
            });

        /** @var Swift_Mailer $swift */
        $swift = $this->getMockBuilder(Swift_Mailer::class)->disableOriginalConstructor()->getMock();
        $swift
            ->expects($this->once())
            ->method('registerPlugin')
            ->willReturnCallback(function ($plugin) use ($transport) {
                $transport->registerPlugin($plugin);
            });

        $mailer->setSwiftMailer($swift);
    }

    public function testSend()
    {
        $email = new Email();
        $email->setTo('to@example.com');
        $email->setFrom('from@example.com');
        $email->setSubject('Subject');

        $mailer = $this->getMockBuilder(SwiftMailer::class)
            ->setMethods(array('sendSwift'))
            ->getMock();
        $mailer->expects($this->once())->method('sendSwift')->willReturnCallback(function ($message) {
            $this->assertInstanceOf(Swift_Message::class, $message);
        });

        $mailer->send($email);
    }

    public function testSendSwift()
    {
        $mailer = new SwiftMailer();
        $sendSwiftMethod = new \ReflectionMethod($mailer, 'sendSwift');
        $sendSwiftMethod->setAccessible(true);
        $transport = $this->getMockBuilder(Swift_NullTransport::class)->getMock();
        $transport->expects($this->once())
            ->method('send');
        $mailer->setSwiftMailer(new Swift_Mailer($transport));
        $swiftMessage = new Swift_Message('Test', 'Body');
        $swiftMessage->setTo('to@example.com');
        $swiftMessage->setFrom('from@example.com');
        $sendSwiftMethod->invoke($mailer, $swiftMessage);
    }
}
