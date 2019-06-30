<?php declare(strict_types = 1);

namespace SilverStripe\Control\Tests\Middleware\ConfirmationMiddleware;

use SilverStripe\Control\Middleware\ConfirmationMiddleware\UrlPathStartswithCaseInsensitive;
use SilverStripe\Control\Tests\HttpRequestMockBuilder;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Confirmation\Item;

class UrlPathStartswithCaseInsensitiveTest extends SapphireTest
{
    use HttpRequestMockBuilder;

    public function testPath()
    {
        $url = new UrlPathStartswithCaseInsensitive('test/path_01');
        $this->assertEquals('test/path_01/', $url->getPath());

        $url->setPath('test/path_02');
        $this->assertEquals('test/path_02/', $url->getPath());
    }

    public function testBypass()
    {
        $request = $this->buildRequestMock('dev/build', ['flush' => 'all']);

        $url = new UrlPathStartswithCaseInsensitive('dev');
        $this->assertTrue($url->checkRequestForBypass($request));

        $url = new UrlPathStartswithCaseInsensitive('dev/');
        $this->assertTrue($url->checkRequestForBypass($request));

        $url = new UrlPathStartswithCaseInsensitive('dev/build');
        $this->assertTrue($url->checkRequestForBypass($request));

        $url = new UrlPathStartswithCaseInsensitive('dev/build/');
        $this->assertTrue($url->checkRequestForBypass($request));

        $url = new UrlPathStartswithCaseInsensitive('de');
        $this->assertFalse($url->checkRequestForBypass($request));

        $url = new UrlPathStartswithCaseInsensitive('dev/buil');
        $this->assertFalse($url->checkRequestForBypass($request));

        $url = new UrlPathStartswithCaseInsensitive('Dev');
        $this->assertTrue($url->checkRequestForBypass($request));

        $url = new UrlPathStartswithCaseInsensitive('dev/builD');
        $this->assertTrue($url->checkRequestForBypass($request));
    }

    public function testConfirmationItem()
    {
        $request = $this->buildRequestMock('dev/build', ['flush' => 'all']);

        $url = new UrlPathStartswithCaseInsensitive('dev');
        $this->assertNotNull($url->getRequestConfirmationItem($request));

        $url = new UrlPathStartswithCaseInsensitive('dev/');
        $this->assertNotNull($url->getRequestConfirmationItem($request));

        $url = new UrlPathStartswithCaseInsensitive('dev/build');
        $this->assertNotNull($url->getRequestConfirmationItem($request));

        $url = new UrlPathStartswithCaseInsensitive('dev/build/');
        $this->assertNotNull($url->getRequestConfirmationItem($request));

        $url = new UrlPathStartswithCaseInsensitive('de');
        $this->assertNull($url->getRequestConfirmationItem($request));

        $url = new UrlPathStartswithCaseInsensitive('dev/buil');
        $this->assertNull($url->getRequestConfirmationItem($request));

        $url = new UrlPathStartswithCaseInsensitive('Dev/build');
        $this->assertNotNull($url->getRequestConfirmationItem($request));

        $url = new UrlPathStartswithCaseInsensitive('dev/builD');
        $this->assertNotNull($url->getRequestConfirmationItem($request));
    }
}
