<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Control\HTTP;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Control\Tests\HTTPCacheControlIntegrationTest\RuleController;
use SilverStripe\Control\Tests\HTTPCacheControlIntegrationTest\SessionController;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;

class HTTPCacheControlIntegrationTest extends FunctionalTest
{
    protected static $extra_controllers = [
        SessionController::class,
        RuleController::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        HTTPCacheControlMiddleware::config()
            ->set('defaultState', 'disabled')
            ->set('defaultForcingLevel', 0);
        HTTPCacheControlMiddleware::reset();
    }

    public function testFormCSRF()
    {
        // CSRF sets caching to disabled
        $response = $this->get('HTTPCacheControlIntegrationTest_SessionController/showform');
        $header = $response->getHeader('Cache-Control');
        $this->assertFalse($response->isError());
        $this->assertStringNotContainsString('public', $header);
        $this->assertStringNotContainsString('private', $header);
        $this->assertStringContainsString('no-cache', $header);
        $this->assertStringContainsString('no-store', $header);
        $this->assertStringContainsString('must-revalidate', $header);
    }

    public function testPublicForm()
    {
        // Public forms (http get) allow public caching
        $response = $this->get('HTTPCacheControlIntegrationTest_SessionController/showpublicform');
        $header = $response->getHeader('Cache-Control');
        $this->assertFalse($response->isError());
        $this->assertStringContainsString('public', $header);
        $this->assertStringContainsString('must-revalidate', $header);
        $this->assertStringNotContainsString('no-cache', $response->getHeader('Cache-Control'));
        $this->assertStringNotContainsString('no-store', $response->getHeader('Cache-Control'));
    }

    public function testPrivateActionsError()
    {
        // disallowed private actions don't cache
        $response = $this->get('HTTPCacheControlIntegrationTest_SessionController/privateaction');
        $header = $response->getHeader('Cache-Control');
        $this->assertTrue($response->isError());
        $this->assertStringContainsString('no-cache', $header);
        $this->assertStringContainsString('no-store', $header);
        $this->assertStringContainsString('must-revalidate', $header);
    }

    public function testPrivateActionsAuthenticated()
    {
        $this->logInWithPermission('ADMIN');
        // Authenticated actions are private cache
        $response = $this->get('HTTPCacheControlIntegrationTest_SessionController/privateaction');
        $header = $response->getHeader('Cache-Control');
        $this->assertFalse($response->isError());
        $this->assertStringContainsString('private', $header);
        $this->assertStringContainsString('must-revalidate', $header);
        $this->assertStringNotContainsString('no-cache', $header);
        $this->assertStringNotContainsString('no-store', $header);
    }

    public function testPrivateCache()
    {
        $response = $this->get('HTTPCacheControlIntegrationTest_RuleController/privateaction');
        $header = $response->getHeader('Cache-Control');
        $this->assertFalse($response->isError());
        $this->assertStringContainsString('private', $header);
        $this->assertStringContainsString('must-revalidate', $header);
        $this->assertStringNotContainsString('no-cache', $header);
        $this->assertStringNotContainsString('no-store', $header);
    }

    public function testPublicCache()
    {
        $response = $this->get('HTTPCacheControlIntegrationTest_RuleController/publicaction');
        $header = $response->getHeader('Cache-Control');
        $this->assertFalse($response->isError());
        $this->assertStringContainsString('public', $header);
        $this->assertStringContainsString('must-revalidate', $header);
        $this->assertStringNotContainsString('no-cache', $header);
        $this->assertStringNotContainsString('no-store', $header);
        $this->assertStringContainsString('max-age=9000', $header);
    }

    public function testDisabledCache()
    {
        $response = $this->get('HTTPCacheControlIntegrationTest_RuleController/disabledaction');
        $header = $response->getHeader('Cache-Control');
        $this->assertFalse($response->isError());
        $this->assertStringNotContainsString('public', $header);
        $this->assertStringNotContainsString('private', $header);
        $this->assertStringContainsString('no-cache', $header);
        $this->assertStringContainsString('no-store', $header);
        $this->assertStringContainsString('must-revalidate', $header);
    }
}
