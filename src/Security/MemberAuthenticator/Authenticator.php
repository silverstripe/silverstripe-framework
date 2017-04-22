<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Session;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\ValidationResult;
use InvalidArgumentException;
use SilverStripe\Security\Authenticator as BaseAuthenticator;
use SilverStripe\Security\Security;
use SilverStripe\Security\Member;

/**
 * Authenticator for the default "member" method
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */
class Authenticator implements BaseAuthenticator
{

    public function supportedServices()
    {
        // Bitwise-OR of all the supported services, to make a bitmask
        return BaseAuthenticator::LOGIN | BaseAuthenticator::LOGOUT | BaseAuthenticator::CHANGE_PASSWORD
            | BaseAuthenticator::RESET_PASSWORD | BaseAuthenticator::CMS_LOGIN;
    }

    /**
     * @inherit
     */
    public function authenticate($data, &$message)
    {
        $success = null;

        // Find authenticated member
        $member = $this->authenticateMember($data, $message, $success);

        // Optionally record every login attempt as a {@link LoginAttempt} object
        $this->recordLoginAttempt($data, $member, $success);

        if ($member) {
            Session::clear('BackURL');
        }

        return $success ? $member : null;
    }

    /**
     * Attempt to find and authenticate member if possible from the given data
     *
     * @param array $data
     * @param Form $form
     * @param bool &$success Success flag
     * @return Member Found member, regardless of successful login
     */
    protected function authenticateMember($data, &$message, &$success)
    {
        // Default success to false
        $success = false;

        // Attempt to identify by temporary ID
        $member = null;
        $email = null;
        if (!empty($data['tempid'])) {
            // Find user by tempid, in case they are re-validating an existing session
            $member = Member::member_from_tempid($data['tempid']);
            if ($member) {
                $email = $member->Email;
            }
        }

        // Otherwise, get email from posted value instead
        /** @skipUpgrade */
        if (!$member && !empty($data['Email'])) {
            $email = $data['Email'];
        }

        // Check default login (see Security::setDefaultAdmin())
        $asDefaultAdmin = $email === Security::default_admin_username();
        if ($asDefaultAdmin) {
            // If logging is as default admin, ensure record is setup correctly
            $member = Member::default_admin();
            $success = !$member->isLockedOut() && Security::check_default_admin($email, $data['Password']);
            //protect against failed login
            if ($success) {
                return $member;
            }
        }

        // Attempt to identify user by email
        if (!$member && $email) {
            // Find user by email
            $member = Member::get()
                ->filter(Member::config()->unique_identifier_field, $email)
                ->first();
        }

        // Validate against member if possible
        if ($member && !$asDefaultAdmin) {
            $result = $member->checkPassword($data['Password']);
            $success = $result->isValid();
        } else {
            $result = ValidationResult::create()->addError(_t(
                'SilverStripe\\Security\\Member.ERRORWRONGCRED',
                'The provided details don\'t seem to be correct. Please try again.'
            ));
        }

        // Emit failure to member and form (if available)
        if (!$success) {
            if ($member) {
                $member->registerFailedLogin();
            }
            $message = implode("; ", array_map(
                function ($message) {
                    return $message['message'];
                },
                $result->getMessages()
            ));
        } else {
            if ($member) {
                $member->registerSuccessfulLogin();
            }
        }

        return $member;
    }

    /**
     * Log login attempt
     * TODO We could handle this with an extension
     *
     * @param array $data
     * @param Member $member
     */
    protected function recordLoginAttempt($data, $member)
    {
        if (!Security::config()->login_recording) {
            return;
        }

        // Check email is valid
        /** @skipUpgrade */
        $email = isset($data['Email']) ? $data['Email'] : null;
        if (is_array($email)) {
            throw new InvalidArgumentException("Bad email passed to MemberAuthenticator::authenticate(): $email");
        }

        $attempt = new LoginAttempt();
        if ($success) {
            // successful login (member is existing with matching password)
            $attempt->MemberID = $member->ID;
            $attempt->Status = 'Success';

            // Audit logging hook
            $member->extend('authenticated');
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
     * @inherit
     */
    public function getLostPasswordHandler($link)
    {
        return LostPasswordHandler::create($link, $this);
    }

    /**
     * @inherit
     */
    public function getChangePasswordHandler($link)
    {
        return ChangePasswordHandler::create($link, $this);
    }

    /**
     * @inherit
     */
    public function getLoginHandler($link)
    {
        return LoginHandler::create($link, $this);
    }

    public function getCMSLoginHandler($link)
    {
        return CMSMemberLoginHandler::create($controller, self::class, "LoginForm");
    }
}
