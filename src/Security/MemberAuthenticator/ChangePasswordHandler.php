<?php


namespace SilverStripe\Security\MemberAuthenticator;

use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Authenticator;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Security\RandomGenerator;

class ChangePasswordHandler extends RequestHandler
{
    /**
     * @var Authenticator
     */
    protected $authenticator;

    /**
     * Link to this handler
     *
     * @var string
     */
    protected $link = null;

    /**
     * @var array Allowed Actions
     */
    private static $allowed_actions = [
        'changepassword',
        'changePasswordForm',
    ];

    /**
     * @var array URL Handlers. All should point to changepassword
     */
    private static $url_handlers = [
        '' => 'changepassword',
    ];

    /**
     * @param string $link The URL to recreate this request handler
     * @param MemberAuthenticator $authenticator
     */
    public function __construct($link, MemberAuthenticator $authenticator)
    {
        $this->link = $link;
        $this->authenticator = $authenticator;
        parent::__construct();
    }


    /**
     * Handle the change password request
     *
     * @return array|HTTPResponse
     */
    public function changepassword()
    {
        $ret = $this->processChangePasswordUrlVars();
        if ($ret) {
            return $ret;
        }
        return $this->createChangePasswordHtml();
    }

    protected function processChangePasswordUrlVars(): mixed
    {
        $request = $this->getRequest();

        // Check is we're resetting via a token in URL i.e. reset password email link
        $member = null;
        if ($request->getVar('m') !== null) {
            $member = Member::get()->filter(['ID' => (int)$request->getVar('m')])->first();
        }
        $token = $request->getVar('t');
        if ($token !== null && $member) {
            if ($member->validateAutoLoginToken($token)) {
                // Redirect to current url, though with a temporary hash in the URL
                // This will ensure that that the member ID and token will not appear in browser history
                // Instead only a harmless temporary hash will appear in the browser history
                // We do this instead of setting the session value at this point because if
                // cookie SameSite is set to Strict, then  when clicking a password reset link via a
                // webmail client, then the redirect will be treated as cross-origin request and value
                // of the session cookie will not be accessible
                $autoLoginTempHash = $this->createAutoLoginTempHash();
                $member->AutoLoginTempHash = $autoLoginTempHash;
                $member->write();
                $response = $this->redirect($this->link . '?th=' . $autoLoginTempHash);
                return $response;
            } else {
                // Show a friendly message saying the login token has expired
                $message = DBField::create_field(
                    'HTMLFragment',
                    _t(
                        'SilverStripe\\Security\\Security.NOTERESETLINKINVALID',
                        '<p>The password reset link is invalid or expired.</p>'
                        . '<p>You can request a new one <a href="{link1}">here</a> or change your password after'
                        . ' you <a href="{link2}">log in</a>.</p>',
                        [
                            'link1' => Security::lost_password_url(),
                            'link2' => Security::login_url(),
                        ]
                    )
                );
                return [
                    'Content' => $message,
                ];
            }
        }

        // Check the if we're processing a temp token redirect
        $tempToken = $request->getVar('th');
        if ($tempToken !== null) {
            $member = Member::get()->find('AutoLoginTempHash', $tempToken);
            if ($member) {
                // Delete the temp token from the member so that it cannot be used again
                // This prevents the browser history from being used to access the change password form
                $member->AutoLoginTempHash = '';
                $member->write();
                // Add the token to the session so that the change password form can be submitted
                // with the token in the session, instead of the URL
                $encryptedToken = $member->AutoLoginHash;
                $this->setSessionTokenShared($member, $encryptedToken, false);
            }
        }
        return null;
    }

