<?php

/**
 * GridFieldPage displays a simple current page count summary.
 * E.g. "View 1 - 15 of 32"
 * 
 * Depends on GridFieldPaginator being added to the same gridfield
 * 
 * @package framework
 * @subpackage fields-relational
 */
class GridFieldPageCount implements GridField_HTMLProvider {
	/**
	 * @var string placement indicator for this control
	 */
	protected $targetFragment;

	/**
	 * Which template to use for rendering
	 * 
	 * @var string
	 */
	protected $itemClass = 'GridFieldPageCount';

	/**
	 * @param string $targetFrament The fragment indicating the placement of this page count
	 */
	public function __construct($targetFragment = 'before') {
		$this->targetFragment = $targetFragment;
	}

	/**
	 * @param GridField $gridField
	 * @return array
	 */
	public function getHTMLFragments($gridField) {

		// Retrieve paging parameters from the directing paginator component
		$paginator = $gridField->getConfig()->getComponentByType('GridFieldPaginator');
		if ($paginator && ($forTemplate = $paginator->getTemplateParameters($gridField))) {
			return array(
				$this->targetFragment => $forTemplate->renderWith($this->itemClass)
			);
		}
	}

}
