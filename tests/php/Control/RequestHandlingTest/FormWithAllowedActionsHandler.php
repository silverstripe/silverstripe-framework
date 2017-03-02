<?php


namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Forms\FormRequestHandler;

/**
 * Request handler for
 * @see FormWithAllowedActions
 */
class FormWithAllowedActionsHandler extends FormRequestHandler
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
