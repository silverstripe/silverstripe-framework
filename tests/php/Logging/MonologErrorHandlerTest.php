<?php

namespace SilverStripe\Logging\Tests;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Logging\MonologErrorHandler;

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
        /** @var LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $handler = new MonologErrorHandler();
        $handler->pushLogger($logger)->pushLogger($logger);
        $this->assertCount(2, $handler->getLoggers(), 'Loggers are pushed to the stack');

        $handler->setLogger($logger);
        $this->assertCount(1, $handler->getLoggers(), 'setLogger resets stack and pushes');

        $handler->setLoggers([]);
        $this->assertCount(0, $handler->getLoggers(), 'setLoggers overwrites all configured loggers');
    }
}
