<?php

namespace SilverStripe\Forms\Tests\UploadFieldTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

class TestController extends Controller implements TestOnly
{
    public function Link($action = null)
    {
        return Controller::join_links('UploadFieldTest_Controller', $action, '/');
    }

    protected $template = 'BlankPage';

    private static $allowed_actions = array('Form', 'index', 'submit');

    public function Form()
    {
        /**
 * @skipUpgrade
*/
        return new UploadFieldTestForm($this, 'Form');
    }
}
