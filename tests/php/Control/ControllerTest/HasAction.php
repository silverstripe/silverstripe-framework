<?php

namespace SilverStripe\Control\Tests\ControllerTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

class HasAction extends Controller implements TestOnly
{
    private static $url_segment = 'HasAction';

    private static $allowed_actions = array(
        'allowed_action',
        //'other_action' => 'lowercase_permission'
    );

    protected $templates = array(
        'template_action' => 'template'
    );
}
