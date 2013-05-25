<?php
/**
 * Log-in form for the "member" authentication method
 * @package framework
 * @subpackage security
 */
class MemberLoginForm extends LoginForm {

	/**
	 * Login form handler method
	 *
	 * This method is called when the user clicks on "Log in".
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
	 * @return void
	 */
	protected function logInUserAndRedirect($data) {
		Session::clear('SessionForms.MemberLoginForm.Email');
		Session::clear('SessionForms.MemberLoginForm.Remember');

		if(Member::currentUser()->isPasswordExpired()) {
			if(isset($_REQUEST['BackURL']) && $backURL = $_REQUEST['BackURL']) {
				Session::set('BackURL', $backURL);
			}
			$cp = new ChangePasswordForm($this->controller, 'ChangePasswordForm', $this->authenticator);
			$cp->sessionMessage('Your password has expired. Please choose a new one.', 'good');
			return $this->controller->redirect('Security/changepassword');
		}
		
		// Absolute redirection URLs may cause spoofing
		if(isset($_REQUEST['BackURL']) && $_REQUEST['BackURL'] && Director::is_site_url($_REQUEST['BackURL']) ) {
			return $this->controller->redirect($_REQUEST['BackURL']);
		}

		// Spoofing attack, redirect to homepage instead of spoofing url
		if(isset($_REQUEST['BackURL']) && $_REQUEST['BackURL'] && !Director::is_site_url($_REQUEST['BackURL'])) {
			return $this->controller->redirect(Director::absoluteBaseURL());
		}

		// If a default login dest has been set, redirect to that.
		if (Security::config()->default_login_dest) {
			return $this->controller->redirect(Director::absoluteBaseURL() . Security::config()->default_login_dest);
		}

		// Redirect the user to the page where he came from
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
		$member = $this->authenticator->authenticate($data, $this);
		if($member) {
			$member->LogIn(isset($data['Remember']));
			return $member;
		} else {
			$this->extend('authenticationFailed', $data);
			return null;
		}
	}


	/**
	 * Forgot password form handler method
	 *
	 * This method is called when the user clicks on "I've lost my password"
	 *
	 * @param array $data Submitted data
	 */
	public function forgotPassword($data) {
		$SQL_data = Convert::raw2sql($data);
		$SQL_email = $SQL_data['Email'];
		$member = DataObject::get_one('Member', "\"Email\" = '{$SQL_email}'");

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
			$this->controller->redirect('Security/passwordsent/' . urlencode($data['Email']));
		} else {
			$this->sessionMessage(
				_t('Member.ENTEREMAIL', 'Please enter an email address to get a password reset link.'),
				'bad'
			);
			
			$this->controller->redirect('Security/lostpassword');
		}
	}

}
