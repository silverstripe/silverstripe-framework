<?php

namespace SilverStripe\ORM\Tests\DBReplicaTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

class TestController extends Controller implements TestOnly
{
    public function index()
    {
        // Make a call to the database
        TestObject::get()->count();
        $response = $this->getResponse();
        $response->setBody('DB_REPLICA_TEST_CONTROLLER');
        return $response;
    }
}
