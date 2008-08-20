<?php
/**
 * Uses the fgetcsv() function to process CSV input.
 * The input is expected to be UTF8.
 * 
 * @see http://rfc.net/rfc4180.html
 * @package cms
 * @subpackage bulkloading
 * @author Ingo Schommer, Silverstripe Ltd. (<firstname>@silverstripe.com)
 */
class CsvBulkLoader extends BulkLoader {
	
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
	 * Identifies if the loaded file has a header row.
	 * If a {@link self::$columnMap} is passed, we assume
	 * the file has no headerrow, unless explicitly noted.
	 *
	 * @var boolean
	 */
	public $hasHeaderRow = false;
	
	protected function processAll($filepath, $preview = false) {
		$file = fopen($filepath, 'r');
		if(!$file) return false;
		
		//$return = new DataObjectSet();
		$numRecords = 0;

		if($this->hasHeaderRow && $this->columnMap) {
			$columnRow = fgetcsv($file, 0, $this->delimiter, $this->enclosure);
			$columnMap = array();
			foreach($columnRow as $k => $origColumnName) {
				$origColumnName = trim($origColumnName);
				if(isset($this->columnMap[$origColumnName])) {
					$columnMap[$origColumnName] = $this->columnMap[$origColumnName];
				} else {
					$columnMap[$origColumnName] = null;
				}
				
			}
		} elseif($this->columnMap) {
			$columnMap = $this->columnMap;
		} else {
			// assuming that first row is column naming if no columnmap is passed
			$columnRow = fgetcsv($file, 0, $this->delimiter, $this->enclosure);
			$columnMap = array_combine($columnRow, $columnRow);
		}

		$rowIndex = 0;
		$rowIndex = 0;
		while (($row = fgetcsv($file, 0, $this->delimiter, $this->enclosure)) !== FALSE) {
			$rowIndex++;
			
			/*
			// the columnMap should have the same amount of columns as each record row
			if(count(array_keys($columnMap)) == count(array_values($row))) {
				user_error("CsvBulkLoader::processAll(): Columns in row {$rowIndex} don't match the \$columnMap", E_USER_WARNING);
			}
			*/
			
			$indexedRow = array();
			foreach($columnMap as $origColumnName => $fieldName) {
				// in case the row has less fields than the columnmap,
				// ignore the "leftover" mappings
				if(!isset($row[count($indexedRow)])) {
						user_error("CsvBulkLoader::processAll(): Columns in row {$rowIndex} don't match the \$columnMap", E_USER_NOTICE);
					continue;
				}
	
				$indexedRow[$origColumnName] = $row[count($indexedRow)];
			}
			$numRecords++;
			$this->processRecord($indexedRow, $columnMap);
			//$return->push();
		}
		
		fclose($file);
		
		return $numRecords;
	}
	
	protected function processRecord($record, $columnMap, $preview = false) {
		$class = $this->objectClass;
		
		// find existing object, or create new one
		$existingObj = $this->findExistingObject($record, $columnMap);
		$obj = ($existingObj) ? $existingObj : new $class(); 
		
		// first run: find/create any relations and store them on the object
		// we can't combine runs, as other columns might rely on the relation being present
		$relations = array();
		foreach($record as $origColumnName => $val) {
			$fieldName = $columnMap[$origColumnName];
			
			// don't bother querying of value is not set
			if($this->isNullValue($val)) continue;
			
			// checking for existing relations
			if(isset($this->relationCallbacks[$fieldName])) {
				// trigger custom search method for finding a relation based on the given value
				// and write it back to the relation (or create a new object)
				$relationName = $this->relationCallbacks[$fieldName]['relationname'];
				$relationObj = $obj->{$this->relationCallbacks[$fieldName]['callback']}($val, $record);
				if(!$relationObj || !$relationObj->exists()) {
					$relationClass = $obj->has_one($relationName);
					$relationObj = new $relationClass();
					$relationObj->write();
				}
				$obj->setComponent($relationName, $relationObj);
				$obj->{"{$relationName}ID"} = $relationObj->ID;
				$obj->write();
			} elseif(strpos($fieldName, '.') !== false) {
				// we have a relation column with dot notation
				list($relationName,$columnName) = split('\.', $fieldName);
				$relationObj = $obj->getComponent($relationName); // always gives us an component (either empty or existing)
				$obj->setComponent($relationName, $relationObj);
				$relationObj->write();
				$obj->{"{$relationName}ID"} = $relationObj->ID;
				$obj->write();
			}
			
			$obj->flushCache(); // avoid relation caching confusion
		}
		$id = ($preview) ? 0 : $obj->write();

		// second run: save data
		foreach($record as $origColumnName => $val) {
			$fieldName = $columnMap[$origColumnName];

			if($this->isNullValue($val, $fieldName)) continue;

			if($obj->hasMethod("import{$fieldName}")) {
				$obj->{"import{$fieldName}"}($val, $record);
			} elseif(strpos($fieldName, '.') !== false) {
				// we have a relation column
				list($relationName,$columnName) = split('\.', $fieldName);
				$relationObj = $obj->getComponent($relationName);
				$relationObj->{$columnName} = $val;
				$relationObj->write();
				$obj->flushCache(); // avoid relation caching confusion
			//} elseif($obj->hasField($fieldName) || $obj->hasMethod($fieldName)) {
			} else {
				// plain old value setter
				$obj->{$fieldName} = $val;
			}
		}
		$id = ($preview) ? 0 : $obj->write();
		$action = 'create';
		$message = '';
		
		// memory usage
		unset($existingObj);
		unset($obj);
		
		return new ArrayData(array(
			'id' => $id,
			'action' => $action,
			'message' => $message
		));
	}
	
	/**
	 * Find an existing objects based on one or more uniqueness
	 * columns specified via {@link self::$duplicateChecks}
	 *
	 * @param array $record CSV data column
	 * @param array $columnMap
	 * @return unknown
	 */
	public function findExistingObject($record, $columnMap) {
		// checking for existing records (only if not already found)
		foreach($this->duplicateChecks as $fieldName => $duplicateCheck) {
			if(is_string($duplicateCheck)) {
				$SQL_fieldName = Convert::raw2sql($duplicateCheck); 
				if(!isset($record[$fieldName])) {
					user_error("CsvBulkLoader:processRecord: Couldn't find duplicate identifier '{$fieldName}' in columns", E_USER_ERROR);
				}
				$SQL_fieldValue = $record[$fieldName];
				$existingRecord = DataObject::get_one($this->objectClass, "`$SQL_fieldName` = '{$SQL_fieldValue}'");
				if($existingRecord) return $existingRecord;
			} elseif(is_array($duplicateCheck) && isset($duplicateCheck['callback'])) {
				$existingRecord = singleton($this->objectClass)->{$duplicateCheck['callback']}($record[$fieldName], $record);
				if($existingRecord) return $existingRecord;
			} else {
				user_error('CsvBulkLoader:processRecord: Wrong format for $duplicateChecks', E_USER_ERROR);
			}
		}
		
		return false;
	}
	
	
	/**
	 * Determine wether any loaded files should be parsed
	 * with a header-row (otherwise we rely on {@link self::$columnMap}.
	 *
	 * @return boolean
	 */
	public function hasHeaderRow() {
		return ($this->hasHeaderRow || isset($this->columnMap));
	}
	
}
?>