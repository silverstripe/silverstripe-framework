<?php

namespace SilverStripe\Security;

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

    const LOGIN = 1;
    const LOGOUT = 2;
    const CHANGE_PASSWORD = 4;
    const RESET_PASSWORD = 8;
    const CMS_LOGIN = 16;

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
     * @param ValidationResult $result A validationresult which is either valid or contains the error message(s)
     * @return Member The matched member, or null if the authentication fails
     */
    public function authenticate($data, &$result = null);
}
