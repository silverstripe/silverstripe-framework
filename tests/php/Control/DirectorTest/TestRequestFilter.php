<?php

namespace SilverStripe\Control\Tests\DirectorTest;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestFilter;
use SilverStripe\Control\Session;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataModel;

class TestRequestFilter implements RequestFilter, TestOnly
{
	public $preCalls = 0;
	public $postCalls = 0;

	public $failPre = false;
	public $failPost = false;

	public function preRequest(HTTPRequest $request, Session $session, DataModel $model)
	{
		++$this->preCalls;

		if ($this->failPre) {
			return false;
		}
	}

	public function postRequest(HTTPRequest $request, HTTPResponse $response, DataModel $model)
	{
		++$this->postCalls;

		if ($this->failPost) {
			return false;
		}
	}

	public function reset()
	{
		$this->preCalls = 0;
		$this->postCalls = 0;
	}

}
