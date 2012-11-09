<?php
/**
 * Implements a basic security model
 * @package sapphire
 * @subpackage security
 */
class Security extends Controller {

	/**
	 * Default user name. Only used in dev-mode by {@link setDefaultAdmin()}
	 * 
	 * @var string
	 * @see setDefaultAdmin()
	 */
	protected static $default_username;

	/**
	 * Default password. Only used in dev-mode by {@link setDefaultAdmin()}
	 * 
	 * @var string
	 * @see setDefaultAdmin()
	 */
	protected static $default_password;

	/**
	 * If set to TRUE to prevent sharing of the session across several sites
	 * in the domain.
	 *
	 * @var bool
	 */
	protected static $strictPathChecking = false;

	/**
	 * Should passwords be stored encrypted?
	 * @deprecated 2.4 Please use 'none' as the default $encryptionAlgorithm instead
	 *
	 * @var bool
	 */
	protected static $encryptPasswords = true;

	/**
	 * The password encryption algorithm to use by default.
	 * This is an arbitrary code registered through {@link PasswordEncryptor}.
	 *
	 * @var string
	 */
	protected static $encryptionAlgorithm = 'sha1_v2.4';

	/**
	 * Should a salt be used for the password encryption?
	 * @deprecated 2.4 Please use a custom {@link PasswordEncryptor} instead
	 *
	 * @var bool
	 */
	protected static $useSalt = true;
	
	/**
	 * Showing "Remember me"-checkbox 
	 * on loginform, and saving encrypted credentials to a cookie. 
 	 *  
	 * @var bool 
	 */ 
	public static $autologin_enabled = true;
	
	/**
	 * Location of word list to use for generating passwords
	 * 
	 * @var string
	 */
	protected static $wordlist = './wordlist.txt';
	
	/**
	 * Template thats used to render the pages.
	 *
	 * @var string
	 */
	public static $template_main = 'Page';
	
	/**
	 * Default message set used in permission failures.
	 *
	 * @var array|string
	 */
	protected static $default_message_set = '';
	
	/**
	 * Get location of word list file
	 */
	static function get_word_list() {
		return Security::$wordlist;
	}
	
	/**
	 * Enable or disable recording of login attempts
	 * through the {@link LoginRecord} object.
	 * 
	 * @var boolean $login_recording
	 */
	protected static $login_recording = false;
	
	/**
	 * @var boolean If set to TRUE or FALSE, {@link database_is_ready()}
	 * will always return FALSE. Used for unit testing.
	 */
	static $force_database_is_ready = null;
	
	/**
	 * Set location of word list file
	 * 
	 * @param string $wordListFile Location of word list file
	 */
	static function set_word_list($wordListFile) {
		Security::$wordlist = $wordListFile;
	}
	
	/**
	 * Set the default message set used in permissions failures.
	 *
	 * @param string|array $messageSet
	 */
	static function set_default_message_set($messageSet) {
		self::$default_message_set = $messageSet;
	}


