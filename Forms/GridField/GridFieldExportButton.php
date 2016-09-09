<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\DataObject;

/**
 * Adds an "Export list" button to the bottom of a {@link GridField}.
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
	 * @var string
	 */
	protected $csvEnclosure = '"';

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
	 *
	 * @param GridField $gridField
	 * @return array
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
		$button->addExtraClass('no-ajax font-icon-down-circled action_export');
		$button->setForm($gridField->getForm());
		return array(
			$this->targetFragment => '<p class="grid-csv-button">' . $button->Field() . '</p>',
		);
	}

	/**
	 * export is an action button
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function getActions($gridField) {
		return array('export');
	}

	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		if($actionName == 'export') {
			return $this->handleExport($gridField);
		}
		return null;
	}

	/**
	 * it is also a URL
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function getURLHandlers($gridField) {
		return array(
			'export' => 'handleExport',
		);
	}

	/**
	 * Handle the export, for both the action button and the URL
	 *
	 * @param GridField $gridField
	 * @param HTTPRequest $request
	 * @return HTTPResponse
	 */
	public function handleExport($gridField, $request = null) {
		$now = date("d-m-Y-H-i");
		$fileName = "export-$now.csv";

		if($fileData = $this->generateExportFileData($gridField)){
			return HTTPRequest::send_file($fileData, $fileName, 'text/csv');
		}
		return null;
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
			return $this->exportColumns;
		}

		/** @var GridFieldDataColumns $dataCols */
		$dataCols = $gridField->getConfig()->getComponentByType(
			'SilverStripe\\Forms\\GridField\\GridFieldDataColumns'
		);
		if($dataCols) {
			return $dataCols->getDisplayFields($gridField);
		}

		return DataObject::singleton($gridField->getModelClass())->summaryFields();
	}

	/**
	 * Generate export fields for CSV.
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function generateExportFileData($gridField) {
		$csvColumns = $this->getExportColumnsForGridField($gridField);
		$fileData = array();

		if($this->csvHasHeader) {
			$headers = array();

			// determine the CSV headers. If a field is callable (e.g. anonymous function) then use the
			// source name as the header instead
			foreach($csvColumns as $columnSource => $columnHeader) {
				$headers[] = (!is_string($columnHeader) && is_callable($columnHeader)) ? $columnSource : $columnHeader;
			}

			$fileData[] = $headers;
		}

		//Remove GridFieldPaginator as we're going to export the entire list.
		$gridField->getConfig()->removeComponentsByType('SilverStripe\\Forms\\GridField\\GridFieldPaginator');

		$items = $gridField->getManipulatedList();

		// @todo should GridFieldComponents change behaviour based on whether others are available in the config?
		foreach($gridField->getConfig()->getComponents() as $component){
			if($component instanceof GridFieldFilterHeader || $component instanceof GridFieldSortableHeader) {
				$items = $component->getManipulatedData($gridField, $items);
			}
		}

		/** @var DataObject $item */
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

					$columnData[] = $value;
				}

				$fileData[] = $columnData;
			}

			if($item->hasMethod('destroy')) {
				$item->destroy();
			}
		}

		// Convert the $fileData array into csv by capturing fputcsv's output
		$csv = fopen('php://temp', 'r+');
		foreach($fileData as $line) {
			fputcsv($csv, $line, $this->csvSeparator, $this->csvEnclosure);
		}
		rewind($csv);
		return stream_get_contents($csv);
	}

	/**
	 * @return array
	 */
	public function getExportColumns() {
		return $this->exportColumns;
	}

	/**
	 * @param array $cols
	 * @return $this
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
	 * @param string $separator
	 * @return $this
	 */
	public function setCsvSeparator($separator) {
		$this->csvSeparator = $separator;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCsvEnclosure() {
		return $this->csvEnclosure;
	}

	/**
	 * @param string $enclosure
	 * @return $this
	 */
	public function setCsvEnclosure($enclosure) {
		$this->csvEnclosure = $enclosure;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getCsvHasHeader() {
		return $this->csvHasHeader;
	}

	/**
	 * @param boolean $bool
	 * @return $this
	 */
	public function setCsvHasHeader($bool) {
		$this->csvHasHeader = $bool;
		return $this;
	}


}
