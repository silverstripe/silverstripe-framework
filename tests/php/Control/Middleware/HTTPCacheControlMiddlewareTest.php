<?php

namespace SilverStripe\Control\Tests\Middleware;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Dev\SapphireTest;

class HTTPCacheControlMiddlewareTest extends SapphireTest
{
    protected function setUp(): void
    {
        parent::setUp();
        // Set to disabled at null forcing level
        HTTPCacheControlMiddleware::config()
            ->set('defaultState', HTTPCacheControlMiddleware::STATE_ENABLED)
            ->set('defaultForcingLevel', 0);
        HTTPCacheControlMiddleware::reset();
    }

    public function provideCacheStates()
    {
        return [
            ['enableCache', false],
            ['publicCache', false],
            ['privateCache', false],
            ['disableCache', true],
        ];
    }

    /**
     * @dataProvider provideCacheStates
     */
    public function testCheckDefaultStates($state, $immutable)
    {
        $cc = HTTPCacheControlMiddleware::singleton();
        $cc->{$state}();

        $response = new HTTPResponse();
        $cc->applyToResponse($response);

        $this->assertContains('must-revalidate', $response->getHeader('cache-control'));
    }

    /**
     * @dataProvider provideCacheStates
     */
    public function testSetMaxAge($state, $immutable)
    {
        $cc = HTTPCacheControlMiddleware::singleton();
        $cc->{$state}();

        $originalResponse = new HTTPResponse();
        $cc->applyToResponse($originalResponse);

        $cc->setMaxAge('300');

        $response = new HTTPResponse();

        $cc->applyToResponse($response);

        if ($immutable) {
            $this->assertEquals($originalResponse->getHeader('cache-control'), $response->getHeader('cache-control'));
        } else {
            $this->assertContains('max-age=300', $response->getHeader('cache-control'));
            $this->assertNotContains('no-cache', $response->getHeader('cache-control'));
            $this->assertNotContains('no-store', $response->getHeader('cache-control'));
        }
    }

    public function testEnableCacheWithMaxAge()
    {
        $maxAge = 300;

        $cc = HTTPCacheControlMiddleware::singleton();
        $cc->enableCache(false, $maxAge);

        $response = new HTTPResponse();
        $cc->applyToResponse($response);

        $this->assertContains('max-age=300', $response->getHeader('cache-control'));
        $this->assertNotContains('no-cache', $response->getHeader('cache-control'));
        $this->assertNotContains('no-store', $response->getHeader('cache-control'));
    }

    public function testEnableCacheWithMaxAgeAppliesWhenLevelDoesNot()
    {
        $maxAge = 300;

        $cc = HTTPCacheControlMiddleware::singleton();
        $cc->privateCache(true);
        $cc->enableCache(false, $maxAge);

        $response = new HTTPResponse();
        $cc->applyToResponse($response);

        $this->assertContains('max-age=300', $response->getHeader('cache-control'));
    }

    public function testPublicCacheWithMaxAge()
    {
        $maxAge = 300;

        $cc = HTTPCacheControlMiddleware::singleton();
        $cc->publicCache(false, $maxAge);

        $response = new HTTPResponse();
        $cc->applyToResponse($response);

        $this->assertContains('max-age=300', $response->getHeader('cache-control'));
        // STATE_PUBLIC doesn't contain no-cache or no-store headers to begin with,
        // so can't test their removal effectively
        $this->assertNotContains('no-cache', $response->getHeader('cache-control'));
    }

    public function testPublicCacheWithMaxAgeAppliesWhenLevelDoesNot()
    {
        $maxAge = 300;

        $cc = HTTPCacheControlMiddleware::singleton();
        $cc->privateCache(true);
        $cc->publicCache(false, $maxAge);

        $response = new HTTPResponse();
        $cc->applyToResponse($response);

        $this->assertContains('max-age=300', $response->getHeader('cache-control'));
    }

    /**
     * @dataProvider provideCacheStates
     */
    public function testSetNoStore($state, $immutable)
    {
        $cc = HTTPCacheControlMiddleware::singleton();
        $cc->setMaxAge('300');
        $cc->setSharedMaxAge('300');

        $cc->{$state}();

        $originalResponse = new HTTPResponse();
        $cc->applyToResponse($originalResponse);

        $cc->setNoStore();

        $response = new HTTPResponse();

        $cc->applyToResponse($response);

        if ($immutable) {
            $this->assertEquals($originalResponse->getHeader('cache-control'), $response->getHeader('cache-control'));
        } else {
            $this->assertContains('no-store', $response->getHeader('cache-control'));
            $this->assertNotContains('max-age', $response->getHeader('cache-control'));
            $this->assertNotContains('s-maxage', $response->getHeader('cache-control'));
        }
    }

    /**
     * @dataProvider provideCacheStates
     */
    public function testSetNoCache($state, $immutable)
    {
        $cc = HTTPCacheControlMiddleware::singleton();
        $cc->setMaxAge('300');
        $cc->setSharedMaxAge('300');

        $cc->{$state}();

        $originalResponse = new HTTPResponse();
        $cc->applyToResponse($originalResponse);

        $cc->setNoCache();

        $response = new HTTPResponse();

        $cc->applyToResponse($response);

        if ($immutable) {
            $this->assertEquals($originalResponse->getHeader('cache-control'), $response->getHeader('cache-control'));
        } else {
            $this->assertContains('no-cache', $response->getHeader('cache-control'));
            $this->assertNotContains('max-age', $response->getHeader('cache-control'));
            $this->assertNotContains('s-maxage', $response->getHeader('cache-control'));
        }
    }

    /**
     * @dataProvider provideCacheStates
     */
    public function testSetSharedMaxAge($state, $immutable)
    {
        $cc = HTTPCacheControlMiddleware::singleton();

        $cc->{$state}();

        $originalResponse = new HTTPResponse();
        $cc->applyToResponse($originalResponse);

        $cc->setSharedMaxAge('300');

        $response = new HTTPResponse();

        $cc->applyToResponse($response);

        if ($immutable) {
            $this->assertEquals($originalResponse->getHeader('cache-control'), $response->getHeader('cache-control'));
        } else {
            $this->assertContains('s-maxage=300', $response->getHeader('cache-control'));
            $this->assertNotContains('no-cache', $response->getHeader('cache-control'));
            $this->assertNotContains('no-store', $response->getHeader('cache-control'));
        }
    }

    /**
     * @dataProvider provideCacheStates
     */
    public function testSetMustRevalidate($state, $immutable)
    {
        $cc = HTTPCacheControlMiddleware::singleton();

        $cc->{$state}();

        $originalResponse = new HTTPResponse();
        $cc->applyToResponse($originalResponse);

        $cc->setMustRevalidate();

        $response = new HTTPResponse();

        $cc->applyToResponse($response);

        if ($immutable) {
            $this->assertEquals($originalResponse->getHeader('cache-control'), $response->getHeader('cache-control'));
        } else {
            $this->assertContains('must-revalidate', $response->getHeader('cache-control'));
            $this->assertNotContains('max-age', $response->getHeader('cache-control'));
            $this->assertNotContains('s-maxage', $response->getHeader('cache-control'));
        }
    }

    public function testCachingPriorities()
    {
        $hcc = new HTTPCacheControlMiddleware();
        $this->assertFalse($this->isDisabled($hcc), 'caching starts as disabled');

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
        return $hcc->hasDirective('no-store') && !$hcc->hasDirective('private') && !$hcc->hasDirective('public');
    }
}
