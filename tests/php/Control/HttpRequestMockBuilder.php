<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;

trait HttpRequestMockBuilder
{
    /**
     * Builds and returns a new mock instance of HTTPRequest
     *
     * @param string $url
     * @param array $getVars GET parameters
     * @param array $postVars POST parameters
     * @param string|null $method HTTP method
     * @param Session|null $session Session instance
     *
     * @return HTTPRequest
     */
    public function buildRequestMock($url, $getVars = [], $postVars = [], $method = null, Session $session = null)
    {
        if (is_null($session)) {
            $session = new Session([]);
        }

        $request = $this->createMock(HTTPRequest::class);

        $request->method('getSession')->willReturn($session);

        $request->method('getURL')->will($this->returnCallback(static function ($addParams) use ($url, $getVars) {
            return $addParams && count($getVars) ? $url.'?'.http_build_query($getVars) : $url;
        }));

        $request->method('getVars')->willReturn($getVars);
        $request->method('getVar')->will($this->returnCallback(static function ($key) use ($getVars) {
            return isset($getVars[$key]) ? $getVars[$key] : null;
        }));

        $request->method('postVars')->willReturn($postVars);
        $request->method('postVar')->will($this->returnCallback(static function ($key) use ($postVars) {
            return isset($postVars[$key]) ? $postVars[$key] : null;
        }));

        if (is_null($method)) {
            if (count($postVars)) {
                $method = 'POST';
            } else {
                $method = 'GET';
            }
        }

        $request->method('httpMethod')->willReturn($method);

        return $request;
    }
}
