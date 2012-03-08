<?php
/**
 * @package sapphire
 * @subpackage gridfield
 */

/**
 * Adds an "Export list" button to the bottom of a GridField.
 * 
 * WARNING: This is experimental and its API is subject to change.  Feel free to use it as long as you are happy of
 * refactoring your code in the future.
 */
class GridFieldExporter implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler {

	/**
	 * @var array Map of a property name on the exported objects, with values being the column title in the CSV file.
	 * Note that titles are only used when {@link $csvHasHeader} is set to TRUE.
	 */
	protected $exportColumns;

	/**
	 * @var string
	 */
	protected $csvSeparator = ",";

	/**
	 * @var boolean
	 */
	protected $csvHasHeader = true;

	/**
	 * @param array
	 */
	public function __construct($exportColumns = null) {
		$this->exportColumns = $exportColumns;
	}

	/**
	 * Place the export button in a <p> tag below the field
	 */
	public function getHTMLFragments($gridField) {
		$button = new GridField_FormAction(
			$gridField, 
			'export', 
			_t('TableListField.CSVEXPORT', 'Export to CSV'),
			'export', 
			null
		);
		$button->setAttribute('data-icon', 'download-csv');
		$button->addExtraClass('no-ajax');
		return array(
			'after' => '<p>' . $button->Field() . '</p>',
		);
	}

	/**
	 * export is an action button
	 */
	public function getActions($gridField) {
		return array('export');
	}

	function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		if($actionName == 'export') {
			return $this->handleExport($gridField);
		}
	}

	/**
	 * it is also a URL
	 */
	function getURLHandlers($gridField) {
		return array(
			'export' => 'handleExport',
		);
	}

	/**
	 * Handle the export, for both the action button and the URL
 	 */
	public function handleExport($gridField, $request = null) {
		$now = Date("d-m-Y-H-i");
		$fileName = "export-$now.csv";

		if($fileData = $this->generateExportFileData($gridField)){
			return SS_HTTPRequest::send_file($fileData, $fileName);
		}
	}

	/**
	 * Export core.
 	 */
	function generateExportFileData($gridField) {
		$separator = $this->csvSeparator;
		$csvColumns = ($this->exportColumns) ? $this->exportColumns : $gridField->getDisplayFields();
		$fileData = '';
		$columnData = array();
		$fieldItems = new ArrayList();
		
		if($this->csvHasHeader) {
			$fileData .= "\"" . implode("\"{$separator}\"", array_values($csvColumns)) . "\"";
			$fileData .= "\n";
		}

		$items = $gridField->getList();
		foreach($items as $item) {
			$columnData = array();
			foreach($csvColumns as $columnSource => $columnHeader) {
				$value = $item->$columnSource;
				$value = str_replace(array("\r", "\n"), "\n", $value);
				$columnData[] = '"' . str_replace('"', '\"', $value) . '"';
			}
			$fileData .= implode($separator, $columnData);
			$fileData .= "\n";

			$item->destroy();
		}
			
		return $fileData;
	}

	/**
	 * @return array
	 */
	function getExportColumns() {
		return $this->exportColumns;
	}

	/**
	 * @param array
	 */
	function setExportColumns($cols) {
		$this->exportColumns = $cols;
		return $this;
	}
	
	/**
	 * @return string
	 */
	function getCsvSeparator() {
		return $this->csvSeparator;
	}

	/**
	 * @param string
	 */
	function setCsvSeparator($separator) {
		$this->csvSeparator = $separator;
		return $this;
	}

	/**
	 * @return boolean
	 */
	function getCsvHasHeader() {
		return $this->csvHasHeader;
	}

	/**
	 * @param boolean
	 */
	function setCsvHasHeader($bool) {
		$this->csvHasHeader = $bool;
		return $this;
	}


}