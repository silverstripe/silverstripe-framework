<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Control\HTTPRequestBuilder;
use SilverStripe\Dev\SapphireTest;

class HTTPRequestBuilderTest extends SapphireTest
{
    public function testExtractRequestHeaders()
    {
        $request = [
            'REDIRECT_STATUS' => '200',
            'HTTP_HOST' => 'host',
            'HTTP_USER_AGENT' => 'User Agent',
            'HTTP_ACCEPT' => 'text/html',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us',
            'HTTP_COOKIE' => 'MyCookie=1',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => FRAMEWORK_DIR . '/main.php',
            'CONTENT_TYPE' => 'text/xml',
            'CONTENT_LENGTH' => 10
        ];

        $headers = [
            'Host' => 'host',
            'User-Agent' => 'User Agent',
            'Accept' => 'text/html',
            'Accept-Language' => 'en-us',
            'Cookie' => 'MyCookie=1',
            'Content-Type' => 'text/xml',
            'Content-Length' => '10'
        ];

        $this->assertEquals($headers, HTTPRequestBuilder::extractRequestHeaders($request));
    }

    /**
     * Ensure basic auth is properly assigned to request headers
     */
    public function testExtractRequestHeadersBasicAuth()
    {
        $request = [
            'HTTP_AUTHORIZATION' => 'Basic YWRtaW46cGFzc3dvcmQ=',
        ];
        $headers = [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'password',
            'Authorization' => 'Basic YWRtaW46cGFzc3dvcmQ=',
        ];
        $this->assertEquals($headers, HTTPRequestBuilder::extractRequestHeaders($request));

        $request = [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'password',
        ];
        $headers = [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'password',
        ];
        $this->assertEquals($headers, HTTPRequestBuilder::extractRequestHeaders($request));

        $request = [
            'REDIRECT_HTTP_AUTHORIZATION' => 'Basic YWRtaW46cGFzc3dvcmQ=',
        ];
        $headers = [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'password',
        ];
        $this->assertEquals($headers, HTTPRequestBuilder::extractRequestHeaders($request));

        $request = [
            'HTTP_AUTHORIZATION' => 'Basic YWRtaW46cGFzc3dvcmQ=',
            'REDIRECT_HTTP_AUTHORIZATION' => 'Basic dXNlcjphdXRo=',
        ];
        $headers = [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'password',
            'Authorization' => 'Basic YWRtaW46cGFzc3dvcmQ=',
        ];
        $this->assertEquals(
            $headers,
            HTTPRequestBuilder::extractRequestHeaders($request),
            'Prefer HTTP_AUTHORIZATION over REDIRECT_HTTP_AUTHORIZATION'
        );
    }
}
