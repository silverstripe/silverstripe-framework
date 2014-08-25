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
	 * Contains encryption algorithm identifiers.
	 * If set, will migrate to new precision-safe password hashing
	 * upon login. See http://open.silverstripe.org/ticket/3004
	 *
	 * @var array
	 */
	private static $migrate_legacy_hashes = array(
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
		// Check for email
		if(empty($RAW_data['Email'])) return false;

		$userEmail = $RAW_data['Email'];
		if(is_array($userEmail)) {
			user_error("Bad email passed to MemberAuthenticator::authenticate()", E_USER_WARNING);
			return false;
		}

		$result = null;

		// Default login (see Security::setDefaultAdmin())
		if(Security::check_default_admin($userEmail, $RAW_data['Password'])) {
			$member = Security::findAnAdministrator();
		} else {
			$member = Member::get()
				->filter(Member::config()->unique_identifier_field, $userEmail)
				->where('"Member"."Password" IS NOT NULL')
				->first();

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
		if(Security::config()->login_recording) {
			$attempt = new LoginAttempt();
			if($member) {
				// successful login (member is existing with matching password)
				$attempt->MemberID = $member->ID;
				$attempt->Status = 'Success';

				// Audit logging hook
				$member->extend('authenticated');
			} else {
				// failed login - we're trying to see if a user exists with this email (disregarding wrong passwords)
				$existingMember = DataObject::get_one("Member", array(
					'"'.Member::config()->unique_identifier_field.'"' => $userEmail
				));
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

			$attempt->Email = $userEmail;
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

		if(!$member && $form && $result) {
			$form->sessionMessage($result->message(), 'bad');
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

