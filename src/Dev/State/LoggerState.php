<?php

namespace SilverStripe\Dev\State;

use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

/**
 * Disables any user configured loggers by pushing a NullHandler during PHPUnit tests.
 *
 * This is designed specifically for Monolog. If using another PSR-3 compatible logging package, this will
 * not do anything.
 */
class LoggerState implements TestState
{
    public function setUp(SapphireTest $test)
    {
        $userLogger = Injector::inst()->get(LoggerInterface::class);
        if ($userLogger && $userLogger instanceof Logger) {
            $userLogger->setHandlers([new NullHandler()]);
        }
    }

    public function tearDown(SapphireTest $test)
    {
        // noop
    }

    public function setUpOnce($class)
    {
        // noop
    }

    public function tearDownOnce($class)
    {
        // noop
    }
}
