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

    /**
     * Run the audit against a config collection that only holds the given config for TestObject,
     * so that config from other modules can't affect the result.
     */
    private function runAudit(array $config, ?string &$result): int
    {
        $collection = new MemoryConfigCollection();
        $collection->set(TestObject::class, null, $config);
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
