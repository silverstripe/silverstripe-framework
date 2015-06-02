<?php
/**
 * Log-in form for the "member" authentication method.
 *
 * Available extension points:
 * - "authenticationFailed": Called when login was not successful.
 *    Arguments: $data containing the form submission
 * - "forgotPassword": Called before forgot password logic kicks in,
 *    allowing extensions to "veto" execution by returning FALSE.
 *    Arguments: $member containing the detected Member record
 *
 * @package framework
 * @subpackage security
 */
class MemberLoginForm extends LoginForm {

	/**
	 * This field is used in the "You are logged in as %s" message
	 * @var string
	 */
	public $loggedInAsField = 'FirstName';

	protected $authenticator_class = 'MemberAuthenticator';

	/**
	 * Since the logout and dologin actions may be conditionally removed, it's necessary to ensure these
	 * remain valid actions regardless of the member login state.
	 *
	 * @var array
	 * @config
	 */
	private static $allowed_actions = array('dologin', 'logout');

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
	 *                                     {@link FormAction} objects
	 * @param bool $checkCurrentUser If set to TRUE, it will be checked if a
	 *                               the user is currently logged in, and if
	 *                               so, only a logout button will be rendered
	 * @param string $authenticatorClassName Name of the authenticator class that this form uses.
	 */
	public function __construct($controller, $name, $fields = null, $actions = null,
								$checkCurrentUser = true) {

		// This is now set on the class directly to make it easier to create subclasses
		// $this->authenticator_class = $authenticatorClassName;

		$customCSS = project() . '/css/member_login.css';
		if(Director::fileExists($customCSS)) {
			Requirements::css($customCSS);
		}

		if(isset($_REQUEST['BackURL'])) {
			$backURL = $_REQUEST['BackURL'];
		} else {
			$backURL = Session::get('BackURL');
		}

		if($checkCurrentUser && Member::currentUser() && Member::logged_in_session_exists()) {
			$fields = new FieldList(
				new HiddenField("AuthenticationMethod", null, $this->authenticator_class, $this)
			);
			$actions = new FieldList(
				new FormAction("logout", _t('Member.BUTTONLOGINOTHER', "Log in as someone else"))
			);
		} else {
			if(!$fields) {
				$label=singleton('Member')->fieldLabel(Member::config()->unique_identifier_field);
				$fields = new FieldList(
					new HiddenField("AuthenticationMethod", null, $this->authenticator_class, $this),
					// Regardless of what the unique identifer field is (usually 'Email'), it will be held in the
					// 'Email' value, below:
					$emailField = new TextField("Email", $label, null, null, $this),
					new PasswordField("Password", _t('Member.PASSWORD', 'Password'))
				);
				if(Security::config()->remember_username) {
					$emailField->setValue(Session::get('SessionForms.MemberLoginForm.Email'));
				} else {
					// Some browsers won't respect this attribute unless it's added to the form
					$this->setAttribute('autocomplete', 'off');
					$emailField->setAttribute('autocomplete', 'off');
				}
				if(Security::config()->autologin_enabled) {
					$fields->push(new CheckboxField(
						"Remember",
						_t('Member.REMEMBERME', "Remember me next time?")
					));
				}
			}
			if(!$actions) {
				$actions = new FieldList(
					new FormAction('dologin', _t('Member.BUTTONLOGIN', "Log in")),
					new LiteralField(
						'forgotPassword',
						'<p id="ForgotPassword"><a href="Security/lostpassword">'
						. _t('Member.BUTTONLOSTPASSWORD', "I've lost my password") . '</a></p>'
					)
				);
			}
		}

		if(isset($backURL)) {
			$fields->push(new HiddenField('BackURL', 'BackURL', $backURL));
		}

		// Reduce attack surface by enforcing POST requests
		$this->setFormMethod('POST', true);

		parent::__construct($controller, $name, $fields, $actions);

		$this->setValidator(new RequiredFields('Email', 'Password'));

		// Focus on the email input when the page is loaded
		$js = <<<JS
			(function() {
				var el = document.getElementById("MemberLoginForm_LoginForm_Email");
				if(el && el.focus && (typeof jQuery == 'undefined' || jQuery(el).is(':visible'))) el.focus();
			})();
JS;
		Requirements::customScript($js, 'MemberLoginFormFieldFocus');
	}

	/**
	 * Get message from session
	 */
	protected function getMessageFromSession() {

		$forceMessage = Session::get('MemberLoginForm.force_message');
		if(($member = Member::currentUser()) && !$forceMessage) {
			$this->message = _t(
				'Member.LOGGEDINAS',
				"You're logged in as {name}.",
				array('name' => $member->{$this->loggedInAsField})
			);
		}

		// Reset forced message
		if($forceMessage) {
			Session::set('MemberLoginForm.force_message', false);
		}

		return parent::getMessageFromSession();
	}


