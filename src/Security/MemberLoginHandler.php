<?php

namespace SilverStripe\Security;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Forms\FormRequestHandler;
use SilverStripe\ORM\ValidationResult;

/**
 * Handle login requests from MemberLoginForm
 */
class MemberLoginHandler extends FormRequestHandler
{
    protected $authenticator_class = MemberAuthenticator::class;

    /**
     * Since the logout and dologin actions may be conditionally removed, it's necessary to ensure these
     * remain valid actions regardless of the member login state.
     *
     * @var array
     * @config
     */
    private static $allowed_actions = [
        'dologin',
        'logout',
    ];

    /**
     * Login form handler method
     *
     * This method is called when the user clicks on "Log in"
     *
     * @param array $data Submitted data
     * @return HTTPResponse
     */
    public function dologin($data)
    {
        if ($this->performLogin($data)) {
            return $this->logInUserAndRedirect($data);
        }

        /** @skipUpgrade */
        if (array_key_exists('Email', $data)) {
            Session::set('SessionForms.MemberLoginForm.Email', $data['Email']);
            Session::set('SessionForms.MemberLoginForm.Remember', isset($data['Remember']));
        }

        // Fail to login redirects back to form
        return $this->redirectBackToForm();
    }

    /**
     * Redirect to password recovery form
     *
     * @return HTTPResponse
     */
    public function redirectToLostPassword()
    {
        $lostPasswordLink = Security::singleton()->Link('lostpassword');
        return $this->redirect($this->addBackURLParam($lostPasswordLink));
    }

    public function getReturnReferer()
    {
        // Home of login form is always this url
        return Security::singleton()->Link('login');
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
    protected function logInUserAndRedirect($data)
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
        $member = call_user_func_array(
            [$this->authenticator_class, 'authenticate'],
            [$data, $this->form]
        );
        if ($member) {
            $member->LogIn(isset($data['Remember']));
            return $member;
        }

        // No member, can't login
        $this->extend('authenticationFailed', $data);
        return null;
    }

    /**
     * Forgot password form handler method.
     * Called when the user clicks on "I've lost my password".
     * Extensions can use the 'forgotPassword' method to veto executing
     * the logic, by returning FALSE. In this case, the user will be redirected back
     * to the form without further action. It is recommended to set a message
     * in the form detailing why the action was denied.
     *
     * @skipUpgrade
     * @param array $data Submitted data
     * @return HTTPResponse
     */
    public function forgotPassword($data)
    {
        // Ensure password is given
        if (empty($data['Email'])) {
            $this->form->sessionMessage(
                _t('Member.ENTEREMAIL', 'Please enter an email address to get a password reset link.'),
                'bad'
            );
            return $this->redirectToLostPassword();
        }

        // Find existing member
        /** @var Member $member */
        $member = Member::get()->filter("Email", $data['Email'])->first();

        // Allow vetoing forgot password requests
        $results = $this->extend('forgotPassword', $member);
        if ($results && is_array($results) && in_array(false, $results, true)) {
            return $this->redirectToLostPassword();
        }

        if ($member) {
            $token = $member->generateAutologinTokenAndStoreHash();

            Email::create()
                ->setHTMLTemplate('SilverStripe\\Control\\Email\\ForgotPasswordEmail')
                ->setData($member)
                ->setSubject(_t('Member.SUBJECTPASSWORDRESET', "Your password reset link", 'Email subject'))
                ->addData('PasswordResetLink', Security::getPasswordResetLink($member, $token))
                ->setTo($member->Email)
                ->send();
        }

        // Avoid information disclosure by displaying the same status,
        // regardless wether the email address actually exists
        $link = Controller::join_links(
            Security::singleton()->Link('passwordsent'),
            rawurlencode($data['Email']),
            '/'
        );
        return $this->redirect($this->addBackURLParam($link));
    }

    /**
     * Invoked if password is expired and must be changed
     *
     * @skipUpgrade
     * @return HTTPResponse
     */
    protected function redirectToChangePassword()
    {
        $cp = ChangePasswordForm::create($this->form->getController(), 'ChangePasswordForm');
        $cp->sessionMessage(
            _t('Member.PASSWORDEXPIRED', 'Your password has expired. Please choose a new one.'),
            'good'
        );
        $changedPasswordLink = Security::singleton()->Link('changepassword');
        return $this->redirect($this->addBackURLParam($changedPasswordLink));
    }
}
