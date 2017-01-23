<?php
/**
 * Utility class to facilitate complex CSV-imports by defining column-mappings
 * and custom converters.
 *
 * Uses the fgetcsv() function to process CSV input. Accepts a file-handler as
 * input.
 *
 * @see http://tools.ietf.org/html/rfc4180
 *
 * @package framework
 * @subpackage bulkloading
 *
 * @todo Support for deleting existing records not matched in the import
 * (through relation checks)
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
	 * Identifies if csv the has a header row.
	 *
	 * @var boolean
	 */
	public $hasHeaderRow = true;

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
	public function preview($filepath) {
		return $this->processAll($filepath, true);
	}

	/**
	 * @param string $filepath
	 * @param boolean $preview
	 *
	 * @return null|BulkLoader_Result
	 */
	protected function processAll($filepath, $preview = false) {
		$filepath = Director::getAbsFile($filepath);
		$files = $this->splitFile($filepath);

		$result = null;
		$last = null;

		try {
			foreach ($files as $file) {
				$last = $file;

				$next = $this->processChunk($file, $preview);

				if ($result instanceof BulkLoader_Result) {
					$result->merge($next);
				} else {
					$result = $next;
				}

				@unlink($file);
			}
		} catch (Exception $e) {
			$failedMessage = sprintf("Failed to parse %s", $last);
			if (Director::isDev()) {
				$failedMessage = sprintf($failedMessage . " because %s", $e->getMessage());
			}
			print $failedMessage . PHP_EOL;
		}

		return $result;
	}

	/**
	 * Splits a large file up into many smaller files.
	 *
	 * @param string $path Path to large file to split
	 * @param int $lines Number of lines per file
	 *
	 * @return array List of file paths
	 */
	protected function splitFile($path, $lines = null) {
		$previous = ini_get('auto_detect_line_endings');

		ini_set('auto_detect_line_endings', true);

		if (!is_int($lines)) {
			$lines = $this->config()->get("lines");
		}

		$new = $this->getNewSplitFileName();

		$to = fopen($new, 'w+');
		$from = fopen($path, 'r');

		$header = null;

		if ($this->hasHeaderRow) {
			$header = fgets($from);
			fwrite($to, $header);
		}

		$files = array();
		$files[] = $new;

		$count = 0;

		while (!feof($from)) {
			fwrite($to, fgets($from));

			$count++;

			if ($count >= $lines) {
				fclose($to);

				// get a new temporary file name, to write the next lines to
				$new = $this->getNewSplitFileName();

				$to = fopen($new, 'w+');

				if ($this->hasHeaderRow) {
					// add the headers to the new file
					fwrite($to, $header);
				}

				$files[] = $new;

				$count = 0;
			}
		}

		fclose($to);

		ini_set('auto_detect_line_endings', $previous);

		return $files;
	}

	/**
	 * @return string
	 */
	protected function getNewSplitFileName() {
		return TEMP_FOLDER . '/' . uniqid('BulkLoader', true) . '.csv';
	}

	/**
	 * @param string $filepath
	 * @param boolean $preview
	 *
	 * @return BulkLoader_Result
	 */
	protected function processChunk($filepath, $preview = false) {
		$results = new BulkLoader_Result();

		$csv = new CSVParser(
			$filepath,
			$this->delimiter,
			$this->enclosure
		);

		// ColumnMap has two uses, depending on whether hasHeaderRow is set
		if($this->columnMap) {
			// if the map goes to a callback, use the same key value as the map
			// value, rather than function name as multiple keys may use the
			// same callback
			foreach($this->columnMap as $k => $v) {
				if(strpos($v, "->") === 0) {
					$map[$k] = $k;
				} else {
					$map[$k] = $v;
				}
			}

			if($this->hasHeaderRow) {
				$csv->mapColumns($map);
			} else {
				$csv->provideHeaderRow($map);
			}
		}

		foreach($csv as $row) {
			$this->processRecord($row, $this->columnMap, $results, $preview);
		}

		return $results;
	}

	/**
	 * @todo Better messages for relation checks and duplicate detection
	 * Note that columnMap isn't used.
	 *
	 * @param array $record
	 * @param array $columnMap
	 * @param BulkLoader_Result $results
	 * @param boolean $preview
	 *
	 * @return int
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
					$relationClass = $obj->hasOneComponent($relationName);
					$relationObj = new $relationClass();
					//write if we aren't previewing
					if (!$preview) $relationObj->write();
				}
				$obj->{"{$relationName}ID"} = $relationObj->ID;
				//write if we are not previewing
				if (!$preview) {
					$obj->write();
					$obj->flushCache(); // avoid relation caching confusion
				}

			} elseif(strpos($fieldName, '.') !== false) {
				// we have a relation column with dot notation
				list($relationName, $columnName) = explode('.', $fieldName);
				// always gives us an component (either empty or existing)
				$relationObj = $obj->getComponent($relationName);
				if (!$preview) $relationObj->write();
				$obj->{"{$relationName}ID"} = $relationObj->ID;

				//write if we are not previewing
				if (!$preview) {
					$obj->write();
					$obj->flushCache(); // avoid relation caching confusion
				}
			}
		}

		// second run: save data

		foreach($record as $fieldName => $val) {
			// break out of the loop if we are previewing
			if ($preview) {
				break;
			}

			// look up the mapping to see if this needs to map to callback
			$mapped = $this->columnMap && isset($this->columnMap[$fieldName]);

			if($mapped && strpos($this->columnMap[$fieldName], '->') === 0) {
				$funcName = substr($this->columnMap[$fieldName], 2);

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
	 * Find an existing objects based on one or more uniqueness columns
	 * specified via {@link self::$duplicateChecks}.
	 *
	 * @param array $record CSV data column
	 *
	 * @return mixed
	 */
	public function findExistingObject($record) {
		$SNG_objectClass = singleton($this->objectClass);
		// checking for existing records (only if not already found)

		foreach($this->duplicateChecks as $fieldName => $duplicateCheck) {
			if(is_string($duplicateCheck)) {

				// Skip current duplicate check if field value is empty
				if(empty($record[$duplicateCheck])) continue;

				// Check existing record with this value
				$dbFieldValue = $record[$duplicateCheck];
				$existingRecord = DataObject::get($this->objectClass)
					->filter($duplicateCheck, $dbFieldValue)
					->first();

				if($existingRecord) return $existingRecord;
			} elseif(is_array($duplicateCheck) && isset($duplicateCheck['callback'])) {
				if($this->hasMethod($duplicateCheck['callback'])) {
					$existingRecord = $this->{$duplicateCheck['callback']}($record[$fieldName], $record);
				} elseif($SNG_objectClass->hasMethod($duplicateCheck['callback'])) {
					$existingRecord = $SNG_objectClass->{$duplicateCheck['callback']}($record[$fieldName], $record);
				} else {
					user_error("CsvBulkLoader::processRecord():"
						. " {$duplicateCheck['callback']} not found on importer or object class.", E_USER_ERROR);
				}

				if($existingRecord) {
					return $existingRecord;
				}
			} else {
				user_error('CsvBulkLoader::processRecord(): Wrong format for $duplicateChecks', E_USER_ERROR);
			}
		}

		return false;
	}

	/**
	 * Determine whether any loaded files should be parsed with a
	 * header-row (otherwise we rely on {@link self::$columnMap}.
	 *
	 * @return boolean
	 */
	public function hasHeaderRow() {
		return ($this->hasHeaderRow || isset($this->columnMap));
	}
}
