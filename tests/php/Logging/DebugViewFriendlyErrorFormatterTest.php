<?php declare(strict_types = 1);

namespace SilverStripe\Logging\Tests;

use PHPUnit_Framework_MockObject_MockObject;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Logging\DebugViewFriendlyErrorFormatter;

class DebugViewFriendlyErrorFormatterTest extends SapphireTest
{
    protected function setUp()
    {
        parent::setUp();
        Email::config()->set('admin_email', 'testy@mctest.face');
    }

    public function testFormatPassesRecordCodeToOutput()
    {
        /** @var DebugViewFriendlyErrorFormatter|PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->getMockBuilder(DebugViewFriendlyErrorFormatter::class)
            ->setMethods(['output'])
            ->getMock();

        $mock->expects($this->once())->method('output')->with(403)->willReturn('foo');
        $this->assertSame('foo', $mock->format(['code' => 403]));
    }

    public function testFormatPassesInstanceStatusCodeToOutputWhenNotProvidedByRecord()
    {
        /** @var DebugViewFriendlyErrorFormatter|PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->getMockBuilder(DebugViewFriendlyErrorFormatter::class)
            ->setMethods(['output'])
            ->getMock();

        $mock->setStatusCode(404);

        $mock->expects($this->once())->method('output')->with(404)->willReturn('foo');
        $this->assertSame('foo', $mock->format(['notacode' => 'bar']));
    }

    public function testFormatBatch()
    {
        $records = [
            ['message' => 'bar'],
            ['open' => 'sausage'],
            ['horse' => 'caballo'],
        ];

        /** @var DebugViewFriendlyErrorFormatter|PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->getMockBuilder(DebugViewFriendlyErrorFormatter::class)
            ->setMethods(['format'])
            ->getMock();

        $mock->expects($this->exactly(3))
            ->method('format')
            ->willReturn('foo');

        $this->assertSame('foofoofoo', $mock->formatBatch($records));
    }

    public function testOutput()
    {
        $formatter = new DebugViewFriendlyErrorFormatter();
        $formatter->setTitle("There has been an error");
        $formatter->setBody("The website server has not been able to respond to your request");

        $expected = <<<TEXT
WEBSITE ERROR
There has been an error
-----------------------
The website server has not been able to respond to your request

Contact an administrator: testy [at] mctest [dot] face


TEXT
        ;

        $this->assertEquals($expected, $formatter->output(404));
    }

    public function testOutputReturnsTitleWhenRequestIsAjax()
    {
        // Mock an AJAX request
        Injector::inst()->registerService(new HTTPRequest('GET', '', ['ajax' => true]));

        $formatter = new DebugViewFriendlyErrorFormatter();
        $formatter->setTitle('The Diary of Anne Frank');

        $this->assertSame('The Diary of Anne Frank', $formatter->output(200));
    }
}