	/**
	 * Login form handler method
	 *
	 * This method is called when the user clicks on "Log in"
	 *
	 * @param array $data Submitted data
	 */
	public function dologin($data) {
		if($this->performLogin($data)) {
			$this->logInUserAndRedirect($data);
		} else {
			if(array_key_exists('Email', $data)){
				Session::set('SessionForms.MemberLoginForm.Email', $data['Email']);
				Session::set('SessionForms.MemberLoginForm.Remember', isset($data['Remember']));
			}

			if(isset($_REQUEST['BackURL'])) $backURL = $_REQUEST['BackURL'];
			else $backURL = null;

			if($backURL) Session::set('BackURL', $backURL);

			// Show the right tab on failed login
			$loginLink = Director::absoluteURL($this->controller->Link('login'));
			if($backURL) $loginLink .= '?BackURL=' . urlencode($backURL);
			$this->controller->redirect($loginLink . '#' . $this->FormName() .'_tab');
		}
	}

	/**
	 * Login in the user and figure out where to redirect the browser.
	 *
	 * The $data has this format
	 * array(
	 *   'AuthenticationMethod' => 'MemberAuthenticator',
	 *   'Email' => 'sam@silverstripe.com',
	 *   'Password' => '1nitialPassword',
	 *   'BackURL' => 'test/link',
	 *   [Optional: 'Remember' => 1 ]
	 * )
	 *
	 * @param array $data
	 * @return SS_HTTPResponse
	 */
	protected function logInUserAndRedirect($data) {
		Session::clear('SessionForms.MemberLoginForm.Email');
		Session::clear('SessionForms.MemberLoginForm.Remember');

		if(Member::currentUser()->isPasswordExpired()) {
			if(isset($_REQUEST['BackURL']) && $backURL = $_REQUEST['BackURL']) {
				Session::set('BackURL', $backURL);
			}
			$cp = new ChangePasswordForm($this->controller, 'ChangePasswordForm');
			$cp->sessionMessage(
				_t('Member.PASSWORDEXPIRED', 'Your password has expired. Please choose a new one.'),
				'good'
			);
			return $this->controller->redirect('Security/changepassword');
		}

		// Absolute redirection URLs may cause spoofing
		if(!empty($_REQUEST['BackURL'])) {
			$url = $_REQUEST['BackURL'];
			if(Director::is_site_url($url) ) {
				$url = Director::absoluteURL($url);
			} else {
				// Spoofing attack, redirect to homepage instead of spoofing url
				$url = Director::absoluteBaseURL();
			}
			return $this->controller->redirect($url);
		}

		// If a default login dest has been set, redirect to that.
		if ($url = Security::config()->default_login_dest) {
			$url = Controller::join_links(Director::absoluteBaseURL(), $url);
			return $this->controller->redirect($url);
		}

		// Redirect the user to the page where they came from
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
				_t('Member.WELCOMEBACK', "Welcome Back, {firstname}", array('firstname' => $firstname))
			);
			Session::set("Security.Message.type", "good");
		}
		Controller::curr()->redirectBack();
	}


	/**
	 * Log out form handler method
	 *
	 * This method is called when the user clicks on "logout" on the form
	 * created when the parameter <i>$checkCurrentUser</i> of the
	 * {@link __construct constructor} was set to TRUE and the user was
	 * currently logged in.
	 */
	public function logout() {
		$s = new Security();
		$s->logout();
	}


	/**
	 * Try to authenticate the user
	 *
	 * @param array Submitted data
	 * @return Member Returns the member object on successful authentication
	 *                or NULL on failure.
	 */
	public function performLogin($data) {
		$member = call_user_func_array(array($this->authenticator_class, 'authenticate'), array($data, $this));
		if($member) {
			$member->LogIn(isset($data['Remember']));
			return $member;
		} else {
			$this->extend('authenticationFailed', $data);
			return null;
		}
	}


	/**
	 * Forgot password form handler method.
	 * Called when the user clicks on "I've lost my password".
	 * Extensions can use the 'forgotPassword' method to veto executing
	 * the logic, by returning FALSE. In this case, the user will be redirected back
	 * to the form without further action. It is recommended to set a message
	 * in the form detailing why the action was denied.
	 *
	 * @param array $data Submitted data
	 */
	public function forgotPassword($data) {
		// Ensure password is given
		if(empty($data['Email'])) {
			$this->sessionMessage(
				_t('Member.ENTEREMAIL', 'Please enter an email address to get a password reset link.'),
				'bad'
			);

			$this->controller->redirect('Security/lostpassword');
			return;
		}

		// Find existing member
		$member = Member::get()->filter("Email", $data['Email'])->first();

		// Allow vetoing forgot password requests
		$results = $this->extend('forgotPassword', $member);
		if($results && is_array($results) && in_array(false, $results, true)) {
			return $this->controller->redirect('Security/lostpassword');
		}

		if($member) {
			$token = $member->generateAutologinTokenAndStoreHash();

			$e = Member_ForgotPasswordEmail::create();
			$e->populateTemplate($member);
			$e->populateTemplate(array(
				'PasswordResetLink' => Security::getPasswordResetLink($member, $token)
			));
			$e->setTo($member->Email);
			$e->send();

			$this->controller->redirect('Security/passwordsent/' . urlencode($data['Email']));
		} elseif($data['Email']) {
			// Avoid information disclosure by displaying the same status,
			// regardless wether the email address actually exists
			$this->controller->redirect('Security/passwordsent/' . rawurlencode($data['Email']));
		} else {
			$this->sessionMessage(
				_t('Member.ENTEREMAIL', 'Please enter an email address to get a password reset link.'),
				'bad'
			);

			$this->controller->redirect('Security/lostpassword');
		}
	}

}

