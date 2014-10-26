<?php
/**
 * Converts a SS_List to a CSV string
 *
 * @package exporters
 */
class CSVListExporter implements SS_ListExporter{
	
	/**
	 * List to extract data from
	 * 
	 * @var SS_List
	 */
	protected $list;

	/**
	 * List of columns to export
	 * 
	 * @var array
	 */
	protected $exportColumns;

	/**
	 * CSV seperator
	 * 
	 * @var string
	 */
	protected $csvSeparator = ",";

	/**
	 * Include header in export
	 * 
	 * @var boolean
	 */
	protected $csvHasHeader = true;

	/**
	 * Function that gets values from a record
	 * 
	 * @var Closure
	 */
	protected $callback;

	public function __construct(SS_List $list) {
		$this->list = $list;
	}

	/**
	 * Determine if exports contain a CSV header.
	 * 
	 * @param boolean $hasHeader
	 */
	public function setHasHeader($hasheader) {
		$this->csvHasHeader = $hasheader;
		return $this;
	}

	/**
	 * Set the character(s) that should be used to seperate
	 * CSV data values.
	 * @param string $seperator
	 */
	public function setSeperator($seperator) {
		$this->csvSeparator = $seperator;
		return $this;
	}

	/**
	 * Set the columns that should be included in export.
	 * @param array $columns
	 */
	public function setColumns(array $columns) {
		$this->exportColumns = $columns;
		return $this;
	}

	/**
	 * Get the columns that will be included in exports.
	 * @return array
	 * @todo fall back to work out automatically from list model class
	 */
	public function getColumns() {
		return $this->exportColumns;	
	}

	/**
	 * Get value from a record in a customised way
	 * The callbake takes two arguments: $record and $field
	 *
	 * Eg:
	 * <code>
	 * $writer->setDataCallback(function($item, $field) use ($gridField) {
	 *		return $gridField->getDataFieldValue($item, $field);
	 * });
	 * </code>
	 * 
	 * @see export()
	 * @param Closure $callback [description]
	 */
	public function setDataCallback(Closure $callback) {
		$this->callback = $callback;
		return $this;
	}

	/**
	 * Export the list to a string
	 * @return string
	 */
	public function export() {
		$fileData = '';
		if($this->csvHasHeader) {
			$headers = array();
			// determine the CSV headers. If a field is callable (e.g. anonymous function) then use the
			// source name as the header instead
			foreach($this->exportColumns as $columnSource => $columnHeader) {
				$headers[] = (!is_string($columnHeader) && is_callable($columnHeader)) ? $columnSource : $columnHeader;
			}
			$fileData .= "\"" . implode("\"{$this->csvSeparator}\"", array_values($headers)) . "\"";
			$fileData .= "\n";
		}
		foreach($this->list->limit(null) as $item) {
			$columnData = array();
			foreach($this->exportColumns as $columnSource => $columnHeader) {
				if(!is_string($columnHeader) && is_callable($columnHeader)) {
					if($item->hasMethod($columnSource)) {
						$relObj = $item->{$columnSource}();
					} else {
						$relObj = $item->relObject($columnSource);
					}
					$value = $columnHeader($relObj);
				} elseif($callback = $this->callback) {
					$value = $callback($item, $columnSource);
				}else{
					$value = $this->getFieldValue($item, $columnSource);
				}
				$value = str_replace(array("\r", "\n"), "\n", $value);
				$columnData[] = '"' . str_replace('"', '\"', $value) . '"';
			}
			$fileData .= implode($this->csvSeparator, $columnData);
			$fileData .= "\n";
			$item->destroy();
		}

		return $fileData;
	}

	/**
	 * Get a field from a list record in various ways.
	 * @param  mixed $record
	 * @param  string $fieldName
	 * @return mixed
	 */
	protected function getFieldValue($record, $fieldName) {
		if($record->hasMethod('relField')) {
			return $record->relField($fieldName);
		} elseif($record->hasMethod($fieldName)) {
			return $record->$fieldName();
		}
		return $record->$fieldName;
	}

}