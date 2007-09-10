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
	 * Register that we've had a permission failure trying to view the given page.
	 * This will redirect to a login page.
	 * @param page The controller that you were on to cause the permission failure.
	 * @param messageSet The message to show to the user.  This can be a string, or a map of different
	 * messages for different contexts.  If you pass an array, you can use the following keys:
	 *   - default: The default message
	 *   - logInAgain: The message to show if the user has just logged out and the 
	 *   - alreadyLoggedIn: The message to show if the user is already logged in and lacks the permission to access the item.
	 * If you don't provide a messageSet, a default will be used.
	 */
	static function permissionFailure($page = null, $messageSet = null) {
		$loginForm = singleton('Security')->LoginForm();
		
		//user_error('debug', E_USER_ERROR);
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
			
			$message = $messageSet['alreadyLoggedIn'] ? $messageSet['alreadyLoggedIn'] : $messageSet['default'];
			//$_SESSION['LoginForm']['force_form'] = true;
			//$_SESSION['LoginForm']['type'] = 'warning';
			
			Member::logout();
			
		} else if(substr(Director::history(),0,15) == 'Security/logout') {
			$message = $messageSet['logInAgain'] ? $messageSet['logInAgain'] : $messageSet['default'];
			
		} else {
			$message = $messageSet['default'];
		}
		
		$loginForm->sessionMessage($message, 'warning');

//		$_SESSION['LoginForm']['message'] = $message;
		Session::set("BackURL", $_SERVER['REQUEST_URI']);
		
		if(Director::is_ajax()) {
			die('NOTLOGGEDIN:');
		} else {
			Director::redirect("Security/login");
		}
		return;
	}

	function LoginForm() {
		$customCSS = project() . '/css/login.css';
		if(Director::fileExists($customCSS)) Requirements::css($customCSS);
		return Object::create("LoginForm", $this, "LoginForm");
	}
	function Link($action = null) {
		return "Security/$action";
	}
	/**
	 * @param bool $redirect Redirect the user back to where they came. 
	 * - If it's false, the code calling logout() is responsible for sending the user where-ever they should go.
	 */
	function logout($redirect = true) {
		Cookie::set('alc_enc',null);
		Cookie::forceExpiry('alc_enc');
		Session::clear("loggedInAs");
		if($redirect) Director::redirectBack();
	}
	
	function login() {
		Requirements::javascript("jsparty/behaviour.js");
		Requirements::javascript("jsparty/loader.js");
		Requirements::javascript("jsparty/prototype.js");
		Requirements::javascript("jsparty/prototype_improvements.js");
		Requirements::javascript("jsparty/scriptaculous/effects.js");
		
		$tmpPage = DataObject::get_one('Page', "URLSegment = 'home'");
		$tmpPage->Title = "Log in";
		$tmpPage->URLSegment = "Security";

		$controller = new Page_Controller($tmpPage);
		$controller->init();
		//Controller::$currentController = $controller;

		if(SSViewer::hasTemplate("Security_login")) {
			return $controller->renderWith(array("Security_login", "Page"));
			
		} else {
			$customisedController = $controller->customise(array(
				"Content" => $this->LoginForm()->forTemplate()
			));

			return $customisedController->renderWith("Page");
		}
	}
	
	function basicauthlogin() {
		$member = BasicAuth::requireLogin("SilverStripe login", 'ADMIN');
		$member->LogIn();
	}

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
			"Content" => "<p>Enter your e-mail address and we will send you a password</p>",
			"Form" => $this->LostPasswordForm(),
		));
		
		//Controller::$currentController = $controller;
		return $customisedController->renderWith("Page");
	}

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
			"Content" => "<p>Thank you, your password has been sent to '$email'.</p>",
		));
		
		//Controller::$currentController = $controller;
		return $customisedController->renderWith("Page");
	}
	
	
	function LostPasswordForm() {
		return new LoginForm($this, "LostPasswordForm", new FieldSet(
				new EmailField("Email", "Email address")
			), new FieldSet(
				new FormAction("forgotPassword", "Send me my password")
			), false
		);
	}


	/**
	 * Authenticate using the given email and password, returning the appropriate member object if
	 */
	static function authenticate($RAW_email, $RAW_password) {
		$SQL_email = Convert::raw2sql($RAW_email);
		$SQL_password = Convert::raw2sql($RAW_password);

		// Default login (see {@setDetaultAdmin()})
		if( $RAW_email == self::$username && $RAW_password == self::$password && !empty( self::$username ) && !empty( self::$password ) ) {
			$member = self::findAnAdministrator();
		} else {
			$member = DataObject::get_one("Member", "Email = '$SQL_email' And Password = '$SQL_password'");
		}
		
		return $member;
	}

	/**
	 * Return a member with administrator privileges
	 */
	static function findAnAdministrator($username = 'admin', $password = 'password') {
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
	 * This will set a static default-admin (e.g. "td") which is not existing as
	 * a database-record. By this workaround we can test pages in dev-mode with
	 * a unified login. Submitted login-credentials are first checked against
	 * this static information in {@authenticate()}.
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
	 * Set strict path checking. This prevents sharing of the session
	 * across several sites in the domain.
	 * 
	 * @param strictPathChecking boolean to enable or disable strict patch checking. 
	 */
	static function setStrictPathChecking($strictPathChecking) {
		self::$strictPathChecking = $strictPathChecking;
	}
	
	static function getStrictPathChecking() {
		return self::$strictPathChecking;
	}
}
?>
