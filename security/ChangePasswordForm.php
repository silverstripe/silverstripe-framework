<?php

/**
 * Standard Change Password Form
 */
class ChangePasswordForm extends Form {

	function __construct($controller, $name, $fields = null, $actions = null) {
		if(!$fields) {
			$fields = new FieldSet();
			if(Member::currentUser()) {
				$fields->push(new EncryptField("OldPassword","Your old password"));
			}

			$fields->push(new EncryptField("NewPassword1", "New Password"));
			$fields->push(new EncryptField("NewPassword2", "Confirm New Password"));
		}
		if(!$actions) {
			$actions = new FieldSet(
				new FormAction("changePassword", "Change Password")
			);
		}

		parent::__construct($controller, $name, $fields, $actions);
	}

	/**
	 * Change the password
	 *
	 * @param array $data The user submitted data
	 */
	function changePassword(array $data) {
		if($member = Member::currentUser()) {
			// The user was logged in, check the current password
			if($member->checkPassword($data['OldPassword']) == false) {
				$this->clearMessage();
				$this->sessionMessage(
					"Your current password does not match, please try again", "bad");
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
				"Your password has been changed, and a copy emailed to you.",
				"good");
			Session::clear('AutoLoginHash');
			Director::redirect(Security::Link('login'));

		} else {
			$this->clearMessage();
			$this->sessionMessage(
				"Your have entered your new password differently, try again",
				"bad");
			Director::redirectBack();
		}
	}

}


?>