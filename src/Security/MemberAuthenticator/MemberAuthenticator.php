<?php

namespace SilverStripe\Security\MemberAuthenticator;

use InvalidArgumentException;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Session;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Authenticator;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\Service\DefaultAdminService;

/**
 * Authenticator for the default "member" method
 *
 * @author Sam Minnee <sam@silverstripe.com>
 * @author Simon Erkelens <simonerkelens@silverstripe.com>
 */
class MemberAuthenticator implements Authenticator
{
    use Extensible;

    public function supportedServices()
    {
        // Bitwise-OR of all the supported services in this Authenticator, to make a bitmask
        return Authenticator::LOGIN | Authenticator::LOGOUT | Authenticator::CHANGE_PASSWORD
            | Authenticator::RESET_PASSWORD;
    }

    /**
     * @param array $data
     * @param null|ValidationResult $result
     * @return null|Member
     */
    public function authenticate($data, &$result = null)
    {
        // Find authenticated member
        $member = $this->authenticateMember($data, $result);

        // Optionally record every login attempt as a {@link LoginAttempt} object
        $this->recordLoginAttempt($data, $member, $result->isValid());

        if ($member) {
            Session::clear('BackURL');
        }

        return $result->isValid() ? $member : null;
    }

    /**
     * Attempt to find and authenticate member if possible from the given data
     *
     * @param array $data Form submitted data
     * @param ValidationResult $result
     * @param Member|null This third parameter is used in the CMSAuthenticator(s)
     * @return Member|null Found member, regardless of successful login
     */
    protected function authenticateMember($data, &$result = null, $member = null)
    {
        $email = !empty($data['Email']) ? $data['Email'] : null;
        // Default success to false
        $result = new ValidationResult();

        // Check default login (see Security::setDefaultAdmin())
        $asDefaultAdmin = $email === DefaultAdminService::getDefaultAdminUsername();
        if ($asDefaultAdmin) {
            // If logging is as default admin, ensure record is setup correctly
            /** @var Member $member */
            $service = Injector::inst()->get(DefaultAdminService::class);
            $member = $service->findOrCreateDefaultAdmin();
            $validAdmin = $service->validateDefaultAdmin($email, $data['Password']);
            $result = $member->canLogIn();
            //protect against failed login
            if ($validAdmin->isValid() && $result->isValid()) {
                return $member;
            } else {
                $result->addError(_t(
                    'SilverStripe\\Security\\Member.ERRORWRONGCRED',
                    "The provided details don't seem to be correct. Please try again."
                ));
            }
        }

        // Attempt to identify user by email
        if (!$member && $email) {
            // Find user by email
            $identifierField = Member::config()->get('unique_identifier_field');
            /** @var Member $member */
            $member = Member::get()
                ->filter([$identifierField => $email])
                ->first();
        }

        // Validate against member if possible
        if ($member && !$asDefaultAdmin) {
            $result = $member->checkPassword($data['Password']);
        }

        // Emit failure to member and form (if available)
        if (!$result->isValid()) {
            if ($member) {
                $member->registerFailedLogin();
            }
        } else {
            if ($member) {
                $member->registerSuccessfulLogin();
            } else {
                // A non-existing member occurred. This will make the result "valid" so let's invalidate
                $result->addError(_t(
                    'SilverStripe\\Security\\Member.ERRORWRONGCRED',
                    "The provided details don't seem to be correct. Please try again."
                ));
                $member = null;
            }
        }

        return $member;
    }

    /**
     * Check if the passed password matches the stored one (if the member is not locked out).
     *
     * Note, we don't return early, to prevent differences in timings to give away if a member
     * password is invalid.
     *
     * @param Member $member
     * @param  string $password
     * @return ValidationResult
     */
    public function checkPassword($member, $password)
    {
        $result = $member->canLogIn();

        // Check a password is set on this member
        if (empty($member->Password) && $member->exists()) {
            $result->addError(_t(__CLASS__ . '.NoPassword', 'There is no password on this member.'));
        }

        $encryptor = PasswordEncryptor::create_for_algorithm($member->PasswordEncryption);
        if (!$encryptor->check($member->Password, $password, $member->Salt, $member)) {
            $result->addError(_t(
                __CLASS__ . '.ERRORWRONGCRED',
                'The provided details don\'t seem to be correct. Please try again.'
            ));
        }

        return $result;
    }


    /**
     * Log login attempt
     * TODO We could handle this with an extension
     *
     * @param array $data
     * @param Member $member
     * @param boolean $success
     */
    protected function recordLoginAttempt($data, $member, $success)
    {
        if (!Security::config()->get('login_recording')) {
            return;
        }

        // Check email is valid
        /** @skipUpgrade */
        $email = isset($data['Email']) ? $data['Email'] : null;
        if (is_array($email)) {
            throw new InvalidArgumentException("Bad email passed to MemberAuthenticator::authenticate(): $email");
        }

        $attempt = LoginAttempt::create();
        if ($success && $member) {
            // successful login (member is existing with matching password)
            $attempt->MemberID = $member->ID;
            $attempt->Status = 'Success';

            // Audit logging hook
            $member->extend('authenticationSucceeded');
        } else {
            // Failed login - we're trying to see if a user exists with this email (disregarding wrong passwords)
            $attempt->Status = 'Failure';
            if ($member) {
                // Audit logging hook
                $attempt->MemberID = $member->ID;
                $member->extend('authenticationFailed');
            } else {
                // Audit logging hook
                Member::singleton()->extend('authenticationFailedUnknownUser', $data);
            }
        }

        $attempt->Email = $email;
        $attempt->IP = Controller::curr()->getRequest()->getIP();
        $attempt->write();
    }

    /**
     * @param string $link
     * @return LostPasswordHandler
     */
    public function getLostPasswordHandler($link)
    {
        return LostPasswordHandler::create($link, $this);
    }

    /**
     * @param string $link
     * @return ChangePasswordHandler
     */
    public function getChangePasswordHandler($link)
    {
        return ChangePasswordHandler::create($link, $this);
    }

    /**
     * @param string $link
     * @return LoginHandler
     */
    public function getLoginHandler($link)
    {
        return LoginHandler::create($link, $this);
    }

    /**
     * @param string $link
     * @return LogoutHandler
     */
    public function getLogoutHandler($link)
    {
        return LogoutHandler::create($link, $this);
    }
}
