<?php

/**
 * LoginForm base class
 */



/**
 * Abstract base class for a login form
 *
 * This class is used as a base class for the different log-in forms like
 * {@link MemberLoginForm} or {@link OpenIDLoginForm}.
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */
abstract class LoginForm extends Form
{
  /**
   * Get the authenticator class
   *
   * @return Authenticator Returns the authenticator class for this login
   *                       form.
   */
  public abstract static function getAuthenticator();
}

?>