<?php

namespace SilverStripe\Logging\Tests;

use SilverStripe\Control\Email\Email;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Logging\DebugViewFriendlyErrorFormatter;

class DebugViewFriendlyErrorFormatterTest extends SapphireTest
{
    public function setUp()
    {
        parent::setUp();
        Email::config()->set('admin_email', 'testy@mctest.face');
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
}
