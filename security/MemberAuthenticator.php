<?php

/**
 * @package sapphire
 * @subpackage security
 * @author Markus Lanthaler <markus@silverstripe.com>
 */

/**
 * Authenticator for the default "member" method
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 * @package sapphire
 * @subpackage security
 */
class MemberAuthenticator extends Authenticator {

  /**
   * Method to authenticate an user
   *
   * @param array $RAW_data Raw data to authenticate the user
   * @param Form $form Optional: If passed, better error messages can be
   *                             produced by using
   *                             {@link Form::sessionMessage()}
   * @return bool|Member Returns FALSE if authentication fails, otherwise
   *                     the member object
   * @see Security::setDefaultAdmin()
   */
  public static function authenticate($RAW_data, Form $form = null) {
    $SQL_user = Convert::raw2sql($RAW_data['Email']);

	// Default login (see Security::setDefaultAdmin())
	if(Security::check_default_admin($RAW_data['Email'], $RAW_data['Password'])) {
		$member = Security::findAnAdministrator();
	} else {
		$member = DataObject::get_one("Member", "Email = '$SQL_user' AND Password IS NOT NULL");
		if($member && ($member->checkPassword($RAW_data['Password']) == false)) {
			$member = null;
		}
	}

    if($member) {
		Session::clear("BackURL");
    } else if(!is_null($form)) {
		$form->sessionMessage(
			_t('Member.ERRORWRONGCRED', "That doesn't seem to be the right e-mail address or password. Please try again."),
			"bad"
		);
    }

    return $member;
  }


  /**
   * Method that creates the login form for this authentication method
   *
   * @param Controller The parent controller, necessary to create the
   *                   appropriate form action tag
   * @return Form Returns the login form to use with this authentication
   *              method
   */
  public static function get_login_form(Controller $controller) {
    return Object::create("MemberLoginForm", $controller, "LoginForm");
  }


  /**
   * Get the name of the authentication method
   *
   * @return string Returns the name of the authentication method.
   */
  public static function get_name() {
		return _t('MemberAuthenticator.TITLE', "E-mail &amp; Password");
	}
}


?>