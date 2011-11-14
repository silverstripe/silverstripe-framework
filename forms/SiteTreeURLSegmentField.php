<?php
/**
 * @package cms
 * @subpackage forms
 */

/**
 * Used to edit the SiteTree->URLSegment property,
 * and suggest input based on the serverside rules
 * defined through {@link SiteTree->generateURLSegment()} and {@link URLSegmentFilter}.
 * 
 * Note: The actual conversion for saving the value takes place in the model layer.
 */
class SiteTreeURLSegmentField extends TextField {
	
	static $allowed_actions = array(
		'suggest'
	);
	
	function suggest($request) {
		if(!$request->getVar('value')) return $this->httpError(405);
		$page = $this->getPage();
		$return = array(
			'value' => $page->generateURLSegment($request->getVar('value'))
		);
		Controller::curr()->getResponse()->addHeader('Content-Type', 'application/json');
		return Convert::raw2json($return);
	}
	
	function Value() {
		return rawurldecode($this->value);
	}
	
	function dataValue() { 
		return $this->value;
	}
	
	/**
	 * @return SiteTree
	 */
	function getPage() {
		$idField = $this->getForm()->dataFieldByName('ID');
		return ($idField && $idField->Value()) ? DataObject::get_by_id('SiteTree', $idField->Value()) : singleton('SiteTree');
	}
}