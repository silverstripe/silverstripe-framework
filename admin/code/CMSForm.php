<?php
/**
 * Deals with special form handling in CMS, mainly around {@link PjaxResponseNegotiator}
 */
class CMSForm extends Form {

	protected $responseNegotiator;
	
	/**
	 * Route validation error responses through response negotiator,
	 * so they return the correct markup as expected by the requesting client.
	 */
	protected function getValidationErrorResponse(ValidationResult $result) {
		$request = $this->getRequest();
		$negotiator = $this->getResponseNegotiator();

		if($request->isAjax() && $negotiator) {
			// Load form errors from the result into the form
			// Also save them to session, in case the negotation returns a 302
			$this->setupFormErrors($result, $this->getData());
			$this->saveFormErrorsToSession($result, $this->getData());

			$negotiator->setResponse(new SS_HTTPResponse($this));
			return $negotiator->respond($request);

		} else {
			return parent::getValidationErrorResponse($result);
		}
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

}