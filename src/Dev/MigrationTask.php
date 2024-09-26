<?php

namespace SilverStripe\Dev;

use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * A migration task is a build task that is reversible.
 *
 * To create your own migration task, you need to define your own subclass of MigrationTask
 * and implement the abstract methods.
 */
abstract class MigrationTask extends BuildTask
{
    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        if ($input->getOption('direction') === 'down') {
            $this->down();
        } else {
            $this->up();
        }
        return Command::SUCCESS;
    }

    /**
     * Migrate from old to new
     */
    abstract public function up();

    /**
     * Revert the migration (new to old)
     */
    abstract public function down();

    public function getOptions(): array
    {
        return [
            new InputOption(
                'direction',
                null,
                InputOption::VALUE_REQUIRED,
                '"up" if migrating from old to new, "down" to revert a migration',
                suggestedValues: ['up', 'down'],
            ),
        ];
    }
}
