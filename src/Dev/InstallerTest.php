<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\Controller;

/**
 * Simple controller that the installer uses to test that URL rewriting is working.
 * @deprecated 4.4.7 This class will be removed in Silverstripe Framework 5.
 */
class InstallerTest extends Controller
{

    private static $allowed_actions = [
        'testrewrite'
    ];

    public function testrewrite()
    {
        echo "OK";
    }
}
