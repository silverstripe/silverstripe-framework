<?php

namespace SilverStripe\Control\Tests\Email;

use PHPUnit_Framework_MockObject_MockObject;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\Email\Mailer;
use SilverStripe\Control\Email\SwiftMailer;
use SilverStripe\Control\Tests\Email\EmailTest\EmailSubClass;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestMailer;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Member;
use SilverStripe\View\SSViewer;
use Swift_Attachment;
use Swift_Mailer;
use Swift_Message;
use Swift_NullTransport;
use Swift_RfcComplianceException;

class EmailTest extends SapphireTest
{

    public function testAddAttachment()
    {
        $email = new Email();

        $email->addAttachment(__DIR__ . '/EmailTest/attachment.txt', null, 'text/plain');

        $children = $email->getSwiftMessage()->getChildren();
        $this->assertCount(1, $children);

        /** @var Swift_Attachment $child */
        $child = reset($children);

        $this->assertInstanceOf(Swift_Attachment::class, $child);
        $this->assertEquals('text/plain', $child->getContentType());
        $this->assertEquals('attachment.txt', $child->getFilename());
    }

    public function testAddAttachmentFromData()
    {
        $email = new Email();

        $email->addAttachmentFromData('foo bar', 'foo.txt', 'text/plain');
        $children = $email->getSwiftMessage()->getChildren();

        $this->assertCount(1, $children);

        /** @var Swift_Attachment $child */
        $child = reset($children);

        $this->assertInstanceOf(Swift_Attachment::class, $child);
        $this->assertEquals('foo bar', $child->getBody());
        $this->assertEquals('text/plain', $child->getContentType());
        $this->assertEquals('foo.txt', $child->getFilename());
    }

    public function testValidEmailAddress()
    {
        $validEmails = array('test@example.com', 'test-123@example.sub.com');
        $invalidEmails = array('foo.bar@', '@example.com', 'foo@');

        foreach ($validEmails as $email) {
            $this->assertTrue(Email::is_valid_address($email));
        }

        foreach ($invalidEmails as $email) {
            $this->assertFalse(Email::is_valid_address($email));
        }
    }

    public function testObfuscate()
    {
        $emailAddress = 'test-1@example.com';

        $direction = Email::obfuscate($emailAddress, 'direction');
        $visible = Email::obfuscate($emailAddress, 'visible');
        $hex = Email::obfuscate($emailAddress, 'hex');

        $this->assertEquals('<span class="codedirection">moc.elpmaxe@1-tset</span>', $direction);
        $this->assertEquals('test [dash] 1 [at] example [dot] com', $visible);
        $this->assertEquals(
            '&#x74;&#x65;&#x73;&#x74;&#x2d;&#x31;&#x40;&#x65;&#x78;&#x61;&#x6d;&#x70;&#x6c;&#x65;&#x2e;&#x63;&#x6f;&#x6d;',
            $hex
        );
    }

    public function testSendPlain()
    {
        $email = $this->makeEmailMock('Test send plain');

        // email should not call render if a body is supplied
        $email->expects($this->never())->method('renderWith');
        $successful = $email->sendPlain();

        $this->assertTrue($successful);
        $this->assertEmpty($email->getFailedRecipients());

        /** @var TestMailer $mailer */
        $mailer = Injector::inst()->get(Mailer::class);
        $sentMail = $mailer->findEmail('to@example.com');

        $this->assertTrue(is_array($sentMail));

        $this->assertEquals('to@example.com', $sentMail['To']);
        $this->assertEquals('from@example.com', $sentMail['From']);
        $this->assertEquals('Test send plain', $sentMail['Subject']);
        $this->assertEquals('Body for Test send plain', $sentMail['Content']);

        $this->assertCount(1, $sentMail['AttachedFiles']);
        $child = reset($sentMail['AttachedFiles']);
        $this->assertEquals('text/plain', $child['mimetype']);
        $this->assertEquals('attachment.txt', $child['filename']);
        $this->assertEquals('Hello, I\'m a text document.', $child['contents']);
    }