    protected function createChangePasswordHtml()
    {
        // If there is AutoLoginHash in the session, then create a form
        // If the token is valid then Member will be automatically logined in
        // as part of doChangePassword() which is the form action handler of the change password form
        $session = $this->getRequest()->getSession();
        if ($session->get('AutoLoginHash')) {
            $message = DBField::create_field(
                'HTMLFragment',
                '<p>' . _t(
                    'SilverStripe\\Security\\Security.ENTERNEWPASSWORD',
                    'Please enter a new password.'
                ) . '</p>'
            );

            // Subsequent request after the "first load with hash" (see previous if clause).
            return [
                'Content' => $message,
                'Form'    => $this->changePasswordForm()
            ];
        }

        if (Security::getCurrentUser()) {
            // Logged in user requested a password change form.
            $message = DBField::create_field(
                'HTMLFragment',
                '<p>' . _t(
                    'SilverStripe\\Security\\Security.CHANGEPASSWORDBELOW',
                    'You can change your password below.'
                ) . '</p>'
            );

            return [
                'Content' => $message,
                'Form'    => $this->changePasswordForm()
            ];
        }

        // Someone attempted to go to changepassword without token or being logged in
        return Security::permissionFailure(
            Controller::curr(),
            _t(
                'SilverStripe\\Security\\Security.ERRORPASSWORDPERMISSION',
                'You must be logged in in order to change your password!'
            )
        );
    }

    /**
     * Set the session token for the member with a token that has not been encrypted
     *
     * @param Member $member
     * @param string $token
     * @deprecated 5.4.0 Will be removed without equivalent functionality to replace it
     */
    protected function setSessionToken($member, $token)
    {
        Deprecation::noticeWithNoReplacment('5.4.0');
        $this->setSessionTokenShared($member, $token, true);
    }

    /**
     * Return a link to this request handler.
     * The link returned is supplied in the constructor
     *
     * @param string|null $action
     * @return string
     */
    public function Link($action = null)
    {
        $link = Controller::join_links($this->link, $action);
        $this->extend('updateLink', $link, $action);
        return $link;
    }

    /**
     * Factory method for the lost password form
     *
     * @return ChangePasswordForm Returns the lost password form
     */
    public function changePasswordForm()
    {
        return ChangePasswordForm::create(
            $this,
            'ChangePasswordForm'
        );
    }

