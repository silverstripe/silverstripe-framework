<?php

namespace SilverStripe\Security\MemberAuthenticator;

use InvalidArgumentException;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extensible;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Authenticator;
use SilverStripe\Security\DefaultAdminService;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\Security\Member;
use SilverStripe\Security\PasswordEncryptor;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;

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
            | Authenticator::RESET_PASSWORD | Authenticator::CHECK_PASSWORD;
    }

    public function authenticate(array $data, HTTPRequest $request, ValidationResult &$result = null)
    {
        // Find authenticated member
        if (class_exists(Versioned::class)) {
            [$member, $result] = Versioned::withVersionedMode(function () use ($data) {
                Versioned::set_stage(Versioned::DRAFT);
                $member = $this->authenticateMember($data, $result);
                return [$member, $result];
            });
        } else {
            $member = $this->authenticateMember($data, $result);
        }

        // Optionally record every login attempt as a {@link LoginAttempt} object
        $this->recordLoginAttempt($data, $request, $member, $result->isValid());

        if ($member && $request->hasSession()) {
            $request->getSession()->clear('BackURL');
        }

        return $result->isValid() ? $member : null;
    }

    /**
     * Attempt to find and authenticate member if possible from the given data
     *
     * @param array $data Form submitted data
     * @param ValidationResult $result
     * @param Member $member This third parameter is used in the CMSAuthenticator(s)
     * @return Member Found member, regardless of successful login
     */
    protected function authenticateMember($data, ValidationResult &$result = null, Member $member = null)
    {
        $email = !empty($data['Email']) ? $data['Email'] : null;
        $result = $result ?: ValidationResult::create();

        // Check default login
        $asDefaultAdmin = DefaultAdminService::isDefaultAdmin($email);
        if ($asDefaultAdmin) {
            // If logging is as default admin, ensure record is setup correctly
            $member = DefaultAdminService::singleton()->findOrCreateDefaultAdmin();
            $member->validateCanLogin($result);
            if ($result->isValid()) {
                // Check if default admin credentials are correct
                if (DefaultAdminService::isDefaultAdminCredentials($email, $data['Password'])) {
                    return $member;
                } else {
                    $result->addError(_t(
                        'SilverStripe\\Security\\Member.ERRORWRONGCRED',
                        "The provided details don't seem to be correct. Please try again."
                    ));
                }
            }
        }

        // Attempt to identify user by email
        if (!$member && $email) {
            // Find user by email
            $identifierField = Member::config()->get('unique_identifier_field');
            $member = Member::get()
                ->filter([$identifierField => $email])
                ->first();
        }

        // Validate against member if possible
        if ($member && !$asDefaultAdmin) {
            $this->checkPassword($member, $data['Password'], $result);
        } elseif (!$asDefaultAdmin) {
            // spoof a login attempt
            $tempMember = Member::create();
            $tempMember->{Member::config()->get('unique_identifier_field')} = $email;
            $tempMember->validateCanLogin($result);
        }

        // Emit failure to member and form (if available)
        if (!$result->isValid()) {
            if ($member) {
                $member->registerFailedLogin();
            }
        } elseif ($member) {
            $member->registerSuccessfulLogin();
        } else {
            // A non-existing member occurred. This will make the result "valid" so let's invalidate
            $result->addError(_t(
                'SilverStripe\\Security\\Member.ERRORWRONGCRED',
                "The provided details don't seem to be correct. Please try again."
            ));
            return null;
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
     * @param string $password
     * @param ValidationResult $result
     * @return ValidationResult
     */
    public function checkPassword(Member $member, $password, ValidationResult &$result = null)
    {
        // Check if allowed to login
        $result = $member->validateCanLogin($result);
        if (!$result->isValid()) {
            return $result;
        }

        // Allow default admin to login as self
        if (DefaultAdminService::isDefaultAdminCredentials($member->Email, $password)) {
            return $result;
        }

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
     *
     * @param array $data
     * @param HTTPRequest $request
     * @param Member $member
     * @param boolean $success
     * @return LoginAttempt|null
     */
    protected function recordLoginAttempt($data, HTTPRequest $request, $member, $success)
    {
        if (!Security::config()->get('login_recording')
            && !Member::config()->get('lock_out_after_incorrect_logins')
        ) {
            return null;
        }

        // Check email is valid
        $email = isset($data['Email']) ? $data['Email'] : null;
        if (is_array($email)) {
            throw new InvalidArgumentException("Bad email passed to MemberAuthenticator::authenticate(): $email");
        }

        $attempt = LoginAttempt::create();
        if ($success && $member) {
            // successful login (member is existing with matching password)
            $attempt->MemberID = $member->ID;
            $attempt->Status = LoginAttempt::SUCCESS;

            // Audit logging hook
            $member->extend('authenticationSucceeded');
        } else {
            // Failed login - we're trying to see if a user exists with this email (disregarding wrong passwords)
            $attempt->Status = LoginAttempt::FAILURE;
            if ($member) {
                // Audit logging hook
                $attempt->MemberID = $member->ID;
                $member->extend('authenticationFailed', $data, $request);
            } else {
                // Audit logging hook
                Member::singleton()
                   ->extend('authenticationFailedUnknownUser', $data, $request);
            }
        }

        $attempt->Email = $email;
        $attempt->IP = $request->getIP();

        $this->invokeWithExtensions('updateLoginAttempt', $attempt, $data, $request);

        $attempt->write();

        return $attempt;
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
