<?php

namespace SilverStripe\Core\Tests\Startup\ParameterConfirmationTokenTest;

use SilverStripe\Core\Tests\Startup\ParameterConfirmationTokenTest\ParameterConfirmationTokenTest_Token;

/**
 * A token that always validates a given token
 */
class ParameterConfirmationTokenTest_ValidToken extends ParameterConfirmationTokenTest_Token
{

    protected function checkToken($token)
    {
        return true;
    }
}
