<?php

/**
 * GridFieldPage displays a simple current page count summary.
 * E.g. "View 1 - 15 of 32"
 * 
 * Depends on {@link GridFieldPaginator} being added to the {@link GridField}.
 * 
 * @package forms
 * @subpackage fields-gridfield
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
	 * Flag indicating whether or not this control should throw an error if a
	 * {@link GridFieldPaginator} is not present on the same {@link GridField}
	 *
	 * @config
	 * @var boolean
	 */
	private static $require_paginator = true;
	
	/**
	 * Retrieves an instance of a GridFieldPaginator attached to the same control
	 * @param GridField $gridField The parent gridfield
	 * @return GridFieldPaginator The attached GridFieldPaginator, if found.
	 * @throws LogicException 
	 */
	protected function getPaginator($gridField) {
		$paginator = $gridField->getConfig()->getComponentByType('GridFieldPaginator');
		
		if(!$paginator && Config::inst()->get('GridFieldPageCount', 'require_paginator')) {
			throw new LogicException(
				get_class($this) . " relies on a GridFieldPaginator to be added " .
				"to the same GridField, but none are present."
			);
		}
		
		return $paginator;
	}

	/**
	 * @param GridField $gridField
	 * @return array
	 */
	public function getHTMLFragments($gridField) {

		// Retrieve paging parameters from the directing paginator component
		$paginator = $this->getPaginator($gridField);
		if ($paginator && ($forTemplate = $paginator->getTemplateParameters($gridField))) {
			return array(
				$this->targetFragment => $forTemplate->renderWith($this->itemClass)
			);
		}
	}

}
