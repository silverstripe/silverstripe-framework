<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for the RequestHandler class
 */
class RequestHandlerTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function provideTestLink(): array
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

    /**
     * @dataProvider provideTestLink
     */
    public function testLink(?string $urlSegment, ?string $action, ?string $expected)
    {
        if ($urlSegment === null) {
            $this->expectWarning();
            $this->expectWarningMessage('Request handler SilverStripe\Control\RequestHandler does not have a url_segment defined. Relying on this link may be an application error');
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

    /**
     * @dataProvider provideTestLink
     */
    public function testAbsoluteLink(?string $urlSegment, ?string $action, ?string $expected)
    {
        if ($urlSegment === null) {
            $this->expectWarning();
            $this->expectWarningMessage('Request handler SilverStripe\Control\RequestHandler does not have a url_segment defined. Relying on this link may be an application error');
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
