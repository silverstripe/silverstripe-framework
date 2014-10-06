<?php

/**
 * Provides the in-cms session re-authentication form for the "member" authenticator
 *
 * @package framework
 * @subpackage security
 */
class CMSMemberLoginForm extends LoginForm {

	protected $authenticator_class = 'MemberAuthenticator';

	/**
	 * Get link to use for external security actions
	 *
	 * @param string $action Action
	 * @return string
	 */
	public function getExternalLink($action = null) {
		return Security::create()->Link($action);
	}

	public function __construct(Controller $controller, $name) {
		// Set default fields
		$fields = new FieldList(
			HiddenField::create("AuthenticationMethod", null, $this->authenticator_class, $this),
			HiddenField::create('tempid', null, $controller->getRequest()->requestVar('tempid')),
			PasswordField::create("Password", _t('Member.PASSWORD', 'Password')),
			LiteralField::create(
				'forgotPassword',
				sprintf(
					'<p id="ForgotPassword"><a href="%s" target="_top">%s</a></p>',
					$this->getExternalLink('lostpassword'),
					_t('CMSMemberLoginForm.BUTTONFORGOTPASSWORD', "Forgot password?")
				)
			)
		);

		if(Security::config()->autologin_enabled) {
			$fields->push(new CheckboxField(
				"Remember",
				_t('Member.REMEMBERME', "Remember me next time?")
			));
		}

		// Determine returnurl to redirect to parent page
		$logoutLink = $this->getExternalLink('logout');
		if($returnURL = $controller->getRequest()->requestVar('BackURL')) {
			$logoutLink = Controller::join_links($logoutLink,  '?BackURL='.urlencode($returnURL));
		}

		// Make actions
		$actions = new FieldList(
			FormAction::create('dologin', _t('CMSMemberLoginForm.BUTTONLOGIN', "Log back in")),
			LiteralField::create(
				'doLogout',
				sprintf(
					'<p id="doLogout"><a href="%s" target="_top">%s</a></p>',
					$logoutLink,
					_t('CMSMemberLoginForm.BUTTONLOGOUT', "Log out")
				)
			)
		);
		
		parent::__construct($controller, $name, $fields, $actions);
	}

	/**
	 * Try to authenticate the user
	 *
	 * @param array Submitted data
	 * @return Member Returns the member object on successful authentication
	 *                or NULL on failure.
	 */
	public function performLogin($data) {
		$authenticator = $this->authenticator_class;
		$member = $authenticator::authenticate($data, $this);
		if($member) {
			$member->LogIn(isset($data['Remember']));
			return $member;
		}

		$this->extend('authenticationFailed', $data);
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
			// Find best url to redirect back to
			$request = $this->controller->getRequest();
			$url = $request->getHeader('X-Backurl')
				?: $request->getHeader('Referer')
				?: $this->controller->Link('login');
			return $this->controller->redirect($url);
		}
	}

	/**
	 * Redirect the user to the change password form.
	 *
	 * @return SS_HTTPResponse
	 */
	protected function redirectToChangePassword() {
		// Since this form is loaded via an iframe, this redirect must be performed via javascript
		$changePasswordForm = new ChangePasswordForm($this->controller, 'ChangePasswordForm');
		$changePasswordForm->sessionMessage(
			_t('Member.PASSWORDEXPIRED', 'Your password has expired. Please choose a new one.'),
			'good'
		);

		// Get redirect url
		$changePasswordURL = $this->getExternalLink('changepassword');
		if($backURL = $this->controller->getRequest()->requestVar('BackURL')) {
			Session::set('BackURL', $backURL);
			$changePasswordURL = Controller::join_links($changePasswordURL,'?BackURL=' . urlencode($backURL));
		}
		$changePasswordURLATT = Convert::raw2att($changePasswordURL);
		$changePasswordURLJS = Convert::raw2js($changePasswordURL);
		$message = _t(
			'CMSMemberLoginForm.PASSWORDEXPIRED',
			'<p>Your password has expired. <a target="_top" href="{link}">Please choose a new one.</a></p>',
			'Message displayed to user if their session cannot be restored',
			array('link' => $changePasswordURLATT)
		);

		// Redirect to change password page
		$this->controller->getResponse()->setStatusCode(200);
		$this->controller->getResponse()->setBody(<<<PHP
<!DOCTYPE html>
<html><body>
$message
<script type="text/javascript">
setTimeout(function(){top.location.href = "$changePasswordURLJS";}, 0);
</script>
</body></html>
PHP
		);
		return $this->controller->getResponse();
	}

	/**
	 * Send user to the right location after login
	 *
	 * @param array $data
	 * @return SS_HTTPResponse
	 */
	protected function logInUserAndRedirect($data) {
		// Check password expiry
		if(Member::currentUser()->isPasswordExpired()) {
			// Redirect the user to the external password change form if necessary
			return $this->redirectToChangePassword();
		} else {
			// Link to success template
			$url = $this->controller->Link('success');
			return $this->controller->redirect($url);
		}
	}
}