    public function testSend()
    {
        /** @var Email|PHPUnit_Framework_MockObject_MockObject $email */
        $email = $this->makeEmailMock('Test send HTML');

        // email should not call render if a body is supplied
        $email->expects($this->never())->method('renderWith');
        $successful = $email->send();

        $this->assertTrue($successful);
        $this->assertEmpty($email->getFailedRecipients());

        /** @var TestMailer $mailer */
        $mailer = Injector::inst()->get(Mailer::class);
        $sentMail = $mailer->findEmail('to@example.com');

        $this->assertTrue(is_array($sentMail));

        $this->assertEquals('to@example.com', $sentMail['To']);
        $this->assertEquals('from@example.com', $sentMail['From']);
        $this->assertEquals('Test send HTML', $sentMail['Subject']);
        $this->assertEquals('Body for Test send HTML', $sentMail['Content']);

        $this->assertCount(1, $sentMail['AttachedFiles']);
        $child = reset($sentMail['AttachedFiles']);
        $this->assertEquals('text/plain', $child['mimetype']);
        $this->assertEquals('attachment.txt', $child['filename']);
        $this->assertEquals('Hello, I\'m a text document.', $child['contents']);
    }

    public function testRenderedSend()
    {
        /** @var Email|PHPUnit_Framework_MockObject_MockObject $email */
        $email = $this->getMockBuilder(Email::class)
            ->enableProxyingToOriginalMethods()
            ->getMock();
        $email->setFrom('from@example.com');
        $email->setTo('to@example.com');
        $email->setData(array(
            'EmailContent' => 'test',
        ));
        $this->assertFalse($email->hasPlainPart());
        $this->assertEmpty($email->getBody());
        // these seem to fail for some reason :/
        //$email->expects($this->once())->method('render');
        //$email->expects($this->once())->method('generatePlainPartFromBody');
        $email->send();
        $this->assertTrue($email->hasPlainPart());
        $this->assertNotEmpty($email->getBody());
    }

    public function testRenderedSendSubclass()
    {
        // Include dev theme
        SSViewer::set_themes([
            'silverstripe/framework:/tests/php/Control/Email/EmailTest',
            '$default',
        ]);

        /** @var Email|PHPUnit_Framework_MockObject_MockObject $email */
        $email = $this->getMockBuilder(EmailSubClass::class)
            ->enableProxyingToOriginalMethods()
            ->getMock();
        $email->setFrom('from@example.com');
        $email->setTo('to@example.com');
        $email->setData(array(
            'EmailContent' => 'test',
        ));
        $this->assertFalse($email->hasPlainPart());
        $this->assertEmpty($email->getBody());
        $email->send();
        $this->assertTrue($email->hasPlainPart());
        $this->assertNotEmpty($email->getBody());
        $this->assertContains('<h1>Email Sub-class</h1>', $email->getBody());
    }

    public function testConsturctor()
    {
        $email = new Email(
            'from@example.com',
            'to@example.com',
            'subject',
            'body',
            'cc@example.com',
            'bcc@example.com',
            'bounce@example.com'
        );

        $this->assertCount(1, $email->getFrom());
        $this->assertContains('from@example.com', array_keys($email->getFrom()));
        $this->assertCount(1, $email->getTo());
        $this->assertContains('to@example.com', array_keys($email->getTo()));
        $this->assertEquals('subject', $email->getSubject());
        $this->assertEquals('body', $email->getBody());
        $this->assertCount(1, $email->getCC());
        $this->assertContains('cc@example.com', array_keys($email->getCC()));
        $this->assertCount(1, $email->getBCC());
        $this->assertContains('bcc@example.com', array_keys($email->getBCC()));
        $this->assertEquals('bounce@example.com', $email->getReturnPath());
    }

    public function testGetSwiftMessage()
    {
        $email = new Email(
            'from@example.com',
            'to@example.com',
            'subject',
            'body',
            'cc@example.com',
            'bcc@example.com',
            'bounce@example.com'
        );
        $swiftMessage = $email->getSwiftMessage();

        $this->assertInstanceOf(Swift_Message::class, $swiftMessage);

        $this->assertCount(1, $swiftMessage->getFrom());
        $this->assertContains('from@example.com', array_keys($swiftMessage->getFrom()));
        $this->assertCount(1, $swiftMessage->getTo());
        $this->assertContains('to@example.com', array_keys($swiftMessage->getTo()));
        $this->assertEquals('subject', $swiftMessage->getSubject());
        $this->assertEquals('body', $swiftMessage->getBody());
        $this->assertCount(1, $swiftMessage->getCC());
        $this->assertContains('cc@example.com', array_keys($swiftMessage->getCc()));
        $this->assertCount(1, $swiftMessage->getBCC());
        $this->assertContains('bcc@example.com', array_keys($swiftMessage->getBcc()));
        $this->assertEquals('bounce@example.com', $swiftMessage->getReturnPath());
    }

