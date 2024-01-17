<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Authenticator;
use SilverStripe\Security\PasswordExpirationMiddleware;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * Handle login requests from MemberLoginForm
 */
class LoginHandler extends RequestHandler
{
    /**
     * @var Authenticator
     */
    protected $authenticator;

    /**
     * @var array
     */
    private static $url_handlers = [
        '' => 'login',
    ];

    /**
     * @var array
     * @config
     */
    private static $allowed_actions = [
        'login',
        'LoginForm',
        'logout',
    ];

    /**
     * Link to this handler
     *
     * @var string
     */
    protected $link = null;

    /**
     * @param string $link The URL to recreate this request handler
     * @param MemberAuthenticator $authenticator The authenticator to use
     */
    public function __construct($link, MemberAuthenticator $authenticator)
    {
        $this->link = $link;
        $this->authenticator = $authenticator;
        parent::__construct();
    }

    /**
     * Return a link to this request handler.
     * The link returned is supplied in the constructor
     *
     * @param null|string $action
     * @return string
     */
    public function Link($action = null)
    {
        $link = Controller::join_links($this->link, $action);
        $this->extend('updateLink', $link, $action);
        return $link;
    }

    /**
     * URL handler for the log-in screen
     *
     * @return array
     */
    public function login()
    {
        return [
            'Form' => $this->loginForm(),
        ];
    }

    /**
     * Return the MemberLoginForm form
     *
     * @return MemberLoginForm
     */
    public function loginForm()
    {
        return MemberLoginForm::create(
            $this,
            get_class($this->authenticator),
            'LoginForm'
        );
    }

    /**
     * Login form handler method
     *
     * This method is called when the user finishes the login flow
     *
     * @param array $data Submitted data
     * @param MemberLoginForm $form
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function doLogin($data, MemberLoginForm $form, HTTPRequest $request)
    {
        $failureMessage = null;

        $this->extend('beforeLogin');
        // Successful login
        if ($member = $this->checkLogin($data, $request, $result)) {
            $this->performLogin($member, $data, $request);
            // Allow operations on the member after successful login
            $this->extend('afterLogin', $member);

            return $this->redirectAfterSuccessfulLogin();
        }

        $this->extend('failedLogin');

        $message = implode("; ", array_map(
            function ($message) {
                return $message['message'];
            },
            $result->getMessages() ?? []
        ));

        $form->sessionMessage($message, 'bad');

        // Failed login

        if (array_key_exists('Email', $data ?? [])) {
            $rememberMe = (isset($data['Remember']) && Security::config()->get('autologin_enabled') === true);
            $this
                ->getRequest()
                ->getSession()
                ->set('SessionForms.MemberLoginForm.Email', $data['Email'])
                ->set('SessionForms.MemberLoginForm.Remember', $rememberMe);
        }

        // Fail to login redirects back to form
        return $form->getRequestHandler()->redirectBackToForm();
    }

    public function getReturnReferer()
    {
        return $this->Link();
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
     * @return HTTPResponse
     */
    protected function redirectAfterSuccessfulLogin()
    {
        $this
            ->getRequest()
            ->getSession()
            ->clear('SessionForms.MemberLoginForm.Email')
            ->clear('SessionForms.MemberLoginForm.Remember');

        $member = Security::getCurrentUser();
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
            // Welcome message
            $message = _t(
                'SilverStripe\\Security\\Member.WELCOMEBACK',
                'Welcome back, {firstname}',
                ['firstname' => $member->FirstName]
            );
            Security::singleton()->setSessionMessage($message, ValidationResult::TYPE_GOOD);
        }

        // Redirect back
        return $this->redirectBack();
    }

    /**
     * Try to authenticate the user
     *
     * @param array $data Submitted data
     * @param HTTPRequest $request
     * @param ValidationResult $result
     * @return Member Returns the member object on successful authentication
     *                or NULL on failure.
     */
    public function checkLogin($data, HTTPRequest $request, ValidationResult &$result = null)
    {
        $member = $this->authenticator->authenticate($data, $request, $result);
        if ($member instanceof Member) {
            return $member;
        }

        return null;
    }

    /**
     * Try to authenticate the user
     *
     * @param Member $member
     * @param array $data Submitted data
     * @param HTTPRequest $request
     * @return Member Returns the member object on successful authentication
     *                or NULL on failure.
     */
    public function performLogin($member, $data, HTTPRequest $request)
    {
        /** IdentityStore */
        $rememberMe = (isset($data['Remember']) && Security::config()->get('autologin_enabled'));
        $identityStore = Injector::inst()->get(IdentityStore::class);
        $identityStore->logIn($member, $rememberMe, $request);

        return $member;
    }

    /**
     * Invoked if password is expired and must be changed
     *
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
        $changePasswordUrl = $this->addBackURLParam($changedPasswordLink);

        return $this->redirect($changePasswordUrl);
    }
}
