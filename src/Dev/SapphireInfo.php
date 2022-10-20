<?php

namespace SilverStripe\Dev;

use SilverStripe\Dev\Deprecation;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Returns information about the current site instance.
 * @deprecated 4.4.7 Will be removed without equivalent functionality
 */
class SapphireInfo extends Controller
{
    private static $allowed_actions = [
        'baseurl',
        'version',
        'environmenttype',
    ];

    public function __construct()
    {
        Deprecation::notice('4.4.7', 'Will be removed without equivalent functionality', Deprecation::SCOPE_CLASS);
    }

    protected function init()
    {
        parent::init();
        if (!Director::is_cli() && !Permission::check('ADMIN')) {
            Security::permissionFailure();
        }
    }

    public function Version()
    {
        $sapphireVersion = file_get_contents(FRAMEWORK_PATH . '/silverstripe_version');
        if (!$sapphireVersion) {
            $sapphireVersion = _t('SilverStripe\\Admin\\LeftAndMain.VersionUnknown', 'unknown');
        }
        return $sapphireVersion;
    }

    public function EnvironmentType()
    {
        if (Director::isLive()) {
            return "live";
        } elseif (Director::isTest()) {
            return "test";
        } else {
            return "dev";
        }
    }

    public function BaseURL()
    {
        return Director::absoluteBaseURL();
    }
}
