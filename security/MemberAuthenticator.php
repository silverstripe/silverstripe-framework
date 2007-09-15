<?php

/**
 * Member authenticator
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */



/**
 * Authenticator for the default "member" method
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
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
   */
  public function authenticate(array $RAW_data, Form $form = null) {
    $SQL_user = Convert::raw2sql($RAW_data['Email']);

    $member = DataObject::get_one("Member",
			"Email = '$SQL_user' AND Password IS NOT NULL");

		if($member) {
			$encryption_details =
				Security::encrypt_password($RAW_data['Password'], $member->Salt,
																	 $member->PasswordEncryption);

			// Check if the entered password is valid
			if(($member->Password != $encryption_details['password']))
			  $member = null;
		}


    if($member) {
      Session::clear("BackURL");
    } else if(!is_null($form)) {
			$form->sessionMessage(
				"That doesn't seem to be the right e-mail address or password. Please try again.",
				"bad");
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
  public static function getLoginForm(Controller $controller) {
    return Object::create("MemberLoginForm", $controller, "LoginForm");
  }


  /**
   * Get the name of the authentication method
   *
   * @return string Returns the name of the authentication method.
   */
  public static function getName() {
		return "E-mail &amp; Password";
	}
}

?>