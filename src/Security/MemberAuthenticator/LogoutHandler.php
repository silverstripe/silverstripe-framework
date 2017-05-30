<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\Cookie;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Control\Session;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\Member;
use SilverStripe\Security\RememberLoginHash;
use SilverStripe\Security\Security;

/**
 * Class LogoutHandler handles logging out Members from their session and/or cookie.
 * The logout process destroys all traces of the member on the server (not the actual computer user
 * at the other end of the line, don't worry)
 *
 */
class LogoutHandler extends RequestHandler
{
    /**
     * @var array
     */
    private static $url_handlers = [
        '' => 'logout'
    ];

    /**
     * @var array
     */
    private static $allowed_actions = [
        'logout'
    ];


    /**
     * Log out form handler method
     *
     * This method is called when the user clicks on "logout" on the form
     * created when the parameter <i>$checkCurrentUser</i> of the
     * {@link __construct constructor} was set to TRUE and the user was
     * currently logged in.
     *
     * @return bool|Member
     */
    public function logout()
    {
        $member = Security::getCurrentUser();

        return $this->doLogOut($member);
    }

    /**
     *
     * @param Member $member
     * @return bool|Member Return a member if something goes wrong
     */
    public function doLogOut($member)
    {
        if ($member instanceof Member) {
            Injector::inst()->get(IdentityStore::class)->logOut($this->getRequest());
        }

        return true;
    }
}
