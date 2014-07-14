<?php

/**
 * Represents schema management object for MySQL
 *
 * @package framework
 * @subpackage model
 */
class MySQLSchemaManager extends DBSchemaManager {

	/**
	 * Identifier for this schema, used for configuring schema-specific table
	 * creation options
	 */
	const ID = 'MySQLDatabase';

	public function createTable($table, $fields = null, $indexes = null, $options = null, $advancedOptions = null) {
		$fieldSchemas = $indexSchemas = "";

		if (!empty($options[self::ID])) {
			$addOptions = $options[self::ID];
		} else {
			$addOptions = "ENGINE=InnoDB";
		}

		if (!isset($fields['ID'])) {
			$fields['ID'] = "int(11) not null auto_increment";
		}
		if ($fields) {
			foreach ($fields as $k => $v)
				$fieldSchemas .= "\"$k\" $v,\n";
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

	public function alterTable($tableName, $newFields = null, $newIndexes = null, $alteredFields = null,
		$alteredIndexes = null, $alteredOptions = null, $advancedOptions = null
	) {
		if ($this->isView($tableName)) {
			$this->alterationMessage(
				sprintf("Table %s not changed as it is a view", $tableName),
				"changed"
			);
			return;
		}
		$alterList = array();

		if ($newFields) {
			foreach ($newFields as $k => $v) {
				$alterList[] .= "ADD \"$k\" $v";
			}
		}
		if ($newIndexes) {
			foreach ($newIndexes as $k => $v) {
				$alterList[] .= "ADD " . $this->getIndexSqlDefinition($k, $v);
			}
		}
		if ($alteredFields) {
			foreach ($alteredFields as $k => $v) {
				$alterList[] .= "CHANGE \"$k\" \"$k\" $v";
			}
		}
		if ($alteredIndexes) {
			foreach ($alteredIndexes as $k => $v) {
				$alterList[] .= "DROP INDEX \"$k\"";
				$alterList[] .= "ADD " . $this->getIndexSqlDefinition($k, $v);
			}
		}

		$dbID = self::ID;
		if ($alteredOptions && isset($alteredOptions[$dbID])) {
			$indexList = $this->indexList($tableName);
			$skip = false;
			foreach ($indexList as $index) {
				if ($index['type'] === 'fulltext') {
					$skip = true;
					break;
				}
			}
			if ($skip) {
				$this->alterationMessage(
					sprintf(
						"Table %s options not changed to %s due to fulltextsearch index",
						$tableName,
						$alteredOptions[$dbID]
					),
					"changed"
				);
			} else {
				$this->query(sprintf("ALTER TABLE \"%s\" %s", $tableName, $alteredOptions[$dbID]));
				$this->alterationMessage(
					sprintf("Table %s options changed: %s", $tableName, $alteredOptions[$dbID]),
					"changed"
				);
			}
		}

		$alterations = implode(",\n", $alterList);
		$this->query("ALTER TABLE \"$tableName\" $alterations");
	}

	public function isView($tableName) {
		$info = $this->query("SHOW /*!50002 FULL*/ TABLES LIKE '$tableName'")->record();
		return $info && strtoupper($info['Table_type']) == 'VIEW';
	}

	public function renameTable($oldTableName, $newTableName) {
		$this->query("ALTER TABLE \"$oldTableName\" RENAME \"$newTableName\"");
	}

	public function checkAndRepairTable($tableName) {
		// Flag to ensure we only send the warning about PDO + native mode once
		static $pdo_warning_sent = false;

		// If running PDO and not in emulated mode, check table will fail
		if($this->database->getConnector() instanceof PDOConnector && !PDOConnector::is_emulate_prepare()) {
			if (!$pdo_warning_sent) {
				$this->alterationMessage('CHECK TABLE command disabled for PDO in native mode', 'notice');
				$pdo_warning_sent = true;
			}

			return true;
		}

		// Perform check
		if (!$this->runTableCheckCommand("CHECK TABLE \"$tableName\"")) {
			if ($this->runTableCheckCommand("CHECK TABLE \"" . strtolower($tableName) . "\"")) {
				$this->alterationMessage(
					"Table $tableName: renamed from lowercase",
					"repaired"
				);
				return $this->renameTable(strtolower($tableName), $tableName);
			}

			$this->alterationMessage(
				"Table $tableName: repaired",
				"repaired"
			);
			return $this->runTableCheckCommand("REPAIR TABLE \"$tableName\" USE_FRM");
		} else {
			return true;
		}
	}

	/**
	 * Helper function used by checkAndRepairTable.
	 * @param string $sql Query to run.
	 * @return boolean Returns if the query returns a successful result.
	 */
	protected function runTableCheckCommand($sql) {
		$testResults = $this->query($sql);
		foreach ($testResults as $testRecord) {
			if (strtolower($testRecord['Msg_text']) != 'ok') {
				return false;
			}
		}
		return true;
	}

	public function hasTable($table) {
		// MySQLi doesn't like parameterised queries for some queries
		$sqlTable = $this->database->quoteString($table);
		return (bool) ($this->query("SHOW TABLES LIKE $sqlTable")->value());
	}

	public function createField($tableName, $fieldName, $fieldSpec) {
		$this->query("ALTER TABLE \"$tableName\" ADD \"$fieldName\" $fieldSpec");
	}

	public function databaseList() {
		return $this->query("SHOW DATABASES")->column();
	}

	public function databaseExists($name) {
		// MySQLi doesn't like parameterised queries for some queries
		$sqlName = $this->database->quoteString($name);
		return !!($this->query("SHOW DATABASES LIKE $sqlName")->value());
	}

	public function createDatabase($name) {
		$charset = Config::inst()->get('MySQLDatabase', 'charset');
		$collation = Config::inst()->get('MySQLDatabase', 'collation');
		$this->query("CREATE DATABASE \"$name\" DEFAULT CHARACTER SET {$charset} DEFAULT COLLATE {$collation}");
	}

	public function dropDatabase($name) {
		$this->query("DROP DATABASE \"$name\"");
	}

	/**
	 * Change the database type of the given field.
	 * @param string $tableName The name of the tbale the field is in.
	 * @param string $fieldName The name of the field to change.
	 * @param string $fieldSpec The new field specification
	 */
	public function alterField($tableName, $fieldName, $fieldSpec) {
		$this->query("ALTER TABLE \"$tableName\" CHANGE \"$fieldName\" \"$fieldName\" $fieldSpec");
	}

	/**
	 * Change the database column name of the given field.
	 *
	 * @param string $tableName The name of the tbale the field is in.
	 * @param string $oldName The name of the field to change.
	 * @param string $newName The new name of the field
	 */
	public function renameField($tableName, $oldName, $newName) {
		$fieldList = $this->fieldList($tableName);
		if (array_key_exists($oldName, $fieldList)) {
			$this->query("ALTER TABLE \"$tableName\" CHANGE \"$oldName\" \"$newName\" " . $fieldList[$oldName]);
		}
	}

	protected static $_cache_collation_info = array();

	public function fieldList($table) {
		$fields = $this->query("SHOW FULL FIELDS IN \"$table\"");
		foreach ($fields as $field) {

			// ensure that '' is converted to \' in field specification (mostly for the benefit of ENUM values)
			$fieldSpec = str_replace('\'\'', '\\\'', $field['Type']);
			if (!$field['Null'] || $field['Null'] == 'NO') {
				$fieldSpec .= ' not null';
			}

			if ($field['Collation'] && $field['Collation'] != 'NULL') {
				// Cache collation info to cut down on database traffic
				if (!isset(self::$_cache_collation_info[$field['Collation']])) {
					self::$_cache_collation_info[$field['Collation']]
						= $this->query("SHOW COLLATION LIKE '{$field['Collation']}'")->record();
				}
				$collInfo = self::$_cache_collation_info[$field['Collation']];
				$fieldSpec .= " character set $collInfo[Charset] collate $field[Collation]";
			}

			if ($field['Default'] || $field['Default'] === "0") {
				$fieldSpec .= " default " . $this->database->quoteString($field['Default']);
			}
			if ($field['Extra']) $fieldSpec .= " " . $field['Extra'];

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
	public function createIndex($tableName, $indexName, $indexSpec) {
		$this->query("ALTER TABLE \"$tableName\" ADD " . $this->getIndexSqlDefinition($indexName, $indexSpec));
	}

	/**
	 * Generate SQL suitable for creating this index
	 *
	 * @param string $indexName
	 * @param string|array $indexSpec See {@link requireTable()} for details
	 * @return string MySQL compatible ALTER TABLE syntax
	 */
	protected function getIndexSqlDefinition($indexName, $indexSpec) {
		$indexSpec = $this->parseIndexSpec($indexName, $indexSpec);
		if ($indexSpec['type'] == 'using') {
			return "index \"$indexName\" using ({$indexSpec['value']})";
		} else {
			return "{$indexSpec['type']} \"$indexName\" ({$indexSpec['value']})";
		}
	}

	public function alterIndex($tableName, $indexName, $indexSpec) {
		$indexSpec = $this->parseIndexSpec($indexName, $indexSpec);
		$this->query("ALTER TABLE \"$tableName\" DROP INDEX \"$indexName\"");
		$this->query("ALTER TABLE \"$tableName\" ADD {$indexSpec['type']} \"$indexName\" {$indexSpec['value']}");
	}

	protected function indexKey($table, $index, $spec) {
		// MySQL simply uses the same index name as SilverStripe does internally
		return $index;
	}

	public function indexList($table) {
		$indexes = $this->query("SHOW INDEXES IN \"$table\"");
		$groupedIndexes = array();
		$indexList = array();

		foreach ($indexes as $index) {
			$groupedIndexes[$index['Key_name']]['fields'][$index['Seq_in_index']] = $index['Column_name'];

			if ($index['Index_type'] == 'FULLTEXT') {
				$groupedIndexes[$index['Key_name']]['type'] = 'fulltext';
			} else if (!$index['Non_unique']) {
				$groupedIndexes[$index['Key_name']]['type'] = 'unique';
			} else if ($index['Index_type'] == 'HASH') {
				$groupedIndexes[$index['Key_name']]['type'] = 'hash';
			} else if ($index['Index_type'] == 'RTREE') {
				$groupedIndexes[$index['Key_name']]['type'] = 'rtree';
			} else {
				$groupedIndexes[$index['Key_name']]['type'] = 'index';
			}
		}

		if ($groupedIndexes) {
			foreach ($groupedIndexes as $index => $details) {
				ksort($details['fields']);
				$indexList[$index] = $this->parseIndexSpec($index, array(
					'name' => $index,
					'value' => $this->implodeColumnList($details['fields']),
					'type' => $details['type']
				));
			}
		}

		return $indexList;
	}

	public function tableList() {
		$tables = array();
		foreach ($this->query("SHOW TABLES") as $record) {
			$table = reset($record);
			$tables[strtolower($table)] = $table;
		}
		return $tables;
	}

	public function enumValuesForField($tableName, $fieldName) {
		// Get the enum of all page types from the SiteTree table
		$classnameinfo = $this->query("DESCRIBE \"$tableName\" \"$fieldName\"")->first();
		preg_match_all("/'[^,]+'/", $classnameinfo["Type"], $matches);

		$classes = array();
		foreach ($matches[0] as $value) {
			$classes[] = stripslashes(trim($value, "'"));
		}
		return $classes;
	}

	public function dbDataType($type) {
		$values = Array(
			'unsigned integer' => 'UNSIGNED'
		);

		if (isset($values[$type])) return $values[$type];
		else return '';
	}

	/**
	 * Return a boolean type-formatted string
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function boolean($values) {
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'tinyint', 'precision'=>1, 'sign'=>'unsigned', 'null'=>'not null',
		//'default'=>$this->default);
		//DB::requireField($this->tableName, $this->name, "tinyint(1) unsigned not null default
		//'{$this->defaultVal}'");
		return 'tinyint(1) unsigned not null' . $this->defaultClause($values);
	}

	/**
	 * Return a date type-formatted string
	 * For MySQL, we simply return the word 'date', no other parameters are necessary
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function date($values) {
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
	public function decimal($values) {
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
			$decs = strpos($precision, ',') !== false
					? (int) substr($precision, strpos($precision, ',') + 1)
					: 0;
			$values['default'] = number_format($values['default'], $decs, '.', '');
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
	public function enum($values) {
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'enum', 'enums'=>$this->enum, 'character set'=>'utf8', 'collate'=>
		// 'utf8_general_ci', 'default'=>$this->default);
		//DB::requireField($this->tableName, $this->name, "enum('" . implode("','", $this->enum) . "') character set
		// utf8 collate utf8_general_ci default '{$this->default}'");
		$valuesString = implode(",", Convert::raw2sql($values['enums'], true));
		$charset = Config::inst()->get('MySQLDatabase', 'charset');
		$collation = Config::inst()->get('MySQLDatabase', 'collation');
		return "enum($valuesString) character set {$charset} collate {$collation}" . $this->defaultClause($values);
	}

	/**
	 * Return a set type-formatted string
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function set($values) {
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'enum', 'enums'=>$this->enum, 'character set'=>'utf8', 'collate'=>
		// 'utf8_general_ci', 'default'=>$this->default);
		//DB::requireField($this->tableName, $this->name, "enum('" . implode("','", $this->enum) . "') character set
		//utf8 collate utf8_general_ci default '{$this->default}'");
		$valuesString = implode(",", Convert::raw2sql($values['enums'], true));
		$charset = Config::inst()->get('MySQLDatabase', 'charset');
		$collation = Config::inst()->get('MySQLDatabase', 'collation');
		return "set($valuesString) character set {$charset} collate {$collation}" . $this->defaultClause($values);
	}

	/**
	 * Return a float type-formatted string
	 * For MySQL, we simply return the word 'date', no other parameters are necessary
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function float($values) {
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
	public function int($values) {
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'int', 'precision'=>11, 'null'=>'not null', 'default'=>(int)$this->default);
		//DB::requireField($this->tableName, $this->name, "int(11) not null default '{$this->defaultVal}'");
		return "int(11) not null" . $this->defaultClause($values);
	}

	/**
	 * Return a bigint type-formatted string
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function bigint($values) {
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'bigint', 'precision'=>20, 'null'=>'not null', 'default'=>$this->defaultVal,
		//             'arrayValue'=>$this->arrayValue);
		//$values=Array('type'=>'bigint', 'parts'=>$parts);
		//DB::requireField($this->tableName, $this->name, $values);

		return 'bigint(20) not null' . $this->defaultClause($values);
	}

	/**
	 * Return a datetime type-formatted string
	 * For MySQL, we simply return the word 'datetime', no other parameters are necessary
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function ss_datetime($values) {
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
	public function text($values) {
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'mediumtext', 'character set'=>'utf8', 'collate'=>'utf8_general_ci');
		//DB::requireField($this->tableName, $this->name, "mediumtext character set utf8 collate utf8_general_ci");
		$charset = Config::inst()->get('MySQLDatabase', 'charset');
		$collation = Config::inst()->get('MySQLDatabase', 'collation');
		return 'mediumtext character set ' . $charset . ' collate ' . $collation . $this->defaultClause($values);
	}

	/**
	 * Return a time type-formatted string
	 * For MySQL, we simply return the word 'time', no other parameters are necessary
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function time($values) {
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
	public function varchar($values) {
		//For reference, this is what typically gets passed to this function:
		//$parts=Array('datatype'=>'varchar', 'precision'=>$this->size, 'character set'=>'utf8', 'collate'=>
		//'utf8_general_ci');
		//DB::requireField($this->tableName, $this->name, "varchar($this->size) character set utf8 collate
		// utf8_general_ci");
		$default = $this->defaultClause($values);
		$charset = Config::inst()->get('MySQLDatabase', 'charset');
		$collation = Config::inst()->get('MySQLDatabase', 'collation');
		return "varchar({$values['precision']}) character set {$charset} collate {$collation}{$default}";
	}

	/*
	 * Return the MySQL-proprietary 'Year' datatype
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string
	 */
	public function year($values) {
		return 'year(4)';
	}

	public function IdColumn($asDbValue = false, $hasAutoIncPK = true) {
		return 'int(11) not null auto_increment';
	}

	/**
	 * Parses and escapes the default values for a specification
	 *
	 * @param array $values Contains a tokenised list of info about this data type
	 * @return string Default clause
	 */
	protected function defaultClause($values) {
		if(isset($values['default'])) {
			return ' default ' . $this->database->quoteString($values['default']);
		}
		return '';
	}

}
