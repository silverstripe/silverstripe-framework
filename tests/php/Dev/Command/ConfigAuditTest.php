<?php

namespace SilverStripe\Dev\Tests\Command;

use SilverStripe\Config\Collections\MemoryConfigCollection;
use SilverStripe\Core\Config\ConfigLoader;
use SilverStripe\Dev\Command\ConfigAudit;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Tests\Command\ConfigAuditTest\TestObject;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ConfigAuditTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testNoMissingPropertiesReturnsSuccess(): void
    {
        $this->assertSame(Command::SUCCESS, $this->runAudit(['defined_setting' => ['a' => 1]], $output));
        $this->assertStringContainsString('All configured properties are defined', $output);
    }

    public function testMissingPropertiesReturnsFailure(): void
    {
        $this->assertSame(Command::FAILURE, $this->runAudit(['orphaned_setting' => ['a' => 1]], $output));
        // Config keys are stored lowercase, so the class name is output in lowercase
        $this->assertStringContainsStringIgnoringCase(TestObject::class . '::$orphaned_setting', $output);
    }

    public function testInternalConfigKeysAreIgnored(): void
    {
        // "__deprecated" is written by the config system itself whenever a private static is
        // marked as deprecated, so it must not be reported as a class with a missing property
        $exitCode = $this->runAudit(
            ['defined_setting' => ['a' => 1]],
            $output,
            ['__deprecated' => ['config' => [strtolower(TestObject::class) => 'Some deprecation notice']]]
        );
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringNotContainsString('__deprecated', $output);
    }

    public function testConfigForMissingClassReturnsFailure(): void
    {
        // Config for a class that doesn't exist (e.g. a typo, or a class removed in a newer version
        // of a module) is never applied to anything, so it is still reported
        $missingClass = 'SilverStripe\\Dev\\Tests\\Command\\ConfigAuditTest\\DoesNotExist';
        $exitCode = $this->runAudit(
            ['defined_setting' => ['a' => 1]],
            $output,
            [$missingClass => ['some_setting' => ['a' => 1]]]
        );
        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsStringIgnoringCase($missingClass . '::$some_setting', $output);
    }

    /**
     * Run the audit against a config collection that only holds the given config for TestObject
     * (plus any extra keys), so that config from other modules can't affect the result.
     */
    private function runAudit(array $config, ?string &$result, array $extraConfig = []): int
    {
        $collection = new MemoryConfigCollection();
        $collection->set(TestObject::class, null, $config);
        foreach ($extraConfig as $key => $value) {
            $collection->set($key, null, $value);
        }
        ConfigLoader::inst()->pushManifest($collection);
        try {
            $buffer = new BufferedOutput();
            $output = new PolyOutput(PolyOutput::FORMAT_ANSI, decorated: false, wrappedOutput: $buffer);
            $exitCode = (new ConfigAudit())->run(new ArrayInput([]), $output);
            $result = $buffer->fetch();
            return $exitCode;
        } finally {
            ConfigLoader::inst()->popManifest();
        }
    }
}
