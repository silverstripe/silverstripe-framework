<?php

namespace SilverStripe\Control\Middleware\ConfirmationMiddleware;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Kernel;

/**
 * Allows a bypass for a list of environment types (e.g. DEV, TEST, LIVE)
 */
class EnvironmentBypass implements Bypass
{
    /**
     * The list of environments allowing a bypass for a confirmation
     *
     * @var string[]
     */
    private $environments;


    /**
     * Initialize the bypass with the list of environment types
     *
     * @param string[] ...$environments
     */
    public function __construct(...$environments)
    {
        $this->environments = $environments;
    }

    /**
     * Returns the list of environments
     *
     * @return string[]
     *
     */
    public function getEnvironments()
    {
        return $this->environments;
    }

    /**
     * Set the list of environments allowing a bypass
     *
     * @param string[] $environments List of environment types
     *
     * @return $this
     */
    public function setEnvironments($environments)
    {
        $this->environments = $environments;
        return $this;
    }

    /**
     * Checks whether the current environment type in the list
     * of allowed ones
     *
     * @param HTTPRequest $request
     *
     * @return bool
     */
    public function checkRequestForBypass(HTTPRequest $request)
    {
        return in_array(Director::get_environment_type(), $this->environments ?? [], true);
    }
}
