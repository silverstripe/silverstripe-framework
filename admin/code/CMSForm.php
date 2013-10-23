<?php

/**
 * Deals with special form handling in CMS, mainly around
 * {@link PjaxResponseNegotiator}
 *
 * @package framework
 * @subpackage admin
 */
class CMSForm extends Form {

	/**
	 * @var array
	 */
	protected $validationExemptActions = array();

	/**
	 * Always return true if the current form action is exempt from validation
	 * 
	 * @return boolean
	 */
	public function validate() {
		$buttonClicked = $this->buttonClicked();
		return (
			($buttonClicked && in_array($buttonClicked->actionName(), $this->getValidationExemptActions()))
			|| parent::validate()
		);
	}
	
	/**
	 * Route validation error responses through response negotiator,
	 * so they return the correct markup as expected by the requesting client.
	 */
	protected function getValidationErrorResponse() {
		$request = $this->getRequest();
		$negotiator = $this->getResponseNegotiator();

		if($request->isAjax() && $negotiator) {
			$this->setupFormErrors();
			$result = $this->forTemplate();

			return $negotiator->respond($request, array(
				'CurrentForm' => function() use($result) {
					return $result;
				}
			));
		} else {
			return parent::getValidationErrorResponse();
		}
	}

	/**
	 * Set actions that are exempt from validation
	 * 
	 * @param array
	 */
	public function setValidationExemptActions($actions) {
		$this->validationExemptActions = $actions;
		return $this;
	}

	/**
	 * Get a list of actions that are exempt from validation
	 * 
	 * @return array
	 */
	public function getValidationExemptActions() {
		return $this->validationExemptActions;
	}

	/**
	 * Sets the response negotiator
	 * @param ResponseNegotiator $negotiator The response negotiator to use
	 * @return Form The current form
	 */
	public function setResponseNegotiator($negotiator) {
		$this->responseNegotiator = $negotiator;
		return $this;
	}

	/**
	 * Gets the current response negotiator
	 * @return ResponseNegotiator|null
	 */
	public function getResponseNegotiator() {
		return $this->responseNegotiator;
	}

	public function FormName() {
		if($this->htmlID) return $this->htmlID;
		else return 'Form_' . str_replace(array('.', '/'), '', $this->name);
	}

}