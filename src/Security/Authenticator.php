<?php

namespace SilverStripe\Security;

use SilverStripe\Core\Object;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Forms\Form;

/**
 * Abstract base class for an authentication method
 *
 * This class is used as a base class for the different authentication
 * methods like {@link MemberAuthenticator} or {@link OpenIDAuthenticator}.
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */
abstract class Authenticator extends Object
{

    /**
     * This variable holds all authenticators that should be used
     *
     * @var array
     */
    private static $authenticators = [];

    /**
     * Used to influence the order of authenticators on the login-screen
     * (default shows first).
     *
     * @var string
     */
    private static $default_authenticator = MemberAuthenticator::class;


    /**
     * Method to authenticate an user
     *
     * @param array $RAW_data Raw data to authenticate the user
     * @param Form $form Optional: If passed, better error messages can be
     *                             produced by using
     *                             {@link Form::sessionMessage()}
     * @return bool|Member Returns FALSE if authentication fails, otherwise
     *                     the member object
     */
    public static function authenticate($RAW_data, Form $form = null)
    {
    }

    /**
     * Method that creates the login form for this authentication method
     *
     * @param Controller $controller The parent controller, necessary to create the
     *                   appropriate form action tag
     * @return Form Returns the login form to use with this authentication
     *              method
     */
    public static function get_login_form(Controller $controller)
    {
    }

    /**
     * Method that creates the re-authentication form for the in-CMS view
     *
     * @param Controller $controller
     */
    public static function get_cms_login_form(Controller $controller)
    {
    }

    /**
     * Determine if this authenticator supports in-cms reauthentication
     *
     * @return bool
     */
    public static function supports_cms()
    {
        return false;
    }
    
    /**
     * Check if a given authenticator is registered
     *
     * @param string $authenticator Name of the authenticator class to check
     * @return bool Returns TRUE if the authenticator is registered, FALSE
     *              otherwise.
     */
    public static function is_registered($authenticator)
    {
        $authenticators = self::config()->get('authenticators');
        if (count($authenticators) === 0) {
            $authenticators = [self::config()->get('default_authenticator')];
        }

        return in_array($authenticator, $authenticators, true);
    }


    /**
     * Get all registered authenticators
     *
     * @return array Returns an array with the class names of all registered
     *               authenticators.
     */
    public static function get_authenticators()
    {
        $authenticators = self::config()->get('authenticators');
        $default = self::config()->get('default_authenticator');

        if (count($authenticators) === 0) {
            $authenticators = [$default];
        }
        // put default authenticator first (mainly for tab-order on loginform)
        // But only if there's no other authenticator
        if (($key = array_search($default, $authenticators, true)) && count($authenticators) > 1) {
            unset($authenticators[$key]);
            array_unshift($authenticators, $default);
        }

        return $authenticators;
    }

    /**
     * @return string
     */
    public static function get_default_authenticator()
    {
        return self::config()->get('default_authenticator');
    }
}
