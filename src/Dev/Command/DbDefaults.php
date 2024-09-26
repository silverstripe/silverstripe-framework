<?php

namespace SilverStripe\Dev\Command;

use SilverStripe\Core\ClassInfo;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\ORM\DataObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Command to build default records in the database.
 * Can be run either via an HTTP request or the CLI.
 */
class DbDefaults extends DevCommand
{
    protected static string $commandName = 'db:defaults';

    protected static string $description = 'Build the default data, calling requireDefaultRecords on all DataObject classes';

    private static array $permissions_for_browser_execution = [
        'CAN_DEV_BUILD',
    ];

    public function getTitle(): string
    {
        return 'Defaults Builder';
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $dataClasses = ClassInfo::subclassesFor(DataObject::class);
        array_shift($dataClasses);

        $output->startList(PolyOutput::LIST_UNORDERED);
        foreach ($dataClasses as $dataClass) {
            singleton($dataClass)->requireDefaultRecords();
            $output->writeListItem("Defaults loaded for $dataClass");
        }
        $output->stopList();

        return Command::SUCCESS;
    }

    protected function getHeading(): string
    {
        return 'Building default data for all DataObject classes';
    }
}
