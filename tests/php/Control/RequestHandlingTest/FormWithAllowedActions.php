<?php declare(strict_types = 1);

namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\Form;

class FormWithAllowedActions extends Form implements TestOnly
{
    protected function buildRequestHandler()
    {
        return FormWithAllowedActionsHandler::create($this);
    }
}