    public function testSetSwiftMessage()
    {
        Email::config()->update('admin_email', 'admin@example.com');
        DBDatetime::set_mock_now('2017-01-01 07:00:00');
        $email = new Email();
        $swiftMessage = new Swift_Message();
        $email->setSwiftMessage($swiftMessage);
        $this->assertCount(1, $email->getFrom());
        $this->assertContains('admin@example.com', array_keys($swiftMessage->getFrom()));
        $this->assertEquals(strtotime('2017-01-01 07:00:00'), $swiftMessage->getDate());
        $this->assertEquals($swiftMessage, $email->getSwiftMessage());

        // check from field is retained
        $swiftMessage = new Swift_Message();
        $swiftMessage->setFrom('from@example.com');
        $email->setSwiftMessage($swiftMessage);
        $this->assertCount(1, $email->getFrom());
        $this->assertContains('from@example.com', array_keys($email->getFrom()));
    }

    public function testAdminEmailApplied()
    {
        Email::config()->update('admin_email', 'admin@example.com');
        $email = new Email();

        $this->assertCount(1, $email->getFrom());
        $this->assertContains('admin@example.com', array_keys($email->getFrom()));
    }

    public function testGetFrom()
    {
        $email = new Email('from@example.com');
        $this->assertCount(1, $email->getFrom());
        $this->assertContains('from@example.com', array_keys($email->getFrom()));
    }

    public function testSetFrom()
    {
        $email = new Email('from@example.com');
        $this->assertCount(1, $email->getFrom());
        $this->assertContains('from@example.com', array_keys($email->getFrom()));
        $email->setFrom('new-from@example.com');
        $this->assertCount(1, $email->getFrom());
        $this->assertContains('new-from@example.com', array_keys($email->getFrom()));
    }

    public function testAddFrom()
    {
        $email = new Email('from@example.com');
        $this->assertCount(1, $email->getFrom());
        $this->assertContains('from@example.com', array_keys($email->getFrom()));
        $email->addFrom('new-from@example.com');
        $this->assertCount(2, $email->getFrom());
        $this->assertContains('from@example.com', array_keys($email->getFrom()));
        $this->assertContains('new-from@example.com', array_keys($email->getFrom()));
    }

    public function testSetGetSender()
    {
        $email = new Email();
        $this->assertEmpty($email->getSender());
        $email->setSender('sender@example.com', 'Silver Stripe');
        $this->assertEquals(array('sender@example.com' => 'Silver Stripe'), $email->getSender());
    }

    public function testSetGetReturnPath()
    {
        $email = new Email();
        $this->assertEmpty($email->getReturnPath());
        $email->setReturnPath('return@example.com');
        $this->assertEquals('return@example.com', $email->getReturnPath());
    }

    public function testSetGetTo()
    {
        $email = new Email('from@example.com', 'to@example.com');
        $this->assertCount(1, $email->getTo());
        $this->assertContains('to@example.com', array_keys($email->getTo()));
        $email->setTo('new-to@example.com', 'Silver Stripe');
        $this->assertEquals(array('new-to@example.com' => 'Silver Stripe'), $email->getTo());
    }

    public function testAddTo()
    {
        $email = new Email('from@example.com', 'to@example.com');
        $this->assertCount(1, $email->getTo());
        $this->assertContains('to@example.com', array_keys($email->getTo()));
        $email->addTo('new-to@example.com');
        $this->assertCount(2, $email->getTo());
        $this->assertContains('to@example.com', array_keys($email->getTo()));
        $this->assertContains('new-to@example.com', array_keys($email->getTo()));
    }

    public function testSetGetCC()
    {
        $email = new Email('from@example.com', 'to@example.com', 'subject', 'body', 'cc@example.com');
        $this->assertCount(1, $email->getCC());
        $this->assertContains('cc@example.com', array_keys($email->getCC()));
        $email->setCC('new-cc@example.com', 'Silver Stripe');
        $this->assertEquals(array('new-cc@example.com' => 'Silver Stripe'), $email->getCC());
    }

    public function testAddCC()
    {
        $email = new Email('from@example.com', 'to@example.com', 'subject', 'body', 'cc@example.com');
        $this->assertCount(1, $email->getCC());
        $this->assertContains('cc@example.com', array_keys($email->getCC()));
        $email->addCC('new-cc@example.com', 'Silver Stripe');
        $this->assertCount(2, $email->getCC());
        $this->assertContains('cc@example.com', array_keys($email->getCC()));
        $this->assertContains('new-cc@example.com', array_keys($email->getCC()));
    }

