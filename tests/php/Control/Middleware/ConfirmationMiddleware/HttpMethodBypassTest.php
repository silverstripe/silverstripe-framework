<?php declare(strict_types = 1);

namespace SilverStripe\Control\Tests\Middleware\ConfirmationMiddleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\ConfirmationMiddleware\HttpMethodBypass;
use SilverStripe\Dev\SapphireTest;

class HttpMethodBypassTest extends SapphireTest
{
    public function testBypass()
    {
        $getRequest = $this->createMock(HTTPRequest::class);
        $getRequest->method('httpMethod')->willReturn('GET');

        $postRequest = $this->createMock(HTTPRequest::class);
        $postRequest->method('httpMethod')->willReturn('POST');

        $putRequest = $this->createMock(HTTPRequest::class);
        $putRequest->method('httpMethod')->willReturn('PUT');

        $delRequest = $this->createMock(HTTPRequest::class);
        $delRequest->method('httpMethod')->willReturn('DELETE');

        $bypass = new HttpMethodBypass('GET', 'POST');

        $this->assertTrue($bypass->checkRequestForBypass($getRequest));
        $this->assertTrue($bypass->checkRequestForBypass($postRequest));
        $this->assertFalse($bypass->checkRequestForBypass($putRequest));
        $this->assertFalse($bypass->checkRequestForBypass($delRequest));
    }
}
