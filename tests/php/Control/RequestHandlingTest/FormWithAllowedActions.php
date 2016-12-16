<?php

namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\Form;

class FormWithAllowedActions extends Form implements TestOnly
{
    private static $allowed_actions = array(
        'allowedformaction' => 1,
    );

    public function allowedformaction()
    {
        return 'allowedformaction';
    }

    public function disallowedformaction()
    {
        return 'disallowedformaction';
    }
}