	/**
	 * Register that we've had a permission failure trying to view the given page
	 *
	 * This will redirect to a login page.
	 * If you don't provide a messageSet, a default will be used.
	 *
	 * @param Controller $controller The controller that you were on to cause the permission
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
	 *
	 * The alreadyLoggedIn value can contain a '%s' placeholder that will be replaced with a link
	 * to log in.
	 */
	static function permissionFailure($controller = null, $messageSet = null) {
		if(!$controller) $controller = Controller::curr();
		
		if(Director::is_ajax()) {
			$response = ($controller) ? $controller->getResponse() : new SS_HTTPResponse();
			$response->setStatusCode(403);
			if(!Member::currentUser()) $response->setBody('NOTLOGGEDIN:');
			return $response;
		} else {
			// Prepare the messageSet provided
			if(!$messageSet) {
				if(self::$default_message_set) {
					$messageSet = self::$default_message_set;
				} else {
					$messageSet = array(
						'default' => _t(
							'Security.NOTEPAGESECURED', 
							"That page is secured. Enter your credentials below and we will send "
								. "you right along."
						),
						'alreadyLoggedIn' => _t(
							'Security.ALREADYLOGGEDIN', 
							"You don't have access to this page.  If you have another account that "
								. "can access that page, you can log in again below.",
							PR_MEDIUM,
							"%s will be replaced with a link to log in."
						),
						'logInAgain' => _t(
							'Security.LOGGEDOUT',
							"You have been logged out.  If you would like to log in again, enter "
								. "your credentials below."
						)
					);
				}
			}

			if(!is_array($messageSet)) {
				$messageSet = array('default' => $messageSet);
			}

			// Work out the right message to show
			if(Member::currentUser()) {
				$response = ($controller) ? $controller->getResponse() : new SS_HTTPResponse();
				$response->setStatusCode(403);

				//If 'alreadyLoggedIn' is not specified in the array, then use the default
				//which should have been specified in the lines above
				if(isset($messageSet['alreadyLoggedIn']))
					$message=$messageSet['alreadyLoggedIn'];
				else
					$message=$messageSet['default'];

				// Somewhat hackish way to render a login form with an error message.
				$me = new Security();
				$form = $me->LoginForm();
				$form->sessionMessage($message, 'warning');
				Session::set('MemberLoginForm.force_message',1);
				$formText = $me->login();
				
				$response->setBody($formText);
				return $response;

			} else if(substr(Director::history(),0,15) == 'Security/logout') {
				$message = $messageSet['logInAgain'] ? $messageSet['logInAgain'] : $messageSet['default'];
			} else {
				$message = $messageSet['default'];
			}

			Session::set("Security.Message.message", $message);
			Session::set("Security.Message.type", 'warning');

			Session::set("BackURL", $_SERVER['REQUEST_URI']);

			// TODO AccessLogEntry needs an extension to handle permission denied errors
			// Audit logging hook
			if($controller) $controller->extend('permissionDenied', $member);

			Director::redirect("Security/login?BackURL=" . urlencode($_SERVER['REQUEST_URI']));
		}
		return;
	}


