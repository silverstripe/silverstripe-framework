<?php

namespace SilverStripe\Core\Tests\Startup\ParameterConfirmationTokenTest;

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
