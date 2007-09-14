<?php


/**
 * OpenID log-in form
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */
class OpenIDLoginForm extends Form {

	/**
	 * Constructor
	 *
	 * @param $controller
	 * @param $name
	 * @param $fields
	 * @param $actions
	 * @param $checkCurrentUser
	 */
  function __construct($controller, $name, $fields = null, $actions = null,
                       $checkCurrentUser = true) {
		$customCSS = project() . '/css/openid_login.css';
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
          new HiddenField("AuthenticationMethod", null, "OpenID"),
          new TextField("OpenIDURL", "OpenID URL",
                        Session::get('SessionForms.OpenIDLoginForm.OpenIDURL')),
          new CheckboxField("Remember", "Remember me next time?", true)
        );
      }
      if(!$actions) {
        $actions = new FieldSet(
          new FormAction("dologin", "Log in")
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
    if(($member = Member::currentUser()) &&
         !Session::get('OpenIDLoginForm.force_message')) {
      $this->message = "You're logged in as $member->FirstName.";
    }
    Session::set('OpenIDLoginForm.force_message', false);
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
				Session::clear('SessionForms.OpenIDLoginForm.OpenIDURL');
				Director::redirect($backURL);
			} else
				Director::redirectBack();

		} else {
			Session::set('SessionForms.OpenIDLoginForm.OpenIDURL', $data['OpenIDURL']);
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
  public function logout() {
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
  public function performLogin(array $data) {
    if($member = OpenIDAuthenticator::authenticate($data)) {
      $firstname = Convert::raw2xml($member->FirstName);
      $this->sessionMessage("Welcome Back, {$firstname}", "good");
      $member->LogIn();

      if(isset($data['Remember'])) {
        // Deliberately obscure...
        Cookie::set('alc_enc',base64_encode("$data[OpenIDURL]"));
      }
      return $member;

    } else {
      $this->sessionMessage("Login failed. Please try again.", "bad");
      return null;
    }
  }
}


?>