<?php

namespace SilverStripe\ORM\Connect;

use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

class TableBuilder
{
    use Injectable;

    public function buildTables(DBSchemaManager $dbSchema, array $dataClasses, array $extraDataObjects = [], bool $quiet = false, bool $testMode = false, bool $showRecordCounts = false)
    {
        $dbSchema->schemaUpdate(function () use ($dataClasses, $extraDataObjects, $testMode, $quiet, $showRecordCounts) {
            $dataObjectSchema = DataObject::getSchema();

            foreach ($dataClasses as $dataClass) {
                // Check if class exists before trying to instantiate - this sidesteps any manifest weirdness
                if (!class_exists($dataClass)) {
                    continue;
                }

                // Check if this class should be excluded as per testing conventions
                /** @var DataObject $SNG */
                $SNG = new $dataClass([], DataObject::CREATE_SINGLETON);
                if (!$testMode && $SNG instanceof TestOnly) {
                    continue;
                }

                // Log data
                if (!$quiet) {
                    $tableName = $dataObjectSchema->tableName($dataClass);
                    if ($showRecordCounts && DB::get_schema()->hasTable($tableName)) {
                        try {
                            $count = DB::query("SELECT COUNT(*) FROM \"$tableName\"")->value();
                            $countSuffix = " ($count records)";
                        } catch (\Exception $e) {
                            $countSuffix = " (error getting record count)";
                        }
                    } else {
                        $countSuffix = "";
                    }

                    if (Director::is_cli()) {
                        echo " * $tableName$countSuffix\n";
                    } else {
                        echo "<li>$tableName$countSuffix</li>\n";
                    }
                }

                // Instruct the class to apply its schema to the database
                $SNG->requireTable();
            }

            // If we have additional dataobjects which need schema (i.e. for tests), do so here:
            if ($extraDataObjects) {
                foreach ($extraDataObjects as $dataClass) {
                    $SNG = new $dataClass([], DataObject::CREATE_SINGLETON);
                    if ($SNG instanceof DataObject) {
                        $SNG->requireTable();
                    }
                }
            }
        });
    }
}
