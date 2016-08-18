<?php

namespace SilverStripe\Control;

use Exception;

/**
 * A {@link SS_HTTPResponse} encapsulated in an exception, which can interrupt the processing flow and be caught by the
 * {@link RequestHandler} and returned to the user.
 *
 * Example Usage:
 * <code>
 * throw new SS_HTTPResponse_Exception('This request was invalid.', 400);
 * throw new SS_HTTPResponse_Exception(new SS_HTTPResponse('There was an internal server error.', 500));
 * </code>
 */
class SS_HTTPResponse_Exception extends Exception
{

	protected $response;

	/**
	 * @param SS_HTTPResponse|string $body Either the plaintext content of the error
	 * message, or an SS_HTTPResponse object representing it. In either case, the
	 * $statusCode and $statusDescription will be the HTTP status of the resulting
	 * response.
	 * @param int $statusCode
	 * @param string $statusDescription
	 * @see SS_HTTPResponse::__construct();
	 */
	public function __construct($body = null, $statusCode = null, $statusDescription = null)
	{
		if ($body instanceof SS_HTTPResponse) {
			// statusCode and statusDescription should override whatever is passed in the body
			if ($statusCode) {
				$body->setStatusCode($statusCode);
			}
			if ($statusDescription) {
				$body->setStatusDescription($statusDescription);
			}

			$this->setResponse($body);
		} else {
			$response = new SS_HTTPResponse($body, $statusCode, $statusDescription);

			// Error responses should always be considered plaintext, for security reasons
			$response->addHeader('Content-Type', 'text/plain');

			$this->setResponse($response);
		}

		parent::__construct($this->getResponse()->getBody(), $this->getResponse()->getStatusCode());
	}

	/**
	 * @return SS_HTTPResponse
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * @param SS_HTTPResponse $response
	 */
	public function setResponse(SS_HTTPResponse $response)
	{
		$this->response = $response;
	}

}
