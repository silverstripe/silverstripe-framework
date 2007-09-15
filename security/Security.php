<?php

/**
 * Implements a basic security model
 */
class Security extends Controller {

	/**
	 * @var $username String Only used in dev-mode by setDefaultAdmin()
	 */
	protected static $username;

	/**
	 * @var $password String Only used in dev-mode by setDefaultAdmin()
	 */
	protected static $password;

	protected static $strictPathChecking = false;

	/**
	 * Register that we've had a permission failure trying to view the given page
	 *
	 * This will redirect to a login page.
	 * If you don't provide a messageSet, a default will be used.
	 *
	 * @param $page The controller that you were on to cause the permission
	 *              failure.
	 * @param string|array $messageSet The message to show to the user. This
	 *                                  can be a string, or a map of different
	 *                                  messages for different contexts.
	 *                                  If you pass an array, you can use the
	 *                                  following keys:
	 *                                    - default: The default message
	 *                                    - logInAgain: The message to show
	 *                                                  if the user has just
	 *                                                  logged out and the
	 *                                    - alreadyLoggedIn: The message to
	 *                                                       show if the user
	 *                                                       is already logged
	 *                                                       in and lacks the
	 *                                                       permission to
	 *                                                       access the item.
	 */
	static function permissionFailure($page, $messageSet = null) {
		// Prepare the messageSet provided
		if(!$messageSet) {
			$messageSet = array(
				'default' => "That page is secured. Enter your email address and password and we will send you right along.",
				'alreadyLoggedIn' => "You don't have access to this page.  If you have another password that can access that page, you can log in below.",
				'logInAgain' => "You have been logged out.  If you would like to log in again, enter a username and password below.",
			);
		} else if(!is_array($messageSet)) {
			$messageSet = array('default' => $messageSet);
		}

		// Work out the right message to show
		if(Member::currentUserID()) {
			// user_error( 'PermFailure with member', E_USER_ERROR );

			$message = $messageSet['alreadyLoggedIn']
										? $messageSet['alreadyLoggedIn']
										: $messageSet['default'];

			if($member = Member::currentUser())
				$member->logout();

		} else if(substr(Director::history(),0,15) == 'Security/logout') {
			$message = $messageSet['logInAgain']
										? $messageSet['logInAgain']
										: $messageSet['default'];

		} else {
			$message = $messageSet['default'];
		}

		Session::set("Security.Message.message", $message);
		Session::set("Security.Message.type", 'warning');

		Session::set("BackURL", $_SERVER['REQUEST_URI']);
		
		if(Director::is_ajax()) {
			die('NOTLOGGEDIN:');
		} else {
			Director::redirect("Security/login");
		}
		return;
	}


  /**
	 * Get the login form to process according to the submitted data
	 */
	function LoginForm() {
		if(is_array($_REQUEST) && isset($_REQUEST['AuthenticationMethod']))
		{
			$authenticator = trim($_REQUEST['AuthenticationMethod']);

			$authenticators = Authenticator::getAuthenticators();
			if(in_array($authenticator, $authenticators)) {
				return call_user_func(array($authenticator, 'GetLoginForm'), $this);
			}
		}

		user_error('Passed invalid authentication method', E_USER_ERROR);
	}


  /**
	 * Get the login forms for all available authentication methods
	 *
	 * @return array Returns an array of available login forms (array of Form
	 *               objects).
	 *
	 * @todo Check how to activate/deactivate authentication methods
	 */
	function GetLoginForms()
	{
		$forms = array();

		$authenticators = Authenticator::getAuthenticators();
		foreach($authenticators as $authenticator) {
		  array_push($forms,
								 call_user_func(array($authenticator, 'GetLoginForm'),
																$this));
		}

		return $forms;
	}


	/**
	 * Get a link to a security action
	 *
	 * @param string $action Name of the action
	 * @return string Returns the link to the given action
	 */
	function Link($action = null) {
		return "Security/$action";
	}


	/**
	 * Log the currently logged in user out
	 *
	 * @param bool $redirect Redirect the user back to where they came.
	 *                         - If it's false, the code calling logout() is
	 *                           responsible for sending the user where-ever
	 *                           they should go.
	 */
	function logout($redirect = true) {
		if($member = Member::currentUser())
			$member->logOut();

		if($redirect)
			Director::redirectBack();
	}


	/**
	 * Show the "login" page
	 *
	 * @return string Returns the "login" page as HTML code.
	 */
	function login() {
		Requirements::javascript("jsparty/behaviour.js");
		Requirements::javascript("jsparty/loader.js");
		Requirements::javascript("jsparty/prototype.js");
		Requirements::javascript("jsparty/prototype_improvements.js");
		Requirements::javascript("jsparty/scriptaculous/effects.js");

		$customCSS = project() . '/css/tabs.css';
		if(Director::fileExists($customCSS)) {
			Requirements::css($customCSS);
		}

		$tmpPage = new Page();
		$tmpPage->Title = "Log in";
		$tmpPage->URLSegment = "Security";

		$controller = new Page_Controller($tmpPage);
		$controller->init();
		Controller::$currentController = $controller;

		if(SSViewer::hasTemplate("Security_login")) {
			return $controller->renderWith(array("Security_login", "Page"));

		} else {
			$forms = $this->GetLoginForms();
			$content = '';
			foreach($forms as $form)
				$content .= $form->forTemplate();

			if(strlen($message = Session::get('Security.Message.message')) > 0) {
				$message_type = Session::get('Security.Message.type');
				if($message_type == 'bad') {
					$message = "<p class=\"message $message_type\">$message</p>";
				} else {
					$message = "<p>$message</p>";
				}

				$customisedController = $controller->customise(array(
					"Content" => $message,
					"Form" => $content
				));
			} else {
				$customisedController = $controller->customise(array(
					"Content" => $content
				));
			}

			return $customisedController->renderWith("Page");
		}
	}


