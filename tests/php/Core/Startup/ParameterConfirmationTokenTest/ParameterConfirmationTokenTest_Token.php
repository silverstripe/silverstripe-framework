<?php

namespace SilverStripe\Core\Tests\Startup\ParameterConfirmationTokenTest;

use SilverStripe\Core\Startup\ParameterConfirmationToken;
use SilverStripe\Dev\TestOnly;

/**
 * Dummy parameter token
 */
class ParameterConfirmationTokenTest_Token extends ParameterConfirmationToken implements TestOnly
{

    public function currentAbsoluteURL()
    {
        return parent::currentAbsoluteURL();
    }
}
