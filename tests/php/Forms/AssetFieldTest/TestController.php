<?php

namespace SilverStripe\Forms\Tests\AssetFieldTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

class TestController extends Controller implements TestOnly
{
    public function Link($action = null)
    {
        /**
 * @skipUpgrade
*/
        return Controller::join_links('AssetFieldTest_Controller', $action, '/');
    }

    protected $template = 'BlankPage';

    private static $allowed_actions = array('Form');

    public function Form()
    {
        return new TestForm($this, 'Form');
    }
}
