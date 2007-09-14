<?php


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
   * Method to authenticate an user
   *
   * @param array $RAW_data Raw data to authenticate the user
   * @return bool|Member Returns FALSE if authentication fails, otherwise
   *                     the member object
   */
  public abstract function authenticate(array $RAW_data);


  /**
   * Method that creates the login form for this authentication method
   *
   * @return Form Returns the login form to use with this authentication
   *              method
   */
  public abstract function getLoginForm();
}

?>