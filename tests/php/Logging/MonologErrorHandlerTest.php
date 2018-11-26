<?php

namespace SilverStripe\Logging\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Logging\MonologErrorHandler;

class MonologErrorHandlerTest extends SapphireTest
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /No Logger property passed to MonologErrorHandler/
     */
    public function testStartThrowsExceptionWithoutLoggerDefined()
    {
        $handler = new MonologErrorHandler();
        $handler->start();
    }
}
