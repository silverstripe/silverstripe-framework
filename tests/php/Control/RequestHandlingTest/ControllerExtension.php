<?php

namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

/**
 * Simple extension for the test controller
 */
class ControllerExtension extends Extension implements TestOnly
{

    public static $called_error = false;

    public static $called_404_error = false;

    private static $allowed_actions = ['extendedMethod'];

    public function extendedMethod()
    {
        return "extendedMethod";
    }

    /**
     * Called whenever there is an HTTP error
     */
    public function onBeforeHTTPError()
    {
        ControllerExtension::$called_error = true;
    }

    /**
     * Called whenever there is an 404 error
     */
    public function onBeforeHTTPError404()
    {
        ControllerExtension::$called_404_error = true;
    }
}
