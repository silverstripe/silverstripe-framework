<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Control\RequestHandler;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\MemberAuthenticator\Authenticator;
use SilverStripe\Security\Security;
use SilverStripe\Security\Member;

/**
 * Handle login requests from MemberLoginForm
 */
class LoginHandler extends RequestHandler
{
    protected $authenticator;

    private static $url_handlers = [
        '' => 'login',
    ];

    /**
     * Since the logout and dologin actions may be conditionally removed, it's necessary to ensure these
     * remain valid actions regardless of the member login state.
     *
     * @var array
     * @config
     */
    private static $allowed_actions = [
        'login',
        'LoginForm',
        'dologin',
        'logout',
    ];

    private $link = null;

    /**
     * @param string $link The URL to recreate this request handler
     * @param Authenticator $authenticator The
     */
    public function __construct($link, Authenticator $authenticator)
    {
        $this->link = $link;
        $this->authenticator = $authenticator;
        parent::__construct($link, $this);
    }

    /**
     * Return a link to this request handler.
     * The link returned is supplied in the constructor
     * @return string
     */
    public function link($action = null)
    {
        if ($action) {
            return Controller::join_links($this->link, $action);
        } else {
            return $this->link;
        }
    }

    /**
     * URL handler for the log-in screen
     */
    public function login()
    {
        return [
            'Form' => $this->loginForm(),
        ];
    }

    /**
     * Return the MemberLoginForm form
     */
    public function loginForm()
    {
        return LoginForm::create(
            $this,
            get_class($this->authenticator),
            'LoginForm'
        );
    }

    /**
     * Login form handler method
     *
     * This method is called when the user clicks on "Log in"
     *
     * @param array $data Submitted data
     * @param LoginHandler $formHandler
     * @return HTTPResponse
     */
    public function doLogin($data, $formHandler)
    {
        if ($this->performLogin($data)) {
            return $this->logInUserAndRedirect($data, $formHandler);
        }

        /** @skipUpgrade */
        if (array_key_exists('Email', $data)) {
            Session::set('SessionForms.MemberLoginForm.Email', $data['Email']);
            Session::set('SessionForms.MemberLoginForm.Remember', isset($data['Remember']));
        }

        return $this->redirectBack();
        // Fail to login redirects back to form
        return $formHandler->redirectBackToForm();
    }


    public function getReturnReferer()
    {
        return $this->link();
    }

    /**
     * Login in the user and figure out where to redirect the browser.
     *
     * The $data has this format
     * array(
     *   'AuthenticationMethod' => 'MemberAuthenticator',
     *   'Email' => 'sam@silverstripe.com',
     *   'Password' => '1nitialPassword',
     *   'BackURL' => 'test/link',
     *   [Optional: 'Remember' => 1 ]
     * )
     *
     * @param array $data
     * @return HTTPResponse
     */
    protected function logInUserAndRedirect($data, $formHandler)
    {
        Session::clear('SessionForms.MemberLoginForm.Email');
        Session::clear('SessionForms.MemberLoginForm.Remember');

        $member = Member::currentUser();
        if ($member->isPasswordExpired()) {
            return $this->redirectToChangePassword();
        }

        // Absolute redirection URLs may cause spoofing
        $backURL = $this->getBackURL();
        if ($backURL) {
            return $this->redirect($backURL);
        }

        // If a default login dest has been set, redirect to that.
        $defaultLoginDest = Security::config()->get('default_login_dest');
        if ($defaultLoginDest) {
            return $this->redirect($defaultLoginDest);
        }

        // Redirect the user to the page where they came from
        if ($member) {
            if (!empty($data['Remember'])) {
                Session::set('SessionForms.MemberLoginForm.Remember', '1');
                $member->logIn(true);
            } else {
                $member->logIn();
            }

            // Welcome message
            $message = _t(
                'SilverStripe\\Security\\Member.WELCOMEBACK',
                "Welcome Back, {firstname}",
                ['firstname' => $member->FirstName]
            );
            Security::setLoginMessage($message, ValidationResult::TYPE_GOOD);
        }

        // Redirect back
        return $this->redirectBack();
    }

    /**
     * Log out form handler method
     *
     * This method is called when the user clicks on "logout" on the form
     * created when the parameter <i>$checkCurrentUser</i> of the
     * {@link __construct constructor} was set to TRUE and the user was
     * currently logged in.
     *
     * @return HTTPResponse
     */
    public function logout()
    {
        return Security::singleton()->logout();
    }

    /**
     * Try to authenticate the user
     *
     * @param array $data Submitted data
     * @return Member Returns the member object on successful authentication
     *                or NULL on failure.
     */
    public function performLogin($data)
    {
        $message = null;
        $member = $this->authenticator->authenticate($data, $message);
        if ($member) {
            $member->LogIn(isset($data['Remember']));
            return $member;
        } else {
            Security::setLoginMessage($message, ValidationResult::TYPE_ERROR);
        }

        // No member, can't login
        $this->extend('authenticationFailed', $data);
        return null;
    }

    /**
     * Invoked if password is expired and must be changed
     *
     * @skipUpgrade
     * @return HTTPResponse
     */
    protected function redirectToChangePassword()
    {
        $cp = ChangePasswordForm::create($this, 'ChangePasswordForm');
        $cp->sessionMessage(
            _t('SilverStripe\\Security\\Member.PASSWORDEXPIRED', 'Your password has expired. Please choose a new one.'),
            'good'
        );
        $changedPasswordLink = Security::singleton()->Link('changepassword');
        return $this->redirect($this->addBackURLParam($changedPasswordLink));
    }



    /**
     * @todo copypaste from FormRequestHandler - refactor
     */
    protected function addBackURLParam($link)
    {
        $backURL = $this->getBackURL();
        if ($backURL) {
            return Controller::join_links($link, '?BackURL=' . urlencode($backURL));
        }
        return $link;
    }
}
