<?php

namespace SilverStripe\ORM\Connect;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use LogicException;

/**
 * Represents schema management object for MySQL
 */
class MySQLSchemaManager extends DBSchemaManager
{

    /**
     * Identifier for this schema, used for configuring schema-specific table
     * creation options
     *
     */
    const ID = 'MySQLDatabase';

    public function createTable($table, $fields = null, $indexes = null, $options = null, $advancedOptions = null)
    {
        $fieldSchemas = $indexSchemas = "";

        if (!empty($options[MySQLSchemaManager::ID])) {
            $addOptions = $options[MySQLSchemaManager::ID];
        } else {
            $addOptions = "ENGINE=InnoDB";
        }

        if (!isset($fields['ID'])) {
            $fields['ID'] = "int(11) not null auto_increment";
        }
        if ($fields) {
            foreach ($fields as $k => $v) {
                $fieldSchemas .= "\"$k\" $v,\n";
            }
        }
        if ($indexes) {
            foreach ($indexes as $k => $v) {
                $indexSchemas .= $this->getIndexSqlDefinition($k, $v) . ",\n";
            }
        }

        // Switch to "CREATE TEMPORARY TABLE" for temporary tables
        $temporary = empty($options['temporary'])
                ? ""
                : "TEMPORARY";

        $this->query("CREATE $temporary TABLE \"$table\" (
				$fieldSchemas
				$indexSchemas
				primary key (ID)
			) {$addOptions}");

        return $table;
    }

    public function alterTable(
        $tableName,
        $newFields = null,
        $newIndexes = null,
        $alteredFields = null,
        $alteredIndexes = null,
        $alteredOptions = null,
        $advancedOptions = null
    ) {
        if ($this->isView($tableName)) {
            $this->alterationMessage(
                sprintf("Table %s not changed as it is a view", $tableName),
                "changed"
            );
            return;
        }
        $alterList = [];

        if ($newFields) {
            foreach ($newFields as $k => $v) {
                $alterList[] = "ADD \"$k\" $v";
            }
        }
        if ($newIndexes) {
            foreach ($newIndexes as $k => $v) {
                $alterList[] = "ADD " . $this->getIndexSqlDefinition($k, $v);
            }
        }
        if ($alteredFields) {
            foreach ($alteredFields as $k => $v) {
                $alterList[] = "CHANGE \"$k\" \"$k\" $v";
            }
        }
        if ($alteredIndexes) {
            foreach ($alteredIndexes as $k => $v) {
                $alterList[] = "DROP INDEX \"$k\"";
                $alterList[] = "ADD " . $this->getIndexSqlDefinition($k, $v);
            }
        }

        $dbID = MySQLSchemaManager::ID;
        if ($alteredOptions && isset($alteredOptions[$dbID])) {
            $this->query(sprintf("ALTER TABLE \"%s\" %s", $tableName, $alteredOptions[$dbID]));
            $this->alterationMessage(
                sprintf("Table %s options changed: %s", $tableName, $alteredOptions[$dbID]),
                "changed"
            );
        }

        $alterations = implode(",\n", $alterList);
        $this->query("ALTER TABLE \"$tableName\" $alterations");
    }

    public function isView($tableName)
    {
        $info = $this->query("SHOW /*!50002 FULL*/ TABLES LIKE '$tableName'")->record();
        return $info && strtoupper($info['Table_type'] ?? '') == 'VIEW';
    }

    /**
     * Renames a table
     *
     * @param string $oldTableName
     * @param string $newTableName
     * @throws LogicException
     * @return Query
     */
    public function renameTable($oldTableName, $newTableName)
    {
        if (!$this->hasTable($oldTableName)) {
            throw new LogicException('Table ' . $oldTableName . ' does not exist.');
        }

        return $this->query("ALTER TABLE \"$oldTableName\" RENAME \"$newTableName\"");
    }

    public function checkAndRepairTable($tableName)
    {
        // Perform check
        if ($this->runTableCheckCommand("CHECK TABLE \"$tableName\"")) {
            return true;
        }
        $this->alterationMessage(
            "Table $tableName: repaired",
            "repaired"
        );
        return $this->runTableCheckCommand("REPAIR TABLE \"$tableName\"");
    }

