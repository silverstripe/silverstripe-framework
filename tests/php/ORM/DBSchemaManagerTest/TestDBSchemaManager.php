<?php

namespace SilverStripe\ORM\Tests\DBSchemaManagerTest;

use SilverStripe\ORM\Connect\DBSchemaManager;

class TestDBSchemaManager extends DBSchemaManager
{
    public function hasTable($tableName)
    {
        return true;
    }

    public function IdColumn($asDbValue = false, $hasAutoIncPK = true)
    {
        return '';
    }

    public function checkAndRepairTable($tableName)
    {
        return true;
    }

    public function enumValuesForField($tableName, $fieldName)
    {
        return [];
    }

    public function dbDataType($type)
    {
        return '';
    }

    public function databaseList()
    {
        return [];
    }

    public function databaseExists($name)
    {
        return true;
    }

    public function createDatabase($name)
    {
        return true;
    }

    public function dropDatabase($name)
    {
        return '';
    }

    public function alterIndex($tableName, $indexName, $indexSpec)
    {
    }

    protected function indexKey($table, $index, $spec)
    {
        return '';
    }

    public function indexList($table)
    {
        return [];
    }

    public function tableList()
    {
        return [];
    }

    public function createTable(
        $table,
        $fields = null,
        $indexes = null,
        $options = null,
        $advancedOptions = null
    ) {
        return '';
    }

    public function alterTable(
        $table,
        $newFields = null,
        $newIndexes = null,
        $alteredFields = null,
        $alteredIndexes = null,
        $alteredOptions = null,
        $advancedOptions = null
    ) {
    }

    public function renameTable($oldTableName, $newTableName)
    {
    }

    function createField($table, $field, $spec)
    {
    }

    function renameField($tableName, $oldName, $newName)
    {
    }

    function fieldList($table)
    {
        return [];
    }

    function boolean($values)
    {
        return '';
    }

    function date($values)
    {
        return '';
    }

    function decimal($values)
    {
        return '';
    }

    function enum($values)
    {
        return '';
    }

    function set($values)
    {
        return '';
    }

    function float($values)
    {
        return '';
    }

    function int($values)
    {
        return '';
    }

    function datetime($values)
    {
        return '';
    }

    function text($values)
    {
        return '';
    }

    function time($values)
    {
        return '';
    }

    function varchar($values)
    {
        return '';
    }

    function year($values)
    {
        return '';
    }
}