  /**
	 * Get the login form to process according to the submitted data
	 */
	protected function LoginForm() {
		if(isset($this->requestParams['AuthenticationMethod'])) {
			$authenticator = trim($_REQUEST['AuthenticationMethod']);

			$authenticators = Authenticator::get_authenticators();
			if(in_array($authenticator, $authenticators)) {
				return call_user_func(array($authenticator, 'get_login_form'), $this);
			}
		}
		else {
			if($authenticator = Authenticator::get_default_authenticator()) {
				return call_user_func(array($authenticator, 'get_login_form'), $this);
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
	protected function GetLoginForms()
	{
		$forms = array();

		$authenticators = Authenticator::get_authenticators();
		foreach($authenticators as $authenticator) {
		  array_push($forms,
								 call_user_func(array($authenticator, 'get_login_form'),
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
	public static function Link($action = null) {
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
	public function logout($redirect = true) {
		$member = Member::currentUser();
		if($member) $member->logOut();

		if($redirect) Director::redirectBack();
	}


	/**
	 * Show the "login" page
	 *
	 * @return string Returns the "login" page as HTML code.
	 */
	public function login() {
		// Event handler for pre-login, with an option to let it break you out of the login form
		$eventResults = $this->extend('onBeforeSecurityLogin');
		// If there was a redirection, return
		if(Director::redirected_to()) return;
		// If there was an SS_HTTPResponse object returned, then return that
		else if($eventResults) {
			foreach($eventResults as $result) {
				if($result instanceof SS_HTTPResponse) return $result;
			}
		}
		
		
		$customCSS = project() . '/css/tabs.css';
		if(Director::fileExists($customCSS)) {
			Requirements::css($customCSS);
		}

		$tmpPage = new Page();
		$tmpPage->Title = _t('Security.LOGIN', 'Log in');
		$tmpPage->URLSegment = "Security";
		// Disable ID-based caching  of the log-in page by making it a random number
		$tmpPage->ID = -1 * rand(1,10000000);

		$controller = new Page_Controller($tmpPage);
		$controller->init();
		//Controller::$currentController = $controller;

		$content = '';
		$forms = $this->GetLoginForms();
		if(!count($forms)) {
			user_error('No login-forms found, please use Authenticator::register_authenticator() to add one', E_USER_ERROR);
		}
		
		// only display tabs when more than one authenticator is provided
		// to save bandwidth and reduce the amount of custom styling needed 
		if(count($forms) > 1) {
			Requirements::javascript(SAPPHIRE_DIR . '/javascript/loader.js');
			Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/prototype/prototype.js");
			Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/behaviour/behaviour.js");
			Requirements::javascript(SAPPHIRE_DIR . "/javascript/prototype_improvements.js");
			Requirements::javascript(THIRDPARTY_DIR . "/scriptaculous/effects.js");
			Requirements::css(SAPPHIRE_DIR . "/css/Form.css");
			
			// Needed because the <base href=".."> in the template makes problems
			// with the tabstrip library otherwise
			$link_base = Director::absoluteURL($this->Link("login"));
			
			Requirements::javascript(THIRDPARTY_DIR . "/jquery/jquery.js");
			Requirements::javascript(SAPPHIRE_DIR . "/javascript/jquery_improvements.js");
			Requirements::javascript(THIRDPARTY_DIR . "/tabstrip/tabstrip.js");
			Requirements::css(THIRDPARTY_DIR . "/tabstrip/tabstrip.css");
			
			$content = '<div id="Form_EditForm">';
			$content .= '<ul class="tabstrip">';
			$content_forms = '';

			foreach($forms as $form) {
				$content .= "<li><a href=\"$link_base#{$form->FormName()}_tab\">{$form->getAuthenticator()->get_name()}</a></li>\n";
				$content_forms .= '<div class="tab" id="' . $form->FormName() . '_tab">' . $form->forTemplate() . "</div>\n";
			}

			$content .= "</ul>\n" . $content_forms . "\n</div>\n";
		} else {
			$content .= $forms[0]->forTemplate();
		}
		
		if(strlen($message = Session::get('Security.Message.message')) > 0) {
			$message_type = Session::get('Security.Message.type');
			if($message_type == 'bad') {
				$message = "<p class=\"message $message_type\">$message</p>";
			} else {
				$message = "<p>$message</p>";
			}

			$customisedController = $controller->customise(array(
				"Content" => $message,
				"Form" => $content,
			));
		} else {
			$customisedController = $controller->customise(array(
				"Form" => $content,
			));
		}
		
		Session::clear('Security.Message');

		// custom processing
		return $customisedController->renderWith(array('Security_login', 'Security', $this->stat('template_main'), 'ContentController'));
	}
	
	function basicauthlogin() {
		$member = BasicAuth::requireLogin("SilverStripe login", 'ADMIN');
		$member->LogIn();
	}
	
	/**
	 * Show the "lost password" page
	 *
	 * @return string Returns the "lost password" page as HTML code.
	 */
	public function lostpassword() {
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/prototype/prototype.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/behaviour/behaviour.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/loader.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/prototype_improvements.js');
		Requirements::javascript(THIRDPARTY_DIR . '/scriptaculous/effects.js');

		$tmpPage = new Page();
		$tmpPage->Title = _t('Security.LOSTPASSWORDHEADER', 'Lost Password');
		$tmpPage->URLSegment = 'Security';
		$tmpPage->ID = -1; // Set the page ID to -1 so we dont get the top level pages as its children
		$controller = new Page_Controller($tmpPage);
		$controller->init();

		$customisedController = $controller->customise(array(
			'Content' => 
				'<p>' . 
				_t(
					'Security.NOTERESETPASSWORD', 
					'Enter your e-mail address and we will send you a link with which you can reset your password'
				) . 
				'</p>',
			'Form' => $this->LostPasswordForm(),
		));
		
		//Controller::$currentController = $controller;
		return $customisedController->renderWith(array('Security_lostpassword', 'Security', $this->stat('template_main'), 'ContentController'));
	}


	/**
	 * Factory method for the lost password form
	 *
	 * @return Form Returns the lost password form
	 */
	public function LostPasswordForm() {
		return new MemberLoginForm(
			$this,
			'LostPasswordForm',
			new FieldSet(
				new EmailField('Email', _t('Member.EMAIL', 'Email'))
			),
			new FieldSet(
				new FormAction(
					'forgotPassword',
					_t('Security.BUTTONSEND', 'Send me the password reset link')
				)
			),
			false
		);
	}


	/**
	 * Show the "password sent" page, after a user has requested
	 * to reset their password.
	 *
	 * @param SS_HTTPRequest $request The SS_HTTPRequest for this action. 
	 * @return string Returns the "password sent" page as HTML code.
	 */
	public function passwordsent($request) {
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/behaviour/behaviour.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/loader.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/prototype/prototype.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/prototype_improvements.js');
		Requirements::javascript(THIRDPARTY_DIR . '/scriptaculous/effects.js');

		$tmpPage = new Page();
		$tmpPage->Title = _t('Security.LOSTPASSWORDHEADER');
		$tmpPage->URLSegment = 'Security';
		$tmpPage->ID = -1; // Set the page ID to -1 so we dont get the top level pages as its children
		$controller = new Page_Controller($tmpPage);
		$controller->init();

		$email = Convert::raw2xml($request->param('ID') . '.' . $request->getExtension());
		
		$customisedController = $controller->customise(array(
			'Title' => sprintf(_t('Security.PASSWORDSENTHEADER', "Password reset link sent to '%s'"), $email),
			'Content' =>
				"<p>" . 
				sprintf(_t('Security.PASSWORDSENTTEXT', "Thank you! A reset link has been sent to  '%s', provided an account exists for this email address."), $email) .
				"</p>",
			'Email' => $email
		));
		
		//Controller::$currentController = $controller;
		return $customisedController->renderWith(array('Security_passwordsent', 'Security', $this->stat('template_main'), 'ContentController'));
	}


	/**
	 * Create a link to the password reset form.
	 *
	 * GET parameters used:
	 * - m: member ID
	 * - t: plaintext token
	 *
	 * @param Member $member Member object associated with this link.
	 * @param string $autoLoginHash The auto login token.
	 */
	public static function getPasswordResetLink($member, $autologinToken) {
		$autologinToken = urldecode($autologinToken);
		return self::Link('changepassword') . "?m={$member->ID}&t=$autologinToken";
	}
	
	/**
	 * Show the "change password" page.
	 * This page can either be called directly by logged-in users
	 * (in which case they need to provide their old password),
	 * or through a link emailed through {@link lostpassword()}.
	 * In this case no old password is required, authentication is ensured
	 * through the Member.AutoLoginHash property.
	 * 
	 * @see ChangePasswordForm
	 *
	 * @return string Returns the "change password" page as HTML code.
	 */
	public function changepassword() {
		$tmpPage = new Page();
		$tmpPage->Title = _t('Security.CHANGEPASSWORDHEADER', 'Change your password');
		$tmpPage->URLSegment = 'Security';
		$tmpPage->ID = -1; // Set the page ID to -1 so we dont get the top level pages as its children
		$controller = new Page_Controller($tmpPage);
		$controller->init();

		// Extract the member from the URL.
		$member = null;
		if (isset($_REQUEST['m'])) {
			$member = DataObject::get_by_id('Member', (int)$_REQUEST['m']);
		}

		// Check whether we are merely changin password, or resetting.
		if(isset($_REQUEST['t']) && $member && $member->validateAutoLoginToken($_REQUEST['t'])) {
			// On first valid password reset request redirect to the same URL without hash to avoid referrer leakage.

			// Store the hash for the change password form. Will be unset after reload within the ChangePasswordForm.
			Session::set('AutoLoginHash', $member->encryptWithUserSettings($_REQUEST['t']));
			
			return $this->redirect($this->Link('changepassword'));
		} elseif(Session::get('AutoLoginHash')) {
			// Subsequent request after the "first load with hash" (see previous if clause).
			$customisedController = $controller->customise(array(
				'Content' =>
					'<p>' . 
					_t('Security.ENTERNEWPASSWORD', 'Please enter a new password.') .
					'</p>',
				'Form' => $this->ChangePasswordForm(),
			));
		} elseif(Member::currentUser()) {
			// Logged in user requested a password change form.
			$customisedController = $controller->customise(array(
				'Content' => '<p>' . _t('Security.CHANGEPASSWORDBELOW', 'You can change your password below.') . '</p>',
				'Form' => $this->ChangePasswordForm()));

		} else {
			// show an error message if the auto login token is invalid and the
			// user is not logged in
			if(!isset($_REQUEST['t']) || !$member) {
				$customisedController = $controller->customise(
					array('Content' =>
						sprintf(
							_t('Security.NOTERESETLINKINVALID',
								'<p>The password reset link is invalid or expired.</p><p>You can request a new one <a href="%s">here</a> or change your password after you <a href="%s">logged in</a>.</p>'
							),
							$this->Link('lostpassword'),
							$this->link('login')
						)
					)
				);
			} else {
				self::permissionFailure(
					$this,
					_t('Security.ERRORPASSWORDPERMISSION', 'You must be logged in in order to change your password!')
				);
				return;
			}
		}

		return $customisedController->renderWith(array('Security_changepassword', 'Security', $this->stat('template_main'), 'ContentController'));
	}
	
	/**
	 * Security/ping can be visited with ajax to keep a session alive.
	 * This is used in the CMS.
	 */
	function ping() {
		return 1;
	}


	/**
	 * Factory method for the lost password form
	 *
	 * @return Form Returns the lost password form
	 */
	public function ChangePasswordForm() {
		return new ChangePasswordForm($this, 'ChangePasswordForm');
	}


	/**
	 * Authenticate using the given email and password, returning the
	 * appropriate member object if
	 *
	 * @return bool|Member Returns FALSE if authentication fails, otherwise
	 *                     the member object
	 * @see setDefaultAdmin()
	 */
	public static function authenticate($RAW_email, $RAW_password) {
		$SQL_email = Convert::raw2sql($RAW_email);
		$SQL_password = Convert::raw2sql($RAW_password);

		// Default login (see {@setDetaultAdmin()})
		if(($RAW_email == self::$default_username) && ($RAW_password == self::$default_password)
				&& !empty(self::$default_username) && !empty(self::$default_password)) {
			$member = self::findAnAdministrator();
		} else {
			$member = DataObject::get_one("Member", 	"\"" . Member::get_unique_identifier_field() . "\" = '$SQL_email' AND \"Password\" IS NOT NULL");
			if($member && ($member->checkPassword($RAW_password) == false)) {
				$member = null;
			}
		}

		return $member;
	}


	/**
	 * Return an existing member with administrator privileges, or create one of necessary.
	 * 
	 * Will create a default 'Administrators' group if no group is found
	 * with an ADMIN permission. Will create a new 'Admin' member with administrative permissions
	 * if no existing Member with these permissions is found. 
	 * 
	 * Important: Any newly created administrator accounts will NOT have valid
	 * login credentials (Email/Password properties), which means they can't be used for login
	 * purposes outside of any default credentials set through {@link Security::setDefaultAdmin()}.
	 * 
	 * @return Member 
	 */
	static function findAnAdministrator() {
		// coupling to subsites module
		$origSubsite = null;
		if(is_callable('Subsite::changeSubsite')) {
			$origSubsite = Subsite::currentSubsiteID();
			Subsite::changeSubsite(0);
		}

		// find a group with ADMIN permission
		$adminGroup = DataObject::get('Group', 
								"\"Permission\".\"Code\" = 'ADMIN'", 
								"\"Group\".\"ID\"", 
								"JOIN \"Permission\" ON \"Group\".\"ID\"=\"Permission\".\"GroupID\"", 
								'1');
		
		if(is_callable('Subsite::changeSubsite')) {
			Subsite::changeSubsite($origSubsite);
		}
		if ($adminGroup) {
			$adminGroup = $adminGroup->First();

			if($adminGroup->Members()->First()) {
				$member = $adminGroup->Members()->First();
			}
		}

		if(!$adminGroup) {
			singleton('Group')->requireDefaultRecords();
		}
		
		if(!isset($member)) {
			singleton('Member')->requireDefaultRecords();
			$members = Permission::get_members_by_permission('ADMIN');
			$member = $members->First();
		}

		return $member;
	}


	/**
	 * Set a default admin in dev-mode
	 * 
	 * This will set a static default-admin which is not existing
	 * as a database-record. By this workaround we can test pages in dev-mode
	 * with a unified login. Submitted login-credentials are first checked
	 * against this static information in {@link Security::authenticate()}.
	 *
	 * @param string $username The user name
	 * @param string $password The password (in cleartext)
	 */
	public static function setDefaultAdmin($username, $password) {
		// don't overwrite if already set
		if(self::$default_username || self::$default_password) {
			return false;
		}

		self::$default_username = $username;
		self::$default_password = $password;
	}
	
	/**
	 * Checks if the passed credentials are matching the default-admin.
	 * Compares cleartext-password set through Security::setDefaultAdmin().
	 * 
	 * @param string $username
	 * @param string $password 
	 * @return bool
	 */
	public static function check_default_admin($username, $password) {
		return (
			self::$default_username === $username
			&& self::$default_password === $password
			&& self::has_default_admin()
		);
	}
	
	/**
	 * Check that the default admin account has been set.
	 */
	public static function has_default_admin() {
		return !empty(self::$default_username) && !empty(self::$default_password);		
	}

	/**
	 * Set strict path checking
	 *
	 * This prevents sharing of the session across several sites in the
	 * domain.
	 *
	 * @param boolean $strictPathChecking To enable or disable strict patch
	 *                                    checking.
	 */
	public static function setStrictPathChecking($strictPathChecking) {
		self::$strictPathChecking = $strictPathChecking;
	}


	/**
	 * Get strict path checking
	 *
	 * @return boolean Status of strict path checking
	 */
	public static function getStrictPathChecking() {
		return self::$strictPathChecking;
	}


	/**
	 * Set if passwords should be encrypted or not
	 *
	 * @deprecated 2.4 Use PasswordEncryptor_None instead.
	 * 
	 * @param bool $encrypt Set to TRUE if you want that all (new) passwords
	 *                      will be stored encrypted, FALSE if you want to
	 *                      store the passwords in clear text.
	 */
	public static function encrypt_passwords($encrypt) {
		self::$encryptPasswords = (bool)$encrypt;
	}


	/**
	 * Get a list of all available encryption algorithms.
	 * Note: These are arbitrary codes, and not callable methods.
	 * 
	 * @deprecated 2.4 Use PasswordEncryptor::get_encryptors()
	 *
	 * @return array Returns an array of strings containing all supported encryption algorithms.
	 */
	public static function get_encryption_algorithms() {
		return array_keys(PasswordEncryptor::get_encryptors());
	}


	/**
	 * Set the password encryption algorithm
	 *
	 * @param string $algorithm One of the available password encryption
	 *  algorithms determined by {@link Security::get_encryption_algorithms()}
	 * @return bool Returns TRUE if the passed algorithm was valid, otherwise FALSE.
	 */
	public static function set_password_encryption_algorithm($algorithm) {
		if(!array_key_exists($algorithm, PasswordEncryptor::get_encryptors())) return false;
		
		self::$encryptionAlgorithm = $algorithm;
		return true;
	}
	
	/**
	 * @return String
	 */
	public static function get_password_encryption_algorithm() {
		return self::$encryptionAlgorithm;
	}

	/**
	 * Encrypt a password according to the current password encryption settings.
	 * If the settings are so that passwords shouldn't be encrypted, the
	 * result is simple the clear text password with an empty salt except when
	 * a custom algorithm ($algorithm parameter) was passed.
	 *
	 * @param string $password The password to encrypt
	 * @param string $salt Optional: The salt to use. If it is not passed, but
	 *  needed, the method will automatically create a
	 *  random salt that will then be returned as return value.
	 * @param string $algorithm Optional: Use another algorithm to encrypt the
	 *  password (so that the encryption algorithm can be changed over the time).
	 * @param Member $member Optional
	 * @return mixed Returns an associative array containing the encrypted
	 *  password and the used salt in the form:
	 * <code>
	 * 	array(
	 * 	'password' => string, 
	 * 	'salt' => string, 
	 * 	'algorithm' => string,
	 * 	'encryptor' => PasswordEncryptor instance
	 * 	)
	 * </code>
	 * If the passed algorithm is invalid, FALSE will be returned.
	 *
	 * @see encrypt_passwords()
	 * @see set_password_encryption_algorithm()
	 */
	static function encrypt_password($password, $salt = null, $algorithm = null, $member = null) {
		if(
			// if the password is empty, don't encrypt
			strlen(trim($password)) == 0  
			// if no algorithm is provided and no default is set, don't encrypt
			|| (!$algorithm && self::$encryptPasswords == false)
		) {
			$algorithm = 'none';
		} else {
			// Fall back to the default encryption algorithm
			if(!$algorithm) $algorithm = self::$encryptionAlgorithm;
		} 
		
		$e = PasswordEncryptor::create_for_algorithm($algorithm);

		// New salts will only need to be generated if the password is hashed for the first time
		$salt = ($salt) ? $salt : $e->salt($password);
		
		return array(
			'password' => $e->encrypt($password, $salt, $member),
			'salt' => $salt,
			'algorithm' => $algorithm,
			'encryptor' => $e
		);
	}
	
	/**
	 * Checks the database is in a state to perform security checks.
	 * See {@link DatabaseAdmin->init()} for more information.
	 * 
	 * @return bool
	 */
	public static function database_is_ready() {
		// Used for unit tests
		if(self::$force_database_is_ready !== NULL) return self::$force_database_is_ready;
		
		$requiredTables = ClassInfo::dataClassesFor('Member');
		$requiredTables[] = 'Group';
		$requiredTables[] = 'Permission';
		
		foreach($requiredTables as $table) {
			// if any of the tables aren't created in the database
			if(!ClassInfo::hasTable($table)) return false;
		
			// if any of the tables don't have all fields mapped as table columns
			$dbFields = DB::fieldList($table);
			if(!$dbFields) return false;
			
			$objFields = DataObject::database_fields($table);
			$missingFields = array_diff_key($objFields, $dbFields);
			
			if($missingFields) return false;
		}
		
		return true;
	}
	
	/**
	 * Enable or disable recording of login attempts
	 * through the {@link LoginRecord} object.
	 * 
	 * @param boolean $bool
	 */
	public static function set_login_recording($bool) {
		self::$login_recording = (bool)$bool;
	}
	
	/**
	 * @return boolean
	 */
	public static function login_recording() {
		return self::$login_recording;
	}
	
	protected static $default_login_dest = "";
	
	/**
	 * Set the default login dest
	 * This is the URL that users will be redirected to after they log in,
	 * if they haven't logged in en route to access a secured page.
	 * 
	 * By default, this is set to the homepage
	 */
	public static function set_default_login_dest($dest) {
		self::$default_login_dest = $dest;
	}

	/**
	 * Get the default login dest
	 */
	public static function default_login_dest() {
		return self::$default_login_dest;
	}

}
?>
