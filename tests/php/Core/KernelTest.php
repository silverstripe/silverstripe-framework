<?php

namespace SilverStripe\Core\Tests;

use BadMethodCallException;
use Exception;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use SilverStripe\Control\Director;
use SilverStripe\Control\Middleware\AllowedHostsMiddleware;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\ConfigLoader;
use SilverStripe\Core\CoreKernel;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorLoader;
use SilverStripe\Core\Kernel;
use SilverStripe\Dev\SapphireTest;

class KernelTest extends SapphireTest
{
    public function testNesting()
    {
        /** @var Kernel $kernel */
        $kernel = Injector::inst()->get(Kernel::class);

        /** @var CoreKernel $nested1 */
        $nested1 = $kernel->nest();
        Director::config()->set('alternate_base_url', '/mysite/');
        $this->assertEquals($kernel, $nested1->getNestedFrom());
        $this->assertEquals($nested1->getConfigLoader(), ConfigLoader::inst());
        $this->assertEquals($nested1->getInjectorLoader(), InjectorLoader::inst());
        $this->assertEquals(1, ConfigLoader::inst()->countManifests());
        $this->assertEquals(1, InjectorLoader::inst()->countManifests());

        // Re-nest
        $nested2 = $nested1->nest();

        // Nesting config / injector should increase this count
        Injector::nest();
        Config::nest();
        $this->assertEquals($nested2->getConfigLoader(), ConfigLoader::inst());
        $this->assertEquals($nested2->getInjectorLoader(), InjectorLoader::inst());
        $this->assertEquals(2, ConfigLoader::inst()->countManifests());
        $this->assertEquals(2, InjectorLoader::inst()->countManifests());
        Director::config()->set('alternate_base_url', '/anothersite/');

        // Nesting always resets sub-loaders to 1
        $nested2->nest();
        $this->assertEquals(1, ConfigLoader::inst()->countManifests());
        $this->assertEquals(1, InjectorLoader::inst()->countManifests());

        // Calling ->activate() on a previous kernel restores
        $nested1->activate();
        $this->assertEquals($nested1->getConfigLoader(), ConfigLoader::inst());
        $this->assertEquals($nested1->getInjectorLoader(), InjectorLoader::inst());
        $this->assertEquals('/mysite/', Director::config()->get('alternate_base_url'));
        $this->assertEquals(1, ConfigLoader::inst()->countManifests());
        $this->assertEquals(1, InjectorLoader::inst()->countManifests());
    }

    public function testInvalidInjectorDetection()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            "Non-current injector manifest cannot be accessed. Please call ->activate() first"
        );

        /** @var Kernel $kernel */
        $kernel = Injector::inst()->get(Kernel::class);
        $kernel->nest(); // $kernel is no longer current kernel

        $kernel->getInjectorLoader()->getManifest();
    }

    public function testInvalidConfigDetection()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            "Non-current config manifest cannot be accessed. Please call ->activate() first"
        );

        /** @var Kernel $kernel */
        $kernel = Injector::inst()->get(Kernel::class);
        $kernel->nest(); // $kernel is no longer current kernel

        $kernel->getConfigLoader()->getManifest();
    }

    public function provideAllowedHostsWarning(): array
    {
        $scenarios = [
            [
                'config' => [],
                'isCli' => true,
                'shouldLog' => true,
            ],
            [
                'config' => ['*'],
                'isCli' => true,
                'shouldLog' => false,
            ],
            [
                'config' => ['www.example.com', 'example.org'],
                'isCli' => true,
                'shouldLog' => false,
            ],
        ];
        // Test both in CLI and non-CLI context
        foreach ($scenarios as $name => $scenario) {
            $scenario['isCli'] = false;
            $scenarios[$name . ' (non-CLI)'] = $scenario;
        }
        return $scenarios;
    }

    /**
     * @dataProvider provideAllowedHostsWarning
     */
    public function testAllowedHostsWarning(array $config, bool $isCli, bool $shouldLog): void
    {
        // Prepare mock to check if a warning is logged or not
        $mockLogger = $this->getMockBuilder(Logger::class)->setConstructorArgs(['testLogger'])->getMock();
        $expectLog = $shouldLog ? $this->once() : $this->never();
        $mockLogger->expects($expectLog)->method('warning');
        Injector::inst()->registerService($mockLogger, LoggerInterface::class);

        // Set the config in our middleware
        $middleware = Injector::inst()->get(AllowedHostsMiddleware::class, true);
        $middleware->setAllowedHosts($config);

        $reflectionEnvironment = new ReflectionClass(Environment::class);
        $origIsCli = $reflectionEnvironment->getStaticPropertyValue('isCliOverride');
        $reflectionEnvironment->setStaticPropertyValue('isCliOverride', $isCli);

        try {
            $kernel = Injector::inst()->get(Kernel::class);
            $kernel->nest(); // $kernel is no longer current kernel
            $kernel->boot();
        } finally {
            $reflectionEnvironment->setStaticPropertyValue('isCliOverride', $origIsCli);
        }
    }
}
