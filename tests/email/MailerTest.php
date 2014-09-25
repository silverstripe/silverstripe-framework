<?php

/**
 * @package framework
 * @subpackage tests
 */
class MailerTest extends SapphireTest {

	/**
	 * Replaces ----=_NextPart_214491627619 placeholders with ----=_NextPart_000000000000
	 *
	 * @param string $input
	 * @return string
	 */
	protected function normaliseDivisions($input) {
		return preg_replace('/----=_NextPart_\d+/m', '----=_NextPart_000000000000', $input);
	}

	/**
	 * Test plain text messages
	 */
	public function testSendPlain() {
		$mailer = new MailerTest_MockMailer();

		// Test with default encoding
		$testMessage = "The majority of the answers so far are saying that private methods are implementation details ".
			"which don't (or at least shouldn't) matter so long as the public interface is well-tested and ".
			"working. That's absolutely correct if your only purpose for testing is to guarantee that the ".
			"public interface works.";
		list($to, $subjectEncoded, $fullBody, $headersEncoded, $bounceAddress) = $mailer->sendPlain(
			'<email@silverstripe.com>',
			'tom@jones <tom@silverstripe.com>',
			"What is the <purpose> of testing?",
			$testMessage,
			null,
			array('CC' => 'admin@silverstripe.com', 'bcc' => 'andrew@thing.com')
		);

		$this->assertEquals('email@silverstripe.com', $to);
		$this->assertEquals('=?UTF-8?B?V2hhdCBpcyB0aGUgPHB1cnBvc2U+IG9mIHRlc3Rpbmc/?=', $subjectEncoded);
		$this->assertEquals('=?UTF-8?B?'.  base64_encode('What is the <purpose> of testing?').'?=', $subjectEncoded);

		$this->assertEquals(<<<PHP
The majority of the answers so far are saying that private methods are impl=
ementation details which don't (or at least shouldn't) matter so long as th=
e public interface is well-tested and working. That's absolutely correct if=
 your only purpose for testing is to guarantee that the public interface wo=
rks.
PHP
			,
			Convert::nl2os($fullBody)
		);
		$this->assertEquals($testMessage, quoted_printable_decode($fullBody));
		$this->assertEquals(<<<PHP
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: quoted-printable
From: tomjones <tom@silverstripe.com>
X-Mailer: SilverStripe Mailer - version 2006.06.21
X-Priority: 3
Bcc: andrew@thing.com
Cc: admin@silverstripe.com

PHP
			,
			Convert::nl2os($headersEncoded)
		);
		$this->assertEquals('tom@silverstripe.com', $bounceAddress);

		// Test override bounce email and alternate encoding
		$mailer->setBounceEmail('bounce@silverstripe.com');
		$mailer->setMessageEncoding('base64');
		list($to, $subjectEncoded, $fullBody, $headersEncoded, $bounceAddress) = $mailer->sendPlain(
			'<email@silverstripe.com>',
			'tom@jones <tom@silverstripe.com>',
			"What is the <purpose> of testing?",
			$testMessage,
			null,
			array('CC' => 'admin@silverstripe.com', 'bcc' => 'andrew@thing.com')
		);

