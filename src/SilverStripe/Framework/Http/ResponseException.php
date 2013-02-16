<?php

namespace SilverStripe\Framework\Http;

use Exception;

/**
 * A {@link Response} encapsulated in an exception, which can interrupt the processing flow and be caught by the
 * {@link RequestHandler} and returned to the user.
 *
 * Example Usage:
 * <code>
 * throw new ResponseException('This request was invalid.', 400);
 * throw new ResponseException(new SS_HTTPResponse('There was an internal server error.', 500));
 * </code>
 */
class ResponseException extends Exception {

	protected $response;

	/**
	 * @param  string|Response body Either the plaintext content of the error message, or an SS_HTTPResponse
	 *                                     object representing it.  In either case, the $statusCode and
	 *                                     $statusDescription will be the HTTP status of the resulting response.
	 * @see Response::__construct();
	 */
	public function __construct($body = null, $statusCode = null, $statusDescription = null) {
		if($body instanceof Response) {
			// statusCode and statusDescription should override whatever is passed in the body
			if($statusCode) $body->setStatusCode($statusCode);
			if($statusDescription) $body->setStatusDescription($statusDescription);

			$this->setResponse($body);
		} else {
			$response = new Response($body, $statusCode, $statusDescription);

			// Error responses should always be considered plaintext, for security reasons
			$response->setHeader('Content-Type', 'text/plain');

			$this->setResponse($response);
		}

		parent::__construct($this->getResponse()->getBody(), $this->getResponse()->getStatusCode());
	}

	/**
	 * @return Response
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * @param Response $response
	 */
	public function setResponse(Response $response) {
		$this->response = $response;
	}

}
