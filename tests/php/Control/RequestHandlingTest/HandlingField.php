<?php

namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FormField;

/**
 * Form field for the test
 */
class HandlingField extends FormField implements TestOnly
{

    private static $allowed_actions = array(
        'actionOnField'
    );

    public function actionOnField()
    {
        return "Test method on $this->name";
    }

    public function actionNotAllowedOnField()
    {
        return "actionNotAllowedOnField on $this->name";
    }
}
