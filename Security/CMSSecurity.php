<?php

namespace SilverStripe\Security;

use SilverStripe\Admin\AdminRootController;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Session;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\Requirements;

/**
 * Provides a security interface functionality within the cms
 */
class CMSSecurity extends Security {

	private static $casting = array(
		'Title' => 'HTMLFragment'
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

	protected function init() {
		parent::init();

		Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/client/dist/js/vendor.js');
		Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/client/dist/js/CMSSecurity.js');
	}

	public function Link($action = null) {
		/** @skipUpgrade */
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
		return null;
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
	 * @return HTTPResponse
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
		$response = $this->getResponse();
		$response->setStatusCode(200);
		$response->setBody(<<<PHP
<!DOCTYPE html>
<html><body>
$message
<script type="application/javascript">
setTimeout(function(){top.location.href = "$loginURLJS";}, 0);
</script>
</body></html>
PHP
		);
		$this->setResponse($response);
		return $response;
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

	/**
	 * Given a successful login, tell the parent frame to close the dialog
	 *
	 * @return HTTPResponse|DBField
	 */
	public function success() {
		// Ensure member is properly logged in
		if(!Member::currentUserID()) {
			return $this->redirectToExternalLogin();
		}

		// Get redirect url
		$controller = $this->getResponseController(_t('CMSSecurity.SUCCESS', 'Success'));
		$backURLs = array(
			$this->getRequest()->requestVar('BackURL'),
			Session::get('BackURL'),
			Director::absoluteURL(AdminRootController::config()->url_base, true),
		);
		$backURL = null;
		foreach ($backURLs as $backURL) {
			if ($backURL && Director::is_site_url($backURL)) {
				break;
			}
		}

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
