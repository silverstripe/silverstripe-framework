<?php

namespace SilverStripe\Core\Tests\Startup\URLConfirmationTokenTest;

use SilverStripe\Core\Startup\URLConfirmationToken;
use SilverStripe\Dev\TestOnly;

/**
 * Dummy url token
 */
class StubToken extends URLConfirmationToken implements TestOnly
{
    public function urlMatches()
    {
        return parent::urlMatches();
    }

    public function currentURL()
    {
        return parent::currentURL();
    }

    public function redirectURL()
    {
        return parent::redirectURL();
    }
}
