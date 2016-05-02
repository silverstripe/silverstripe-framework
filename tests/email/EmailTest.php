<?php
/**
 * @package framework
 * @subpackage tests
 */
class EmailTest extends SapphireTest {

	public function testAttachFiles() {
		$email = new Email();

		$email->attachFileFromString('foo bar', 'foo.txt', 'text/plain');
		$email->attachFile(__DIR__ . '/fixtures/attachment.txt', null, 'text/plain');

		$this->assertEquals(
			array('contents'=>'foo bar', 'filename'=>'foo.txt', 'mimetype'=>'text/plain'),
			$email->attachments[0],
			'File is attached correctly from string'
		);

		$this->assertEquals(
			array('contents'=>'Hello, I\'m a text document.', 'filename'=>'attachment.txt', 'mimetype'=>'text/plain'),
			$email->attachments[1],
			'File is attached correctly from file'
		);
	}

	public function testCustomHeaders() {
		$email = new Email();

		$email->addCustomHeader('Cc', 'test1@example.com');
		$email->addCustomHeader('Bcc', 'test2@example.com');

		$this->assertEmpty(
			$email->customHeaders,
			'addCustomHeader() doesn\'t add Cc and Bcc headers'
		);

		$email->addCustomHeader('Reply-To', 'test1@example.com');
		$this->assertEquals(
			array('Reply-To' => 'test1@example.com'),
			$email->customHeaders,
			'addCustomHeader() adds headers'
		);

		$email->addCustomHeader('Reply-To', 'test2@example.com');
		$this->assertEquals(
			array('Reply-To' => 'test1@example.com, test2@example.com'),
			$email->customHeaders,
			'addCustomHeader() appends data to existing headers'
		);
	}

	public function testValidEmailAddress() {
		$validEmails = array('test@example.com', 'test-123@example.sub.com');
		$invalidEmails = array('foo.bar@', '@example.com', 'foo@');

		foreach ($validEmails as $email) {
			$this->assertEquals(
				$email,
				Email::validEmailAddress($email),
				'validEmailAddress() returns a valid email address'
			);
			$this->assertEquals(
				1,
				Email::is_valid_address($email),
				'is_valid_address() returns 1 for a valid email address'
			);
		}

		foreach ($invalidEmails as $email) {
			$this->assertFalse(
				Email::validEmailAddress($email),
				'validEmailAddress() returns false for an invalid email address'
			);
			$this->assertEquals(
				0,
				Email::is_valid_address($email),
				'is_valid_address() returns 0 for an invalid email address'
			);
		}
	}

	public function testObfuscate() {
		$emailAddress = 'test-1@example.com';

		$direction = Email::obfuscate($emailAddress, 'direction');
		$visible = Email::obfuscate($emailAddress, 'visible');
		$hex = Email::obfuscate($emailAddress, 'hex');

		$this->assertEquals(
			'<span class="codedirection">moc.elpmaxe@1-tset</span>',
			$direction,
			'obfuscate() correctly reverses the email direction'
		);
		$this->assertEquals(
			'test [dash] 1 [at] example [dot] com',
			$visible,
			'obfuscate() correctly obfuscates email characters'
		);
		$this->assertEquals(
			'&#x74;&#x65;&#x73;&#x74;&#x2d;&#x31;&#x40;&#x65;&#x78;&#x61;&#x6d;&#x70;'
				. '&#x6c;&#x65;&#x2e;&#x63;&#x6f;&#x6d;',
			$hex,
			'obfuscate() correctly returns hex representation of email'
		);
	}

	public function testSendPlain() {
		// Set custom $project - used in email headers
		global $project;
		$oldProject = $project;
		$project = 'emailtest';

		Injector::inst()->registerService(new EmailTest_Mailer(), 'Mailer');
		$email = new Email(
			'from@example.com',
			'to@example.com',
			'Test send plain',
			'Testing Email->sendPlain()',
			null,
			'cc@example.com',
			'bcc@example.com'
		);
		$email->attachFile(__DIR__ . '/fixtures/attachment.txt', null, 'text/plain');
		$email->addCustomHeader('foo', 'bar');
		$sent = $email->sendPlain(123);

		// Restore old project name after sending
		$project = $oldProject;

		$this->assertEquals('to@example.com', $sent['to']);
		$this->assertEquals('from@example.com', $sent['from']);
		$this->assertEquals('Test send plain', $sent['subject']);
		$this->assertEquals('Testing Email->sendPlain()', $sent['content']);
		$this->assertEquals(
			array(
				0 => array(
					'contents'=>'Hello, I\'m a text document.',
					'filename'=>'attachment.txt',
					'mimetype'=>'text/plain'
				)
			),
			$sent['files']
		);
		$this->assertEquals(
			array(
				'foo' => 'bar',
				'X-SilverStripeMessageID' => 'emailtest.123',
				'X-SilverStripeSite' => 'emailtest',
				'Cc' => 'cc@example.com',
				'Bcc' => 'bcc@example.com'
			),
			$sent['customheaders']
		);
	}

	public function testSendHTML() {
		// Set custom $project - used in email headers
		global $project;
		$oldProject = $project;
		$project = 'emailtest';

		Injector::inst()->registerService(new EmailTest_Mailer(), 'Mailer');
		$email = new Email(
			'from@example.com',
			'to@example.com',
			'Test send plain',
			'Testing Email->sendPlain()',
			null,
			'cc@example.com',
			'bcc@example.com'
		);
		$email->attachFile(__DIR__ . '/fixtures/attachment.txt', null, 'text/plain');
		$email->addCustomHeader('foo', 'bar');
		$sent = $email->send(123);

		// Restore old project name after sending
		$project = $oldProject;

		$this->assertEquals('to@example.com', $sent['to']);
		$this->assertEquals('from@example.com', $sent['from']);
		$this->assertEquals('Test send plain', $sent['subject']);
		$this->assertContains('Testing Email->sendPlain()', $sent['content']);
		$this->assertNull($sent['plaincontent']);
		$this->assertEquals(
			array(
				0 => array(
					'contents'=>'Hello, I\'m a text document.',
					'filename'=>'attachment.txt',
					'mimetype'=>'text/plain'
				)
			),
			$sent['files']
		);
		$this->assertEquals(
			array(
				'foo' => 'bar',
				'X-SilverStripeMessageID' => 'emailtest.123',
				'X-SilverStripeSite' => 'emailtest',
				'Cc' => 'cc@example.com',
				'Bcc' => 'bcc@example.com'
			),
			$sent['customheaders']
		);
	}

}

class EmailTest_Mailer extends Mailer {

	public function sendHTML($to, $from, $subject, $htmlContent, $attachedFiles = false, $customheaders = false,
			$plainContent = false) {
		return array(
			'to' => $to,
			'from' => $from,
			'subject' => $subject,
			'content' => $htmlContent,
			'files' => $attachedFiles,
			'customheaders' => $customheaders,
			'plaincontent' => $plainContent
		);
	}

	public function sendPlain($to, $from, $subject, $plainContent, $attachedFiles = false, $customheaders = false) {
		return array(
			'to' => $to,
			'from' => $from,
			'subject' => $subject,
			'content' => $plainContent,
			'files' => $attachedFiles,
			'customheaders' => $customheaders
		);
	}

}
