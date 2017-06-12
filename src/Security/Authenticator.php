<?php

namespace SilverStripe\Security;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\MemberAuthenticator\LoginHandler;
use SilverStripe\Security\MemberAuthenticator\LogoutHandler;

/**
 * Abstract base class for an authentication method
 *
 * This class is used as a base class for the different authentication
 * methods like {@link MemberAuthenticator} or {@link OpenIDAuthenticator}.
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */
interface Authenticator
{
    /**
     * Can log a user in
     */
    const LOGIN = 1;

    /**
     * Can log user out
     */
    const LOGOUT = 2;

    /**
     * Can change password (check + reset)
     */
    const CHANGE_PASSWORD = 4;

    /**
     * Can modify password
     */
    const RESET_PASSWORD = 8;

    /**
     * In-CMS authentication
     */
    const CMS_LOGIN = 16;

    /**
     * Can check password is valid without logging the user in or modifying the password
     */
    const CHECK_PASSWORD = 32;

    /**
     * Returns the services supported by this authenticator
     *
     * The number should be a bitwise-OR of 1 or more of the following constants:
     * Authenticator::LOGIN, Authenticator::LOGOUT, Authenticator::CHANGE_PASSWORD,
     * Authenticator::RESET_PASSWORD, or Authenticator::CMS_LOGIN
     *
     * @return int
     */
    public function supportedServices();

    /**
     * Return RequestHandler to manage the log-in process.
     *
     * The default URL of the RequestHandler should return the initial log-in form, any other
     * URL may be added for other steps & processing.
     *
     * URL-handling methods may return an array [ "Form" => (form-object) ] which can then
     * be merged into a default controller.
     *
     * @param string $link The base link to use for this RequestHandler
     * @return LoginHandler
     */
    public function getLoginHandler($link);

    /**
     * Return the RequestHandler to manage the log-out process.
     *
     * The default URL of the RequestHandler should log the user out immediately and destroy the session.
     *
     * @param string $link The base link to use for this RequestHandler
     * @return LogoutHandler
     */
    public function getLogOutHandler($link);

    /**
     * Return RequestHandler to manage the change-password process.
     *
     * The default URL of the RequetHandler should return the initial change-password form,
     * any other URL may be added for other steps & processing.
     *
     * URL-handling methods may return an array [ "Form" => (form-object) ] which can then
     * be merged into a default controller.
     *
     * @param string $link The base link to use for this RequestHnadler
     */
    public function getChangePasswordHandler($link);


    /**
     * @param string $link
     * @return mixed
     */
    public function getLostPasswordHandler($link);

    /**
     * Method to authenticate an user.
     *
     * @param array $data Raw data to authenticate the user.
     * @param HTTPRequest $request
     * @param ValidationResult $result A validationresult which is either valid or contains the error message(s)
     * @return Member The matched member, or null if the authentication fails
     */
    public function authenticate(array $data, HTTPRequest $request, ValidationResult &$result = null);

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
    public function checkPassword(Member $member, $password, ValidationResult &$result = null);
}
