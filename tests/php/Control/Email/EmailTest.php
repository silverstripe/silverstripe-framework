<?php

namespace SilverStripe\Control\Tests\Email;

use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\Tests\Email\EmailTest\EmailSubClass;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestMailer;
use SilverStripe\Security\Member;
use SilverStripe\View\SSViewer;
use SilverStripe\Model\ModelData;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\AbstractPart;
use PHPUnit\Framework\Attributes\DataProvider;

class EmailTest extends SapphireTest
{
    private array $origThemes = [];

    protected function setUp(): void
    {
        parent::setUp();
        Director::config()->set('alternate_base_url', 'http://www.mysite.com/');
        $this->origThemes = SSViewer::get_themes();
        SSViewer::set_themes([
            'silverstripe/framework:/tests/php/Control/Email/EmailTest',
            '$default',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        SSViewer::set_themes($this->origThemes);
    }

    public function testAddAttachment(): void
    {
        $email = new Email();
        $email->addAttachment(__DIR__ . '/EmailTest/attachment.txt', null, 'text/plain');
        $attachments = $email->getAttachments();
        $this->assertCount(1, $attachments);
        $attachment = $this->getFirstAttachment($attachments);
        $this->assertSame('text/plain', $attachment->getContentType());
        $this->assertSame('attachment.txt', $attachment->getFilename());
    }

    public function testAddAttachmentFromData(): void
    {
        $email = new Email();
        $email->addAttachmentFromData('foo bar', 'foo.txt', 'text/plain');
        $attachments = $email->getAttachments();
        $this->assertCount(1, $attachments);
        $attachment = $this->getFirstAttachment($attachments);
        $this->assertSame('text/plain', $attachment->getContentType());
        $this->assertSame('foo.txt', $attachment->getFilename());
        $this->assertSame('foo bar', $attachment->getBody());
    }

    private function getFirstAttachment(array $attachments): DataPart
    {
        return $attachments[0];
    }

    #[DataProvider('provideValidEmailAddresses')]
    public function testValidEmailAddress($email): void
    {
        $this->assertTrue(Email::is_valid_address($email));
    }

    #[DataProvider('provideInvalidEmailAddresses')]
    public function testInvalidEmailAddress($email): void
    {
        $this->assertFalse(Email::is_valid_address($email));
    }

    public static function provideValidEmailAddresses(): array
    {
        return [
            ['test@example.com', 'test-123@sub.example.com'],
        ];
    }

    public static function provideInvalidEmailAddresses(): array
    {
        return [
            ['foo.bar@', '@example.com', 'foo@'],
        ];
    }

    public function testObfuscate(): void
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

    private function getTemplateClass(string $templateName): string
    {
        return implode('\\', ['SilverStripe', 'Control', 'Tests', 'Email', 'EmailTest', $templateName]);
    }

    private function getMailer(): TestMailer
    {
        return Injector::inst()->get(MailerInterface::class);
    }

    private function createTestEmail(string $subject = 'My subject', $setPlain = true): Email
    {
        $email = new Email();
        $email->setFrom('from@example.com');
        $email->setTo('to@example.com');
        $email->setSubject($subject);
        if ($setPlain) {
            $email->text("Plain body for $subject");
        }
        $email->html("<p>HTML body for $subject</p>");
        $email->setCC('cc@example.com');
        $email->setBCC('bcc@example.com');
        $email->addAttachment(__DIR__ . '/EmailTest/attachment.txt', null, 'text/plain');
        return $email;
    }

    public function testSendPlain(): void
    {
        $email = $this->createTestEmail('Test send plain');
        $email->sendPlain();
        $this->assertStringNotContainsString($email->getTextBody(), 'My Plain Template');
        $sentMail = $this->getMailer()->findEmail('to@example.com');

        $this->assertSame('to@example.com', $sentMail['To']);
        $this->assertSame('from@example.com', $sentMail['From']);
        $this->assertSame('Test send plain', $sentMail['Subject']);
        $this->assertStringContainsString('Plain body for Test send plain', $sentMail['Content']);

        $this->assertCount(1, $sentMail['AttachedFiles']);
        $child = reset($sentMail['AttachedFiles']);
        $this->assertSame('text/plain', $child['mimetype']);
        $this->assertSame('attachment.txt', $child['filename']);
        $this->assertSame('Hello, I\'m a text document.', $child['contents']);

        // assert MIME types
        // explicitly setting $email->html(null) because sendPlain() will itself set $this->html(null), and then
        // revert it to its previous AFTER sending the email. For testing purposes, we need to manuall set it
        // to null in order to test the MIME types for what would have been sent in practice
        $email->html(null);
        $this->assertSame([
            'text/plain charset: utf-8',
            'text/plain disposition: attachment filename: attachment.txt'
        ], array_map(fn(AbstractPart $part) => $part->asDebugString(), $email->getBody()->getParts()));
    }

    public function testSendPlainFallback(): void
    {
        $email = $this->createTestEmail('Test send plain', false);
        $email->sendPlain();
        $sentMail = $this->getMailer()->findEmail('to@example.com');
        // assert that it has HTML body with HTML tags removed
        $this->assertSame('HTML body for Test send plain', $sentMail['Content']);
    }

    public function testSendPlainThenNormalWithSetData(): void
    {
        $email = $this->createTestEmail('Test send plain', false);
        $email->setData([
            'EmailContent' => 'This is the content of the email',
        ]);
        $email->sendPlain();
        $email->send();
        $sentMail = $this->getMailer()->findEmail('to@example.com');
        $this->assertSame('This is the content of the email', $sentMail['Content']);
        $email->to('to2@example.com');
        $email->send();
        $sentMail = $this->getMailer()->findEmail('to2@example.com');
        $this->assertStringContainsString('This is the content of the email', $sentMail['Content']);
    }

    public function testSend(): void
    {
        $email = $this->createTestEmail('Test send HTML');

        // email should not call render if a body is supplied
        $email->setHTMLTemplate($this->getTemplateClass('HtmlTemplate'));
        $email->send();
        $this->assertStringNotContainsString($email->getHtmlBody(), 'My HTML Template');

        $sentMail = $this->getMailer()->findEmail('to@example.com');

        $this->assertSame('to@example.com', $sentMail['To']);
        $this->assertSame('from@example.com', $sentMail['From']);
        $this->assertSame('Test send HTML', $sentMail['Subject']);
        $this->assertStringContainsString('<p>HTML body for Test send HTML</p>', $sentMail['Content']);

        $this->assertCount(1, $sentMail['AttachedFiles']);
        $child = reset($sentMail['AttachedFiles']);
        $this->assertSame('text/plain', $child['mimetype']);
        $this->assertSame('attachment.txt', $child['filename']);
        $this->assertSame('Hello, I\'m a text document.', $child['contents']);

        // assert MIME types
        $this->assertSame([
            implode("\n  └ ", [
                'multipart/alternative',
                'text/plain charset: utf-8',
                'text/html charset: utf-8'
            ]),
            'text/plain disposition: attachment filename: attachment.txt'
        ], array_map(fn(AbstractPart $part) => $part->asDebugString(), $email->getBody()->getParts()));
    }

    public function testRenderedSend(): void
    {
        $email = new Email(to: 'to@example.com');
        $email->setHTMLTemplate($this->getTemplateClass('HtmlTemplate'));
        $email->setData([
            'EmailContent' => '<p>test</p>',
        ]);
        $email->send();
        $sentMail = $this->getMailer()->findEmail('to@example.com');
        $this->assertStringContainsString('My HTML Template', $sentMail['Content']);
    }

    public function testRenderedSendSubclass(): void
    {
        $email = new EmailSubClass(to: 'to@example.com');
        $email->setData([
            'EmailContent' => 'test',
        ]);
        $email->send();
        $sentMail = $this->getMailer()->findEmail('to@example.com');
        $this->assertStringContainsString('<h1>Email Sub-class</h1>', $sentMail['Content']);
    }

    public function testConstructor(): void
    {
        $email = new Email(
            'from@example.com',
            'to@example.com',
            'subject',
            '<p>body</p>',
            'cc@example.com',
            'bcc@example.com',
            'bounce@example.com'
        );
        $this->assertCount(1, $email->getFrom());
        $this->assertSame('from@example.com', $email->getFrom()[0]->getAddress());
        $this->assertCount(1, $email->getTo());
        $this->assertSame('to@example.com', $email->getTo()[0]->getAddress());
        $this->assertEquals('subject', $email->getSubject());
        $this->assertEquals('<p>body</p>', $email->getHtmlBody());
        $this->assertCount(1, $email->getCC());
        $this->assertEquals('cc@example.com', $email->getCC()[0]->getAddress());
        $this->assertCount(1, $email->getBCC());
        $this->assertEquals('bcc@example.com', $email->getBcc()[0]->getAddress());
        $this->assertEquals('bounce@example.com', $email->getReturnPath()->getAddress());
    }

    public function testConstructorArray(): void
    {
        $email = new Email(
            ['from@example.com' => 'From name'],
            ['a@example.com' => "A", 'b@example.com' => "B", 'c@example.com', 'd@example.com'],
            'subject',
            '<p>body</p>',
            ['cca@example.com' => 'CCA', 'ccb@example.com' => "CCB", 'ccc@example.com', 'ccd@example.com'],
            ['bcca@example.com' => 'BCCA', 'bccb@example.com' => "BCCB", 'bccc@example.com', 'bccd@example.com'],
            'bounce@example.com'
        );
        $this->assertCount(1, $email->getFrom());
        $this->assertSame('from@example.com', $email->getFrom()[0]->getAddress());
        $this->assertSame('From name', $email->getFrom()[0]->getName());
        $this->assertCount(4, $email->getTo());
        $this->assertSame('a@example.com', $email->getTo()[0]->getAddress());
        $this->assertSame('A', $email->getTo()[0]->getName());
        $this->assertSame('b@example.com', $email->getTo()[1]->getAddress());
        $this->assertSame('B', $email->getTo()[1]->getName());
        $this->assertSame('c@example.com', $email->getTo()[2]->getAddress());
        $this->assertSame('', $email->getTo()[2]->getName());
        $this->assertCount(4, $email->getCC());
        $this->assertEquals('cca@example.com', $email->getCC()[0]->getAddress());
        $this->assertEquals('CCA', $email->getCC()[0]->getName());
        $this->assertEquals('ccb@example.com', $email->getCC()[1]->getAddress());
        $this->assertEquals('CCB', $email->getCC()[1]->getName());
        $this->assertEquals('ccc@example.com', $email->getCC()[2]->getAddress());
        $this->assertEquals('', $email->getCC()[2]->getName());
        $this->assertEquals('ccd@example.com', $email->getCC()[3]->getAddress());
        $this->assertEquals('', $email->getCC()[2]->getName());
        $this->assertCount(4, $email->getBCC());
        $this->assertEquals('bcca@example.com', $email->getBCC()[0]->getAddress());
        $this->assertEquals('BCCA', $email->getBCC()[0]->getName());
        $this->assertEquals('bccb@example.com', $email->getBCC()[1]->getAddress());
        $this->assertEquals('BCCB', $email->getBCC()[1]->getName());
        $this->assertEquals('bccc@example.com', $email->getBCC()[2]->getAddress());
        $this->assertEquals('', $email->getBCC()[2]->getName());
        $this->assertEquals('bccd@example.com', $email->getBCC()[3]->getAddress());
        $this->assertEquals('', $email->getBCC()[2]->getName());
    }

    public function testSetBody(): void
    {
        $email = new Email();
        $email->setBody('<p>body</p>');
        $this->assertSame('<p>body</p>', $email->getHtmlBody());
    }

    public function testSetFrom(): void
    {
        $email = new Email();
        $email->setFrom('from@example.com');
        $this->assertCount(1, $email->getFrom());
        $this->assertSame('from@example.com', $email->getFrom()[0]->getAddress());
    }

    public function testSender(): void
    {
        $email = new Email();
        $email->setSender('sender@example.com');
        $this->assertSame('sender@example.com', $email->getSender()->getAddress());
    }

    public function testSetTo(): void
    {
        $email = new Email();
        $email->setTo('to@example.com');
        $this->assertCount(1, $email->getTo());
        $this->assertSame('to@example.com', $email->getTo()[0]->getAddress());
    }

    public function testSetReplyTo(): void
    {
        $email = new Email();
        $email->setReplyTo('reply-to@example.com');
        $this->assertCount(1, $email->getReplyTo());
        $this->assertSame('reply-to@example.com', $email->getReplyTo()[0]->getAddress());
    }

    public function testSetSubject(): void
    {
        $email = new Email();
        $email->setSubject('my subject');
        $this->assertSame('my subject', $email->getSubject());
    }

    public function testSetReturnPath(): void
    {
        $email = new Email();
        $email->setReturnPath('return-path@example.com');
        $this->assertSame('return-path@example.com', $email->getReturnPath()->getAddress());
    }

    public function testSetPriority(): void
    {
        $email = new Email();
        // Intentionally set above 5 to test that Symfony\Component\Mime\Email->priority() is being called
        $email->setPriority(7);
        $this->assertSame(5, $email->getPriority());
    }

    public function testAdminEmailApplied()
    {
        Email::config()->set('admin_email', 'admin@example.com');
        $email = new Email();
        $this->assertCount(1, $email->getFrom());
        $this->assertSame('admin@example.com', $email->getFrom()[0]->getAddress());
    }

    public function testDataWithArray(): void
    {
        $email = new Email();
        $this->assertSame(true, $email->getData()->IsEmail);
        $this->assertSame(Director::absoluteBaseURL(), $email->getData()->BaseURL);
        $email->setData(['Lorem' => 'Ipsum']);
        $this->assertSame(true, $email->getData()->IsEmail);
        $this->assertSame(Director::absoluteBaseURL(), $email->getData()->BaseURL);
        $this->assertSame('Ipsum', $email->getData()->Lorem);
        $email->addData('Content', 'My content');
        $this->assertSame(true, $email->getData()->IsEmail);
        $this->assertSame(Director::absoluteBaseURL(), $email->getData()->BaseURL);
        $this->assertSame('Ipsum', $email->getData()->Lorem);
        $this->assertSame('My content', $email->getData()->Content);
    }

    public function testDataWithModelData(): void
    {
        $email = new Email();
        $model = new ModelData();
        $model->ABC = 'XYZ';
        $email->setData($model);
        $data = $email->getData();
        $this->assertSame('XYZ', $data->ABC);
        $this->assertSame(true, $data->IsEmail);
        $this->assertSame(Director::absoluteBaseURL(), $data->BaseURL);
        $member = new Member();
        $member->FirstName = 'First Name';
        $email->setData($member);
        $this->assertSame($member->FirstName, $email->getData()->FirstName);
        $email->addData('Test', 'Test value');
        $this->assertEquals('Test value', $email->getData()->Test);
        $email->removeData('Test');
        $this->assertNull($email->getData()->Test);
    }

    public function testHTMLTemplate(): void
    {
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

    public function testPlainTemplate(): void
    {
        $email = new Email();
        $this->assertEmpty($email->getPlainTemplate());
        $email->setPlainTemplate('MyTemplate');
        $this->assertEquals('MyTemplate', $email->getPlainTemplate());
    }

    public function testRerender(): void
    {
        $email = new Email();
        $email->setPlainTemplate($this->getTemplateClass('PlainTemplate'));
        $email->setData([
            'EmailContent' => '<p>my content</p>',
        ]);
        $email->send();
        $this->assertStringContainsString('&lt;p&gt;my content&lt;/p&gt;', $email->getHtmlBody());

        // Ensure setting data causes html() to be updated
        $email->setData([
            'EmailContent' => '<p>your content</p>'
        ]);
        $email->send();
        $this->assertStringContainsString('&lt;p&gt;your content&lt;/p&gt;', $email->getHtmlBody());

        // Ensure removing data causes html() to be updated
        $email->removeData('EmailContent');
        $email->send();
        $this->assertStringNotContainsString('&lt;p&gt;your content&lt;/p&gt;', $email->getHtmlBody());

        // Ensure adding data causes html() to be updated
        $email->addData([
            'EmailContent' => '<p>their content</p>'
        ]);
        $email->send();
        $this->assertStringContainsString('&lt;p&gt;their content&lt;/p&gt;', $email->getHtmlBody());
    }

    public function testRenderPlainOnly(): void
    {
        $email = new Email();
        $email->setData([
            'EmailContent' => 'test content',
        ]);
        $email->sendPlain();
        $this->assertSame('test content', $email->getTextBody());
    }

    public function testMultipleEmailSends(): void
    {
        $email = new Email(to: 'to@example.com');
        $email->setData([
            'EmailContent' => '<p>Test</p>',
        ]);
        $this->assertSame(null, $email->getHtmlBody());
        $this->assertSame(null, $email->getTextBody());
        $email->send();
        $this->assertStringContainsString('&lt;p&gt;Test&lt;/p&gt;', $email->getHtmlBody());
        $this->assertSame('Test', $email->getTextBody());
        //send again
        $email->send();
        $this->assertStringContainsString('&lt;p&gt;Test&lt;/p&gt;', $email->getHtmlBody());
        $this->assertSame('Test', $email->getTextBody());
    }

    public function testGetDefaultFrom(): void
    {
        $email = new Email();
        $class = new \ReflectionClass(Email::class);
        $method = $class->getMethod('getDefaultFrom');
        $method->setAccessible(true);

        // default to no-reply@mydomain.com if admin_email config not set
        Email::config()->set('admin_email', null);
        $this->assertSame('no-reply@www.mysite.com', $method->invokeArgs($email, []));

        // default to no-reply@mydomain.com if admin_email config is misconfigured
        Email::config()->set('admin_email', 123);
        $this->assertSame('no-reply@www.mysite.com', $method->invokeArgs($email, []));

        // use admin_email config string syntax
        Email::config()->set('admin_email', 'myadmin@somewhere.com');
        $this->assertSame('myadmin@somewhere.com', $method->invokeArgs($email, []));
        $this->assertTrue(true);

        // use admin_email config array syntax
        Email::config()->set('admin_email', ['anotheradmin@somewhere.com' => 'Admin-email']);
        $this->assertSame(
            ['anotheradmin@somewhere.com' => 'Admin-email'],
            $method->invokeArgs($email, [])
        );
        $this->assertTrue(true);
    }

    #[DataProvider('provideCreateAddressArray')]
    public function testCreateAddressArray(string|array $address, string $name, array $expected): void
    {
        $method = new \ReflectionMethod(Email::class, 'createAddressArray');
        $method->setAccessible(true);
        $obj = new Email();
        $actual = $method->invoke($obj, $address, $name);
        for ($i = 0; $i < count($expected); $i++) {
            $this->assertSame($expected[$i]->getAddress(), $actual[$i]->getAddress());
            $this->assertSame($expected[$i]->getName(), $actual[$i]->getName());
        }
    }

    public static function provideCreateAddressArray(): array
    {
        return [
            [
                'my@email.com',
                'My name',
                [
                    new Address('my@email.com', 'My name'),
                ],
            ],
            [
                [
                    'my@email.com' => 'My name',
                    'other@email.com' => 'My other name',
                    'no-name@email.com'
                ],
                '',
                [
                    new Address('my@email.com', 'My name'),
                    new Address('other@email.com', 'My other name'),
                    new Address('no-name@email.com', ''),
                ],
            ]
        ];
    }
}
