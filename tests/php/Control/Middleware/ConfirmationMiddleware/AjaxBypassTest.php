<?php

namespace SilverStripe\Control\Tests\Middleware\ConfirmationMiddleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\ConfirmationMiddleware\AjaxBypass;
use SilverStripe\Dev\SapphireTest;

class AjaxBypassTest extends SapphireTest
{
    public function testBypass()
    {
        $ajaxRequest = $this->createMock(HTTPRequest::class);
        $ajaxRequest->method('isAjax')->willReturn(true);

        $simpleRequest = $this->createMock(HTTPRequest::class);
        $simpleRequest->method('isAjax')->willReturn(false);

        $ajaxBypass = new AjaxBypass();

        $this->assertFalse($ajaxBypass->checkRequestForBypass($simpleRequest));
        $this->assertTrue($ajaxBypass->checkRequestForBypass($ajaxRequest));
    }
}