    /**
     * Helper function used by checkAndRepairTable.
     * @param string $sql Query to run.
     * @return boolean Returns if the query returns a successful result.
     */
    protected function runTableCheckCommand($sql)
    {
        $testResults = $this->query($sql);
        foreach ($testResults as $testRecord) {
            if (strtolower($testRecord['Msg_text'] ?? '') != 'ok') {
                return false;
            }
        }
        return true;
    }

    public function hasTable($table)
    {
        // MySQLi doesn't like parameterised queries for some queries
        // underscores need to be escaped in a SHOW TABLES LIKE query
        $sqlTable = str_replace('_', '\\_', $this->database->quoteString($table) ?? '');
        return (bool) ($this->query("SHOW TABLES LIKE $sqlTable")->value());
    }

    public function createField($tableName, $fieldName, $fieldSpec)
    {
        $this->query("ALTER TABLE \"$tableName\" ADD \"$fieldName\" $fieldSpec");
    }

    public function databaseList()
    {
        return $this->query("SHOW DATABASES")->column();
    }

    public function databaseExists($name)
    {
        // MySQLi doesn't like parameterised queries for some queries
        $sqlName = addcslashes($this->database->quoteString($name) ?? '', '%_');
        return !!($this->query("SHOW DATABASES LIKE $sqlName")->value());
    }

