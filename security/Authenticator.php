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
}

?>