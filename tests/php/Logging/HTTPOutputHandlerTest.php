<?php

namespace SilverStripe\Logging\Tests;

use Monolog\Handler\HandlerInterface;
use PhpParser\Node\Scalar\MagicConst\Dir;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Logging\DebugViewFriendlyErrorFormatter;
use SilverStripe\Logging\DetailedErrorFormatter;
use SilverStripe\Logging\HTTPOutputHandler;

class HTTPOutputHandlerTest extends SapphireTest
{
    public function setUp()
    {
        parent::setUp();
        if (!Director::is_cli()) {
            $this->markTestSkipped("This test only runs in CLI mode");
        }
        if (!Director::isDev()) {
            $this->markTestSkipped("This test only runs in dev mode");
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
}
