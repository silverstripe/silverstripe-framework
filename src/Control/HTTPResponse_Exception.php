<?php

namespace SilverStripe\Control;

use Exception;

/**
 * A {@link HTTPResponse} encapsulated in an exception, which can interrupt the processing flow and be caught by the
 * {@link RequestHandler} and returned to the user.
 *
 * Example Usage:
 * <code>
 * throw new HTTPResponse_Exception('This request was invalid.', 400);
 * throw new HTTPResponse_Exception(new HTTPResponse('There was an internal server error.', 500));
 * </code>
 */
class HTTPResponse_Exception extends Exception
{

    protected $response;

    /**
     * @param HTTPResponse|string $body Either the plaintext content of the error
     * message, or an HTTPResponse object representing it. In either case, the
     * $statusCode and $statusDescription will be the HTTP status of the resulting
     * response.
     * @param int $statusCode
     * @param string $statusDescription
     * @see HTTPResponse::__construct()
     */
    public function __construct(string|SilverStripe\Control\HTTPResponse $body = null, int $statusCode = null, string $statusDescription = null): void
    {
        if ($body instanceof HTTPResponse) {
            // statusCode and statusDescription should override whatever is passed in the body
            if ($statusCode) {
                $body->setStatusCode($statusCode);
            }
            if ($statusDescription) {
                $body->setStatusDescription($statusDescription);
            }

            $this->setResponse($body);
        } else {
            $response = new HTTPResponse($body, $statusCode, $statusDescription);

            // Error responses should always be considered plaintext, for security reasons
            $response->addHeader('Content-Type', 'text/plain');

            $this->setResponse($response);
        }

        parent::__construct((string) $this->getResponse()->getBody(), $this->getResponse()->getStatusCode());
    }

    /**
     * @return HTTPResponse
     */
    public function getResponse(): SilverStripe\Control\HTTPResponse
    {
        return $this->response;
    }

    /**
     * @param HTTPResponse $response
     */
    public function setResponse(HTTPResponse $response): void
    {
        $this->response = $response;
    }
}
