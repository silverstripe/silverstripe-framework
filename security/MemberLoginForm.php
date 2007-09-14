<?php
/**
 * Log-in form for the "member" authentication method
 */
class MemberLoginForm extends Form {

	/**
	 * Constructor
	 *
	 * @param $controller
	 * @param $name
	 * @param $fields
	 * @param $actions
	 * @param $checkCurrentUser
	 */
	function __construct($controller, $name, $fields = null, $actions = null, $checkCurrentUser = true) {

		$customCSS = project() . '/css/member_login.css';
		if(Director::fileExists($customCSS)) {
			Requirements::css($customCSS);
		}

		if(isset($_REQUEST['BackURL'])) {
			$backURL = $_REQUEST['BackURL'];
		} else {
			$backURL = Session::get('BackURL');
			//Session::clear("BackURL"); don't clear the back URL here! Should be used until the right password is entered!
		}

		if($checkCurrentUser && Member::currentUserID()) {
			$fields = new FieldSet();
			$actions = new FieldSet(new FormAction("logout", "Log in as someone else"));
		} else {
			if(!$fields) {
				$fields = new FieldSet(
					new HiddenField("AuthenticationMethod", null, "Member"),
					new TextField("Email", "Email address", Session::get('SessionForms.MemberLoginForm.Email')),
					new EncryptField("Password", "Password"),
					new CheckboxField("Remember", "Remember me next time?",true)
				);
			}
			if(!$actions) {
				$actions = new FieldSet(
					new FormAction("dologin", "Log in"),
					new FormAction("forgotPassword", "I've lost my password")
				);
			}
		}

		if(isset($backURL)) {
			$fields->push(new HiddenField('BackURL', 'BackURL', $backURL));
		}

		parent::__construct($controller, $name, $fields, $actions);
	}


	/**
	 * Get message from session
	 */
	protected function getMessageFromSession() {
		parent::getMessageFromSession();
		if(($member = Member::currentUser()) && !Session::get('MemberLoginForm.force_message')) {
			$this->message = "You're logged in as $member->FirstName.";
		}
		Session::set('MemberLoginForm.force_message', false);
	}


	/**
	 * Login form handler method
	 *
	 * This method is called when the user clicks on "Log in"
	 *
	 * @param array $data Submitted data
	 */
	public function dologin($data) {
		if($this->performLogin($data)){

			if($backURL = $_REQUEST['BackURL']) {
				Session::clear("BackURL");
				Session::clear('SessionForms.MemberLoginForm.Email');
				Director::redirect($backURL);
			} else
				Director::redirectBack();

		} else {
			Session::set('SessionForms.MemberLoginForm.Email', $data['Email']);
			if($badLoginURL = Session::get("BadLoginURL")){
				Director::redirect($badLoginURL);
			} else {
				Director::redirectBack();
			}
		}
	}


	/**
	 * Log out
	 *
	 * @todo Figure out for what this method is used! Is it really used at all?
	 */
	public function logout(){
		$s = new Security();
		return $s->logout();
	}


  /**
   * Try to authenticate the user
   *
   * @param array Submitted data
   * @return Member Returns the member object on successful authentication
   *                or NULL on failure.
   */
	public function performLogin($data){
		if($member = MemberAuthenticator::authenticate($data)) {
			$firstname = Convert::raw2xml($member->FirstName);
			$this->sessionMessage("Welcome Back, {$firstname}", "good");
			$member->LogIn();
			
			if(isset($data['Remember'])) {
				// Deliberately obscure...
				Cookie::set('alc_enc',base64_encode("$data[Email]:$data[Password]"));
			}
			return $member;

		} else {
			$this->sessionMessage("That doesn't seem to be the right email address or password.  Please try again.", "bad");
			return null;
		}
	}


	/**
	 * Forgot password form handler method
	 *
	 * This method is called when the user clicks on "Log in"
	 *
	 * @param array $data Submitted data
	 */
	function forgotPassword($data) {
		$SQL_data = Convert::raw2sql($data);
		if($data['Email'] && $member = DataObject::get_one("Member", "Member.Email = '$SQL_data[Email]'")) {
			if(!$member->Password) {
				$member->createNewPassword();
				$member->write();
			}

			$member->sendInfo('forgotPassword');
			Director::redirect('Security/passwordsent/' . urlencode($data['Email']));

		} else if($data['Email']) {
			$this->sessionMessage("Sorry, but I don't recognise the email address.  Maybe you need to sign up, or perhaps you used another email address?", "bad");
			Director::redirectBack();

		} else {
			Director::redirect("Security/lostpassword");

		}
	}
}


?>