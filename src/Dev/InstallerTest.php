<?php

namespace SilverStripe\Dev;

use SilverStripe\Dev\Deprecation;
use SilverStripe\Control\Controller;

/**
 * Simple controller that the installer uses to test that URL rewriting is working.
 * @deprecated 4.4.7 Will be removed without equivalent functionality
 */
class InstallerTest extends Controller
{

    private static $allowed_actions = [
        'testrewrite'
    ];

    public function __construct()
    {
        Deprecation::notice('4.4.7', 'Will be removed without equivalent functionality', Deprecation::SCOPE_CLASS);
    }

    public function testrewrite()
    {
        echo "OK";
    }
}
