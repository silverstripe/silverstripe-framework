<?php
/**
 * Standard Change Password Form
 * @package sapphire
 * @subpackage security
 */
class ChangePasswordForm extends Form {
	
	/**
	 * Constructor
	 *
	 * @param Controller $controller The parent controller, necessary to
	 *                               create the appropriate form action tag.
	 * @param string $name The method on the controller that will return this
	 *                     form object.
	 * @param FieldSet|FormField $fields All of the fields in the form - a
	 *                                   {@link FieldSet} of {@link FormField}
	 *                                   objects.
	 * @param FieldSet|FormAction $actions All of the action buttons in the
	 *                                     form - a {@link FieldSet} of
	 */
	function __construct($controller, $name, $fields = null, $actions = null) {
		if(!$fields) {
			$fields = new FieldSet();
			
			// Security/changepassword?h=XXX redirects to Security/changepassword
			// without GET parameter to avoid potential HTTP referer leakage.
			// In this case, a user is not logged in, and no 'old password' should be necessary.
			if(Member::currentUser()) {
				$fields->push(new PasswordField("OldPassword",_t('Member.YOUROLDPASSWORD', "Your old password")));
			}

			$fields->push(new PasswordField("NewPassword1", _t('Member.NEWPASSWORD', "New Password")));
			$fields->push(new PasswordField("NewPassword2", _t('Member.CONFIRMNEWPASSWORD', "Confirm New Password")));
		}
		if(!$actions) {
			$actions = new FieldSet(
				new FormAction("doChangePassword", _t('Member.BUTTONCHANGEPASSWORD', "Change Password"))
			);
		}

		parent::__construct($controller, $name, $fields, $actions);
	}


	/**
	 * Change the password
	 *
	 * @param array $data The user submitted data
	 */
	function doChangePassword(array $data) {
		if($member = Member::currentUser()) {
			// The user was logged in, check the current password
			if(isset($data['OldPassword']) && $member->checkPassword($data['OldPassword']) == false) {
				$this->clearMessage();
				$this->sessionMessage(
					_t('Member.ERRORPASSWORDNOTMATCH', "Your current password does not match, please try again"), 
					"bad"
				);
				Director::redirectBack();
				return;
			}
		}

		if(!$member) {
			if(Session::get('AutoLoginHash')) {
				$member = Member::member_from_autologinhash(Session::get('AutoLoginHash'));
			}

			// The user is not logged in and no valid auto login hash is available
			if(!$member) {
				Session::clear('AutoLoginHash');
				Director::redirect('loginpage');
				return;
			}
		}

		// Check the new password
		if($data['NewPassword1'] == $data['NewPassword2']) {
			$isValid = $member->changePassword($data['NewPassword1']);
			if($isValid->valid()) {
				$member->logIn();
				
				// TODO Add confirmation message to login redirect
				Session::clear('AutoLoginHash');
				$redirectURL = HTTP::setGetVar('BackURL', urlencode(Director::absoluteBaseURL()), Security::Link('login'));
				Director::redirect($redirectURL);

			} else {
				$this->clearMessage();
				$this->sessionMessage(nl2br("We couldn't accept that password:\n" . $isValid->starredList()), "bad");
				Director::redirectBack();
			}

		} else {
			$this->clearMessage();
			$this->sessionMessage(
				_t('Member.ERRORNEWPASSWORD', "Your have entered your new password differently, try again"),
				"bad");
			Director::redirectBack();
		}
	}

}

?>