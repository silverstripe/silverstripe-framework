<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\HTTPResponse;

/**
 * Wrapper around HTTPResponse to make it look like a SimpleHTTPResposne
 * @deprecated 4.13.0 Will be removed without equivalent functionality to replace it
 */
class TestSession_STResponseWrapper
{

    /**
     * @var HTTPResponse
     */
    private $response;

    public function __construct(HTTPResponse $response)
    {
        Deprecation::withNoReplacement(function () {
            Deprecation::notice(
                '4.13.0',
                'Will be removed without equivalent functionality to replace it',
                Deprecation::SCOPE_CLASS
            );
        });
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->response->getBody();
    }

    /**
     * @return string
     */
    public function getError()
    {
        return "";
    }

    /**
     * @return null
     */
    public function getSent()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getHeaders()
    {
        return "";
    }

    /**
     * @return string 'GET'
     */
    public function getMethod()
    {
        return "GET";
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return "";
    }

    /**
     * @return null
     */
    public function getRequestData()
    {
        return null;
    }
}
