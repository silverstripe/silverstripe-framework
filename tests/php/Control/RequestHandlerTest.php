<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Control\Tests\RequestHandlerTest\RequestHandlerTestException;
use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Dev\Exceptions\ExpectedWarningException;

/**
 * Tests for the RequestHandler class
 */
class RequestHandlerTest extends SapphireTest
{
    protected $usesDatabase = false;

    public static function provideTestLink(): array
    {
        return [
            // If there's no url segment, there's no link
            [
                'urlSegment' => null,
                'action' => null,
                'expected' => null,
            ],
            [
                'urlSegment' => null,
                'action' => 'some-action',
                'expected' => null,
            ],
            // The action is just addeed on after the url segment
            [
                'urlSegment' => 'my-controller',
                'action' => null,
                'expected' => 'my-controller',
            ],
            [
                'urlSegment' => 'my-controller',
                'action' => 'some-action',
                'expected' => 'my-controller/some-action',
            ],
        ];
    }

    #[DataProvider('provideTestLink')]
    public function testLink(?string $urlSegment, ?string $action, ?string $expected)
    {
        $this->enableErrorHandler();
        if ($urlSegment === null) {
            $this->expectException(ExpectedWarningException::class);
            $this->expectExceptionMessage('Request handler SilverStripe\Control\RequestHandler does not have a url_segment defined. Relying on this link may be an application error');
        }

        $handler = new RequestHandler();
        RequestHandler::config()->set('url_segment', $urlSegment);
        Controller::config()->set('add_trailing_slash', false);

        $this->assertEquals($expected, $handler->Link($action));

        // Validate that trailing slash config is respected
        Controller::config()->set('add_trailing_slash', true);
        if (is_string($expected)) {
            $expected .= '/';
        }

        $this->assertEquals($expected, $handler->Link($action));
    }

    #[DataProvider('provideTestLink')]
    public function testAbsoluteLink(?string $urlSegment, ?string $action, ?string $expected)
    {
        $this->enableErrorHandler();
        if ($urlSegment === null) {
            $this->expectException(ExpectedWarningException::class);
            $this->expectExceptionMessage('Request handler SilverStripe\Control\RequestHandler does not have a url_segment defined. Relying on this link may be an application error');
        }

        $handler = new RequestHandler();
        RequestHandler::config()->set('url_segment', $urlSegment);
        Controller::config()->set('add_trailing_slash', false);

        if ($expected !== null) {
            $expected = Director::absoluteURL($expected);
        }
        $this->assertEquals($expected, $handler->AbsoluteLink($action));

        // Validate that trailing slash config is respected
        Controller::config()->set('add_trailing_slash', true);
        if (is_string($expected)) {
            $expected = Director::absoluteURL($expected . '/');
        }

        $this->assertEquals(Director::absoluteURL($expected), $handler->AbsoluteLink($action));
    }
}
