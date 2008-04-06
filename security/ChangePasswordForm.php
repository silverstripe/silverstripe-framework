<?php

/**
 * @package sapphire
 * @subpackage security
 */

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
			if(Member::currentUser() && (!isset($_REQUEST['h']) || !Member::autoLoginHash($_REQUEST['h']))) {
				$fields->push(new EncryptField("OldPassword",_t('Member.YOUROLDPASSWORD', "Your old password")));
			}

			$fields->push(new EncryptField("NewPassword1", _t('Member.NEWPASSWORD', "New Password")));
			$fields->push(new EncryptField("NewPassword2", _t('Member.CONFIRMNEWPASSWORD', "Confirm New Password")));
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
			if($member->checkPassword($data['OldPassword']) == false) {
				$this->clearMessage();
				$this->sessionMessage(
					_t('Member.ERRORPASSWORDNOTMATCH', "Your current password does not match, please try again"), 
					"bad"
				);
				Director::redirectBack();
			}
		}

		if(!$member) {
			if(Session::get('AutoLoginHash')) {
				$member = Member::autoLoginHash(Session::get('AutoLoginHash'));
			}

			// The user is not logged in and no valid auto login hash is available
			if(!$member) {
				Session::clear('AutoLoginHash');
				Director::redirect('loginpage');
			}
		}

		// Check the new password
		if($data['NewPassword1'] == $data['NewPassword2']) {
			$member->Password = $data['NewPassword1'];
			$member->AutoLoginHash = null;
			$member->write();

			$member->sendinfo('changePassword',
												array('CleartextPassword' => $data['NewPassword1']));

			$this->clearMessage();
			$this->sessionMessage(
				_t('Member.PASSWORDCHANGED', "Your password has been changed, and a copy emailed to you."),
				"good");
			Session::clear('AutoLoginHash');
			Director::redirect(Security::Link('login'));

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