<?php

namespace SilverStripe\Core\Tests\Startup\URLConfirmationTokenTest;

/**
 * A token that always validates a given token
 */
class StubValidToken extends StubToken
{

    protected function checkToken($token)
    {
        return true;
    }
}
