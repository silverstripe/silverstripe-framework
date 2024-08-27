<?php

namespace SilverStripe\Dev\Tests\DevAdminControllerTest;

use SilverStripe\Control\Controller;

class Controller1 extends Controller
{

    const OK_MSG = 'DevAdminControllerTest_Controller1 TEST OK';

    private static $url_handlers = [
        '' => 'index',
        'y1' => 'y1Action'
    ];

    private static $allowed_actions = [
        'index',
        'y1Action',
    ];


    public function index()
    {
        echo Controller1::OK_MSG;
    }

    public function y1Action()
    {
        echo Controller1::OK_MSG . ' y1';
    }
}