    public function createDatabase($name)
    {
        $charset = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'charset');
        $collation = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'collation');
        $this->query("CREATE DATABASE \"$name\" DEFAULT CHARACTER SET {$charset} DEFAULT COLLATE {$collation}");
    }

    public function dropDatabase($name)
    {
        $this->query("DROP DATABASE \"$name\"");
    }

    /**
     * Change the database type of the given field.
     * @param string $tableName The name of the tbale the field is in.
     * @param string $fieldName The name of the field to change.
     * @param string $fieldSpec The new field specification
     */
    public function alterField($tableName, $fieldName, $fieldSpec)
    {
        $this->query("ALTER TABLE \"$tableName\" CHANGE \"$fieldName\" \"$fieldName\" $fieldSpec");
    }

    /**
     * Change the database column name of the given field.
     *
     * @param string $tableName The name of the tbale the field is in.
     * @param string $oldName The name of the field to change.
     * @param string $newName The new name of the field
     */
    public function renameField($tableName, $oldName, $newName)
    {
        $fieldList = $this->fieldList($tableName);
        if (array_key_exists($oldName, $fieldList ?? [])) {
            $this->query("ALTER TABLE \"$tableName\" CHANGE \"$oldName\" \"$newName\" " . $fieldList[$oldName]);
        }
    }

    protected static $_cache_collation_info = [];

    private function shouldUseIntegerWidth()
    {
        // MySQL 8.0.17 stopped reporting the width attribute for integers
        // https://github.com/silverstripe/silverstripe-framework/issues/9453
        // Note: MariaDB did not change its behaviour
        $forceWidth = Config::inst()->get(MySQLSchemaManager::class, 'schema_use_int_width');
        if ($forceWidth !== null) {
            return $forceWidth;
        }
        $v = $this->database->getVersion();
        if (false !== strpos($v ?? '', 'MariaDB')) {
            // MariaDB is included in the version string: https://mariadb.com/kb/en/version/
            return true;
        }
        return version_compare($v ?? '', '8.0.17', '<');
    }

    public function fieldList($table)
    {
        $fields = $this->query("SHOW FULL FIELDS IN \"$table\"");
        $fieldList = [];
        foreach ($fields as $field) {
            $fieldSpec = $field['Type'];
            if (!$field['Null'] || $field['Null'] == 'NO') {
                $fieldSpec .= ' not null';
            }

            if ($field['Collation'] && $field['Collation'] != 'NULL') {
                // Cache collation info to cut down on database traffic
                if (!isset(MySQLSchemaManager::$_cache_collation_info[$field['Collation']])) {
                    MySQLSchemaManager::$_cache_collation_info[$field['Collation']]
                        = $this->query("SHOW COLLATION LIKE '{$field['Collation']}'")->record();
                }
                $collInfo = MySQLSchemaManager::$_cache_collation_info[$field['Collation']];
                $fieldSpec .= " character set $collInfo[Charset] collate $field[Collation]";
            }

            if ($field['Default'] || $field['Default'] === "0" || $field['Default'] === '') {
                $fieldSpec .= " default " . $this->database->quoteString($field['Default']);
            }
            if ($field['Extra']) {
                $fieldSpec .= " " . $field['Extra'];
            }

            $fieldList[$field['Field']] = $fieldSpec;
        }
        return $fieldList;
    }

    /**
     * Create an index on a table.
     *
     * @param string $tableName The name of the table.
     * @param string $indexName The name of the index.
     * @param string $indexSpec The specification of the index, see {@link SS_Database::requireIndex()} for more
     *                          details.
     */
    public function createIndex($tableName, $indexName, $indexSpec)
    {
        $this->query("ALTER TABLE \"$tableName\" ADD " . $this->getIndexSqlDefinition($indexName, $indexSpec));
    }

    /**
     * Generate SQL suitable for creating this index
     *
     * @param string $indexName
     * @param string|array $indexSpec See {@link requireTable()} for details
     * @return string MySQL compatible ALTER TABLE syntax
     */
    protected function getIndexSqlDefinition($indexName, $indexSpec)
    {
        if ($indexSpec['type'] == 'using') {
            return sprintf('index "%s" using (%s)', $indexName, $this->implodeColumnList($indexSpec['columns']));
        } else {
            return sprintf('%s "%s" (%s)', $indexSpec['type'], $indexName, $this->implodeColumnList($indexSpec['columns']));
        }
    }

    public function alterIndex($tableName, $indexName, $indexSpec)
    {
        $this->query(sprintf('ALTER TABLE "%s" DROP INDEX "%s"', $tableName, $indexName));
        $this->query(sprintf(
            'ALTER TABLE "%s" ADD %s "%s" %s',
            $tableName,
            $indexSpec['type'],
            $indexName,
            $this->implodeColumnList($indexSpec['columns'])
        ));
    }

    protected function indexKey($table, $index, $spec)
    {
        // MySQL simply uses the same index name as SilverStripe does internally
        return $index;
    }

    public function indexList($table)
    {
        $indexes = $this->query("SHOW INDEXES IN \"$table\"");
        $groupedIndexes = [];
        $indexList = [];

        foreach ($indexes as $index) {
            $groupedIndexes[$index['Key_name']]['fields'][$index['Seq_in_index']] = $index['Column_name'];

            if ($index['Index_type'] == 'FULLTEXT') {
                $groupedIndexes[$index['Key_name']]['type'] = 'fulltext';
            } elseif (!$index['Non_unique']) {
                $groupedIndexes[$index['Key_name']]['type'] = 'unique';
            } elseif ($index['Index_type'] == 'HASH') {
                $groupedIndexes[$index['Key_name']]['type'] = 'hash';
            } elseif ($index['Index_type'] == 'RTREE') {
                $groupedIndexes[$index['Key_name']]['type'] = 'rtree';
            } else {
                $groupedIndexes[$index['Key_name']]['type'] = 'index';
            }
        }

        if ($groupedIndexes) {
            foreach ($groupedIndexes as $index => $details) {
                ksort($details['fields']);
                $indexList[$index] = [
                    'name' => $index,
                    'columns' => $details['fields'],
                    'type' => $details['type'],
                ];
            }
        }

        return $indexList;
    }

    public function tableList()
    {
        $tables = [];
        foreach ($this->query("SHOW FULL TABLES WHERE Table_Type != 'VIEW'") as $record) {
            $table = reset($record);
            $tables[strtolower($table)] = $table;
        }
        return $tables;
    }

    public function enumValuesForField($tableName, $fieldName)
    {
        // Get the enum of all page types from the SiteTree table
        $classnameinfo = $this->query("DESCRIBE \"$tableName\" \"$fieldName\"")->record();
        preg_match_all("/'[^,]+'/", $classnameinfo["Type"] ?? '', $matches);

        $classes = [];
        foreach ($matches[0] as $value) {
            $classes[] = stripslashes(trim($value ?? '', "'"));
        }
        return $classes;
    }

    public function dbDataType($type)
    {
        $values = [
            'unsigned integer' => 'UNSIGNED'
        ];

        if (isset($values[$type])) {
            return $values[$type];
        } else {
            return '';
        }
    }

    /**
     * Return a boolean type-formatted string
     *
     * @param array $values Contains a tokenised list of info about this data type
     * @return string
     */
    public function boolean($values)
    {
        //For reference, this is what typically gets passed to this function:
        //$parts=Array('datatype'=>'tinyint', 'precision'=>1, 'sign'=>'unsigned', 'null'=>'not null',
        //'default'=>$this->default);
        //DB::requireField($this->tableName, $this->name, "tinyint(1) unsigned not null default
        //'{$this->defaultVal}'");
        $width = $this->shouldUseIntegerWidth() ? '(1)' : '';
        return 'tinyint' . $width . ' unsigned not null' . $this->defaultClause($values);
    }

    /**
     * Return a date type-formatted string
     * For MySQL, we simply return the word 'date', no other parameters are necessary
     *
     * @param array $values Contains a tokenised list of info about this data type
     * @return string
     */
    public function date($values)
    {
        //For reference, this is what typically gets passed to this function:
        //$parts=Array('datatype'=>'date');
        //DB::requireField($this->tableName, $this->name, "date");
        return 'date';
    }

    /**
     * Return a decimal type-formatted string
     *
     * @param array $values Contains a tokenised list of info about this data type
     * @return string
     */
    public function decimal($values)
    {
        //For reference, this is what typically gets passed to this function:
        //$parts=Array('datatype'=>'decimal', 'precision'=>"$this->wholeSize,$this->decimalSize");
        //DB::requireField($this->tableName, $this->name, "decimal($this->wholeSize,$this->decimalSize)");
        // Avoid empty strings being put in the db
        if ($values['precision'] == '') {
            $precision = 1;
        } else {
            $precision = $values['precision'];
        }

        // Fix format of default value to match precision
        if (isset($values['default']) && is_numeric($values['default'])) {
            $decs = strpos($precision ?? '', ',') !== false
                    ? (int) substr($precision, strpos($precision, ',') + 1)
                    : 0;
            $values['default'] = number_format($values['default'] ?? 0.0, $decs ?? 0, '.', '');
        } else {
            unset($values['default']);
        }

        return "decimal($precision) not null" . $this->defaultClause($values);
    }

    /**
     * Return a enum type-formatted string
     *
     * @param array $values Contains a tokenised list of info about this data type
     * @return string
     */
    public function enum($values)
    {
        //For reference, this is what typically gets passed to this function:
        //$parts=Array('datatype'=>'enum', 'enums'=>$this->enum, 'character set'=>'utf8', 'collate'=>
        // 'utf8_general_ci', 'default'=>$this->default);
        //DB::requireField($this->tableName, $this->name, "enum('" . implode("','", $this->enum) . "') character set
        // utf8 collate utf8_general_ci default '{$this->default}'");
        $valuesString = implode(",", Convert::raw2sql($values['enums'], true));
        $charset = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'charset');
        $collation = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'collation');
        return "enum($valuesString) character set {$charset} collate {$collation}" . $this->defaultClause($values);
    }

    /**
     * Return a set type-formatted string
     *
     * @param array $values Contains a tokenised list of info about this data type
     * @return string
     */
    public function set($values)
    {
        //For reference, this is what typically gets passed to this function:
        //$parts=Array('datatype'=>'enum', 'enums'=>$this->enum, 'character set'=>'utf8', 'collate'=>
        // 'utf8_general_ci', 'default'=>$this->default);
        //DB::requireField($this->tableName, $this->name, "enum('" . implode("','", $this->enum) . "') character set
        //utf8 collate utf8_general_ci default '{$this->default}'");
        $valuesString = implode(",", Convert::raw2sql($values['enums'], true));
        $charset = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'charset');
        $collation = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'collation');
        return "set($valuesString) character set {$charset} collate {$collation}" . $this->defaultClause($values);
    }

    /**
     * Return a float type-formatted string
     * For MySQL, we simply return the word 'date', no other parameters are necessary
     *
     * @param array $values Contains a tokenised list of info about this data type
     * @return string
     */
    public function float($values)
    {
        //For reference, this is what typically gets passed to this function:
        //$parts=Array('datatype'=>'float');
        //DB::requireField($this->tableName, $this->name, "float");
        return "float not null" . $this->defaultClause($values);
    }

    /**
     * Return a int type-formatted string
     *
     * @param array $values Contains a tokenised list of info about this data type
     * @return string
     */
    public function int($values)
    {
        //For reference, this is what typically gets passed to this function:
        //$parts=Array('datatype'=>'int', 'precision'=>11, 'null'=>'not null', 'default'=>(int)$this->default);
        //DB::requireField($this->tableName, $this->name, "int(11) not null default '{$this->defaultVal}'");
        $width = $this->shouldUseIntegerWidth() ? '(11)' : '';
        return 'int' . $width . ' not null' . $this->defaultClause($values);
    }

    /**
     * Return a bigint type-formatted string
     *
     * @param array $values Contains a tokenised list of info about this data type
     * @return string
     */
    public function bigint($values)
    {
        //For reference, this is what typically gets passed to this function:
        //$parts=Array('datatype'=>'bigint', 'precision'=>20, 'null'=>'not null', 'default'=>$this->defaultVal,
        //             'arrayValue'=>$this->arrayValue);
        //$values=Array('type'=>'bigint', 'parts'=>$parts);
        //DB::requireField($this->tableName, $this->name, $values);
        $width = $this->shouldUseIntegerWidth() ? '(20)' : '';
        return 'bigint' . $width . ' not null' . $this->defaultClause($values);
    }

    /**
     * Return a datetime type-formatted string
     * For MySQL, we simply return the word 'datetime', no other parameters are necessary
     *
     * @param array $values Contains a tokenised list of info about this data type
     * @return string
     */
    public function datetime($values)
    {
        //For reference, this is what typically gets passed to this function:
        //$parts=Array('datatype'=>'datetime');
        //DB::requireField($this->tableName, $this->name, $values);
        return 'datetime';
    }

    /**
     * Return a text type-formatted string
     *
     * @param array $values Contains a tokenised list of info about this data type
     * @return string
     */
    public function text($values)
    {
        //For reference, this is what typically gets passed to this function:
        //$parts=Array('datatype'=>'mediumtext', 'character set'=>'utf8', 'collate'=>'utf8_general_ci');
        //DB::requireField($this->tableName, $this->name, "mediumtext character set utf8 collate utf8_general_ci");
        $charset = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'charset');
        $collation = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'collation');
        return 'mediumtext character set ' . $charset . ' collate ' . $collation . $this->defaultClause($values);
    }

    /**
     * Return a time type-formatted string
     * For MySQL, we simply return the word 'time', no other parameters are necessary
     *
     * @param array $values Contains a tokenised list of info about this data type
     * @return string
     */
    public function time($values)
    {
        //For reference, this is what typically gets passed to this function:
        //$parts=Array('datatype'=>'time');
        //DB::requireField($this->tableName, $this->name, "time");
        return 'time';
    }

    /**
     * Return a varchar type-formatted string
     *
     * @param array $values Contains a tokenised list of info about this data type
     * @return string
     */
    public function varchar($values)
    {
        //For reference, this is what typically gets passed to this function:
        //$parts=Array('datatype'=>'varchar', 'precision'=>$this->size, 'character set'=>'utf8', 'collate'=>
        //'utf8_general_ci');
        //DB::requireField($this->tableName, $this->name, "varchar($this->size) character set utf8 collate
        // utf8_general_ci");
        $default = $this->defaultClause($values);
        $charset = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'charset');
        $collation = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'collation');
        return "varchar({$values['precision']}) character set {$charset} collate {$collation}{$default}";
    }

    /*
     * Return the MySQL-proprietary 'Year' datatype
     *
     * @param array $values Contains a tokenised list of info about this data type
     * @return string
     */
    public function year($values)
    {
        return 'year(4)';
    }

    public function IdColumn($asDbValue = false, $hasAutoIncPK = true)
    {
        $width = $this->shouldUseIntegerWidth() ? '(11)' : '';
        return 'int' . $width . ' not null auto_increment';
    }

    /**
     * Parses and escapes the default values for a specification
     *
     * @param array $values Contains a tokenised list of info about this data type
     * @return string Default clause
     */
    protected function defaultClause($values)
    {
        if (isset($values['default'])) {
            return ' default ' . $this->database->quoteString($values['default']);
        }
        return '';
    }
}
