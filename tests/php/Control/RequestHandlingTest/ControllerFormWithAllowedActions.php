<?php

namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;

class ControllerFormWithAllowedActions extends Controller implements TestOnly
{
    private static $url_segment = 'ControllerFormWithAllowedActions';

    private static $allowed_actions = array('Form');

    /**
     * @skipUpgrade
     */
    public function Form()
    {
        return new FormWithAllowedActions(
            $this,
            'Form',
            new FieldList(),
            new FieldList(
                new FormAction('allowedformaction')
            )
        );
    }
}