	/**
	 * Show the "lost password" page
	 *
	 * @return string Returns the "lost password " page as HTML code.
	 */
	function lostpassword() {
		Requirements::javascript("jsparty/prototype.js");
		Requirements::javascript("jsparty/behaviour.js");
		Requirements::javascript("jsparty/loader.js");
		Requirements::javascript("jsparty/prototype_improvements.js");
		Requirements::javascript("jsparty/scriptaculous/effects.js");

		$tmpPage = new Page();
		$tmpPage->Title = "Lost Password";
		$tmpPage->URLSegment = "Security";
		$controller = new Page_Controller($tmpPage);

		$customisedController = $controller->customise(array(
			"Content" =>
				"<p>Enter your e-mail address and we will send you a password</p>",
			"Form" => $this->LostPasswordForm(),
		));
		
		//Controller::$currentController = $controller;
		return $customisedController->renderWith("Page");
	}


	/**
	 * Show the "password sent" page
	 *
	 * @return string Returns the "password sent" page as HTML code.
	 */
	function passwordsent() {
		Requirements::javascript("jsparty/behaviour.js");
		Requirements::javascript("jsparty/loader.js");
		Requirements::javascript("jsparty/prototype.js");
		Requirements::javascript("jsparty/prototype_improvements.js");
		Requirements::javascript("jsparty/scriptaculous/effects.js");

		$tmpPage = new Page();
		$tmpPage->Title = "Lost Password";
		$tmpPage->URLSegment = "Security";
		$controller = new Page_Controller($tmpPage);

		$email = $this->urlParams['ID'];
		$customisedController = $controller->customise(array(
			"Title" => "Password sent to '$email'",
			"Content" =>
				"<p>Thank you, your password has been sent to '$email'.</p>",
		));
		
		//Controller::$currentController = $controller;
		return $customisedController->renderWith("Page");
	}


	/**
	 * Factory method for the lost password form
	 *
	 * @return Form Returns the lost password form
	 */
	function LostPasswordForm() {
		return new MemberLoginForm($this, "LostPasswordForm", new FieldSet(
				new EmailField("Email", "Email address")
			), new FieldSet(
				new FormAction("forgotPassword", "Send me my password")
			), false
		);
	}


	/**
	 * Authenticate using the given email and password, returning the
	 * appropriate member object if
	 *
	 * @return bool|Member Returns FALSE if authentication fails, otherwise
	 *                     the member object
	 */
	static function authenticate($RAW_email, $RAW_password) {
		$SQL_email = Convert::raw2sql($RAW_email);
		$SQL_password = Convert::raw2sql($RAW_password);

		// Default login (see {@setDetaultAdmin()})
		if(($RAW_email == self::$username) && ($RAW_password == self::$password)
				&& !empty(self::$username) && !empty(self::$password)) {
			$member = self::findAnAdministrator();
		} else {
			$member = DataObject::get_one("Member",
				"Email = '$SQL_email' And Password = '$SQL_password'");
		}

		return $member;
	}


	/**
	 * Return a member with administrator privileges
	 *
	 * @return Member Returns a member object that has administrator
	 *                privileges.
	 */
	static function findAnAdministrator($username = 'admin',
																			$password = 'password') {
		$permission = DataObject::get_one("Permission", "`Code` = 'ADMIN'");

		$adminGroup = null;
		if($permission) $adminGroup = DataObject::get_one("Group", "`ID` = '{$permission->GroupID}'", true, "ID");
		
		if($adminGroup) {
			if($adminGroup->Members()->First()) {
				$member = $adminGroup->Members()->First();
			}
		}

		if(!$adminGroup) {
			$adminGroup = Object::create('Group');
			$adminGroup->Title = 'Administrators';
			$adminGroup->Code = "administrators";
			$adminGroup->write();
			Permission::grant($adminGroup->ID, "ADMIN");
		}
		
		if(!isset($member)) {
			$member = Object::create('Member');
			$member->FirstName = $member->Surname = 'Admin';
			$member->Email = $username;
			$member->Password = $password;
			$member->write();
			$member->Groups()->add($adminGroup);
		}

		return $member;
	}


	/**
	 * This will set a static default-admin (e.g. "td") which is not existing
	 * as a database-record. By this workaround we can test pages in dev-mode
	 * with a unified login. Submitted login-credentials are first checked
	 * against this static information in {@authenticate()}.
	 *
	 * @param $username String
	 * @param $password String (Cleartext)
	 */
	static function setDefaultAdmin( $username, $password ) {
		if( self::$username || self::$password )
			return;

		self::$username = $username;
		self::$password = $password;
	}


	/**
	 * Set strict path checking
	 *
	 * This prevents sharing of the session across several sites in the domain.
	 *
	 * @param boolean $strictPathChecking To enable or disable strict patch
	 *                                    checking.
	 */
	static function setStrictPathChecking($strictPathChecking) {
		self::$strictPathChecking = $strictPathChecking;
	}


	/**
	 * Get strict path checking
	 *
	 * @return boolean Status of strict path checking
	 */
	static function getStrictPathChecking() {
		return self::$strictPathChecking;
	}
}

?>