<?php
/**
 * @package framework
 * @subpackage gridfield
 */

/**
 * Adds an "Export list" button to the bottom of a GridField.
 */
class GridFieldExportButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler {

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
	 * Fragment to write the button to
	 */
	protected $targetFragment;

	/**
	 * @param string $targetFragment The HTML fragment to write the button into
	 * @param array $exportColumns The columns to include in the export
	 */
	public function __construct($targetFragment = "after", $exportColumns = null) {
		$this->targetFragment = $targetFragment;
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
			$this->targetFragment => '<p class="grid-csv-button">' . $button->Field() . '</p>',
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
			return SS_HTTPRequest::send_file($fileData, $fileName, 'text/csv');
		}
	}

	/**
	 * Generate export fields for CSV.
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	function generateExportFileData($gridField) {
		$separator = $this->csvSeparator;
		$csvColumns = ($this->exportColumns) ? $this->exportColumns : singleton($gridField->getModelClass())->summaryFields();
		$fileData = '';
		$columnData = array();
		$fieldItems = new ArrayList();

		if($this->csvHasHeader) {
			$headers = array();

			// determine the CSV headers. If a field is callable (e.g. anonymous function) then use the
			// source name as the header instead
			foreach($csvColumns as $columnSource => $columnHeader) {
				$headers[] = (!is_string($columnHeader) && is_callable($columnHeader)) ? $columnSource : $columnHeader;
			}

			$fileData .= "\"" . implode("\"{$separator}\"", array_values($headers)) . "\"";
			$fileData .= "\n";
		}

		$items = $gridField->getList();

		// @todo should GridFieldComponents change behaviour based on whether others are available in the config?
		foreach($gridField->getConfig()->getComponents() as $component){
			if($component instanceof GridFieldFilterHeader || $component instanceof GridFieldSortableHeader) {
				$items = $component->getManipulatedData($gridField, $items);
			}
		}

		foreach($items as $item) {
			$columnData = array();
			foreach($csvColumns as $columnSource => $columnHeader) {
				if(!is_string($columnHeader) && is_callable($columnHeader)) {
					if($item->hasMethod($columnSource)) {
						$relObj = $item->{$columnSource}();
					} else {
						$relObj = $item->relObject($columnSource);
					}

					$value = $columnHeader($relObj);
				} else {
					$value = $gridField->getDataFieldValue($item, $columnSource);
				}

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
