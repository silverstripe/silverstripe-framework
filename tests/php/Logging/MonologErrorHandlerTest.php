<?php

namespace SilverStripe\Logging\Tests;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Logging\MonologErrorHandler;
use SilverStripe\Dev\Deprecation;

class MonologErrorHandlerTest extends SapphireTest
{
    public function testStartThrowsExceptionWithoutLoggerDefined()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/No Logger properties passed to MonologErrorHandler/');
        $handler = new MonologErrorHandler();
        $handler->start();
    }

    public function testSetLoggerResetsStack()
    {
        if (Deprecation::isEnabled()) {
            $this->markTestSkipped('Test calls deprecated code');
        }
        /** @var LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $handler = new MonologErrorHandler();
        $handler->pushLogger($logger)->pushLogger($logger);
        $this->assertCount(2, $handler->getLoggers(), 'Loggers are pushed to the stack');

        $handler->setLoggers([]);
        $this->assertCount(0, $handler->getLoggers(), 'setLoggers overwrites all configured loggers');
    }
}
