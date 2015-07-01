<?php
/**
 * Standard Change Password Form
 * @package framework
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
	 * @param FieldList|FormField $fields All of the fields in the form - a
	 *                                   {@link FieldList} of {@link FormField}
	 *                                   objects.
	 * @param FieldList|FormAction $actions All of the action buttons in the
	 *                                     form - a {@link FieldList} of
	 */
	public function __construct($controller, $name, $fields = null, $actions = null) {
		if(isset($_REQUEST['BackURL'])) {
			$backURL = $_REQUEST['BackURL'];
		} else {
			$backURL = Session::get('BackURL');
		}

		if(!$fields) {
			$fields = new FieldList();
			
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
			$actions = new FieldList(
				new FormAction("doChangePassword", _t('Member.BUTTONCHANGEPASSWORD', "Change Password"))
			);
		}

		if(isset($backURL)) {
			$fields->push(new HiddenField('BackURL', 'BackURL', $backURL));
		}

		parent::__construct($controller, $name, $fields, $actions);
	}


	/**
	 * Change the password
	 *
	 * @param array $data The user submitted data
	 * @return SS_HTTPResponse
	 */
	public function doChangePassword(array $data) {
		if($member = Member::currentUser()) {
			// The user was logged in, check the current password
			if(empty($data['OldPassword']) || !$member->checkPassword($data['OldPassword'])->valid()) {
				$this->clearMessage();
				$this->sessionMessage(
					_t('Member.ERRORPASSWORDNOTMATCH', "Your current password does not match, please try again"), 
					"bad"
				);
				// redirect back to the form, instead of using redirectBack() which could send the user elsewhere.
				return $this->controller->redirect($this->controller->Link('changepassword'));
			}
		}

		if(!$member) {
			if(Session::get('AutoLoginHash')) {
				$member = Member::member_from_autologinhash(Session::get('AutoLoginHash'));
			}

			// The user is not logged in and no valid auto login hash is available
			if(!$member) {
				Session::clear('AutoLoginHash');
				return $this->controller->redirect($this->controller->Link('login'));
			}
		}

		// Check the new password
		if(empty($data['NewPassword1'])) {
			$this->clearMessage();
			$this->sessionMessage(
				_t('Member.EMPTYNEWPASSWORD', "The new password can't be empty, please try again"),
				"bad");

			// redirect back to the form, instead of using redirectBack() which could send the user elsewhere.
			return $this->controller->redirect($this->controller->Link('changepassword'));
		}
		else if($data['NewPassword1'] == $data['NewPassword2']) {
			$isValid = $member->changePassword($data['NewPassword1']);
			if($isValid->valid()) {
				$member->logIn();
				
				// TODO Add confirmation message to login redirect
				Session::clear('AutoLoginHash');

				// Clear locked out status
				$member->LockedOutUntil = null;
				$member->FailedLoginCount = null;
				$member->write();
				
				if (!empty($_REQUEST['BackURL'])
					// absolute redirection URLs may cause spoofing 
					&& Director::is_site_url($_REQUEST['BackURL'])
				) {
					$url = Director::absoluteURL($_REQUEST['BackURL']);
					return $this->controller->redirect($url);
				}
				else {
					// Redirect to default location - the login form saying "You are logged in as..."
					$redirectURL = HTTP::setGetVar(
						'BackURL',
						Director::absoluteBaseURL(), $this->controller->Link('login')
					);
					return $this->controller->redirect($redirectURL);
				}
			} else {
				$this->clearMessage();
				$this->sessionMessage(
					_t(
						'Member.INVALIDNEWPASSWORD', 
						"We couldn't accept that password: {password}",
						array('password' => nl2br("\n".Convert::raw2xml($isValid->starredList())))
					), 
					"bad",
					false
				);

				// redirect back to the form, instead of using redirectBack() which could send the user elsewhere.
				return $this->controller->redirect($this->controller->Link('changepassword'));
			}

		} else {
			$this->clearMessage();
			$this->sessionMessage(
				_t('Member.ERRORNEWPASSWORD', "You have entered your new password differently, try again"),
				"bad");

			// redirect back to the form, instead of using redirectBack() which could send the user elsewhere.
			return $this->controller->redirect($this->controller->Link('changepassword'));
		}
	}

}

