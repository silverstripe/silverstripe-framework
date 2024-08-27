<?php

namespace SilverStripe\Cli\Command;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\PolyExecution\PolyCommand;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Wraps a PolyCommand for use in CLI.
 */
class PolyCommandCliWrapper extends Command
{
    use Injectable;

    private PolyCommand $command;

    public function __construct(PolyCommand $command, string $alias = '')
    {
        $this->command = $command;
        parent::__construct($command->getName());
        if ($alias) {
            $this->setAliases([$alias]);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $polyOutput = PolyOutput::create(
            PolyOutput::FORMAT_ANSI,
            $output->getVerbosity(),
            $output->isDecorated(),
            $output
        );
        return $this->command->run($input, $polyOutput);
    }

    protected function configure(): void
    {
        $this->setDescription($this->command::getDescription());
        $this->setDefinition(new InputDefinition($this->command->getOptions()));
        $this->setHelp($this->command->getHelp());
    }
}
