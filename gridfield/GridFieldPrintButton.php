<?php

/**
 * Adds an "Print" button to the bottom or top of a GridField.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldPrintButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler {

	/**
	 * @var array Map of a property name on the printed objects, with values
	 * being the column title in the CSV file.
	 *
	 * Note that titles are only used when {@link $csvHasHeader} is set to TRUE
	 */
	protected $printColumns;

	/**
	 * @var boolean
	 */
	protected $printHasHeader = true;

	/**
	 * Fragment to write the button to.
	 *
	 * @var string
	 */
	protected $targetFragment;

	/**
	 * @param string $targetFragment The HTML fragment to write the button into
	 * @param array $printColumns The columns to include in the print view
	 */
	public function __construct($targetFragment = "after", $printColumns = null) {
		$this->targetFragment = $targetFragment;
		$this->printColumns = $printColumns;
	}

	/**
	 * Place the print button in a <p> tag below the field
	 *
	 * @param GridField
	 *
	 * @return array
	 */
	public function getHTMLFragments($gridField) {
		$button = new GridField_FormAction(
			$gridField,
			'print',
			_t('TableListField.Print', 'Print'),
			'print',
			null
		);

		$button->setAttribute('data-icon', 'grid_print');
		$button->addExtraClass('gridfield-button-print');

		return array(
			$this->targetFragment => '<p class="grid-print-button">' . $button->Field() . '</p>',
		);
	}

	/**
	 * Print is an action button.
	 *
	 * @param GridField
	 *
	 * @return array
	 */
	public function getActions($gridField) {
		return array('print');
	}

	/**
	 * Handle the print action.
	 *
	 * @param GridField
	 * @param string
	 * @param array
	 * @param array
	 */
	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		if($actionName == 'print') {
			return $this->handlePrint($gridField);
		}
	}

	/**
	 * Print is accessible via the url
	 *
	 * @param GridField
	 * @return array
	 */
	public function getURLHandlers($gridField) {
		return array(
			'print' => 'handlePrint',
		);
	}

	/**
	 * Handle the print, for both the action button and the URL
 	 */
	public function handlePrint($gridField, $request = null) {
		set_time_limit(60);
		Requirements::clear();
		Requirements::css(FRAMEWORK_DIR . '/css/GridField_print.css');

		if($data = $this->generatePrintData($gridField)){
			return $data->renderWith("GridField_print");
		}
	}

	/**
	 * Return the columns to print
	 *
	 * @param GridField
	 *
	 * @return array
	 */
	protected function getPrintColumnsForGridField(GridField $gridField) {
		if($this->printColumns) {
			$printColumns = $this->printColumns;
		} else if($dataCols = $gridField->getConfig()->getComponentByType('GridFieldDataColumns')) {
			$printColumns = $dataCols->getDisplayFields($gridField);
		} else {
			$printColumns = singleton($gridField->getModelClass())->summaryFields();
		}

		return $printColumns;
	}

	/**
	 * Return the title of the printed page
	 *
	 * @param GridField
	 *
	 * @return array
	 */
	public function getTitle(GridField $gridField) {
		$form = $gridField->getForm();
		$currentController = $gridField->getForm()->getController();
		$title = '';

		if(method_exists($currentController, 'Title')) {
			$title = $currentController->Title();
		} else {
			if ($currentController->Title) {
				$title = $currentController->Title;
			} elseif ($form->getName()) {
				$title = $form->getName();
			}
		}

		if($fieldTitle = $gridField->Title()) {
			if($title) {
				$title .= " - ";
			}

			$title .= $fieldTitle;
		}

		return $title;
	}

	/**
	 * Export core.
	 *
	 * @param GridField
 	 */
	public function generatePrintData(GridField $gridField) {
		$printColumns = $this->getPrintColumnsForGridField($gridField);

		$header = null;

		if($this->printHasHeader) {
			$header = new ArrayList();

			foreach($printColumns as $field => $label){
				$header->push(new ArrayData(array(
					"CellString" => $label,
				)));
			}
		}

		$items = $gridField->getManipulatedList();
		$itemRows = new ArrayList();

		foreach($items as $item) {
			$itemRow = new ArrayList();

			foreach($printColumns as $field => $label) {
				$value = $gridField->getDataFieldValue($item, $field);

				if($item->escapeTypeForField($field) != 'xml') {
					$value = Convert::raw2xml($value);
				}

				$itemRow->push(new ArrayData(array(
					"CellString" => $value,
				)));
			}

			$itemRows->push(new ArrayData(array(
				"ItemRow" => $itemRow
			)));
			if ($item->hasMethod('destroy')) {
				$item->destroy();
			}
		}

		$ret = new ArrayData(array(
			"Title" => $this->getTitle($gridField),
			"Header" => $header,
			"ItemRows" => $itemRows,
			"Datetime" => SS_Datetime::now(),
			"Member" => Member::currentUser(),
		));

		return $ret;
	}

	/**
	 * @return array
	 */
	public function getPrintColumns() {
		return $this->printColumns;
	}

	/**
	 * @param array
	 */
	public function setPrintColumns($cols) {
		$this->printColumns = $cols;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getPrintHasHeader() {
		return $this->printHasHeader;
	}

	/**
	 * @param boolean
	 */
	public function setPrintHasHeader($bool) {
		$this->printHasHeader = $bool;

		return $this;
	}
}
