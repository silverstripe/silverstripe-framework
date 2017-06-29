<?php

namespace SilverStripe\Control\Tests\ControllerTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

class SubController extends Controller implements TestOnly
{
    private static $url_segment = 'SubController';

    private static $allowed_actions = array(
        'subaction',
        'subvieweraction',
    );

    private static $url_handlers = array(
        'substring/subvieweraction' => 'subvieweraction',
    );

    public function subaction()
    {
        return $this->getAction();
    }

    /* This is messy, but Controller->handleRequest is a hard to test method which warrants such measures... */
    public function getViewer($action)
    {
        if (empty($action)) {
            throw new SubController_Exception("Null action passed, getViewer will break");
        }
        return parent::getViewer($action);
    }

    public function subvieweraction()
    {
        return 'Hope this works';
    }
}
