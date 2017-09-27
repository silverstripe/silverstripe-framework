<?php

namespace SilverStripe\Control\Tests\Middleware;

use SilverStripe\Control\Middleware\RateLimitMiddleware;
use SilverStripe\Control\Middleware\RequestHandlerMiddlewareAdapter;
use SilverStripe\Control\Tests\Middleware\Control\TestController;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\ORM\FieldType\DBDatetime;

class RateLimitMiddlewareTest extends FunctionalTest
{

    protected static $extra_controllers = [
        TestController::class,
    ];

    protected function setUp()
    {
        parent::setUp();
        DBDatetime::set_mock_now('2017-09-27 00:00:00');
        Config::modify()->set(Injector::class, 'TestRateLimitMiddleware', [
            'class' => RateLimitMiddleware::class,
            'properties' => [
                'ExtraKey' => 'test',
                'MaxAttempts' => 2,
                'Decay' => 1,
            ],
        ]);
        Config::modify()->set(Injector::class, 'RateLimitTestController', [
            'class' => RequestHandlerMiddlewareAdapter::class,
            'properties' => [
                'RequestHandler' => '%$' . TestController::class,
                'Middlewares' => [
                    '%$TestRateLimitMiddleware'
                ],
            ],
        ]);
    }

    protected function getExtraRoutes()
    {
        $rules = parent::getExtraRoutes();
        $rules['TestController//$Action/$ID/$OtherID'] = '%$RateLimitTestController';
        return $rules;
    }

    public function testRequest()
    {
        $response = $this->get('TestController');
        $this->assertFalse($response->isError());
        $this->assertEquals(2, $response->getHeader('X-RateLimit-Limit'));
        $this->assertEquals(1, $response->getHeader('X-RateLimit-Remaining'));
        $this->assertEquals(DBDatetime::now()->getTimestamp() + 60, $response->getHeader('X-RateLimit-Reset'));
        $this->assertEquals('Success', $response->getBody());
        $response = $this->get('TestController');
        $this->assertFalse($response->isError());
        $this->assertEquals(0, $response->getHeader('X-RateLimit-Remaining'));
        $response = $this->get('TestController');
        $this->assertTrue($response->isError());
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertEquals(60, $response->getHeader('retry-after'));
        $this->assertNotEquals('Success', $response->getBody());
    }
}
