<?php

namespace SilverStripe\Control\Tests\DirectorTest;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestFilter;
use SilverStripe\Dev\TestOnly;

class TestRequestFilter implements RequestFilter, TestOnly
{
    public $preCalls = 0;
    public $postCalls = 0;

    public $failPre = false;
    public $failPost = false;

    public function preRequest(HTTPRequest $request)
    {
        ++$this->preCalls;

        if ($this->failPre) {
            return false;
        }
        return true;
    }

    public function postRequest(HTTPRequest $request, HTTPResponse $response)
    {
        ++$this->postCalls;

        if ($this->failPost) {
            return false;
        }
        return true;
    }

    public function reset()
    {
        $this->preCalls = 0;
        $this->postCalls = 0;
    }
}
