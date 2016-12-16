<?php

namespace SilverStripe\Dev\Tests\DevAdminControllerTest;

use SilverStripe\Control\Controller;

class Controller1 extends Controller
{

    const OK_MSG = 'DevAdminControllerTest_Controller1 TEST OK';

    private static $url_handlers = array(
        '' => 'index',
        'y1' => 'y1Action'
    );

    private static $allowed_actions = array(
        'index',
        'y1Action',
    );


    public function index()
    {
        echo self::OK_MSG;
    }

    public function y1Action()
    {
        echo self::OK_MSG;
    }
}
