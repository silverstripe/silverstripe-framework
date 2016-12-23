<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\DebugView;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Tests\DebugViewTest\ObjectWithDebug;

class DebugViewTest extends SapphireTest
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
        $view = new DebugView();
        $this->assertEquals(
            <<<EOS
<div style="background-color: white; text-align: left;">
<hr>
<h3>Debug <span style="font-size: 65%">(DebugViewTest.php:17 - SilverStripe\Dev\Tests\DebugViewTest::setUp())</span>
</h3>
<pre style="font-family: Courier new, serif">string</pre>
</div>
EOS
            ,
            $view->debugVariable('string', $this->caller)
        );

        $this->assertEquals(
            <<<EOS
<div style="background-color: white; text-align: left;">
<hr>
<h3>Debug <span style="font-size: 65%">(DebugViewTest.php:17 - SilverStripe\Dev\Tests\DebugViewTest::setUp())</span>
</h3>
<ul>
<li>key = <pre style="font-family: Courier new, serif">value</pre>
</li>
<li>another = <pre style="font-family: Courier new, serif">text</pre>
</li>
</ul>
</div>
EOS
            ,
            $view->debugVariable([ 'key' => 'value', 'another' => 'text' ], $this->caller)
        );

        $this->assertEquals(
            <<<EOS
<div style="background-color: white; text-align: left;">
<hr>
<h3>Debug <span style="font-size: 65%">(DebugViewTest.php:17 - SilverStripe\Dev\Tests\DebugViewTest::setUp())</span>
</h3>
SilverStripe\Dev\Tests\DebugViewTest\ObjectWithDebug::debug() custom content</div>
EOS
            ,
            $view->debugVariable(new ObjectWithDebug(), $this->caller)
        );
    }
}
