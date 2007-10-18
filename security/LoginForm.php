<?php
/**
 * Standard log-in form.
 */
class LoginForm extends Form {
	function __construct($controller, $name, $fields = null, $actions = null, $checkCurrentUser = true) {
		if(isset($_REQUEST['BackURL'])) {
			$backURL = $_REQUEST['BackURL'];
		} else {
			$backURL = Session::get('BackURL');
			Session::clear("BackURL");
		}
		
		if($checkCurrentUser && Member::currentUserID()) {
			$fields = new FieldSet();
			$actions = new FieldSet(new FormAction("logout", "Log in as someone else"));
		} else {
			if(!$fields) {
				$fields = new FieldSet(
					new TextField("Email", "Email address", Session::get('SessionForms.LoginForm.Email')),
					new EncryptField("Password", "Password"),
					new CheckboxField("Remember", "Remember me next time?", true)
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

	protected function getMessageFromSession() {
		parent::getMessageFromSession();
		if(($member = Member::currentUser()) && !Session::get('LoginForm.force_message')) {
			$this->message = "You're logged in as $member->FirstName.";
		}
		Session::set('LoginForm.force_message', false);
	}
	
	public function dologin($data) {
		if($this->performLogin($data)) {
			if(isset($_REQUEST['BackURL']) && $backURL = $_REQUEST['BackURL']) {
				Session::clear("BackURL");
				Director::redirect($backURL);
			} else {
				Director::redirectBack();
			}
		} else {
			if(isset($_REQUEST['BackURL']) && $backURL = $_REQUEST['BackURL']) {
				Session::set('BackURL', $backURL);
			}
			
			if($badLoginURL = Session::get("BadLoginURL")) {
				Director::redirect($badLoginURL);
			} else {
				Director::redirectBack();
			}
		}
	}
	
	public function logout(){
		$s = new Security();
		return $s->logout();
	}

	/* check the membership

	 * if one of them or both don't match, set the fields which are unmatched with red star *
	 */
	public function performLogin($data){
		if($member = Security::authenticate($data['Email'], $data['Password'])) {
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
