<?php
namespace SilverStripe\Forms\GridField\FormAction;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

abstract class AbstractRequestAwareStore implements StateStore
{
    /**
     * @return HTTPRequest
     */
    public function getRequest()
    {
        // Replicating existing functionality from GridField_FormAction
        return Controller::curr()->getRequest();
    }
}
