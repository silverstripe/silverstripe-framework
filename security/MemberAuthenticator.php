<?php
/**
 * Authenticator for the default "member" method
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 * @package framework
 * @subpackage security
 */
class MemberAuthenticator extends Authenticator {

	/**
	 * @var Array Contains encryption algorithm identifiers.
	 *  If set, will migrate to new precision-safe password hashing
	 *  upon login. See http://open.silverstripe.org/ticket/3004.
	 */
	static $migrate_legacy_hashes = array(
		'md5' => 'md5_v2.4', 
		'sha1' => 'sha1_v2.4'
	);

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
	if(array_key_exists('Email', $RAW_data) && $RAW_data['Email']){
		$SQL_user = Convert::raw2sql($RAW_data['Email']);
	} else {
		return false;
	}
    
	$isLockedOut = false;
	$result = null;

	// Default login (see Security::setDefaultAdmin())
	if(Security::check_default_admin($RAW_data['Email'], $RAW_data['Password'])) {
		$member = Security::findAnAdministrator();
	} else {
		$member = DataObject::get_one(
			"Member", 
			"\"" . Member::get_unique_identifier_field() . "\" = '$SQL_user' AND \"Password\" IS NOT NULL"
		);

		if($member) {
			$result = $member->checkPassword($RAW_data['Password']);
		} else {
			$result = new ValidationResult(false, _t('Member.ERRORWRONGCRED'));
		}

		if($member && !$result->valid()) { 
			$member->registerFailedLogin();
			$member = false;
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
			$existingMember = DataObject::get_one("Member", "\"" . Member::get_unique_identifier_field() . "\" = '$SQL_user'");
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
	
	// Legacy migration to precision-safe password hashes.
	// A login-event with cleartext passwords is the only time
	// when we can rehash passwords to a different hashing algorithm,
	// bulk-migration doesn't work due to the nature of hashing.
	// See PasswordEncryptor_LegacyPHPHash class.
	if(
		$member // only migrate after successful login
		&& self::$migrate_legacy_hashes
		&& array_key_exists($member->PasswordEncryption, self::$migrate_legacy_hashes)
	) {
		$member->Password = $RAW_data['Password'];
		$member->PasswordEncryption = self::$migrate_legacy_hashes[$member->PasswordEncryption];
		$member->write();
	}

		if($member) {
			Session::clear('BackURL');
		} else {
			if($form && $result) $form->sessionMessage($result->message(), 'bad');
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

