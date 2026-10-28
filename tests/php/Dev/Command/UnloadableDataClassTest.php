<?php

namespace SilverStripe\Dev\Tests\Command;

use ReflectionMethod;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Manifest\ClassManifest;
use SilverStripe\Dev\Command\DbBuild;
use SilverStripe\Dev\Command\DbDefaults;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Tests\Command\UnloadableDataClassTest\LoadableThing;
use SilverStripe\ORM\DataObject;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * The manifest can list a DataObject subclass whose file returns before declaring it,
 * e.g. when an optional dependency is missing. Database commands must skip such classes.
 */
class UnloadableDataClassTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();

        $manifest = new ClassManifest(__DIR__ . '/UnloadableDataClassTest/manifest');
        $manifest->init(includeTests: true);
        // DataObject isn't in this manifest, so its descendants aren't coalesced on init
        $method = new ReflectionMethod($manifest, 'coalesceDescendants');
        $method->invoke($manifest, DataObject::class);
        ClassLoader::inst()->pushManifest($manifest, false);

        LoadableThing::$calls = [];
    }

    protected function tearDown(): void
    {
        ClassLoader::inst()->popManifest();
        parent::tearDown();
    }

    public function testDbBuildSkipsUnloadableClass(): void
    {
        // Populating remaps legacy class names, which needs config this fixture manifest doesn't provide
        DbBuild::singleton()->doBuild($this->createOutput(), populate: false);

        $this->assertSame(['onAfterBuild'], LoadableThing::$calls);
    }

    public function testDbDefaultsSkipsUnloadableClass(): void
    {
        $exitCode = DbDefaults::singleton()->run(new ArrayInput([]), $this->createOutput());

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame(['requireDefaultRecords'], LoadableThing::$calls);
    }

    private function createOutput(): PolyOutput
    {
        return new PolyOutput(PolyOutput::FORMAT_ANSI, wrappedOutput: new BufferedOutput());
    }
}
