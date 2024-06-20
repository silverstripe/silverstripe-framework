<?php

namespace SilverStripe\Dev;

use League\Csv\MapIterator;
use League\Csv\Reader;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\ORM\DataObject;

/**
 * Utility class to facilitate complex CSV-imports by defining column-mappings
 * and custom converters.
 *
 * Uses the fgetcsv() function to process CSV input. Accepts a file-handler as
 * input.
 *
 * @see http://tools.ietf.org/html/rfc4180
 */
class CsvBulkLoader extends BulkLoader
{

    /**
     * Delimiter character (Default: comma).
     *
     * @var string
     */
    public $delimiter = ',';

    /**
     * Enclosure character (Default: doublequote)
     *
     * @var string
     */
    public $enclosure = '"';

    /**
     * Identifies if csv the has a header row.
     *
     * @var boolean
     */
    public $hasHeaderRow = true;

    public $duplicateChecks = [
        'ID' => 'ID',
    ];

    /**
     * Number of lines to split large CSV files into.
     *
     * @var int
     *
     * @config
     */
    private static $lines = 1000;

    /**
     * @inheritDoc
     */
    public function preview($filepath)
    {
        return $this->processAll($filepath, true);
    }

    /**
     * @param string $filepath
     * @param boolean $preview
     *
     * @return null|BulkLoader_Result
     */
    protected function processAll($filepath, $preview = false)
    {
        $this->extend('onBeforeProcessAll', $filepath, $preview);

        $result = BulkLoader_Result::create();

        try {
            $filepath = Director::getAbsFile($filepath);
            $csvReader = Reader::createFromPath($filepath, 'r');
            $csvReader->setDelimiter($this->delimiter);
            $csvReader->skipInputBOM();

            $tabExtractor = function ($row, $rowOffset) {
                foreach ($row as &$item) {
                    // [SS-2017-007] Ensure all cells with leading tab and then [@=+] have the tab removed on import
                    if (preg_match("/^\t[\-@=\+]+.*/", $item ?? '')) {
                        $item = ltrim($item ?? '', "\t");
                    }
                }
                return $row;
            };

            if ($this->columnMap) {
                $headerMap = $this->getNormalisedColumnMap();

                $remapper = function ($row, $rowOffset) use ($headerMap, $tabExtractor) {
                    $row = $tabExtractor($row, $rowOffset);
                    foreach ($headerMap as $column => $renamedColumn) {
                        if ($column == $renamedColumn) {
                            continue;
                        }
                        if (array_key_exists($column, $row ?? [])) {
                            if (strpos($renamedColumn ?? '', '_ignore_') !== 0) {
                                $row[$renamedColumn] = $row[$column];
                            }
                            unset($row[$column]);
                        }
                    }
                    return $row;
                };
            } else {
                $remapper = $tabExtractor;
            }

            if ($this->hasHeaderRow) {
                if (method_exists($csvReader, 'fetchAssoc')) {
                    $rows = $csvReader->fetchAssoc(0, $remapper);
                } else {
                    $csvReader->setHeaderOffset(0);
                    $rows = new MapIterator($csvReader->getRecords(), $remapper);
                }
            } elseif ($this->columnMap) {
                if (method_exists($csvReader, 'fetchAssoc')) {
                    $rows = $csvReader->fetchAssoc($headerMap, $remapper);
                } else {
                    $rows = new MapIterator($csvReader->getRecords($headerMap), $remapper);
                }
            }

            foreach ($rows as $row) {
                $this->processRecord($row, $this->columnMap, $result, $preview);
            }
        } catch (\Exception $e) {
            if ($e instanceof HTTPResponse_Exception) {
                throw $e;
            }

            $failedMessage = sprintf("Failed to parse %s", $filepath);
            if (Director::isDev()) {
                $failedMessage = sprintf($failedMessage . " because %s", $e->getMessage());
            }
            print $failedMessage . PHP_EOL;
        }

        $this->extend('onAfterProcessAll', $result, $preview);

        return $result;
    }