    public function testSetGetBCC()
    {
        $email = new Email(
            'from@example.com',
            'to@example.com',
            'subject',
            'body',
            'cc@example.com',
            'bcc@example.com'
        );
        $this->assertCount(1, $email->getBCC());
        $this->assertContains('bcc@example.com', array_keys($email->getBCC()));
        $email->setBCC('new-bcc@example.com', 'Silver Stripe');
        $this->assertEquals(array('new-bcc@example.com' => 'Silver Stripe'), $email->getBCC());
    }

    public function testAddBCC()
    {
        $email = new Email(
            'from@example.com',
            'to@example.com',
            'subject',
            'body',
            'cc@example.com',
            'bcc@example.com'
        );
        $this->assertCount(1, $email->getBCC());
        $this->assertContains('bcc@example.com', array_keys($email->getBCC()));
        $email->addBCC('new-bcc@example.com', 'Silver Stripe');
        $this->assertCount(2, $email->getBCC());
        $this->assertContains('bcc@example.com', array_keys($email->getBCC()));
        $this->assertContains('new-bcc@example.com', array_keys($email->getBCC()));
    }

    public function testReplyTo()
    {
        $email = new Email();
        $this->assertEmpty($email->getReplyTo());
        $email->setReplyTo('reply-to@example.com', 'Silver Stripe');
        $this->assertEquals(array('reply-to@example.com' => 'Silver Stripe'), $email->getReplyTo());
        $email->addReplyTo('new-reply-to@example.com');
        $this->assertCount(2, $email->getReplyTo());
        $this->assertContains('reply-to@example.com', array_keys($email->getReplyTo()));
        $this->assertContains('new-reply-to@example.com', array_keys($email->getReplyTo()));
    }

    public function testSubject()
    {
        $email = new Email('from@example.com', 'to@example.com', 'subject');
        $this->assertEquals('subject', $email->getSubject());
        $email->setSubject('new subject');
        $this->assertEquals('new subject', $email->getSubject());
    }

    public function testPriority()
    {
        $email = new Email();
        $this->assertEquals(3, $email->getPriority());
        $email->setPriority(5);
        $this->assertEquals(5, $email->getPriority());
    }

    public function testData()
    {
        $email = new Email();
        $this->assertEmpty($email->getData());
        $email->setData(array(
            'Title' => 'My Title',
        ));
        $this->assertCount(1, $email->getData());
        $this->assertEquals(array('Title' => 'My Title'), $email->getData());

        $email->addData('Content', 'My content');
        $this->assertCount(2, $email->getData());
        $this->assertEquals(array(
            'Title' => 'My Title',
            'Content' => 'My content',
        ), $email->getData());
        $email->removeData('Title');
        $this->assertEquals(array('Content' => 'My content'), $email->getData());
    }

    public function testDataWithViewableData()
    {
        $member = new Member();
        $member->FirstName = 'First Name';
        $email = new Email();
        $this->assertEmpty($email->getData());
        $email->setData($member);
        $this->assertEquals($member, $email->getData());
        $email->addData('Test', 'Test value');
        $this->assertEquals('Test value', $email->getData()->Test);
        $email->removeData('Test');
        $this->assertNull($email->getData()->Test);
    }

    public function testBody()
    {
        $email = new Email();
        $this->assertEmpty($email->getBody());
        $email->setBody('<h1>Title</h1>');
        $this->assertEquals('<h1>Title</h1>', $email->getBody());
    }

    public function testHTMLTemplate()
    {
        // Include dev theme
        SSViewer::set_themes([
            'silverstripe/framework:/tests/php/Control/Email/EmailTest',
            '$default',
        ]);

        // Find template on disk
        $emailTemplate = ModuleResourceLoader::singleton()->resolveResource(
            'silverstripe/framework:templates/SilverStripe/Control/Email/Email.ss'
        );
        $subClassTemplate = ModuleResourceLoader::singleton()->resolveResource(
            'silverstripe/framework:tests/php/Control/Email/EmailTest/templates/'
            . str_replace('\\', '/', EmailSubClass::class)
            . '.ss'
        );
        $this->assertTrue($emailTemplate->exists());
        $this->assertTrue($subClassTemplate->exists());

        // Check template is auto-found
        $email = new Email();
        $this->assertEquals($emailTemplate->getPath(), $email->getHTMLTemplate());
        $email->setHTMLTemplate('MyTemplate');
        $this->assertEquals('MyTemplate', $email->getHTMLTemplate());

        // Check subclass template is found
        $email2 = new EmailSubClass();
        $this->assertEquals($subClassTemplate->getPath(), $email2->getHTMLTemplate());
        $email->setHTMLTemplate('MyTemplate');
        $this->assertEquals('MyTemplate', $email->getHTMLTemplate());
    }