    /**
     * Change the password
     *
     * @param array $data The user submitted data
     * @param ChangePasswordForm $form
     * @return HTTPResponse
     * @throws ValidationException
     * @throws NotFoundExceptionInterface
     */
    public function doChangePassword(array $data, $form)
    {
        $member = Security::getCurrentUser();
        // The user was logged in, check the current password
        $oldPassword = isset($data['OldPassword']) ? $data['OldPassword'] : null;
        if ($member && !$this->checkPassword($member, $oldPassword)) {
            $form->sessionMessage(
                _t(
                    'SilverStripe\\Security\\Member.ERRORPASSWORDNOTMATCH',
                    'Your current password does not match, please try again'
                ),
                'bad'
            );

            // redirect back to the form, instead of using redirectBack() which could send the user elsewhere.
            return $this->redirectBackToForm();
        }

        $session = $this->getRequest()->getSession();
        if (!$member) {
            $autoLoginHash = $session->get('AutoLoginHash');
            if ($autoLoginHash) {
                $member = Member::member_from_autologinhash($autoLoginHash);
            }

            // The user is not logged in and no valid auto login hash is available
            if (!$member) {
                $session->clear('AutoLoginHash');

                return $this->redirect($this->addBackURLParam(Security::singleton()->Link('login')));
            }
        }

        // Check the new password
        if (empty($data['NewPassword1'])) {
            $form->sessionMessage(
                _t(
                    'SilverStripe\\Security\\Member.EMPTYNEWPASSWORD',
                    "The new password can't be empty, please try again"
                ),
                'bad'
            );

            // redirect back to the form, instead of using redirectBack() which could send the user elsewhere.
            return $this->redirectBackToForm();
        }

        // Fail if passwords do not match
        if ($data['NewPassword1'] !== $data['NewPassword2']) {
            $form->sessionMessage(
                _t(
                    'SilverStripe\\Security\\Member.ERRORNEWPASSWORD',
                    'You have entered your new password differently, try again'
                ),
                'bad'
            );

            // redirect back to the form, instead of using redirectBack() which could send the user elsewhere.
            return $this->redirectBackToForm();
        }

        // Check if the new password is accepted
        $validationResult = $member->changePassword($data['NewPassword1']);
        if (!$validationResult->isValid()) {
            $form->setSessionValidationResult($validationResult);

            return $this->redirectBackToForm();
        }

        // Clear locked out status
        $member->LockedOutUntil = null;
        $member->FailedLoginCount = null;

        // Create a successful 'LoginAttempt' as the password is reset
        if (Security::config()->get('login_recording')) {
            $loginAttempt = LoginAttempt::create();
            $loginAttempt->Status = LoginAttempt::SUCCESS;
            $loginAttempt->MemberID = $member->ID;

            if ($member->Email) {
                $loginAttempt->setEmail($member->Email);
            }

            $loginAttempt->IP = $this->getRequest()->getIP();
            $loginAttempt->write();
        }

        // Clear the members login hashes
        $member->AutoLoginHash = null;
        $member->AutoLoginExpired = DBDatetime::create()->now();
        $member->write();

        if ($member->canLogin()) {
            $identityStore = Injector::inst()->get(IdentityStore::class);
            $identityStore->logIn($member, false, $this->getRequest());
        }

        $session->clear('AutoLoginHash');

        // Redirect to backurl
        $backURL = $this->getBackURL();
        if ($backURL
            // Don't redirect back to itself
            && $backURL !== Security::singleton()->Link('changepassword')
        ) {
            return $this->redirect($backURL);
        }

        $backURL = Security::config()->get('default_reset_password_dest');
        if ($backURL) {
            return $this->redirect($backURL);
        }
        // Redirect to default location - the login form saying "You are logged in as..."
        $url = Security::singleton()->Link('login');

        return $this->redirect($url);
    }

    /**
     * Something went wrong, go back to the changepassword
     *
     * @return HTTPResponse
     */
    public function redirectBackToForm()
    {
        // Redirect back to form
        $url = $this->addBackURLParam(Security::singleton()->Link('changepassword'));

        return $this->redirect($url);
    }

    /**
     * Check if password is ok
     *
     * @param Member $member
     * @param string $password
     * @return bool
     */
    protected function checkPassword($member, $password)
    {
        if (empty($password)) {
            return false;
        }
        // With a valid user and password, check the password is correct
        $authenticators = Security::singleton()->getApplicableAuthenticators(Authenticator::CHECK_PASSWORD);
        foreach ($authenticators as $authenticator) {
            if (!$authenticator->checkPassword($member, $password)->isValid()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Generate a temporary auto login hash for the member
     */
    private function createAutoLoginTempHash(): string
    {
        $member = null;
        $autoLoginTempHash = '';
        do {
            $autoLoginTempHash = Injector::inst()->get(RandomGenerator::class)->randomToken('sha256');
            $member = Member::get()->find('AutoLoginTempHash', $autoLoginTempHash);
        } while ($member !== null);
        return $autoLoginTempHash;
    }

    /**
     * Shared function to set the session token
     */
    private function setSessionTokenShared(Member $member, string $token, bool $encrypt): void
    {
        // if there is a current member, they should be logged out
        if (Security::getCurrentUser()) {
            Injector::inst()->get(IdentityStore::class)->logOut();
        }
        $this->getRequest()->getSession()->regenerateSessionId();
        // Store the hash for the change password form. Will be unset after reload within the ChangePasswordForm.
        if ($encrypt) {
            $autoLoginHash = $member->encryptWithUserSettings($token);
        } else {
            $autoLoginHash = $token;
        }
        $this->getRequest()->getSession()->set('AutoLoginHash', $autoLoginHash);
    }
}
