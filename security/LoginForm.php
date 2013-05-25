<?php
/**
 * Abstract base class for a login form
 *
 * This class is used as a base class for the different log-in forms like
 * {@link MemberLoginForm} or {@link OpenIDLoginForm}.
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 * @package framework
 * @subpackage security
 */
class LoginForm extends Form {
	public function __construct($controller, $name, Authenticator $authenticator) {

		$this->authenticator = $authenticator;
		
		$this->disableSecurityToken();	

		$customCSS = project() . '/css/member_login.css';
		if(Director::fileExists($customCSS)) {
			Requirements::css($customCSS);
		}

		$fields = $this->authenticator->getLoginFields();

		$backURL = null;
		if(isset($_REQUEST['BackURL'])) {
			$backURL = $_REQUEST['BackURL'];
		} else {
			$backURL = Session::get('BackURL');
		}
		if($backURL) {
			$fields->push(new HiddenField('BackURL', 'BackURL', $backURL));
		}

		$fields->push(new HiddenField("AuthenticationMethod", null, get_class($this->authenticator)));

		// Log in as someone else
		if(Member::currentUser() && Member::logged_in_session_exists()) {
			$fields = new FieldList(
			);
			$actions = new FieldList(
				new FormAction("doLogout", _t('Member.BUTTONLOGINOTHER', "Log in as someone else"))
			);

		//  Regular log-in
		} else {
			if(Security::config()->autologin_enabled) {
				$fields->push(new CheckboxField(
					"Remember",
					_t('Member.REMEMBERME', "Remember me next time?")
				));
			}
		}

		$actions = new FieldList(
			new FormAction('doLogin', _t('Member.BUTTONLOGIN', "Log in"))
		);

		if($this->authenticator->supportsPasswordReset()) {
			$actions->push(new LiteralField(
				'forgotPassword',
				'<p id="ForgotPassword"><a href="Security/lostpassword">' . _t('Member.BUTTONLOSTPASSWORD', "I've lost my password") . '</a></p>'
			));
		}

		// Reduce attack surface by enforcing POST requests
		$this->setFormMethod('POST', true);

		parent::__construct($controller, $name, $fields, $actions);
	}

	public function doLogin($data, $form) {
		$member = null;
		try {
			$member = $this->authenticator->authenticate($data, $form);
		} catch(SS_UserException $e) {
			$this->sessionMessage($e->getMessage(), "error");
		}

		if($member) {
			$member->LogIn(!empty($data['Remember']));

			Session::clear('SessionForms.MemberLoginForm.Email');
			Session::clear('SessionForms.MemberLoginForm.Remember');
			if(
				isset($_REQUEST['BackURL'])
				&& $_REQUEST['BackURL']
				// absolute redirection URLs may cause spoofing
				&& Director::is_site_url($_REQUEST['BackURL'])
			) {
				Director::redirect($_REQUEST['BackURL']);
			} elseif (Security::config()->default_login_dest) {
				Director::redirect(Director::absoluteBaseURL() . Security::default_login_dest());
			} else {
				$member = Member::currentUser();
				if($member) {
					$firstname = Convert::raw2xml($member->FirstName);

					if(!empty($data['Remember'])) {
						Session::set('SessionForms.MemberLoginForm.Remember', '1');
						$member->logIn(true);
					} else {
						$member->logIn();
					}

					Session::set('Security.Message.message',
						sprintf(_t('Member.WELCOMEBACK', "Welcome Back, %s"), $firstname)
					);
					Session::set("Security.Message.type", "good");
				}
				Director::redirectBack();
			}
		} else {
			foreach(array('Email','Username','Remember') as $field) {
				if(isset($data[$field])) {
					Session::set('SessionForms.MemberLoginForm.' . $field, $data[$field]);
				}
			}

			if(isset($_REQUEST['BackURL'])) $backURL = $_REQUEST['BackURL'];
			else $backURL = null;

			if($backURL) Session::set('BackURL', $backURL);

			// Show the right tab on failed login
			$loginLink = Director::absoluteURL(Security::Link("login"));
			if($backURL) $loginLink .= '?BackURL=' . urlencode($backURL);
			Director::redirect($loginLink . '#' . $this->FormName() .'_tab');
		}
	}


	/**
	 * This field is used in the "You are logged in as %s" message
	 * @var string
	 */
	public $loggedInAsField = 'FirstName';

	protected $authenticator_class = 'MemberAuthenticator';

	/**
	 * Log out form handler method
	 *
	 * This method is called when the user clicks on "logout" on the form
	 * created when the parameter <i>$checkCurrentUser</i> of the
	 * {@link __construct constructor} was set to TRUE and the user was
	 * currently logged in.
	 */
	public function doLogout() {
		$s = new Security();
		$s->logout();
		Director::redirectBack();
	}

	/**
	 * Authenticator class to use with this login form
	 * 
	 * Set this variable to the authenticator class to use with this login
	 * form.
	 * @var string
	 */
	
	protected $authenticator;


	public function setAuthenticator(Authenticator $authenticator) {
		$this->authenticator = $authenticator;
	}

	/**
	 * Get the authenticator class
	 * @return Authenticator Returns the authenticator class for this login form.
	 */
	public function getAuthenticator() {
		return $this->authenticator;
	}
}

