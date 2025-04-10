<?php


namespace SilverStripe\Security\MemberAuthenticator;

use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Security\Authenticator;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\Security\Member;
use SilverStripe\Security\RandomGenerator;
use SilverStripe\Security\Security;

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

    private static bool $customise_array_return_value = false;

    /**
     * Keep track of whether a temporary hash is already generated during this request cycle.
     * @internal
     */
    private static bool $tempHashAlreadyGenerated = false;

    /**
     * Keep track of whether a temporary hash has already been processed during this request cycle.
     * @internal
     */
    private static bool $tempHashAlreadyProcessed = false;

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
        return $this->createChangePasswordResponse();
    }

    /**
     * Process the get URL variables for a change password request.
     * If additional action is required, return a response for that action.
     * Otherwise, return null which indicates the change password form should be presented.
     */
    protected function processChangePasswordUrlVars(): array|HTTPResponse|null
    {
        $request = $this->getRequest();

        // Check if we're resetting via a token in URL i.e. reset password email link
        $member = null;
        if ($request->getVar('m') !== null) {
            $member = Member::get()->filter(['ID' => (int)$request->getVar('m')])->first();
        }

        // If we have a token and a member, a user is trying to reset their password.
        // We'll set a temp token and redirect back here for the next round of processing.
        $token = $request->getVar('t');
        if ($token !== null && $member !== null) {
            if (!$member->validateAutoLoginToken($token)) {
                return $this->getInvalidTokenResponse();
            }
            // Redirect to current url, though with a temporary hash in the URL.
            // This will ensure that that the member ID and token will not appear in browser history.
            // Instead only a harmless temporary hash will appear in the browser history.
            // We do this instead of setting the session value at this point because if
            // cookie SameSite is set to Strict, then when clicking a password reset link via a
            // webmail client the redirect will be treated as cross-origin request and value
            // of the session cookie will not be accessible, so we have to set the session variable
            // AFTER the redirect.
            $autoLoginTempHash = $this->createAutoLoginTempHash($member);
            $member->AutoLoginTempHash = $autoLoginTempHash;
            $member->write();
            $response = $this->redirect(Controller::join_links($this->link, '?th=' . $autoLoginTempHash));
            return $response;
        }

        // If a member or password reset token was included on its own, the URL was invalid.
        if ($token !== null || $member !== null) {
            return $this->getInvalidTokenResponse();
        }

        // If this request was a redirect which includes the temp token, set the session token.
        $tempToken = $request->getVar('th');
        if ($tempToken !== null && !ChangePasswordHandler::$tempHashAlreadyProcessed) {
            $member = Member::get()->find('AutoLoginTempHash', $tempToken);
            if (!$member) {
                return $this->getInvalidTokenResponse();
            }
            // Delete the temp token from the member so that it cannot be used again
            // This prevents the browser history from being used to access the change password form
            $member->AutoLoginTempHash = '';
            $member->write();
            // Add the token to the session so that the change password form can be submitted
            // with the token in the session, instead of the URL
            $encryptedToken = $member->AutoLoginHash;
            $this->setSessionToken($member, $encryptedToken, true);
            ChangePasswordHandler::$tempHashAlreadyProcessed = true;
        }

        // If we get here, either the token is valid and we've just set it to the session,
        // or a logged in user is changing their password with this form.
        return null;
    }

    /**
     * Get a response which either includes the change password form or a permission failure
     * for when a user is trying to reset or change their password.
     */
    protected function createChangePasswordResponse(): array|HTTPResponse
    {
        // If there is AutoLoginHash in the session, then create a form.
        // If the token is valid then Member will be automatically logined in
        // as part of doChangePassword() which is the form action handler of the change password form.
        $session = $this->getRequest()->getSession();
        $hash = $session->get('AutoLoginHash');
        if ($hash) {
            if (!Member::get()->filter(['AutoLoginHash' => $hash])) {
                return $this->getInvalidTokenResponse();
            }
            $message = DBField::create_field(
                'HTMLFragment',
                '<p>' . _t(
                    Security::class . '.ENTERNEWPASSWORD',
                    'Please enter a new password.'
                ) . '</p>'
            );

            return [
                'Content' => $message,
                'Form' => $this->changePasswordForm()
            ];
        }

        // Logged in user requested a password change form.
        if (Security::getCurrentUser()) {
            $message = DBField::create_field(
                'HTMLFragment',
                '<p>' . _t(
                    Security::class . '.CHANGEPASSWORDBELOW',
                    'You can change your password below.'
                ) . '</p>'
            );

            return [
                'Content' => $message,
                'Form' => $this->changePasswordForm()
            ];
        }

        // Someone attempted to go to changepassword without token or being logged in
        return Security::permissionFailure(
            Controller::curr(),
            _t(
                Security::class . '.ERRORPASSWORDPERMISSION',
                'You must be logged in in order to change your password!'
            )
        );
    }

    /**
     * Set the encrypted session token for the member.
     */
    protected function setSessionToken(Member $member, string $token, bool $alreadyEncrypted = false): void
    {
        // if there is a current member, they should be logged out
        if (Security::getCurrentUser()) {
            Injector::inst()->get(IdentityStore::class)->logOut();
        }
        $this->getRequest()->getSession()->regenerateSessionId();

        if ($alreadyEncrypted) {
            $autoLoginHash = $token;
        } else {
            $autoLoginHash = $member->encryptWithUserSettings($token);
        }

        // Store the hash for the change password form. Will be unset after reload within the ChangePasswordForm.
        $this->getRequest()->getSession()->set('AutoLoginHash', $autoLoginHash);
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
            if (!$autoLoginHash) {
                // The user is not logged in and had no reset token, so give them a login form.
                return $this->redirect($this->addBackURLParam(Security::singleton()->Link('login')));
            }

            $member = Member::member_from_autologinhash($autoLoginHash);
            if (!$member) {
                // Hash was invalid or expired
                $session->clear('AutoLoginHash');
                return $this->getInvalidTokenResponse();
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
    private function createAutoLoginTempHash(Member $member): string
    {
        // If a second authenticator wants to set the temp hash, we should just use the existing hash.
        if (ChangePasswordHandler::$tempHashAlreadyGenerated) {
            return $member->AutoLoginTempHash;
        }
        // Generate a new random token. We do this instead of re-hashing the regular token
        // because there is a slim edge-case that the member doesn't have a salt or hashing algorithm set
        // (e.g. the member was created in code without a password) which would result in reusing the
        // token in plain text.
        $foundMember = null;
        $autoLoginTempHash = '';
        do {
            $autoLoginTempHash = Injector::inst()->get(RandomGenerator::class)->randomToken('sha256');
            $foundMember = Member::get()->find('AutoLoginTempHash', $autoLoginTempHash);
        } while ($foundMember !== null);
        ChangePasswordHandler::$tempHashAlreadyGenerated = true;
        return $autoLoginTempHash;
    }

    /**
     * Prepare a friendly message for if the login token is invalid or expired.
     */
    private function getInvalidTokenResponse(): array
    {
        return [
            'Content' => DBField::create_field(
                'HTMLFragment',
                _t(
                    Security::class . '.NOTERESETLINKINVALID',
                    '<p>The password reset link is invalid or expired.</p>'
                    . '<p>You can request a new one <a href="{link1}">here</a> or change your password after'
                    . ' you <a href="{link2}">log in</a>.</p>',
                    [
                        'link1' => Security::lost_password_url(),
                        'link2' => Security::login_url(),
                    ]
                )
            ),
        ];
    }
}
