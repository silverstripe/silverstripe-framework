<?php

namespace SilverStripe\Control\Tests\DirectorTest;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Dev\TestOnly;

class TestMiddleware implements HTTPMiddleware, TestOnly
{
    public $preCalls = 0;
    public $postCalls = 0;

    public $failPre = false;
    public $failPost = false;

    public function process(HTTPRequest $request, callable $delegate)
    {
        $this->preCalls++;
        if ($this->failPre) {
            return new HTTPResponse('Fail pre', 400);
        }

        $response = $delegate($request);

        $this->postCalls++;
        if ($this->failPost) {
            return new HTTPResponse('Fail post', 500);
        }

        return $response;
    }

    public function reset()
    {
        $this->preCalls = 0;
        $this->postCalls = 0;
    }
}
