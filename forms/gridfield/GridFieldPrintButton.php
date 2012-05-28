<?php
/**
 * @package framework
 * @subpackage gridfield
 */

/**
 * Adds an "Print" button to the bottom or top of a GridField.
 */
class GridFieldPrintButton implements GridField_HTMLProvider, GridField_ActionProvider, GridField_URLHandler {

	/**
	 * @var array Map of a property name on the printed objects, with values being the column title in the CSV file.
	 * Note that titles are only used when {@link $csvHasHeader} is set to TRUE.
	 */
	protected $printColumns;

	/**
	 * @var boolean
	 */
	protected $printHasHeader = true;
	
	/**
	 * Fragment to write the button to
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
		//$button->addExtraClass('no-ajax');
		return array(
			$this->targetFragment => '<p class="grid-print-button">' . $button->Field() . '</p>', 
		);
	}

	/**
	 * print is an action button
	 */
	public function getActions($gridField) {
		return array('print');
	}

	function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		if($actionName == 'print') {
			return $this->handlePrint($gridField);
		}
	}

	/**
	 * it is also a URL
	 */
	function getURLHandlers($gridField) {
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
	 * Export core.
 	 */
	function generatePrintData($gridField) {
		$printColumns = ($this->printColumns) ? $this->printColumns : singleton($gridField->getModelClass())->summaryFields();
		$header = null;
		if($this->printHasHeader){
			$header = new ArrayList();
			foreach($printColumns as $field => $label){
				$header->push(
					new ArrayData(array(
						"CellString" => $label,
					))
				);
			}
		}
		
		$items = $gridField->getList();
		foreach($gridField->getConfig()->getComponents() as $component){
			if($component instanceof GridFieldFilterHeader || $component instanceof GridFieldSortableHeader) {
				$items = $component->getManipulatedData($gridField, $items);
			}
		}
		
		$itemRows = new ArrayList();
		foreach($items as $item) {
			$itemRow = new ArrayList();
			foreach($printColumns as $field => $label) {
				$value = $gridField->getDataFieldValue($item, $field);
				$itemRow->push(
					new ArrayData(array(
						"CellString" => $value,
					))
				);
			}
			$itemRows->push(new ArrayData(
				array(
					"ItemRow" => $itemRow
				)
			));
			$item->destroy();
		}
		
		//get title for the print view
		$form = $gridField->getForm();
		$currentController = Controller::curr();
		$title = '';
		if(method_exists($currentController, 'Title')) {
			$title = $currentController->Title();
		}else{
			if($currentController->Title){
				$title = $currentController->Title;
			}else{
				if($form->Name()){
					$title = $form->Name();
				}
			}
		}
		if($fieldTitle = $gridField->Title()){
			if($title) $title .= " - ";
			$title .= $fieldTitle;
		}
		
		$ret = new ArrayData(
			array(
				"Title" => $title,
				"Header" => $header,
				"ItemRows" => $itemRows,
				"Datetime" => SS_Datetime::now(),
				"Member" => Member::currentUser(),
			)
		);
		
		return $ret;
	}

	/**
	 * @return array
	 */
	function getPrintColumns() {
		return $this->printColumns;
	}

	/**
	 * @param array
	 */
	function setPrintColumns($cols) {
		$this->printColumns = $cols;
		return $this;
	}

	/**
	 * @return boolean
	 */
	function getPrintHasHeader() {
		return $this->printHasHeader;
	}

	/**
	 * @param boolean
	 */
	function setPrintHasHeader($bool) {
		$this->printHasHeader = $bool;
		return $this;
	}
}
