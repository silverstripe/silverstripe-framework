<?php

namespace SilverStripe\Dev\Tests\DevAdminControllerTest;

use SilverStripe\Control\Controller;

class TestHiddenController extends Controller
{
    const OK_MSG = 'DevAdminControllerTest_TestHiddenController TEST OK';

    public function index()
    {
        echo TestHiddenController::OK_MSG;
    }
}
