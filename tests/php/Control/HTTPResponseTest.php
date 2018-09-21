<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;

class HTTPResponseTest extends SapphireTest
{
    public function testStatusDescriptionStripsNewlines()
    {
        $r = new HTTPResponse('my body', 200, "my description \nwith newlines \rand carriage returns");
        $this->assertEquals(
            "my description with newlines and carriage returns",
            $r->getStatusDescription()
        );
    }

    public function testHTTPResponseException()
    {
        $response = new HTTPResponse("Test", 200, 'OK');

        // Confirm that the exception's statusCode and statusDescription take precedence
        $e = new HTTPResponse_Exception($response, 404, 'not even found');
        $this->assertEquals(404, $e->getResponse()->getStatusCode());
        $this->assertEquals('not even found', $e->getResponse()->getStatusDescription());
    }

    public function testExceptionContentPlainByDefault()
    {
        // Confirm that the exception's statusCode and statusDescription take precedence
        $e = new HTTPResponse_Exception("Some content that may be from a hacker", 404, 'not even found');
        $this->assertEquals("text/plain", $e->getResponse()->getHeader("Content-Type"));
    }

    public function testRemoveHeader()
    {
        $response = new HTTPResponse();

        $response->addHeader('X-Animal', 'Monkey');
        $this->assertSame('Monkey', $response->getHeader('X-Animal'));

        $response->removeHeader('X-Animal');
        $this->assertEmpty($response->getHeader('X-Animal'));
    }

    public function providerTestValidStatusCodes()
    {
        return [
            [200, 'OK'],
            [226, 'IM Used'],
            [426, 'Upgrade Required'],
            [451, 'Unavailable For Legal Reasons'],
        ];
    }

    /**
     * @dataProvider providerTestValidStatusCodes
     * @param int $code
     * @param string $status
     */
    public function testValidStatusCodes($code, $status)
    {
        $response = new HTTPResponse();
        $response->setStatusCode($code);
        $this->assertEquals($status, $response->getStatusDescription());
    }
}