    protected function getNormalisedColumnMap()
    {
        $map = [];
        foreach ($this->columnMap as $column => $newColumn) {
            if (strpos($newColumn ?? '', "->") === 0) {
                $map[$column] = $column;
            } elseif (is_null($newColumn)) {
                // the column map must consist of unique scalar values
                // `null` can be present multiple times and is not scalar
                // so we name it in a standard way so we can remove it later
                $map[$column] = '_ignore_' . $column;
            } else {
                $map[$column] = $newColumn;
            }
        }
        return $map;
    }

    /**
     * @param array $record
     * @param array $columnMap
     * @param BulkLoader_Result $results
     * @param boolean $preview
     *
     * @return int
     */
    protected function processRecord($record, $columnMap, &$results, $preview = false)
    {
        $class = $this->objectClass;

        // find existing object, or create new one
        $existingObj = $this->findExistingObject($record, $columnMap);
        $alreadyExists = (bool) $existingObj;

        // If we can't edit the existing object, bail early.
        if ($this->getCheckPermissions() && !$preview && $alreadyExists && !$existingObj->canEdit()) {
            $type = $existingObj->i18n_singular_name();
            throw new HTTPResponse_Exception(
                _t(BulkLoader::class . '.CANNOT_EDIT', "Not allowed to edit '$type' records"),
                403
            );
        }

        /** @var DataObject $obj */
        $obj = $alreadyExists ? $existingObj : new $class();

        // If we can't create a new record, bail out early.
        if ($this->getCheckPermissions() && !$preview && !$alreadyExists && !$obj->canCreate()) {
            $type = $obj->i18n_singular_name();
            throw new HTTPResponse_Exception(
                _t(BulkLoader::class . '.CANNOT_CREATE', "Not allowed to create '$type' records"),
                403
            );
        }

        $schema = DataObject::getSchema();

        // first run: find/create any relations and store them on the object
        // we can't combine runs, as other columns might rely on the relation being present
        foreach ($record as $fieldName => $val) {
            // don't bother querying of value is not set
            if ($this->isNullValue($val)) {
                continue;
            }

            // checking for existing relations
            if (isset($this->relationCallbacks[$fieldName])) {
                // trigger custom search method for finding a relation based on the given value
                // and write it back to the relation (or create a new object)
                $relationName = $this->relationCallbacks[$fieldName]['relationname'];
                /** @var DataObject $relationObj */
                $relationObj = null;
                if ($this->hasMethod($this->relationCallbacks[$fieldName]['callback'])) {
                    $relationObj = $this->{$this->relationCallbacks[$fieldName]['callback']}($obj, $val, $record);
                } elseif ($obj->hasMethod($this->relationCallbacks[$fieldName]['callback'])) {
                    $relationObj = $obj->{$this->relationCallbacks[$fieldName]['callback']}($val, $record);
                }
                if (!$relationObj || !$relationObj->exists()) {
                    $relationClass = $schema->hasOneComponent(get_class($obj), $relationName);
                    /** @var DataObject $relationObj */
                    $relationObj = new $relationClass();
                    //write if we aren't previewing
                    if (!$preview) {
                        if ($this->getCheckPermissions() && !$relationObj->canCreate()) {
                            $type = $relationObj->i18n_singular_name();
                            throw new HTTPResponse_Exception(
                                _t(BulkLoader::class . '.CANNOT_CREATE', "Not allowed to create '$type' records"),
                                403
                            );
                        }
                        $relationObj->write();
                    }
                }
                $obj->{"{$relationName}ID"} = $relationObj->ID;
                //write if we are not previewing
                if (!$preview) {
                    $obj->write();
                    $obj->flushCache(); // avoid relation caching confusion
                }
            } elseif (strpos($fieldName ?? '', '.') !== false) {
                // we have a relation column with dot notation
                [$relationName, $columnName] = explode('.', $fieldName ?? '');
                // always gives us an component (either empty or existing)
                $relationObj = $obj->getComponent($relationName);
                if (!$preview) {
                    if ($this->getCheckPermissions() && !$relationObj->canEdit()) {
                        $type = $relationObj->i18n_singular_name();
                        throw new HTTPResponse_Exception(
                            _t(BulkLoader::class . '.CANNOT_EDIT', "Not allowed to edit '$type' records"),
                            403
                        );
                    }
                    $relationObj->write();
                }
                $obj->{"{$relationName}ID"} = $relationObj->ID;

                //write if we are not previewing
                if (!$preview) {
                    $obj->write();
                    $obj->flushCache(); // avoid relation caching confusion
                }
            }
        }

        // second run: save data

        foreach ($record as $fieldName => $val) {
            // break out of the loop if we are previewing
            if ($preview) {
                break;
            }

            // look up the mapping to see if this needs to map to callback
            $mapped = $this->columnMap && isset($this->columnMap[$fieldName]);

            if ($mapped && strpos($this->columnMap[$fieldName] ?? '', '->') === 0) {
                $funcName = substr($this->columnMap[$fieldName] ?? '', 2);

                $this->$funcName($obj, $val, $record);
            } elseif ($obj->hasMethod("import{$fieldName}")) {
                $obj->{"import{$fieldName}"}($val, $record);
            } else {
                $obj->update([$fieldName => $val]);
            }
        }

        $isChanged = $obj->isChanged();

        // write record
        if (!$preview) {
            $obj->write();
        }

        $message = '';

        // save to results
        if ($existingObj) {
            // We mark as updated regardless of isChanged, since custom formatters and importers
            // might have affected relationships and other records.
            $results->addUpdated($obj, $message);
        } else {
            $results->addCreated($obj, $message);
        }

        $this->extend('onAfterProcessRecord', $obj, $preview, $isChanged);

        $objID = $obj->ID;

        $obj->destroy();

        // memory usage
        unset($existingObj, $obj);

        return $objID;
    }

