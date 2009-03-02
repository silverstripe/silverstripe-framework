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
	 *
	 * @var bool
	 */
	protected static $encryptPasswords = true;

	/**
	 * The password encryption algorithm to use if {@link $encryptPasswords}
	 * is set to TRUE.
	 *
	 * @var string
	 */
	protected static $encryptionAlgorithm = 'sha1';

	/**
	 * Should a salt be used for the password encryption?
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
	protected static $wordlist = '/usr/share/silverstripe/wordlist.txt';
	
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
	 */
	static function permissionFailure($controller = null, $messageSet = null) {
		if(Director::is_ajax()) {
			$response = ($controller) ? $controller->getResponse() : new HTTPResponse();
			$response->setStatusCode(403);
			$response->setBody('NOTLOGGEDIN:');
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
							"That page is secured. Enter your credentials below and we will send you right along."
						),
						'alreadyLoggedIn' => _t(
							'Security.ALREADYLOGGEDIN', 
							"You don't have access to this page.  If you have another account that can access that page, you can log in below."
						),
						'logInAgain' => _t(
							'Security.LOGGEDOUT',
							"You have been logged out.  If you would like to log in again, enter your credentials below."
						)
					);
				}
			}

			if(!is_array($messageSet)) {
				$messageSet = array('default' => $messageSet);
			}

			// Work out the right message to show
			if(Member::currentUserID()) {
				$message = isset($messageSet['alreadyLoggedIn']) ? $messageSet['alreadyLoggedIn'] : $messageSet['default'];
				if($member = Member::currentUser()) {
					$member->logOut();
				}
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

			Director::redirect("Security/login");
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
	public function login() {
		// Event handler for pre-login, with an option to let it break you out of the login form
		$eventResults = $this->extend('onBeforeSecurityLogin');
		// If there was a redirection, return
		if(Director::redirected_to()) return;
		// If there was an HTTPResponse object returned, then return that
		else if($eventResults) {
			foreach($eventResults as $result) {
				if($result instanceof HTTPResponse) return $result;
			}
		}
		
		
		$customCSS = project() . '/css/tabs.css';
		if(Director::fileExists($customCSS)) {
			Requirements::css($customCSS);
		}

		$tmpPage = new Page();
		$tmpPage->Title = _t('Security.LOGIN', 'Log in');
		$tmpPage->URLSegment = "Security";
		$tmpPage->ID = -1; // Set the page ID to -1 so we dont get the top level pages as its children

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
			Requirements::javascript(THIRDPARTY_DIR . "/loader.js");
			Requirements::javascript(THIRDPARTY_DIR . "/prototype.js");
			Requirements::javascript(THIRDPARTY_DIR . "/behaviour.js");
			Requirements::javascript(THIRDPARTY_DIR . "/prototype_improvements.js");
			Requirements::javascript(THIRDPARTY_DIR . "/scriptaculous/effects.js");
			Requirements::css(SAPPHIRE_DIR . "/css/Form.css");
			
			// Needed because the <base href=".."> in the template makes problems
			// with the tabstrip library otherwise
			$link_base = Director::absoluteURL($this->Link("login"));
			
			Requirements::javascript(THIRDPARTY_DIR . "/jquery/jquery.js");
			Requirements::javascript(THIRDPARTY_DIR . "/jquery/jquery_improvements.js");
			Requirements::javascript(THIRDPARTY_DIR . '/jquery/plugins/livequery/jquery.livequery.js');
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
				"Content" => $content,
			));
		}
		
		Session::clear('Security.Message');

		// custom processing
		return $customisedController->renderWith(array('Security_login', 'Security', $this->stat('template_main')));
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
		Requirements::javascript(THIRDPARTY_DIR . '/prototype.js');
		Requirements::javascript(THIRDPARTY_DIR . '/behaviour.js');
		Requirements::javascript(THIRDPARTY_DIR . '/loader.js');
		Requirements::javascript(THIRDPARTY_DIR . '/prototype_improvements.js');
		Requirements::javascript(THIRDPARTY_DIR . '/scriptaculous/effects.js');

		$tmpPage = new Page();
		$tmpPage->Title = _t('Security.LOSTPASSWORDHEADER', 'Lost Password');
		$tmpPage->URLSegment = 'Security';
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
		return $customisedController->renderWith(array('Security_lostpassword', 'Security', $this->stat('template_main')));
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
	 * @param HTTPRequest $request The HTTPRequest for this action. 
	 * @return string Returns the "password sent" page as HTML code.
	 */
	public function passwordsent($request) {
		Requirements::javascript(THIRDPARTY_DIR . '/behaviour.js');
		Requirements::javascript(THIRDPARTY_DIR . '/loader.js');
		Requirements::javascript(THIRDPARTY_DIR . '/prototype.js');
		Requirements::javascript(THIRDPARTY_DIR . '/prototype_improvements.js');
		Requirements::javascript(THIRDPARTY_DIR . '/scriptaculous/effects.js');

		$tmpPage = new Page();
		$tmpPage->Title = _t('Security.LOSTPASSWORDHEADER');
		$tmpPage->URLSegment = 'Security';
		$controller = new Page_Controller($tmpPage);
		$controller->init();

		$email = Convert::raw2xml($request->param('ID') . '.' . $request->getExtension());
		
		$customisedController = $controller->customise(array(
			'Title' => sprintf(_t('Security.PASSWORDSENTHEADER', "Password reset link sent to '%s'"), $email),
			'Content' =>
				"<p>" . 
				sprintf(_t('Security.PASSWORDSENTTEXT', "Thank you! The password reset link has been sent to '%s'."), $email) .
				"</p>",
		));
		
		//Controller::$currentController = $controller;
		return $customisedController->renderWith(array('Security_passwordsent', 'Security', $this->stat('template_main')));
	}


	/**
	 * Create a link to the password reset form
	 *
	 * @param string $autoLoginHash The auto login hash
	 */
	public static function getPasswordResetLink($autoLoginHash) {
		$autoLoginHash = urldecode($autoLoginHash);
		return self::Link('changepassword') . "?h=$autoLoginHash";
	}
	
	/**
	 * Show the "change password" page
	 *
	 * @return string Returns the "change password" page as HTML code.
	 */
	public function changepassword() {
		$tmpPage = new Page();
		$tmpPage->Title = _t('Security.CHANGEPASSWORDHEADER', 'Change your password');
		$tmpPage->URLSegment = 'Security';
		$controller = new Page_Controller($tmpPage);
		$controller->init();

		if(isset($_REQUEST['h']) && Member::member_from_autologinhash($_REQUEST['h'])) {
			// The auto login hash is valid, store it for the change password form
			Session::set('AutoLoginHash', $_REQUEST['h']);

			$customisedController = $controller->customise(array(
				'Content' =>
					'<p>' . 
					_t('Security.ENTERNEWPASSWORD', 'Please enter a new password.') .
					'</p>',
				'Form' => $this->ChangePasswordForm(),
			));

		} elseif(Member::currentUser()) {
			// let a logged in user change his password
			$customisedController = $controller->customise(array(
				'Content' => '<p>' . _t('Security.CHANGEPASSWORDBELOW', 'You can change your password below.') . '</p>',
				'Form' => $this->ChangePasswordForm()));

		} else {
			// show an error message if the auto login hash is invalid and the
			// user is not logged in
			if(isset($_REQUEST['h'])) {
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

		//Controller::$currentController = $controller;
		return $customisedController->renderWith(array('Security_changepassword', 'Security', $this->stat('template_main')));
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
			$member = DataObject::get_one("Member", 	"Email = '$SQL_email' AND Password IS NOT NULL");
			if($member && ($member->checkPassword($RAW_password) == false)) {
				$member = null;
			}
		}

		return $member;
	}


	/**
	 * Return a member with administrator privileges
	 *
	 * @return Member Returns a member object that has administrator
	 *                privileges.
	 */
	static function findAnAdministrator($username = 'admin', $password = 'password') {
		$permission = DataObject::get_one("Permission", "`Code` = 'ADMIN'", true, "ID");

		$adminGroup = null;
		if($permission) $adminGroup = DataObject::get_one("Group", "`Group`.`ID` = '{$permission->GroupID}'", true, "`Group`.`ID`");
		
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
	 * Set a default admin in dev-mode
	 * 
	 * This will set a static default-admin (e.g. "td") which is not existing
	 * as a database-record. By this workaround we can test pages in dev-mode
	 * with a unified login. Submitted login-credentials are first checked
	 * against this static information in {@authenticate()}.
	 *
	 * @param string $username The user name
	 * @param string $password The password in cleartext
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
	 * @param bool $encrypt Set to TRUE if you want that all (new) passwords
	 *                      will be stored encrypted, FALSE if you want to
	 *                      store the passwords in clear text.
	 */
	public static function encrypt_passwords($encrypt) {
		self::$encryptPasswords = (bool)$encrypt;
	}


	/**
	 * Get a list of all available encryption algorithms
	 *
	 * @return array Returns an array of strings containing all supported
	 *               encryption algorithms.
	 */
	public static function get_encryption_algorithms() {
		$result = function_exists('hash_algos') ? hash_algos() : array(); 

		if(count($result) == 0) { 
			if(function_exists('md5')) $result[] = 'md5'; 

			if(function_exists('sha1')) $result[] = 'sha1'; 
		} else {
			foreach ($result as $i => $algorithm) {
				if (preg_match('/,/',$algorithm)) {
					unset($result[$i]);
				}
			}
		}
		
		// Support for MySQL password() and old_password() functions.  These aren't recommended unless you need them,
		// but can be helpful for migrating legacy user-sets into a SilverStripe application.
		// Since DB::getConn() doesn't exist yet, we need to look at $databaseConfig. Gack!
		global $databaseConfig;
		if($databaseConfig['type'] == 'MySQLDatabase') {
			$result[] = 'password';
			$result[] = 'old_password';
		}

		return $result;
	}


	/**
	 * Set the password encryption algorithm
	 *
	 * @param string $algorithm One of the available password encryption
	 *                          algorithms determined by
	 *                          {@link Security::get_encryption_algorithms()}
	 * @param bool $use_salt Set to TRUE if a random salt should be used to
	 *                       encrypt the passwords, otherwise FALSE
	 * @return bool Returns TRUE if the passed algorithm was valid, otherwise
	 *              FALSE.
	 */
	public static function set_password_encryption_algorithm($algorithm,
																													 $use_salt) {
		if(in_array($algorithm, self::get_encryption_algorithms()) == false)
		  return false;

		self::$encryptionAlgorithm = $algorithm;
		self::$useSalt = (bool)$use_salt;

		return true;
	}


	/**
	 * Get the the password encryption details
	 *
	 * The return value is an array of the following form:
	 * <code>
	 *   array('encrypt_passwords' => bool,
	 *         'algorithm' => string,
	 *         'use_salt' => bool)
	 * </code>
	 *
	 * @return array Returns an associative array containing all the
	 *               password encryption relevant information.
	 */
	public static function get_password_encryption_details() {
		return array('encrypt_passwords' => self::$encryptPasswords,
								 'algorithm' => self::$encryptionAlgorithm,
								 'use_salt' => self::$useSalt);
	}


	/**
	 * Encrypt a password
	 *
	 * Encrypt a password according to the current password encryption
	 * settings.
	 * Use {@link Security::get_password_encryption_details()} to retrieve the
	 * current settings.
	 * If the settings are so that passwords shouldn't be encrypted, the
	 * result is simple the clear text password with an empty salt except when
	 * a custom algorithm ($algorithm parameter) was passed.
	 *
	 * @param string $password The password to encrypt
	 * @param string $salt Optional: The salt to use. If it is not passed, but
	 *                     needed, the method will automatically create a
	 *                     random salt that will then be returned as return
	 *                     value.
	 * @param string $algorithm Optional: Use another algorithm to encrypt the
	 *                          password (so that the encryption algorithm can
	 *                          be changed over the time).
	 * @return mixed Returns an associative array containing the encrypted
	 *               password and the used salt in the form
	 *               <i>array('encrypted_password' => string, 'salt' =>
	 *               string, 'algorithm' => string)</i>.
	 *               If the passed algorithm is invalid, FALSE will be
	 *               returned.
	 *
	 * @see encrypt_passwords()
	 * @see set_password_encryption_algorithm()
	 * @see get_password_encryption_details()
	 */
	public static function encrypt_password($password, $salt = null,
																					$algorithm = null) {
		if(strlen(trim($password)) == 0) {
			// An empty password was passed, return an empty password an salt!
			return array('password' => null,
									 'salt' => null,
									 'algorithm' => 'none');

		} elseif((!$algorithm && self::$encryptPasswords == false) || ($algorithm == 'none')) {
			// The password should not be encrypted
			return array('password' => substr($password, 0, 64),
									 'salt' => null,
									 'algorithm' => 'none');

		} elseif(strlen(trim($algorithm)) != 0) {
			// A custom encryption algorithm was passed, check if we can use it
			if(in_array($algorithm, self::get_encryption_algorithms()) == false)
				return false;

		} else {
			// Just use the default encryption algorithm
			$algorithm = self::$encryptionAlgorithm;
		}
		
		// Support for MySQL password() and old_password() authentication
		if(strtolower($algorithm) == 'password' || strtolower($algorithm) == 'old_password') {
			$SQL_password = Convert::raw2sql($password);
			$enc = DB::query("SELECT $algorithm('$SQL_password')")->value();
			return array(
				'password' => $enc,
				'salt' => null,
				'algorithm' => $algorithm,
			);
		}


		// If no salt was provided but we need one we just generate a random one
		if(strlen(trim($salt)) == 0)
			 $salt = null;

		if((self::$useSalt == true) && is_null($salt)) {
			$salt = sha1(mt_rand()) . time();
			$salt = substr(base_convert($salt, 16, 36), 0, 50);
		}


    	// Encrypt the password
		if(function_exists('hash')) {
			$password = hash($algorithm, $password . $salt);
		} else {
			$password = call_user_func($algorithm, $password . $salt);
		}

		// Convert the base of the hexadecimal password to 36 to make it shorter
		// In that way we can store also a SHA256 encrypted password in just 64
		// letters.
		$password = substr(base_convert($password, 16, 36), 0, 64);


		return array('password' => $password,
								 'salt' => $salt,
								 'algorithm' => $algorithm);
	}


	/**
	 * Encrypt all passwords
	 *
	 * Action to encrypt all *clear text* passwords in the database according
	 * to the current settings.
	 * If the current settings are so that passwords shouldn't be encrypted,
	 * an explanation will be printed out.
	 *
	 * To run this action, the user needs to have administrator rights!
	 */
	public function encryptallpasswords() {
		// Only administrators can run this method
		if(!Permission::check("ADMIN")) {
			Security::permissionFailure($this,
				_t('Security.PERMFAILURE',' This page is secured and you need administrator rights to access it. 
				Enter your credentials below and we will send you right along.'));
			return;
		}


		if(self::$encryptPasswords == false) {
		        print '<h1>'._t('Security.ENCDISABLED1', 'Password encryption disabled!')."</h1>\n";
			print '<p>'._t('Security.ENCDISABLED2', 'To encrypt your passwords change your password settings by adding')."\n";
			print "<pre>Security::encrypt_passwords(true);</pre>\n"._t('Security.ENCDISABLED3', 'to mysite/_config.php')."</p>";

			return;
		}


		// Are there members with a clear text password?
		$members = DataObject::get("Member",
			"PasswordEncryption = 'none' AND Password IS NOT NULL");

		if(!$members) {
		        print '<h1>'._t('Security.NOTHINGTOENCRYPT1', 'No passwords to encrypt')."</h1>\n";
			print '<p>'._t('Security.NOTHINGTOENCRYPT2', 'There are no members with a clear text password that could be encrypted!')."</p>\n";

			return;
		}

		// Encrypt the passwords...
		print '<h1>'._t('Security.ENCRYPT', 'Encrypting all passwords').'</h1>';
		print '<p>'.sprintf(_t('Security.ENCRYPTWITH', 'The passwords will be encrypted using the &quot;%s&quot; algorithm'), htmlentities(self::$encryptionAlgorithm));

		print (self::$useSalt)
		        ? _t('Security.ENCRYPTWITHSALT', 'with a salt to increase the security.')."</p>\n"
		        : _t('Security.ENCRYPTWITHOUTSALT', 'without using a salt to increase the security.')."</p><p>\n";

		foreach($members as $member) {
			// Force the update of the member record, as new passwords get
			// automatically encrypted according to the settings, this will do all
			// the work for us
			$member->forceChange();
			$member->write();

			print '  '._t('Security.ENCRYPTEDMEMBERS', 'Encrypted credentials for member &quot;');
			print htmlentities($member->getTitle()) . '&quot; ('._t('Security.ID', 'ID:').' ' . $member->ID .
			        '; '._t('Security.EMAIL', 'E-Mail:').' ' . htmlentities($member->Email) . ")<br />\n";
		}

		print '</p>';
	}
	
	/**
	 * Checks the database is in a state to perform security checks.
	 * @return bool
	 */
	public static function database_is_ready() {
		$requiredTables = ClassInfo::dataClassesFor('Member');
		$requiredTables[] = 'Group';
		$requiredTables[] = 'Permission';
		
		foreach($requiredTables as $table) if(!ClassInfo::hasTable($table)) return false;
		
		return (($permissionFields = DB::fieldList('Permission')) && isset($permissionFields['Type'])) &&
			(($memberFields = DB::fieldList('Member')) && isset($memberFields['RememberLoginToken']));
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
