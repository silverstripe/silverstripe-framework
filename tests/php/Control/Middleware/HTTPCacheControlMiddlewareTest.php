<?php

namespace SilverStripe\Control\Tests\Middleware;

use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Dev\SapphireTest;

class HTTPCacheControlMiddlewareTest extends SapphireTest
{
    public function testCachingPriorities()
    {
        $hcc = new HTTPCacheControlMiddleware();
        $this->assertTrue($this->isDisabled($hcc), 'caching starts as disabled');

        $hcc->enableCache();
        $this->assertFalse($this->isDisabled($hcc));

        $hcc->publicCache();
        $this->assertTrue($this->isPublic($hcc), 'public can be set at start');

        $hcc->privateCache();
        $this->assertTrue($this->isPrivate($hcc), 'private overrides public');

        $hcc->publicCache();
        $this->assertFalse($this->isPublic($hcc), 'public does not overrides private');

        $hcc->disableCache();
        $this->assertTrue($this->isDisabled($hcc), 'disabled overrides private');

        $hcc->privateCache();
        $this->assertFalse($this->isPrivate($hcc), 'private does not override disabled');

        $hcc->enableCache(true);
        $this->assertFalse($this->isDisabled($hcc));

        $hcc->publicCache(true);
        $this->assertTrue($this->isPublic($hcc), 'force-public overrides disabled');

        $hcc->privateCache();
        $this->assertFalse($this->isPrivate($hcc), 'private does not overrdie force-public');

        $hcc->privateCache(true);
        $this->assertTrue($this->isPrivate($hcc), 'force-private overrides force-public');

        $hcc->publicCache(true);
        $this->assertFalse($this->isPublic($hcc), 'force-public does not override force-private');

        $hcc->disableCache(true);
        $this->assertTrue($this->isDisabled($hcc), 'force-disabled overrides force-private');

        $hcc->publicCache(true);
        $this->assertFalse($this->isPublic($hcc), 'force-public does not overrides force-disabled');
    }

    protected function isPrivate(HTTPCacheControlMiddleware $hcc)
    {
        return $hcc->hasDirective('private') && !$hcc->hasDirective('public') && !$hcc->hasDirective('no-cache');
    }

    protected function isPublic(HTTPCacheControlMiddleware $hcc)
    {
        return $hcc->hasDirective('public') && !$hcc->hasDirective('private') && !$hcc->hasDirective('no-cache');
    }

    protected function isDisabled(HTTPCacheControlMiddleware $hcc)
    {
        return $hcc->hasDirective('no-cache') && !$hcc->hasDirective('private') && !$hcc->hasDirective('public');
    }
}
