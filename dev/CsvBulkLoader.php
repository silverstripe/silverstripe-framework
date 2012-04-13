<?php
/**
 * Utility class to facilitate complex CSV-imports by defining column-mappings and custom converters. 
 * Uses the fgetcsv() function to process CSV input. Accepts a file-handler as input.
 * 
 * @see http://rfc.net/rfc4180.html
 * @package framework
 * @subpackage bulkloading
 * @author Ingo Schommer, Silverstripe Ltd. (<myfirstname>@silverstripe.com)
 * 
 * @todo Support for deleting existing records not matched in the import (through relation checks)
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
	 * Identifies if the has a header row.
	 * @var boolean
	 */
	public $hasHeaderRow = true;
	
	protected function processAll($filepath, $preview = false) {
		$results = new BulkLoader_Result();
		
		$csv = new CSVParser($filepath, $this->delimiter, $this->enclosure);
		
		// ColumnMap has two uses, depending on whether hasHeaderRow is set
		if($this->columnMap) {
			if($this->hasHeaderRow) $csv->mapColumns($this->columnMap);
			else $csv->provideHeaderRow($this->columnMap);
		}
		
		foreach($csv as $row) {
			$this->processRecord($row, $this->columnMap, $results, $preview);
		}
		
		return $results;
	}
	
	/**
	 * @todo Better messages for relation checks and duplicate detection
	 * Note that columnMap isn't used
	 */
	protected function processRecord($record, $columnMap, &$results, $preview = false) {
		$class = $this->objectClass;
		
		// find existing object, or create new one
		$existingObj = $this->findExistingObject($record, $columnMap);
		$obj = ($existingObj) ? $existingObj : new $class(); 
		
		// first run: find/create any relations and store them on the object
		// we can't combine runs, as other columns might rely on the relation being present
		$relations = array();
		foreach($record as $fieldName => $val) {
			// don't bother querying of value is not set
			if($this->isNullValue($val)) continue;
			
			// checking for existing relations
			if(isset($this->relationCallbacks[$fieldName])) {
				// trigger custom search method for finding a relation based on the given value
				// and write it back to the relation (or create a new object)
				$relationName = $this->relationCallbacks[$fieldName]['relationname'];
				if($this->hasMethod($this->relationCallbacks[$fieldName]['callback'])) {
					$relationObj = $this->{$this->relationCallbacks[$fieldName]['callback']}($obj, $val, $record);
				} elseif($obj->hasMethod($this->relationCallbacks[$fieldName]['callback'])) {
					$relationObj = $obj->{$this->relationCallbacks[$fieldName]['callback']}($val, $record);
				}
				if(!$relationObj || !$relationObj->exists()) {
					$relationClass = $obj->has_one($relationName);
					$relationObj = new $relationClass();
					$relationObj->write();
				}
				$obj->{"{$relationName}ID"} = $relationObj->ID;
				$obj->write();
				$obj->flushCache(); // avoid relation caching confusion
				
			} elseif(strpos($fieldName, '.') !== false) {
				// we have a relation column with dot notation
				list($relationName,$columnName) = explode('.', $fieldName);
				$relationObj = $obj->getComponent($relationName); // always gives us an component (either empty or existing)
				$relationObj->write();
				$obj->{"{$relationName}ID"} = $relationObj->ID;
				$obj->write();
				$obj->flushCache(); // avoid relation caching confusion
			}
			
		}

		// second run: save data
		foreach($record as $fieldName => $val) {
			if($this->isNullValue($val, $fieldName)) continue;
			if(strpos($fieldName, '->') !== FALSE) {
				$funcName = substr($fieldName, 2);
				$this->$funcName($obj, $val, $record);
			} else if($obj->hasMethod("import{$fieldName}")) {
				$obj->{"import{$fieldName}"}($val, $record);
			} else {
				$obj->update(array($fieldName => $val));
			}
		}

		// write record
		$id = ($preview) ? 0 : $obj->write();
		
		// @todo better message support
		$message = '';
		
		// save to results
		if($existingObj) {
			$results->addUpdated($obj, $message);
		} else {
			$results->addCreated($obj, $message);
		}
		
		$objID = $obj->ID;
		
		$obj->destroy();
		
		// memory usage
		unset($existingObj);
		unset($obj);
		
		return $objID;
	}
	
	/**
	 * Find an existing objects based on one or more uniqueness
	 * columns specified via {@link self::$duplicateChecks}
	 *
	 * @param array $record CSV data column
	 * @return unknown
	 */
	public function findExistingObject($record) {
		$SNG_objectClass = singleton($this->objectClass);
		
		// checking for existing records (only if not already found)
		foreach($this->duplicateChecks as $fieldName => $duplicateCheck) {
			if(is_string($duplicateCheck)) {
				$SQL_fieldName = Convert::raw2sql($duplicateCheck); 
				if(!isset($record[$fieldName])) {
					return false;
					//user_error("CsvBulkLoader:processRecord: Couldn't find duplicate identifier '{$fieldName}' in columns", E_USER_ERROR);
				}
				$SQL_fieldValue = $record[$fieldName];
				$existingRecord = DataObject::get_one($this->objectClass, "\"$SQL_fieldName\" = '{$SQL_fieldValue}'");
				if($existingRecord) return $existingRecord;
			} elseif(is_array($duplicateCheck) && isset($duplicateCheck['callback'])) {
				if($this->hasMethod($duplicateCheck['callback'])) {
					$existingRecord = $this->{$duplicateCheck['callback']}($record[$fieldName], $record);
				} elseif($SNG_objectClass->hasMethod($duplicateCheck['callback'])) {
					$existingRecord = $SNG_objectClass->{$duplicateCheck['callback']}($record[$fieldName], $record);
				} else {
					user_error("CsvBulkLoader::processRecord(): {$duplicateCheck['callback']} not found on importer or object class.", E_USER_ERROR);
				}
				
				if($existingRecord) return $existingRecord;
			} else {
				user_error('CsvBulkLoader::processRecord(): Wrong format for $duplicateChecks', E_USER_ERROR);
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
