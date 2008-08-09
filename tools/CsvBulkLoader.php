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
		
		$return = new DataObjectSet();
		
		// assuming that first row is column naming if no columnmap is passed
		if($this->hasHeaderRow && $this->columnMap) {
			$columnRow = fgetcsv($file, 0, $this->delimiter, $this->enclosure);
			$columnMap = $this->columnMap;
		} elseif($this->columnMap) {
			$columnMap = $this->columnMap;
		} else {
			$columnRow = fgetcsv($file, 0, $this->delimiter, $this->enclosure);
			$columnMap = array_combine($columnRow, $columnRow);
		}
		
		while (($row = fgetcsv($file, 0, $this->delimiter, $this->enclosure)) !== FALSE) {
			$indexedRow = array_combine(array_values($columnMap), array_values($row));
			$return->push($this->processRecord($indexedRow));
		}
		
		fclose($file);
		
		return $return;
	}
	

	protected function processRecord($record, $preview = false) {
		$class = $this->objectClass;
		$obj = new $class();
		
		// first run: find/create any relations and store them on the object
		// we can't combine runs, as other columns might rely on the relation being present
		$relations = array();
		foreach($record as $key => $val) {
			if(isset($this->relationCallbacks[$key])) {
				// trigger custom search method for finding a relation based on the given value
				// and write it back to the relation (or create a new object)
				$relationName = $this->relationCallbacks[$key]['relationname'];
				$relationObj = $obj->{$this->relationCallbacks[$key]['callback']}($val, $record);
				if(!$relationObj || !$relationObj->exists()) {
					$relationClass = $obj->has_one($relationName);
					$relationObj = new $relationClass();
					$relationObj->write();
				}
				$obj->setComponent($relationName, $relationObj);
				$obj->{"{$relationName}ID"} = $relationObj->ID;
			} elseif(strpos($key, '.') !== false) {
				// we have a relation column with dot notation
				list($relationName,$columnName) = split('\.', $key);
				$relationObj = $obj->getComponent($relationName); // always gives us an component (either empty or existing)
				$obj->setComponent($relationName, $relationObj);
				$relationObj->write();
				$obj->{"{$relationName}ID"} = $relationObj->ID;
			}
			$obj->flushCache(); // avoid relation caching confusion
		}
		$id = ($preview) ? 0 : $obj->write();

		
		// second run: save data
		foreach($record as $key => $val) {
			if($obj->hasMethod("import{$key}")) {
				$obj->{"import{$key}"}($val, $record);
			} elseif(strpos($key, '.') !== false) {
				// we have a relation column
				list($relationName,$columnName) = split('\.', $key);
				$relationObj = $obj->getComponent($relationName);
				$relationObj->{$columnName} = $val;
				$relationObj->write();
				$obj->flushCache(); // avoid relation caching confusion
			} elseif($obj->hasField($key)) {
				// plain old value setter
				$obj->{$key} = $val;
			}
		}
		$id = ($preview) ? 0 : $obj->write();
		$action = 'create';
		$message = '';
		
		// memory usage
		unset($obj);
		
		return new ArrayData(array(
			'id' => $id,
			'action' => $action,
			'message' => $message
		));
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