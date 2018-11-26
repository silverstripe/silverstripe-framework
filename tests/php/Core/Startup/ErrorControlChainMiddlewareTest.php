<?php

namespace SilverStripe\Core\Tests\Startup;

use SilverStripe\Control\HTTPApplication;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Core\Kernel;
use SilverStripe\Core\Startup\ErrorControlChainMiddleware;
use SilverStripe\Core\Tests\Startup\ErrorControlChainMiddlewareTest\BlankKernel;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Security;

class ErrorControlChainMiddlewareTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp()
    {
        parent::setUp();
        Security::force_database_is_ready(true);
    }

    protected function tearDown()
    {
        Security::clear_database_is_ready();
        parent::tearDown();
    }

    public function testLiveFlushAdmin()
    {
        // Mock admin
        $adminID = $this->logInWithPermission('ADMIN');
        $this->logOut();

        // Mock app
        $app = new HTTPApplication(new BlankKernel(BASE_PATH));
        $app->getKernel()->setEnvironment(Kernel::LIVE);

        // Test being logged in as admin
        $chain = new ErrorControlChainMiddleware($app);
        $request = new HTTPRequest('GET', '/', ['flush' => 1]);
        $request->setSession(new Session(['loggedInAs' => $adminID]));
        $result = $chain->process($request, function () {
            return null;
        });

        $this->assertInstanceOf(HTTPResponse::class, $result);
        $location = $result->getHeader('Location');
        $this->assertContains('flush=1&flushtoken=', $location);
        $this->assertNotContains('Security/login', $location);
    }

    public function testLiveFlushUnauthenticated()
    {
        // Mock app
        $app = new HTTPApplication(new BlankKernel(BASE_PATH));
        $app->getKernel()->setEnvironment(Kernel::LIVE);

        // Test being logged in as no one
        Security::setCurrentUser(null);
        $chain = new ErrorControlChainMiddleware($app);
        $request = new HTTPRequest('GET', '/', ['flush' => 1]);
        $request->setSession(new Session(['loggedInAs' => 0]));
        $result = $chain->process($request, function () {
            return null;
        });

        // Should be directed to login, not to flush
        $this->assertInstanceOf(HTTPResponse::class, $result);
        $location = $result->getHeader('Location');
        $this->assertNotContains('?flush=1&flushtoken=', $location);
        $this->assertContains('Security/login', $location);
    }

    public function testLiveBuildAdmin()
    {
        // Mock admin
        $adminID = $this->logInWithPermission('ADMIN');
        $this->logOut();

        // Mock app
        $app = new HTTPApplication(new BlankKernel(BASE_PATH));
        $app->getKernel()->setEnvironment(Kernel::LIVE);

        // Test being logged in as admin
        $chain = new ErrorControlChainMiddleware($app);
        $request = new HTTPRequest('GET', '/dev/build/');
        $request->setSession(new Session(['loggedInAs' => $adminID]));
        $result = $chain->process($request, function () {
            return null;
        });

        $this->assertInstanceOf(HTTPResponse::class, $result);
        $location = $result->getHeader('Location');
        $this->assertContains('/dev/build', $location);
        $this->assertContains('devbuildtoken=', $location);
        $this->assertNotContains('Security/login', $location);
    }

    public function testLiveBuildUnauthenticated()
    {
        // Mock app
        $app = new HTTPApplication(new BlankKernel(BASE_PATH));
        $app->getKernel()->setEnvironment(Kernel::LIVE);

        // Test being logged in as no one
        Security::setCurrentUser(null);
        $chain = new ErrorControlChainMiddleware($app);
        $request = new HTTPRequest('GET', '/dev/build');
        $request->setSession(new Session(['loggedInAs' => 0]));
        $result = $chain->process($request, function () {
            return null;
        });

        // Should be directed to login, not to flush
        $this->assertInstanceOf(HTTPResponse::class, $result);
        $location = $result->getHeader('Location');
        $this->assertNotContains('/dev/build', $location);
        $this->assertNotContains('?devbuildtoken=', $location);
        $this->assertContains('Security/login', $location);
    }

    public function testLiveBuildAndFlushAdmin()
    {
        // Mock admin
        $adminID = $this->logInWithPermission('ADMIN');
        $this->logOut();

        // Mock app
        $app = new HTTPApplication(new BlankKernel(BASE_PATH));
        $app->getKernel()->setEnvironment(Kernel::LIVE);

        // Test being logged in as admin
        $chain = new ErrorControlChainMiddleware($app);
        $request = new HTTPRequest('GET', '/dev/build/', ['flush' => '1']);
        $request->setSession(new Session(['loggedInAs' => $adminID]));
        $result = $chain->process($request, function () {
            return null;
        });

        $this->assertInstanceOf(HTTPResponse::class, $result);
        $location = $result->getHeader('Location');
        $this->assertContains('/dev/build', $location);
        $this->assertContains('flush=1', $location);
        $this->assertContains('devbuildtoken=', $location);
        $this->assertContains('flushtoken=', $location);
        $this->assertNotContains('Security/login', $location);
    }

    public function testLiveBuildAndFlushUnauthenticated()
    {
        // Mock app
        $app = new HTTPApplication(new BlankKernel(BASE_PATH));
        $app->getKernel()->setEnvironment(Kernel::LIVE);

        // Test being logged in as no one
        Security::setCurrentUser(null);
        $chain = new ErrorControlChainMiddleware($app);
        $request = new HTTPRequest('GET', '/dev/build', ['flush' => '1']);
        $request->setSession(new Session(['loggedInAs' => 0]));
        $result = $chain->process($request, function () {
            return null;
        });

        // Should be directed to login, not to flush
        $this->assertInstanceOf(HTTPResponse::class, $result);
        $location = $result->getHeader('Location');
        $this->assertNotContains('/dev/build', $location);
        $this->assertNotContains('flush=1', $location);
        $this->assertNotContains('devbuildtoken=', $location);
        $this->assertNotContains('flushtoken=', $location);
        $this->assertContains('Security/login', $location);
    }
}
