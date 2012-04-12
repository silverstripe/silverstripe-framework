<?php
/**
 * Adding this class to a {@link GridFieldConfig} of a {@link GridField} adds a footer bar to that field.
 * The footer looks just like the {@link GridFieldPaginator} control, except without the pagination controls.
 * It only display the "Viewing 1-8 of 8" status text and (optionally) a configurable status message.
 *
 * The purpose of this class is to have a footer that can round off GridField without having to use pagination.
 *
 * @package framework
 * @subpackage gridfield
 */
class GridFieldFooter implements GridField_HTMLProvider {

	/**
	 * @var string - a message to display in the footer
	 */
	protected $message = null;

	/**
	 *
	 * @param string $message - a message to display in the footer
	 */
	public function __construct($message = null) {
		if($message) $this->message = $message;
	}


	public function getHTMLFragments($gridField) {
		$count = $gridField->getList()->count();

		$forTemplate = new ArrayData(array(
			'Message' => $this->message,
			'FirstShownRecord' => 1,
			'LastShownRecord' => $count,
			'NumRecords' => $count
		));

		return array(
			'footer' => $forTemplate->renderWith('GridFieldFooter', array('Colspan'=>count($gridField->getColumns()))),
		);
	}
}
