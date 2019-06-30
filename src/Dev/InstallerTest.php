<?php declare(strict_types = 1);

namespace SilverStripe\Dev;

use SilverStripe\Control\Controller;

/**
 * Simple controller that the installer uses to test that URL rewriting is working.
 */
class InstallerTest extends Controller
{

    private static $allowed_actions = array(
        'testrewrite'
    );

    public function testrewrite()
    {
        echo "OK";
    }
}
