<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\HTTPResponse;

/**
 * Wrapper around HTTPResponse to make it look like a SimpleHTTPResposne
 */
class TestSession_STResponseWrapper
{

    /**
     * @var HTTPResponse
     */
    private $response;

    public function __construct(HTTPResponse $response): void
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->response->getBody();
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return "";
    }

    /**
     * @return null
     */
    public function getSent(): null
    {
        return null;
    }

    /**
     * @return string
     */
    public function getHeaders(): string
    {
        return "";
    }

    /**
     * @return string 'GET'
     */
    public function getMethod(): string
    {
        return "GET";
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return "";
    }

    /**
     * @return null
     */
    public function getRequestData(): null
    {
        return null;
    }
}