    /**
     * Find an existing objects based on one or more uniqueness columns
     * specified via {@link CsvBulkLoader::$duplicateChecks}.
     *
     * @param array $record CSV data column
     * @param array $columnMap
     * @return DataObject
     */
    public function findExistingObject($record, $columnMap = [])
    {
        $SNG_objectClass = singleton($this->objectClass);
        // checking for existing records (only if not already found)

        foreach ($this->duplicateChecks as $fieldName => $duplicateCheck) {
            $existingRecord = null;
            if (is_string($duplicateCheck)) {
                // Skip current duplicate check if field value is empty
                if (empty($record[$duplicateCheck])) {
                    continue;
                }

                // Check existing record with this value
                $dbFieldValue = $record[$duplicateCheck];
                $existingRecord = DataObject::get($this->objectClass)
                    ->filter($duplicateCheck, $dbFieldValue)
                    ->first();

                if ($existingRecord) {
                    return $existingRecord;
                }
            } elseif (is_array($duplicateCheck) && isset($duplicateCheck['callback'])) {
                if ($this->hasMethod($duplicateCheck['callback'])) {
                    $existingRecord = $this->{$duplicateCheck['callback']}($record[$fieldName], $record);
                } elseif ($SNG_objectClass->hasMethod($duplicateCheck['callback'])) {
                    $existingRecord = $SNG_objectClass->{$duplicateCheck['callback']}($record[$fieldName], $record);
                } else {
                    throw new \RuntimeException(
                        "CsvBulkLoader::processRecord():"
                        . " {$duplicateCheck['callback']} not found on importer or object class."
                    );
                }

                if ($existingRecord) {
                    return $existingRecord;
                }
            } else {
                throw new \InvalidArgumentException(
                    'CsvBulkLoader::processRecord(): Wrong format for $duplicateChecks'
                );
            }
        }

        return false;
    }

    /**
     * Determine whether any loaded files should be parsed with a
     * header-row (otherwise we rely on {@link CsvBulkLoader::$columnMap}.
     *
     * @return boolean
     */
    public function hasHeaderRow()
    {
        return ($this->hasHeaderRow || isset($this->columnMap));
    }
}
