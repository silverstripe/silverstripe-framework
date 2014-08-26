<?php

class CSVListExporter implements SS_ListExporter{
	
	protected $list;

	protected $exportColumns;

	protected $csvSeparator = ",";

	protected $csvHasHeader = true;

	protected $callback;

	function __construct(SS_List $list) {
		$this->list = $list;
	}

	public function setHasHeader(bool $hasheader) {
		$this->csvHasHeader = $hasheader;
		return $this;
	}

	public function setSeperator(string $seperator) {
		$this->csvSeparator = $seperator;
		return $this;
	}

	public function setColumns(array $columns) {
		$this->exportColumns = $columns;
		return $this;
	}

	public function getColumns() {
		return $this->exportColumns;
		//TODO: fall back to work out automatically from list model class
	}

	public function setDataCallback(Closure $callback) {
		$this->callback = $callback;
		return $this;
	}

	function export() {
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

	protected function getFieldValue($record, $fieldName) {
		if($record->hasMethod('relField')) {
			return $record->relField($fieldName);
		} elseif($record->hasMethod($fieldName)) {
			return $record->$fieldName();
		}
		return $record->$fieldName;
	}

}