<?php

namespace SilverStripe\Dev\Tests\Command;

use SilverStripe\Dev\Command\DbBuild;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Versioned\Versioned;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;

class DbBuildTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testBuildRunsInDraftStage(): void
    {
        if (!class_exists(Versioned::class)) {
            $this->markTestSkipped('silverstripe/versioned is required');
        }

        $command = new class extends DbBuild {
            public ?string $stageDuringBuild = null;

            public function doBuild(PolyOutput $output, bool $populate = true, bool $testMode = false): void
            {
                $this->stageDuringBuild = Versioned::get_stage();
            }

            protected function getHeading(): string
            {
                return '';
            }
        };

        // SapphireTest defaults to draft, but sake uses the live stage by default
        Versioned::set_stage(Versioned::LIVE);

        $definition = new InputDefinition($command->getOptions());
        $definition->addOption(new InputOption('flush'));
        $input = new ArrayInput(['--flush' => true], $definition);
        $output = new PolyOutput(PolyOutput::FORMAT_ANSI, wrappedOutput: new BufferedOutput());
        $command->run($input, $output);

        $this->assertSame(Versioned::DRAFT, $command->stageDuringBuild);
    }
}
