<?php

namespace SilverStripe\Core\Tests;

use BadMethodCallException;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\ConfigLoader;
use SilverStripe\Core\CoreKernel;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorLoader;
use SilverStripe\Core\Kernel;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Environment;
use ReflectionClass;
use SilverStripe\ORM\DB;
use ReflectionObject;

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
    
    public function testReplicaDatabaseVarsLoaded()
    {
        // Set environment variables for a fake replica database
        Environment::setEnv('SS_DATABASE_SERVER_REPLICA_01', 'the-moon');
        Environment::setEnv('SS_DATABASE_USERNAME_REPLICA_01', 'alien');
        Environment::setEnv('SS_DATABASE_PASSWORD_REPLICA_01', 'hi_people');
        // Get the CoreKernel
        /** @var Kernel $kernel */
        $kernel = Injector::inst()->get(Kernel::class);
        /** @var CoreKernel $coreKernel */
        $coreKernel = $kernel->nest();
        $this->assertTrue(is_a($coreKernel, CoreKernel::class));
        // Boot the database environment variables
        $reflector = new ReflectionObject($coreKernel);
        $method = $reflector->getMethod('bootDatabaseEnvVars');
        $method->setAccessible(true);
        $method->invoke($coreKernel);
        // Assert DB config was updated
        $default = DB::getConfig(DB::CONN_PRIMARY);
        $configs = (new ReflectionClass(DB::class))->getStaticPropertyValue('configs');
        $this->assertSame([
            'type' => $default['type'],
            'server' => 'the-moon',
            'username' => 'alien',
            'password' => 'hi_people',
        ], $configs['replica_01']);
    }
}
