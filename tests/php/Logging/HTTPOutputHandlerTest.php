<?php

namespace SilverStripe\Logging\Tests;

use Monolog\Handler\HandlerInterface;
use ReflectionMethod;
use ReflectionProperty;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Logging\DebugViewFriendlyErrorFormatter;
use SilverStripe\Logging\DetailedErrorFormatter;
use SilverStripe\Logging\HTTPOutputHandler;

class HTTPOutputHandlerTest extends SapphireTest
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Director::isDev()) {
            $this->markTestSkipped('This test only runs in dev mode');
        }
    }

    public function testGetFormatter()
    {
        $handler = new HTTPOutputHandler();

        $detailedFormatter = new DetailedErrorFormatter();
        $friendlyFormatter = new DebugViewFriendlyErrorFormatter();

        // Handler without CLIFormatter chooses correct formatter
        $handler->setDefaultFormatter($detailedFormatter);
        $this->assertInstanceOf(DetailedErrorFormatter::class, $handler->getFormatter());
        $this->assertInstanceOf(DetailedErrorFormatter::class, $handler->getDefaultFormatter());

        // Handler with CLIFormatter should return that, although default handler is still accessible
        $handler->setCLIFormatter($friendlyFormatter);
        $this->assertInstanceOf(DebugViewFriendlyErrorFormatter::class, $handler->getFormatter());
        $this->assertInstanceOf(DetailedErrorFormatter::class, $handler->getDefaultFormatter());
    }

    /**
     * Covers `#dev-logging` section in logging.yml
     */
    public function testDevConfig()
    {
        /** @var HTTPOutputHandler $handler */
        $handler = Injector::inst()->get(HandlerInterface::class);
        $this->assertInstanceOf(HTTPOutputHandler::class, $handler);

        // Test only default formatter is set, but CLI specific formatter is left out
        $this->assertNull($handler->getCLIFormatter());
        $this->assertInstanceOf(DetailedErrorFormatter::class, $handler->getDefaultFormatter());
        $this->assertInstanceOf(DetailedErrorFormatter::class, $handler->getFormatter());
    }

    public function provideShouldShowError()
    {
        $provide = [];
        // See https://www.php.net/manual/en/errorfunc.constants.php
        $errors = [
            E_ERROR,
            E_WARNING,
            E_PARSE,
            E_NOTICE,
            E_CORE_ERROR,
            E_CORE_WARNING,
            E_COMPILE_ERROR,
            E_COMPILE_WARNING,
            E_USER_ERROR,
            E_USER_WARNING,
            E_USER_NOTICE,
            E_RECOVERABLE_ERROR,
            E_DEPRECATED,
            E_USER_DEPRECATED,
        ];
        foreach ($errors as $errorCode) {
            // Display all errors regardless of type in this scenario
            $provide[] = [
                'errorCode' => $errorCode,
                'triggeringError' => true,
                'isCli' => true,
                'shouldShow' => true,
                'expected' => true,
            ];
            // Don't display E_USER_DEPRECATED that we're triggering if shouldShow is false
            $provide[] = [
                'errorCode' => $errorCode,
                'triggeringError' => true,
                'isCli' => true,
                'shouldShow' => false,
                'expected' => ($errorCode !== E_USER_DEPRECATED) || false
            ];
            // Display all errors regardless of type in this scenario
            $provide[] = [
                'errorCode' => $errorCode,
                'triggeringError' => true,
                'isCli' => false,
                'shouldShow' => true,
                'expected' => true
            ];
            // Don't display E_USER_DEPRECATED that we're triggering if shouldShow is false
            $provide[] = [
                'errorCode' => $errorCode,
                'triggeringError' => true,
                'isCli' => false,
                'shouldShow' => false,
                'expected' => ($errorCode !== E_USER_DEPRECATED) || false
            ];
            // All of the below have triggeringError set to false, in which case
            // all errors should be displayed.
            $provide[] = [
                'errorCode' => $errorCode,
                'triggeringError' => false,
                'isCli' => true,
                'shouldShow' => true,
                'expected' => true
            ];
            $provide[] = [
                'errorCode' => $errorCode,
                'triggeringError' => false,
                'isCli' => false,
                'shouldShow' => true,
                'expected' => true
            ];
            $provide[] = [
                'errorCode' => $errorCode,
                'triggeringError' => false,
                'isCli' => true,
                'shouldShow' => false,
                'expected' => true
            ];
            $provide[] = [
                'errorCode' => $errorCode,
                'triggeringError' => false,
                'isCli' => false,
                'shouldShow' => false,
                'expected' => true
            ];
        }
        return $provide;
    }

    /**
     * @dataProvider provideShouldShowError
     */
    public function testShouldShowError(
        int $errorCode,
        bool $triggeringError,
        bool $isCli,
        bool $shouldShow,
        bool $expected
    ) {
        $reflectionShouldShow = new ReflectionMethod(HTTPOutputHandler::class, 'shouldShowError');
        $reflectionShouldShow->setAccessible(true);
        $reflectionTriggeringError = new ReflectionProperty(Deprecation::class, 'isTriggeringError');
        $reflectionTriggeringError->setAccessible(true);

        $cliShouldShowOrig = Deprecation::shouldShowForCli();
        $httpShouldShowOrig = Deprecation::shouldShowForHttp();
        $triggeringErrorOrig = Deprecation::isTriggeringError();
        // Set the relevant item using $shouldShow, and the other always as true
        // to show that these don't interfere with each other
        if ($isCli) {
            Deprecation::setShouldShowForCli($shouldShow);
            Deprecation::setShouldShowForHttp(true);
        } else {
            Deprecation::setShouldShowForCli(true);
            Deprecation::setShouldShowForHttp($shouldShow);
        }
        $reflectionTriggeringError->setValue($triggeringError);

        $mockHandler = $this->getMockBuilder(HTTPOutputHandler::class)->onlyMethods(['isCli'])->getMock();
        $mockHandler->method('isCli')->willReturn($isCli);

        $result = $reflectionShouldShow->invoke($mockHandler, $errorCode);
        $this->assertSame($expected, $result);

        Deprecation::setShouldShowForCli($cliShouldShowOrig);
        Deprecation::setShouldShowForHttp($httpShouldShowOrig);
        $reflectionTriggeringError->setValue($triggeringErrorOrig);
    }
}
