<?php

namespace SilverStripe\Dev\Command;

use SilverStripe\Core\ClassInfo;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Command to clean up the database.
 * Can be run either via an HTTP request or the CLI.
 */
class DbCleanup extends DevCommand
{
    protected static string $commandName = 'db:cleanup';

    protected static string $description = 'Remove records that don\'t have corresponding rows in their parent class tables';

    private static array $permissions_for_browser_execution = [
        'CAN_DEV_BUILD',
    ];

    public function getTitle(): string
    {
        return 'Database Cleanup';
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $schema = DataObject::getSchema();
        $baseClasses = [];
        foreach (ClassInfo::subclassesFor(DataObject::class) as $class) {
            if (get_parent_class($class ?? '') == DataObject::class) {
                $baseClasses[] = $class;
            }
        }

        $countDeleted = 0;
        $output->startList(PolyOutput::LIST_UNORDERED);
        foreach ($baseClasses as $baseClass) {
            // Get data classes
            $baseTable = $schema->baseDataTable($baseClass);
            $subclasses = ClassInfo::subclassesFor($baseClass);
            unset($subclasses[0]);
            foreach ($subclasses as $k => $subclass) {
                if (!DataObject::getSchema()->classHasTable($subclass)) {
                    unset($subclasses[$k]);
                }
            }

            if ($subclasses) {
                $records = DB::query("SELECT * FROM \"$baseTable\"");

                foreach ($subclasses as $subclass) {
                    $subclassTable = $schema->tableName($subclass);
                    $recordExists[$subclass] =
                        DB::query("SELECT \"ID\" FROM \"$subclassTable\"")->keyedColumn();
                }

                foreach ($records as $record) {
                    foreach ($subclasses as $subclass) {
                        $subclassTable = $schema->tableName($subclass);
                        $id = $record['ID'];
                        if (($record['ClassName'] != $subclass)
                            && (!is_subclass_of($record['ClassName'], $subclass ?? ''))
                            && isset($recordExists[$subclass][$id])
                        ) {
                            $sql = "DELETE FROM \"$subclassTable\" WHERE \"ID\" = ?";
                            $output->writeListItem("$sql [{$id}]");
                            DB::prepared_query($sql, [$id]);
                            $countDeleted++;
                        }
                    }
                }
            }
        }
        $output->stopList();
        $output->writeln("Deleted {$countDeleted} rows");
        return Command::SUCCESS;
    }

    protected function getHeading(): string
    {
        return 'Deleting records with no corresponding row in their parent class tables';
    }
}
