<?php

/**
 * Authenticator base class
 */



/**
 * Abstract base class for an authentication method
 *
 * This class is used as a base class for the different authentication
 * methods like {@link MemberAuthenticator} or {@link OpenIDAuthenticator}.
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 *
 * @todo Wouldn't be an interface be the better choice?
 */
abstract class Authenticator extends Object
{
  /**
   * This variable holds all authenticators that should be used
   *
   * @var array
   */
  private static $authenticators = array();


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
  public abstract function authenticate(array $RAW_data, Form $form = null);


  /**
   * Method that creates the login form for this authentication method
   *
   * @param Controller The parent controller, necessary to create the
   *                   appropriate form action tag
   * @return Form Returns the login form to use with this authentication
   *              method
   */
  public abstract static function getLoginForm(Controller $controller);


  /**
   * Get the name of the authentication method
   *
   * @return string Returns the name of the authentication method.
   */
  public abstract static function getName();


  /**
   * Register a new authenticator
   *
   * The new authenticator has to exist and to be derived from the
   * {@link Authenticator}.
   * Every authenticator can be registered only once.
   *
   * @return bool Returns TRUE on success, FALSE otherwise.
   */
  public static function registerAuthenticator($authenticator) {
    $authenticator = trim($authenticator);

    if(class_exists($authenticator) == false)
      return false;

    if(is_subclass_of($authenticator, 'Authenticator') == false)
      return false;

    if(in_array($authenticator, self::$authenticators) == false)
      array_push(self::$authenticators, $authenticator);

    return true;
  }


  /**
   * Get all registered authenticators
   *
   * @return array Returns an array with the class names of all registered
   *               authenticators.
   */
  public static function getAuthenticators() {
    return self::$authenticators;
  }
}

?>