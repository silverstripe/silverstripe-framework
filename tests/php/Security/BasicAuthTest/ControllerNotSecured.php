<?php declare(strict_types = 1);

namespace SilverStripe\Security\Tests\BasicAuthTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

/**
 * @skipUpgrade
 */
class ControllerNotSecured extends Controller implements TestOnly
{
    protected $template = 'BlankPage';

    /**
     * Disable legacy global-enable
     *
     * @deprecated 4.0.0:5.0.0
     * @var bool
     */
    protected $basicAuthEnabled = false;

    public function Link($action = null)
    {
        return Controller::join_links('BasicAuthTest_ControllerNotSecured', $action, '/');
    }
}