    public function testPlainTemplate()
    {
        $email = new Email();
        $this->assertEmpty($email->getPlainTemplate());
        $email->setPlainTemplate('MyTemplate');
        $this->assertEquals('MyTemplate', $email->getPlainTemplate());
    }

    public function testGetFailedRecipients()
    {
        $mailer = new SwiftMailer();
        /** @var Swift_NullTransport|PHPUnit_Framework_MockObject_MockObject $transport */
        $transport = $this->getMockBuilder(Swift_NullTransport::class)->getMock();
        $transport->expects($this->once())
            ->method('send')
            ->willThrowException(new Swift_RfcComplianceException('Bad email'));
        $mailer->setSwiftMailer(new Swift_Mailer($transport));
        $email = new Email();
        $email->setTo('to@example.com');
        $email->setFrom('from@example.com');
        $mailer->send($email);
        $this->assertCount(1, $email->getFailedRecipients());
    }

    public function testIsEmail()
    {
        $this->assertTrue((new Email)->IsEmail());
    }

    public function testRenderAgain()
    {
        $email = new Email();
        $email->setData(array(
            'EmailContent' => 'my content',
        ));
        $email->render();
        $this->assertContains('my content', $email->getBody());
        $children = $email->getSwiftMessage()->getChildren();
        $this->assertCount(1, $children);
        $plainPart = reset($children);
        $this->assertEquals('my content', $plainPart->getBody());

        // ensure repeat renders don't add multiple plain parts
        $email->render();
        $this->assertCount(1, $email->getSwiftMessage()->getChildren());
    }

    public function testRenderPlainOnly()
    {
        $email = new Email();
        $email->setData(array(
            'EmailContent' => 'test content',
        ));
        $email->render(true);
        $this->assertEquals('text/plain', $email->getSwiftMessage()->getContentType());
        $this->assertEmpty($email->getSwiftMessage()->getChildren());
    }

    public function testHasPlainPart()
    {
        $email = new Email();
        $email->setData(array(
            'EmailContent' => 'test',
        ));
        //emails are assumed to be HTML by default
        $this->assertFalse($email->hasPlainPart());
        //make sure plain attachments aren't picked up as a plain part
        $email->addAttachmentFromData('data', 'attachent.txt', 'text/plain');
        $this->assertFalse($email->hasPlainPart());
        $email->getSwiftMessage()->addPart('plain', 'text/plain');
        $this->assertTrue($email->hasPlainPart());
    }

    public function testGeneratePlainPartFromBody()
    {
        $email = new Email();
        $email->setBody('<h1>Test</h1>');
        $this->assertEmpty($email->getSwiftMessage()->getChildren());
        $email->generatePlainPartFromBody();
        $children = $email->getSwiftMessage()->getChildren();
        $this->assertCount(1, $children);
        $plainPart = reset($children);
        $this->assertContains('Test', $plainPart->getBody());
        $this->assertNotContains('<h1>Test</h1>', $plainPart->getBody());
    }

    public function testMultipleEmailSends()
    {
        $email = new Email();
        $email->setData(array(
            'EmailContent' => 'Test',
        ));
        $this->assertEmpty($email->getBody());
        $this->assertEmpty($email->getSwiftMessage()->getChildren());
        $email->send();
        $this->assertContains('Test', $email->getBody());
        $this->assertCount(1, $email->getSwiftMessage()->getChildren());
        $children = $email->getSwiftMessage()->getChildren();
        /** @var \Swift_MimePart $plainPart */
        $plainPart = reset($children);
        $this->assertContains('Test', $plainPart->getBody());


        //send again
        $email->send();
        $this->assertContains('Test', $email->getBody());
        $this->assertCount(1, $email->getSwiftMessage()->getChildren());
        $children = $email->getSwiftMessage()->getChildren();
        /** @var \Swift_MimePart $plainPart */
        $plainPart = reset($children);
        $this->assertContains('Test', $plainPart->getBody());
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|Email
     */
    protected function makeEmailMock($subject)
    {
        /** @var Email|PHPUnit_Framework_MockObject_MockObject $email */
        $email = $this->getMockBuilder(Email::class)
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $email->setFrom('from@example.com');
        $email->setTo('to@example.com');
        $email->setSubject($subject);
        $email->setBody("Body for {$subject}");
        $email->setCC('cc@example.com');
        $email->setBCC('bcc@example.com');
        $email->addAttachment(__DIR__ . '/EmailTest/attachment.txt', null, 'text/plain');
        return $email;
    }
}
