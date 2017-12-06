<?php

/**
 * Adds an "Export list" button to the bottom of a {@link GridField}.
 *
 * @package forms
 * @subpackage fields-gridfield
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
	 * Set to true to disable XLS sanitisation
	 * [SS-2017-007] Ensure all cells with leading [@=+] have a leading tab
	 *
	 * @config
	 * @var bool
	 */
	private static $xls_export_disabled = false;

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
		$button->addExtraClass('no-ajax action_export');
		$button->setForm($gridField->getForm());
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

	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		if($actionName == 'export') {
			return $this->handleExport($gridField);
		}
	}

	/**
	 * it is also a URL
	 */
	public function getURLHandlers($gridField) {
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
	 * Return the columns to export
	 *
	 * @param GridField $gridField
	 *
	 * @return array
	 */
	protected function getExportColumnsForGridField(GridField $gridField) {
		if($this->exportColumns) {
			$exportColumns = $this->exportColumns;
		} else if($dataCols = $gridField->getConfig()->getComponentByType('GridFieldDataColumns')) {
			$exportColumns = $dataCols->getDisplayFields($gridField);
		} else {
			$exportColumns = singleton($gridField->getModelClass())->summaryFields();
		}

		return $exportColumns;
	}

	/**
	 * Generate export fields for CSV.
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function generateExportFileData($gridField) {
		$separator = $this->csvSeparator;
		$csvColumns = $this->getExportColumnsForGridField($gridField);
		$fileData = '';

		if($this->csvHasHeader) {
			$headers = array();

			// determine the CSV headers. If a field is callable (e.g. anonymous function) then use the
			// source name as the header instead

			foreach($csvColumns as $columnSource => $columnHeader) {
				if (is_array($columnHeader) && array_key_exists('title', $columnHeader)) {
					$headers[] = $columnHeader['title'];
				} else {
					$headers[] = (!is_string($columnHeader) && is_callable($columnHeader)) ? $columnSource : $columnHeader;
				}
			}

			$fileData .= "\"" . implode("\"{$separator}\"", array_values($headers)) . "\"";
			$fileData .= "\n";
		}

		//Remove GridFieldPaginator as we're going to export the entire list.
		$gridField->getConfig()->removeComponentsByType('GridFieldPaginator');

		$items = $gridField->getManipulatedList();

		// @todo should GridFieldComponents change behaviour based on whether others are available in the config?
		foreach($gridField->getConfig()->getComponents() as $component){
			if($component instanceof GridFieldFilterHeader || $component instanceof GridFieldSortableHeader) {
				$items = $component->getManipulatedData($gridField, $items);
			}
		}

		foreach($items->limit(null) as $item) {
			if(!$item->hasMethod('canView') || $item->canView()) {
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

						if($value === null) {
							$value = $gridField->getDataFieldValue($item, $columnHeader);
						}
					}

					$value = str_replace(array("\r", "\n"), "\n", $value);

					// [SS-2017-007] Sanitise XLS executable column values with a leading tab
					if (!Config::inst()->get(get_class($this), 'xls_export_disabled')
						&& preg_match('/^[-@=+].*/', $value)
					) {
						$value = "\t" . $value;
					}
					$columnData[] = '"' . str_replace('"', '""', $value) . '"';
				}

				$fileData .= implode($separator, $columnData);
				$fileData .= "\n";
			}

			if($item->hasMethod('destroy')) {
				$item->destroy();
			}
		}

		return $fileData;
	}

	/**
	 * @return array
	 */
	public function getExportColumns() {
		return $this->exportColumns;
	}

	/**
	 * @param array
	 */
	public function setExportColumns($cols) {
		$this->exportColumns = $cols;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCsvSeparator() {
		return $this->csvSeparator;
	}

	/**
	 * @param string
	 */
	public function setCsvSeparator($separator) {
		$this->csvSeparator = $separator;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getCsvHasHeader() {
		return $this->csvHasHeader;
	}

	/**
	 * @param boolean
	 */
	public function setCsvHasHeader($bool) {
		$this->csvHasHeader = $bool;
		return $this;
	}


}
