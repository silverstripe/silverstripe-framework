<?php

namespace SilverStripe\Framework\Logging;

use Monolog\Handler\AbstractProcessingHandler;

/**
 * Output the error to the browser, with the given HTTP status code.
 * We recommend that you use a formatter that generates HTML with this.
 */
class HTTPOutputHandler extends AbstractProcessingHandler
{

	private $contentType = "text/html";
	private $statusCode = 500;

	/**
	 * Get the mime type to use when displaying this error.
	 */
	public function getContentType() {
		return $this->contentType;
	}

	/**
	 * Set the mime type to use when displaying this error.
	 * Default text/html
	 */
	public function setContentType($contentType) {
		$this->contentType = $contentType;
	}

	/**
	 * Get the HTTP status code to use when displaying this error.
	 */
	public function getStatusCode() {
		return $this->statusCode;
	}

	/**
	 * Set the HTTP status code to use when displaying this error.
	 * Default 500
	 */
	public function setStatusCode($statusCode) {
		$this->statusCode = $statusCode;
	}

	protected function write(array $record) {
		ini_set('display_errors', 0);

		// TODO: This coupling isn't ideal	
		// See https://github.com/silverstripe/silverstripe-framework/issues/4484
		if(\Controller::has_curr()) {
			$response = \Controller::curr()->getResponse();
		} else {
			$response = new \SS_HTTPResponse();
		}

		// If headers have been sent then these won't be used, and may throw errors that we wont' want to see.
		if(!headers_sent()) {
			$response->setStatusCode($this->statusCode);
			$response->addHeader("Content-Type", $this->contentType);
		} else {
			// To supress errors aboot errors
			$response->setStatusCode(200);
		}

		$response->setBody($record['formatted']);
		$response->output();

		return false === $this->bubble;
	}
}
