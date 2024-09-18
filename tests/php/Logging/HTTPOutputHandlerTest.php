<?php

namespace SilverStripe\Logging\Tests;

use Monolog\Handler\HandlerInterface;
use ReflectionClass;
use ReflectionMethod;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Logging\DebugViewFriendlyErrorFormatter;
use SilverStripe\Logging\DetailedErrorFormatter;
use SilverStripe\Logging\HTTPOutputHandler;
use PHPUnit\Framework\Attributes\DataProvider;

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

    public static function provideShouldShowError()
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

    #[DataProvider('provideShouldShowError')]
    public function testShouldShowError(
        int $errorCode,
        bool $triggeringError,
        bool $isCli,
        bool $shouldShow,
        bool $expected
    ) {
        $reflectionShouldShow = new ReflectionMethod(HTTPOutputHandler::class, 'shouldShowError');
        $reflectionShouldShow->setAccessible(true);
        $reflectionDeprecation = new ReflectionClass(Deprecation::class);

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
        $reflectionDeprecation->setStaticPropertyValue('isTriggeringError', $triggeringError);

        $reflectionDirector = new ReflectionClass(Environment::class);
        $origIsCli = $reflectionDirector->getStaticPropertyValue('isCliOverride');
        $reflectionDirector->setStaticPropertyValue('isCliOverride', $isCli);

        try {
            $handler = new HTTPOutputHandler();
            $result = $reflectionShouldShow->invoke($handler, $errorCode);
            $this->assertSame($expected, $result);

            Deprecation::setShouldShowForCli($cliShouldShowOrig);
            Deprecation::setShouldShowForHttp($httpShouldShowOrig);
            $reflectionDeprecation->setStaticPropertyValue('isTriggeringError', $triggeringErrorOrig);
        } finally {
            $reflectionDirector->setStaticPropertyValue('isCliOverride', $origIsCli);
        }
    }
}
