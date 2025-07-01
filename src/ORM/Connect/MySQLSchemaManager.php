<?php

namespace SilverStripe\ORM\Connect;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use LogicException;
use PHPSQLParser\Options;
use PHPSQLParser\PHPSQLCreator;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\utils\ExpressionType;
use PHPSQLParser\utils\PHPSQLParserConstants;
use SilverStripe\ORM\FieldType\DBGenerated;

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
                if (isset($advancedOptions['rebuildCols'][$k]) && $advancedOptions['rebuildCols'][$k] === true) {
                    continue;
                }
                $alterList[] = "CHANGE \"$k\" \"$k\" $v";
            }
        }
        if ($alteredIndexes) {
            foreach ($alteredIndexes as $k => $v) {
                $alterList[] = "DROP INDEX \"$k\"";
                if (!array_key_exists('drop', $v) || !$v['drop']) {
                    $alterList[] = "ADD " . $this->getIndexSqlDefinition($k, $v);
                }
            }
        }
        if (isset($advancedOptions['rebuildCols'])) {
            foreach ($advancedOptions['rebuildCols'] as $k => $v) {
                if ($v !== true || !isset($alteredFields[$k])) {
                    continue;
                }
                $v = $alteredFields[$k];
                $alterList[] = "DROP \"$k\"";
                $alterList[] = "ADD \"$k\" $v";
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
                $extra = $field['Extra'];
                if (str_ends_with($extra, ' GENERATED')) {
                    $expression = $this->query(
                        "SELECT GENERATION_EXPRESSION FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$table}' AND COLUMN_NAME = '{$field['Field']}'"
                    )->record()['GENERATION_EXPRESSION'];
                    $extra =  'AS (' . $this->normaliseGeneratedColumnExpression($expression) . ') ' . $extra;
                }
                $fieldSpec .= ' ' . str_replace(' GENERATED', '', $extra);
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
            return sprintf('index "%s" using (%s)', $indexName, $this->implodeIndexColumnList($indexSpec['columns'], $indexSpec['type']));
        } else {
            return sprintf('%s "%s" (%s)', $indexSpec['type'], $indexName, $this->implodeIndexColumnList($indexSpec['columns'], $indexSpec['type']));
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
            $this->implodeIndexColumnList($indexSpec['columns'], $indexSpec['type'])
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
            $column = $index['Column_name'];
            if (isset($index['Collation']) && $index['Collation'] !== 'NULL') {
                $column .= match ($index['Collation']) {
                    'A' => ' ASC',
                    'D' => ' DESC',
                };
            }
            $groupedIndexes[$index['Key_name']]['fields'][$index['Seq_in_index']] = $column;

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

    public function makeGenerated(string $spec, array $origSpec, string $expression, string $generationType): string
    {
        $expression = $this->normaliseGeneratedColumnExpression($expression);
        // Remove any default and nullability from the schema
        $default = $this->defaultClause($origSpec['parts'] ?? []);
        $spec = str_replace([$default, ' not null'], '', $spec);
        // Add generated column bits
        return "$spec AS ($expression) $generationType";
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
            $values['precision'] = 1;
        }

        return 'decimal(' . $values['precision'] . ') not null' . $this->defaultClause($values);
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
            // Update default for decimal data type
            if (isset($values['datatype']) && $values['datatype'] === 'decimal') {
                if (!is_numeric($values['default']) || !isset($values['precision'])) {
                    return '';
                }
                // Fix format of default value to match precision
                $precision = $values['precision'];
                $decs = strpos($precision ?? '', ',') !== false
                    ? (int) substr($precision, strpos($precision, ',') + 1)
                    : 0;
                $values['default'] = number_format($values['default'] ?? 0.0, $decs ?? 0, '.', '');
            }
            return ' default ' . $this->database->quoteString($values['default']);
        }
        return '';
    }

    /**
     * Check whether a column needs to be rebuilt.
     * Logic based on https://dev.mysql.com/doc/refman/8.4/en/alter-table-generated-columns.html
     */
    protected function needRebuildColumn(string $existingSpec, string $newSpec): bool
    {
        // Virtual generated columns cannot be altered to stored generated columns, or vice versa
        // Non-generated columns can be altered to stored but not virtual generated columns
        // Stored but not virtual generated columns can be altered to non-generated columns
        $existingSpecType = $this->getGeneratedType($existingSpec);
        $newSpecType = $this->getGeneratedType($newSpec);

        // If neither spec is generated, we can just use ALTER
        if (!$existingSpecType && !$newSpecType) {
            return false;
        }

        // Virtual generated columns cannot be altered to stored generated columns
        // Virtual generated columns cannot be altered to non-generated columns
        if ($existingSpecType === DBGenerated::GENERATION_VIRTUAL) {
            if ($newSpecType !== DBGenerated::GENERATION_VIRTUAL) {
                return true;
            }
            return false;
        }

        // Stored generated columns cannot be altered to virtual generated columns
        // Stored generated columns can be altered to non-generated columns
        if ($existingSpecType === DBGenerated::GENERATION_STORED) {
            if ($newSpecType === DBGenerated::GENERATION_VIRTUAL) {
                return true;
            }
            return false;
        }

        // Non-generated columns can be altered to stored but not virtual generated columns
        return $newSpecType === DBGenerated::GENERATION_VIRTUAL;
    }

    /**
     * Get what type of storage is used for a generated column, or null if not a generated column.
     */
    private function getGeneratedType(string $spec): ?string
    {
        // If it ends with "STORED" or "VIRTUAL", it's a generated column spec.
        preg_match('/(STORED|VIRTUAL)$/i', $spec, $matches);
        return strtoupper($matches[1] ?? '');
    }

    /**
     * Normalises a MySQL generated column expression.
     * This is needed to robustly validate whether the expression we want matches the expression
     * that's already in the database, because MySQL sometimes makes changes internally that we
     * need to normalise out, such as:
     * - escaping quotes
     * - double-escaping backslashes
     * - adding the charset before explicit strings
     * - changing case of keywords
     * - using backticks instead of ANSI quotes around columns names
     * - adding spaces around operators
     */
    private function normaliseGeneratedColumnExpression(string $expression): string
    {
        // Clean up string literals. Easier to do here than in the AST because the
        // escaped quotes MySQL gives us change the representation of the parsed string.
        $expression = preg_replace_callback(
            // Both \' and '' are valid escape sequences for a single quote in a string literal
            // see https://dev.mysql.com/doc/refman/8.4/en/string-literals.html
            "/\\\\'(?:[^']|''|\\\\\\\\')*\\\\'/",
            function ($matches) {
                // Replace escaped quotes around the string literal with single unescaped quotes
                $x = preg_replace("/^\\\\'(.*)\\\\'$/", "'$1'", $matches[0]);
                // Tidy up escaped backslashes, e.g. for FQCN
                return str_replace('\\\\\\\\', '\\\\', $x);
            },
            $expression
        );

        // Parse and normalise the AST (needs to be a valid SQL command so prepend with SELECT)
        $parser = new PHPSQLParser(options: [Options::ANSI_QUOTES => true]);
        $parsed = $parser->parse('SELECT ' . $expression);
        $this->normaliseExpressionAST($parsed);
        // Render back out as an SQL expression string
        $sqlCreator = new PHPSQLCreator($parsed);
        return preg_replace('/^SELECT /', '', $sqlCreator->created);
    }

    private function normaliseExpressionAST(array &$ast, ?array &$parent = null, string|int $i = 'root'): void
    {
        if (isset($ast['base_expr']) && isset($ast['expr_type'])) {
            // Normalise casing for reserved keywords
            if (PHPSQLParserConstants::getInstance()->isReserved(strtoupper($ast['base_expr']))) {
                $ast['base_expr'] = strtoupper($ast['base_expr']);
            }

            // Normalise column references
            if ($ast['expr_type'] === ExpressionType::COLREF) {
                // Remove unnecessary charset references
                $charset = '_' . MySQLDatabase::config()->get('charset');
                if ($ast['base_expr'] === $charset) {
                    unset($parent[$i]);
                // Normalise column references to be ANSI quotes
                } elseif (isset($ast['no_quotes'])
                    // If a column reference starts with an underscore and precedes a constant value, it's a charset (e.g. _utf8)
                    && !(str_starts_with($ast['base_expr'], '_') && isset($parent[$i + 1]['expr_type']) && $parent[$i + 1]['expr_type'] === ExpressionType::CONSTANT)) {
                    // Replace backtick quotes from around column references with ANSI quotes
                    $ast['base_expr'] = $this->quoteColumnSpecString(implode($ast['no_quotes']['delim'] ?: '', $ast['no_quotes']['parts']));
                }
            }

            // MySQL sometimes puts unnecessary brackets around various clauses.
            // We need to remove those in case we didn't put them in our original expression.
            // We target specific clauses because these are known scenarios where brackets are added but not necessary.
            // We can't just blindly collapse all bracket expressions because in many cases brackets will affect
            // operation order, e.g. "2 x (1 + 3)"
            if (is_int($i) && $ast['expr_type'] === ExpressionType::BRACKET_EXPRESSION && is_array($ast['sub_tree'])) {
                $children = $ast['sub_tree'];
                // e.g. "(x + y * z)" as the whole expression
                if ($i === 0 && !isset($parent[$i + 1])
                    // e.g. "(CASE WHEN x=y THEN x ELSE y END)"
                    || (strtoupper($children[0]['base_expr']) === 'CASE' && strtoupper($children[array_key_last($children)]['base_expr']) === 'END')
                ) {
                    $ast['expr_type'] = ExpressionType::EXPRESSION;
                // e.g. "CASE WHEN (x=y) THEN"
                } elseif (isset($parent[$i - 1]['base_expr']) && strtoupper($parent[$i - 1]['base_expr']) === 'WHEN') {
                    $this->normaliseExpressionAST($children, $ast, 'sub_tree');
                    array_splice($parent, $i, 1, $children);
                    // No need to recurse into children, we already normalised them before popping them back into the parent AST
                    return;
                }
            }
        }

        // Recurse into sub-elements.
        for ($n = count($ast) - 1; $n >= 0; $n--) {
            $childIndex = array_keys($ast)[$n];
            if (is_array($ast[$childIndex])) {
                $this->normaliseExpressionAST($ast[$childIndex], $ast, $n);
            }
        }
    }
}
