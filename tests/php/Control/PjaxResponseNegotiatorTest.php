<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\PjaxResponseNegotiator;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;

class PjaxResponseNegotiatorTest extends SapphireTest
{

    public function testDefaultCallbacks()
    {
        $negotiator = new PjaxResponseNegotiator(
            array(
            'default' => function () {
                return 'default response';
            },
            )
        );
        $request = new HTTPRequest('GET', '/'); // not setting pjax header
        $request->setSession(new Session([]));
        $response = $negotiator->respond($request);
        $this->assertEquals('default response', $response->getBody());
    }

    public function testSelectsFragmentByHeader()
    {
        $negotiator = new PjaxResponseNegotiator(
            array(
            'default' => function () {
                return 'default response';
            },
            'myfragment' => function () {
                return 'myfragment response';
            },
            )
        );
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $request->addHeader('X-Pjax', 'myfragment');
        $response = $negotiator->respond($request);
        $this->assertEquals('{"myfragment":"myfragment response"}', $response->getBody());
    }

    public function testMultipleFragments()
    {
        $negotiator = new PjaxResponseNegotiator(
            array(
            'default' => function () {
                return 'default response';
            },
            'myfragment' => function () {
                return 'myfragment response';
            },
            'otherfragment' => function () {
                return 'otherfragment response';
            },
            )
        );
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $request->addHeader('X-Pjax', 'myfragment,otherfragment');
        $request->addHeader('Accept', 'text/json');
        $response = $negotiator->respond($request);
        $json = json_decode($response->getBody());
        $this->assertObjectHasAttribute('myfragment', $json);
        $this->assertEquals('myfragment response', $json->myfragment);
        $this->assertObjectHasAttribute('otherfragment', $json);
        $this->assertEquals('otherfragment response', $json->otherfragment);
    }

    public function testFragmentsOverride()
    {
        $negotiator = new PjaxResponseNegotiator(
            array(
            'alpha' => function () {
                return 'alpha response';
            },
            'beta' => function () {
                return 'beta response';
            }
            )
        );

        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $request->addHeader('X-Pjax', 'alpha');
        $request->addHeader('Accept', 'text/json');

        $response = $negotiator->setFragmentOverride(array('beta'))->respond($request);
        $json = json_decode($response->getBody());
        $this->assertFalse(isset($json->alpha));
        $this->assertObjectHasAttribute('beta', $json);
    }
}
