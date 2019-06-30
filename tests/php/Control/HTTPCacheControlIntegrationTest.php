<?php declare(strict_types = 1);

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

    protected function setUp()
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
        $this->assertNotContains('public', $header);
        $this->assertNotContains('private', $header);
        $this->assertContains('no-cache', $header);
        $this->assertContains('no-store', $header);
        $this->assertContains('must-revalidate', $header);
    }

    public function testPublicForm()
    {
        // Public forms (http get) allow public caching
        $response = $this->get('HTTPCacheControlIntegrationTest_SessionController/showpublicform');
        $header = $response->getHeader('Cache-Control');
        $this->assertFalse($response->isError());
        $this->assertContains('public', $header);
        $this->assertContains('must-revalidate', $header);
        $this->assertNotContains('no-cache', $response->getHeader('Cache-Control'));
        $this->assertNotContains('no-store', $response->getHeader('Cache-Control'));
    }

    public function testPrivateActionsError()
    {
        // disallowed private actions don't cache
        $response = $this->get('HTTPCacheControlIntegrationTest_SessionController/privateaction');
        $header = $response->getHeader('Cache-Control');
        $this->assertTrue($response->isError());
        $this->assertContains('no-cache', $header);
        $this->assertContains('no-store', $header);
        $this->assertContains('must-revalidate', $header);
    }

    public function testPrivateActionsAuthenticated()
    {
        $this->logInWithPermission('ADMIN');
        // Authenticated actions are private cache
        $response = $this->get('HTTPCacheControlIntegrationTest_SessionController/privateaction');
        $header = $response->getHeader('Cache-Control');
        $this->assertFalse($response->isError());
        $this->assertContains('private', $header);
        $this->assertContains('must-revalidate', $header);
        $this->assertNotContains('no-cache', $header);
        $this->assertNotContains('no-store', $header);
    }

    public function testPrivateCache()
    {
        $response = $this->get('HTTPCacheControlIntegrationTest_RuleController/privateaction');
        $header = $response->getHeader('Cache-Control');
        $this->assertFalse($response->isError());
        $this->assertContains('private', $header);
        $this->assertContains('must-revalidate', $header);
        $this->assertNotContains('no-cache', $header);
        $this->assertNotContains('no-store', $header);
    }

    public function testPublicCache()
    {
        $response = $this->get('HTTPCacheControlIntegrationTest_RuleController/publicaction');
        $header = $response->getHeader('Cache-Control');
        $this->assertFalse($response->isError());
        $this->assertContains('public', $header);
        $this->assertContains('must-revalidate', $header);
        $this->assertNotContains('no-cache', $header);
        $this->assertNotContains('no-store', $header);
        $this->assertContains('max-age=9000', $header);
    }

    public function testDisabledCache()
    {
        $response = $this->get('HTTPCacheControlIntegrationTest_RuleController/disabledaction');
        $header = $response->getHeader('Cache-Control');
        $this->assertFalse($response->isError());
        $this->assertNotContains('public', $header);
        $this->assertNotContains('private', $header);
        $this->assertContains('no-cache', $header);
        $this->assertContains('no-store', $header);
        $this->assertContains('must-revalidate', $header);
    }
}
