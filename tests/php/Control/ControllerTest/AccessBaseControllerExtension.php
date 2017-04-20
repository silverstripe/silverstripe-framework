<?php

namespace SilverStripe\Control\Tests\ControllerTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class AccessBaseControllerExtension extends Extension implements TestOnly
{

    private static $allowed_actions = array(
        "extensionmethod1" => true, // granted because defined on this class
        "method1" => true, // ignored because method not defined on this class
        "method2" => true, // ignored because method not defined on this class
        "protectedextensionmethod" => true, // ignored because method is protected
    );

    // Allowed for all
    public function extensionmethod1()
    {
    }

    // Denied for all, not defined
    public function extensionmethod2()
    {
    }

    // Denied because its protected
    protected function protectedextensionmethod()
    {
    }

    public function internalextensionmethod()
    {
    }
}
