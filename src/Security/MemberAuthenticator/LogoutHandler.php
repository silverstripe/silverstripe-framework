<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\LogoutForm;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\SecurityToken;

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
        'logout',
        'LogoutForm'
    ];


    /**
     * Log out form handler method
     *
     * This method is called when the user clicks on "logout" on the form
     * created when the parameter <i>$checkCurrentUser</i> of the
     * {@link __construct constructor} was set to TRUE and the user was
     * currently logged in.
     *
     * @return array|HTTPResponse
     */
    public function logout()
    {
        $member = Security::getCurrentUser();

        // If the user doesn't have a security token, show them a form where they can get one.
        // This protects against nuisance CSRF attacks to log out users.
        if ($member && !SecurityToken::inst()->checkRequest($this->getRequest())) {
            Security::singleton()->setSessionMessage(
                _t(
                    'SilverStripe\\Security\\Security.CONFIRMLOGOUT',
                    "Please click the button below to confirm that you wish to log out."
                ),
                ValidationResult::TYPE_WARNING
            );

            return [
                'Form' => $this->logoutForm()
            ];
        }

        return $this->doLogOut($member);
    }

    /**
     * @return LogoutForm
     */
    public function logoutForm()
    {
        return LogoutForm::create($this);
    }

    /**
     * @param Member $member
     * @return HTTPResponse
     */
    public function doLogOut($member)
    {
        $this->extend('beforeLogout');

        if ($member instanceof Member) {
            Injector::inst()->get(IdentityStore::class)->logOut($this->getRequest());
        }

        if (Security::getCurrentUser()) {
            $this->extend('failedLogout');
        } else {
            $this->extend('afterLogout');
        }

        return $this->redirectAfterLogout();
    }

    /**
     * @return HTTPResponse
     */
    protected function redirectAfterLogout()
    {
        $backURL = $this->getBackURL();
        if ($backURL) {
            return $this->redirect($backURL);
        }

        $link = Security::config()->get('login_url');
        $referer = $this->getReturnReferer();
        if ($referer) {
            $link = Controller::join_links($link, '?' . http_build_query([
                'BackURL' => Director::makeRelative($referer)
            ]));
        }

        return $this->redirect($link);
    }
}
