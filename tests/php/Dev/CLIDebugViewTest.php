<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\CliDebugView;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Tests\DebugViewTest\ObjectWithDebug;

class CLIDebugViewTest extends SapphireTest
{
    protected $caller = null;

    protected function setUp()
    {
        parent::setUp();

        $this->caller = [
            'line' => 17,
            'file' => __FILE__,
            'args' => [],
            'type' => '->',
            'class' => __CLASS__,
            'function' => __FUNCTION__,
        ];
    }

    public function testDebugVariable()
    {
        $view = new CliDebugView();
        $this->assertEquals(
            <<<EOS
Debug (CLIDebugViewTest.php:17 - SilverStripe\Dev\Tests\CLIDebugViewTest::setUp())
string


EOS
            ,
            $view->debugVariable('string', $this->caller)
        );

        $this->assertEquals(
            <<<EOS
Debug (CLIDebugViewTest.php:17 - SilverStripe\Dev\Tests\CLIDebugViewTest::setUp())
key = value
another = text



EOS
            ,
            $view->debugVariable([ 'key' => 'value', 'another' => 'text' ], $this->caller)
        );

        $this->assertEquals(
            <<<EOS
Debug (CLIDebugViewTest.php:17 - SilverStripe\Dev\Tests\CLIDebugViewTest::setUp())
SilverStripe\Dev\Tests\DebugViewTest\ObjectWithDebug::debug() custom content


EOS
            ,
            $view->debugVariable(new ObjectWithDebug(), $this->caller)
        );
    }
}