		$this->assertEquals('bounce@silverstripe.com', $bounceAddress);
		$this->assertEquals(<<<PHP
VGhlIG1ham9yaXR5IG9mIHRoZSBhbnN3ZXJzIHNvIGZhciBhcmUgc2F5aW5n
IHRoYXQgcHJpdmF0ZSBtZXRob2RzIGFyZSBpbXBsZW1lbnRhdGlvbiBkZXRh
aWxzIHdoaWNoIGRvbid0IChvciBhdCBsZWFzdCBzaG91bGRuJ3QpIG1hdHRl
ciBzbyBsb25nIGFzIHRoZSBwdWJsaWMgaW50ZXJmYWNlIGlzIHdlbGwtdGVz
dGVkIGFuZCB3b3JraW5nLiBUaGF0J3MgYWJzb2x1dGVseSBjb3JyZWN0IGlm
IHlvdXIgb25seSBwdXJwb3NlIGZvciB0ZXN0aW5nIGlzIHRvIGd1YXJhbnRl
ZSB0aGF0IHRoZSBwdWJsaWMgaW50ZXJmYWNlIHdvcmtzLg==

PHP
			,
			Convert::nl2os($fullBody)
		);
		$this->assertEquals($testMessage, base64_decode($fullBody));
	}

	/**
	 * Test HTML messages
	 */
	public function testSendHTML() {
		$mailer = new MailerTest_MockMailer();

		// Test with default encoding
		$testMessageHTML = "<p>The majority of the <i>answers</i> so far are saying that private methods are " .
			"implementation details which don&#39;t (<a href=\"http://www.google.com\">or at least shouldn&#39;t</a>) ".
			"matter so long as the public interface is well-tested &amp; working</p> ".
			"<p>That&#39;s absolutely correct if your only purpose for testing is to guarantee that the ".
			"public interface works.</p>";
		$testMessagePlain = Convert::xml2raw($testMessageHTML);
		$this->assertTrue(stripos($testMessagePlain, '&#') === false);
		list($to, $subjectEncoded, $fullBody, $headersEncoded, $bounceAddress) = $mailer->sendHTML(
			'<email@silverstripe.com>',
			'tom@jones <tom@silverstripe.com>',
			"What is the <purpose> of testing?",
			$testMessageHTML,
			null,
			array('CC' => 'admin@silverstripe.com', 'bcc' => 'andrew@thing.com')
		);

		$this->assertEquals('email@silverstripe.com', $to);
		$this->assertEquals('=?UTF-8?B?V2hhdCBpcyB0aGUgPHB1cnBvc2U+IG9mIHRlc3Rpbmc/?=', $subjectEncoded);
		$this->assertEquals('=?UTF-8?B?'.  base64_encode('What is the <purpose> of testing?').'?=', $subjectEncoded);

		$this->assertEquals(Convert::nl2os(<<<PHP

This is a multi-part message in MIME format.

------=_NextPart_000000000000
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: quoted-printable

The majority of the answers so far are saying that private methods are impl=
ementation details which don't (or at least shouldn't[http://www.google.com=
]) matter so long as the public interface is well-tested & working=0A=0A=0A=
=0AThat's absolutely correct if your only purpose for testing is to guarant=
ee that the public interface works.

------=_NextPart_000000000000
Content-Type: text/html; charset=utf-8
Content-Transfer-Encoding: quoted-printable

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">=0A<HTML><HEA=
D>=0A<META http-equiv=3D"Content-Type" content=3D"text/html; charset=3Dutf-=
8">=0A<STYLE type=3D"text/css"></STYLE>=0A=0A</HEAD>=0A<BODY bgColor=3D"#ff=
ffff">=0A<p>The majority of the <i>answers</i> so far are saying that priva=
te methods are implementation details which don&#39;t (<a href=3D"http://ww=
w.google.com">or at least shouldn&#39;t</a>) matter so long as the public i=
nterface is well-tested &amp; working</p> <p>That&#39;s absolutely correct =
if your only purpose for testing is to guarantee that the public interface =
works.</p>=0A</BODY>=0A</HTML>
------=_NextPart_000000000000--
PHP
			),
			Convert::nl2os($this->normaliseDivisions($fullBody))
		);
		// Check that the messages exist in the output
		$this->assertTrue(stripos($fullBody, quoted_printable_encode($testMessagePlain)) !== false);
		$this->assertEquals(<<<PHP
MIME-Version: 1.0
Content-Type: multipart/alternative; boundary="----=_NextPart_000000000000"
Content-Transfer-Encoding: 7bit
From: tomjones <tom@silverstripe.com>
X-Mailer: SilverStripe Mailer - version 2006.06.21
X-Priority: 3
Bcc: andrew@thing.com
Cc: admin@silverstripe.com

PHP
			,
			Convert::nl2os($this->normaliseDivisions($headersEncoded))
		);
		$this->assertEquals('tom@silverstripe.com', $bounceAddress);

		// Test override bounce email and alternate encoding
		$mailer->setBounceEmail('bounce@silverstripe.com');
		$mailer->setMessageEncoding('base64');
		list($to, $subjectEncoded, $fullBody, $headersEncoded, $bounceAddress) = $mailer->sendHTML(
			'<email@silverstripe.com>',
			'tom@jones <tom@silverstripe.com>',
			"What is the <purpose> of testing?",
			$testMessageHTML,
			null,
			array('CC' => 'admin@silverstripe.com', 'bcc' => 'andrew@thing.com')
		);

		$this->assertEquals('bounce@silverstripe.com', $bounceAddress);
		$this->assertEquals(<<<PHP

This is a multi-part message in MIME format.

------=_NextPart_000000000000
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: base64

VGhlIG1ham9yaXR5IG9mIHRoZSBhbnN3ZXJzIHNvIGZhciBhcmUgc2F5aW5n
IHRoYXQgcHJpdmF0ZSBtZXRob2RzIGFyZSBpbXBsZW1lbnRhdGlvbiBkZXRh
aWxzIHdoaWNoIGRvbid0IChvciBhdCBsZWFzdCBzaG91bGRuJ3RbaHR0cDov
L3d3dy5nb29nbGUuY29tXSkgbWF0dGVyIHNvIGxvbmcgYXMgdGhlIHB1Ymxp
YyBpbnRlcmZhY2UgaXMgd2VsbC10ZXN0ZWQgJiB3b3JraW5nCgoKClRoYXQn
cyBhYnNvbHV0ZWx5IGNvcnJlY3QgaWYgeW91ciBvbmx5IHB1cnBvc2UgZm9y
IHRlc3RpbmcgaXMgdG8gZ3VhcmFudGVlIHRoYXQgdGhlIHB1YmxpYyBpbnRl
cmZhY2Ugd29ya3Mu


------=_NextPart_000000000000
Content-Type: text/html; charset=utf-8
Content-Transfer-Encoding: base64

PCFET0NUWVBFIEhUTUwgUFVCTElDICItLy9XM0MvL0RURCBIVE1MIDQuMCBU
cmFuc2l0aW9uYWwvL0VOIj4KPEhUTUw+PEhFQUQ+CjxNRVRBIGh0dHAtZXF1
aXY9IkNvbnRlbnQtVHlwZSIgY29udGVudD0idGV4dC9odG1sOyBjaGFyc2V0
PXV0Zi04Ij4KPFNUWUxFIHR5cGU9InRleHQvY3NzIj48L1NUWUxFPgoKPC9I
RUFEPgo8Qk9EWSBiZ0NvbG9yPSIjZmZmZmZmIj4KPHA+VGhlIG1ham9yaXR5
IG9mIHRoZSA8aT5hbnN3ZXJzPC9pPiBzbyBmYXIgYXJlIHNheWluZyB0aGF0
IHByaXZhdGUgbWV0aG9kcyBhcmUgaW1wbGVtZW50YXRpb24gZGV0YWlscyB3
aGljaCBkb24mIzM5O3QgKDxhIGhyZWY9Imh0dHA6Ly93d3cuZ29vZ2xlLmNv
bSI+b3IgYXQgbGVhc3Qgc2hvdWxkbiYjMzk7dDwvYT4pIG1hdHRlciBzbyBs
b25nIGFzIHRoZSBwdWJsaWMgaW50ZXJmYWNlIGlzIHdlbGwtdGVzdGVkICZh
bXA7IHdvcmtpbmc8L3A+IDxwPlRoYXQmIzM5O3MgYWJzb2x1dGVseSBjb3Jy
ZWN0IGlmIHlvdXIgb25seSBwdXJwb3NlIGZvciB0ZXN0aW5nIGlzIHRvIGd1
YXJhbnRlZSB0aGF0IHRoZSBwdWJsaWMgaW50ZXJmYWNlIHdvcmtzLjwvcD4K
PC9CT0RZPgo8L0hUTUw+

------=_NextPart_000000000000--
PHP
			,
			Convert::nl2os($this->normaliseDivisions($fullBody))
		);

		// Check that the text message version is somewhere in there
		$this->assertTrue(stripos($fullBody, chunk_split(base64_encode($testMessagePlain), 60)) !== false);
	}
}

/**
 * Mocks the sending of emails without actually sending anything
 */
class MailerTest_MockMailer extends Mailer implements TestOnly {
	protected function email($to, $subjectEncoded, $fullBody, $headersEncoded, $bounceAddress) {
		return array($to, $subjectEncoded, $fullBody, $headersEncoded, $bounceAddress);
	}
}
