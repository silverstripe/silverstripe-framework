<?php
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
	$isLockedOut = false;

	// Default login (see Security::setDefaultAdmin())
	if(Security::check_default_admin($RAW_data['Email'], $RAW_data['Password'])) {
		$member = Security::findAnAdministrator();
	} else {
		$member = DataObject::get_one("Member", "Email = '$SQL_user' AND Password IS NOT NULL");
		if($member && ($member->checkPassword($RAW_data['Password']) == false)) { 
			if($member->isLockedOut()) $isLockedOut = true;
			$member->registerFailedLogin();
			$member = null;
		}
	}
	
	// Optionally record every login attempt as a {@link LoginAttempt} object
	/**
	 * TODO We could handle this with an extension
	 */
	if(Security::login_recording()) {
		$attempt = new LoginAttempt();
		if($member) {
			// successful login (member is existing with matching password)
			$attempt->MemberID = $member->ID;
			$attempt->Status = 'Success';
			
			// Audit logging hook
			$member->extend('authenticated');
		} else {
			// failed login - we're trying to see if a user exists with this email (disregarding wrong passwords)
			$existingMember = DataObject::get_one("Member", "Email = '$SQL_user'");
			if($existingMember) {
				$attempt->MemberID = $existingMember->ID;
				
				// Audit logging hook
				$existingMember->extend('authenticationFailed');
			} else {
				
				// Audit logging hook
				singleton('Member')->extend('authenticationFailedUnknownUser', $RAW_data);
			}
			$attempt->Status = 'Failure';
		}
		if(is_array($RAW_data['Email'])) {
			user_error("Bad email passed to MemberAuthenticator::authenticate(): $RAW_data[Email]", E_USER_WARNING);
			return false;
		}
		
		$attempt->Email = $RAW_data['Email'];
		$attempt->IP = Controller::curr()->getRequest()->getIP();
		$attempt->write();
	}

    if($member) {
		Session::clear("BackURL");
    } else if($isLockedOut) {
		if($form) $form->sessionMessage(
			_t('Member.ERRORLOCKEDOUT', "Your account has been temporarily disabled because of too many failed attempts at logging in. Please try again in 20 minutes."),
			"bad"
		);
    } else {
		if($form) $form->sessionMessage(
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