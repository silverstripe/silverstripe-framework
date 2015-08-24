<?php

/**
 * Provides a security interface functionality within the cms
 * @package framework
 * @subpackage security
 */
class CMSSecurity extends Security {

	private static $casting = array(
		'Title' => 'HTMLText'
	);

	private static $allowed_actions = array(
		'LoginForm',
		'success'
	);

	/**
	 * Enable in-cms reauthentication
	 *
	 * @var boolean
	 * @config
	 */
	private static $reauth_enabled = true;

	public function init() {
		parent::init();

		// Include CMS styles and js
		Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');
		Requirements::css(FRAMEWORK_ADMIN_DIR . '/css/screen.css');
		Requirements::combine_files(
			'cmssecurity.js',
			array(
				THIRDPARTY_DIR . '/jquery/jquery.js',
				THIRDPARTY_DIR . '/jquery-ui/jquery-ui.js',
				THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/lib.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/CMSSecurity.js'
			)
		);
	}

	public function Link($action = null) {
		return Controller::join_links(Director::baseURL(), "CMSSecurity", $action);
	}

	/**
	 * Get known logged out member
	 *
	 * @return Member
	 */
	public function getTargetMember() {
		if($tempid = $this->getRequest()->requestVar('tempid')) {
			return Member::member_from_tempid($tempid);
		}
	}

	public function getResponseController($title) {
		// Use $this to prevent use of Page to render underlying templates
		return $this;
	}

	protected function getLoginMessage(&$messageType = null) {
		return parent::getLoginMessage($messageType)
			?: _t(
				'CMSSecurity.LoginMessage',
				'<p>If you have any unsaved work you can return to where you left off by logging back in below.</p>'
			);
	}

	public function getTitle() {
		// Check if logged in already
		if(Member::currentUserID()) {
			return _t('CMSSecurity.SUCCESS', 'Success');
		}

		// Display logged-out message
		$member = $this->getTargetMember();
		if($member) {
			return _t(
				'CMSSecurity.TimedOutTitleMember',
				'Hey {name}!<br />Your session has timed out.',
				'Title for CMS popup login form for a known user',
				array('name' => $member->FirstName)
			);
		} else {
			return _t(
				'CMSSecurity.TimedOutTitleAnonymous',
				'Your session has timed out.',
				'Title for CMS popup login form without a known user'
			);
		}
	}

	/**
	 * Redirects the user to the external login page
	 *
	 * @return SS_HTTPResponse
	 */
	protected function redirectToExternalLogin() {
		$loginURL = Security::create()->Link('login');
		$loginURLATT = Convert::raw2att($loginURL);
		$loginURLJS = Convert::raw2js($loginURL);
		$message = _t(
			'CMSSecurity.INVALIDUSER',
			'<p>Invalid user. <a target="_top" href="{link}">Please re-authenticate here</a> to continue.</p>',
			'Message displayed to user if their session cannot be restored',
			array('link' => $loginURLATT)
		);
		$this->response->setStatusCode(200);
		$this->response->setBody(<<<PHP
<!DOCTYPE html>
<html><body>
$message
<script type="text/javascript">
setTimeout(function(){top.location.href = "$loginURLJS";}, 0);
</script>
</body></html>
PHP
		);
		return $this->response;
	}

	protected function preLogin() {
		// If no member has been previously logged in for this session, force a redirect to the main login page
		if(!$this->getTargetMember()) {
			return $this->redirectToExternalLogin();
		}
		
		return parent::preLogin();
	}

	public function GetLoginForms() {
		$forms = array();
		$authenticators = Authenticator::get_authenticators();
		foreach($authenticators as $authenticator) {
			// Get only CMS-supporting authenticators
			if($authenticator::supports_cms()) {
				$forms[] = $authenticator::get_cms_login_form($this);
			}
		}
		return $forms;
	}

	/**
	 * Determine if CMSSecurity is enabled
	 *
	 * @return bool
	 */
	public static function enabled() {
		// Disable shortcut
		if(!static::config()->reauth_enabled) return false;
		
		// Count all cms-supported methods
		$authenticators = Authenticator::get_authenticators();
		foreach($authenticators as $authenticator) {
			// Supported if at least one authenticator is supported
			if($authenticator::supports_cms()) return true;
		}
		return false;
	}

	public function LoginForm() {
		$authenticator = $this->getAuthenticator();
		if($authenticator && $authenticator::supports_cms()) {
			return $authenticator::get_cms_login_form($this);
		}
		user_error('Passed invalid authentication method', E_USER_ERROR);
	}

	public function getTemplatesFor($action) {
		return array("CMSSecurity_{$action}", "CMSSecurity")
			+ parent::getTemplatesFor($action);
	}

	public function getIncludeTemplate($name) {
		return array("CMSSecurity_{$name}")
			+ parent::getIncludeTemplate($name);
	}

	/**
	 * Given a successful login, tell the parent frame to close the dialog
	 *
	 * @return SS_HTTPResponse
	 */
	public function success() {
		// Ensure member is properly logged in
		if(!Member::currentUserID()) {
			return $this->redirectToExternalLogin();
		}

		// Get redirect url
		$controller = $this->getResponseController(_t('CMSSecurity.SUCCESS', 'Success'));
		$backURL = $this->getRequest()->requestVar('BackURL')
			?: Session::get('BackURL')
			?: Director::absoluteURL(AdminRootController::config()->url_base, true);

		// Show login
		$controller = $controller->customise(array(
			'Content' => _t(
				'CMSSecurity.SUCCESSCONTENT',
				'<p>Login success. If you are not automatically redirected '.
				'<a target="_top" href="{link}">click here</a></p>',
				'Login message displayed in the cms popup once a user has re-authenticated themselves',
				array('link' => $backURL)
			)
		));
		
		return $controller->renderWith($this->getTemplatesFor('success'));
	}
}
