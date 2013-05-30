<?php
/**
 * Abstract base class for an authentication method
 *
 * This class is used as a base class for the different authentication
 * methods like {@link MemberAuthenticator} or {@link OpenIDAuthenticator}.
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 * @package framework
 * @subpackage security
 */
abstract class Authenticator extends Object {

	/**
	 * This variable holds all authenticators that should be used
	 *
	 * @var array
	 */
	private static $authenticators = array('MemberAuthenticator');

	/**
	 * Used to influence the order of authenticators on the login-screen
	 * (default shows first).
	 * 
	 * @var string
	 */
	private static $default_authenticator = 'MemberAuthenticator';


	/**
	 * Method to authenticate an user
	 *
	 * @param array $RAW_data Raw data to authenticate the user
	 * @param Form $form Optional: If passed, better error messages can be
	 *                             produced by using
	 *                             {@link Form::sessionMessage()}
	 * @return bool|Member Returns FALSE if authentication fails, otherwise
	 *                     the member object
	 */
	public abstract function authenticate($RAW_data, Form $form = null);


	/**
	 * Check that the password of the given member is correct.
	 * Used, for example, by the change password feature to validate the 'old password'
	 */
	public abstract function checkPassword(Member $member, $password);

	/**
	 * Send the password reset link via email.
	 */
	public function sendResetPasswordEmail($member) {
		$token = $member->generateAutologinTokenAndStoreHash();

		$email = Member_ForgotPasswordEmail::create();
		$email->populateTemplate($member);
		$email->populateTemplate(array(
			'PasswordResetLink' => Security::getPasswordResetLink($member, $token),
		));
		$email->setTo($member->Email);
		$email->send();
	}


	/**
	 * @deprecated
	 */
	public static function get_login_form(Controller $controller) {
		Deprecation::notice('3.1', 'Instantiate the authenticator and use getLoginForm() instaed');

		$authenticator = get_called_class();
		$a = new $authenticator;
		return $a->getLoginForm($controller, 'LoginForm');
	}

	/**
	 * @deprecated
	 */
	public static function get_name() {
		Deprecation::notice('3.1', 'Instantiate the authenticator and use getName() instaed');

		$authenticator = get_called_class();
		$a = new $authenticator;
		return $a->getName();
	}

	/**
	 * Method that creates the login form for this authentication method
	 *
	 * @param Controller The parent controller, necessary to create the
	 *                   appropriate form action tag
	 * @return Form Returns the login form to use with this authentication
	 *              method
	 */
	public function getLoginForm(Controller $controller, $name) {
		return new LoginForm($controller, $name, $this);
	}

	public function getChangePasswordForm($controller,  $name) {
		return new ChangePasswordForm($controller, $name, $this);
	}

	public function getLostPasswordForm($controller, $name) {
		$fields = $this->getLostPasswordFields();
		$fields->push(new HiddenField("AuthenticationMethod", null, get_class($this)));

		$form = new Form(
			$controller,
			'LostPasswordForm',
			$fields,
			new FieldList(
				new FormAction(
					'forgotPassword',
					_t('Security.BUTTONSEND', 'Send me the password reset link')
				)
			),
			false
		);

		$data = $_GET;
		unset($data['AuthenticationMethod']);
		unset($data['SecurityID']);
		unset($data['url']);
		if($data) $form->loadDataFrom($data);

		return $form;
	}

	/**
	 * Retuns a {@link FieldList} of fields to include in the log-in form
	 */
	public abstract function getLoginFields();

	/**
	 * Update the password of the given member.
	 * Used by the change password function
	 */
	public abstract function changePassword(Member $member, $password);

	/**
	 * Get the name of the authentication method
	 *
	 * @return string Returns the name of the authentication method.
	 *
	 */
	public abstract function getName();

	/**
	 * Boolean function; returns true if this authenticator supports password reset
	 */
	public abstract function supportsPasswordReset();

	/**
	 * Return the fields that should be requested on the forgot password form.
	 * The default form shows an Email field and a forgotPassword action.
	 */
	public function getLostPasswordFields() {
		return new FieldList(
			new EmailField('Email', _t('Member.EMAIL', 'Email'))
		);
	}

	/**
	 * Returns the member to send the lost password email to.
	 * @param $data The data from the lost password form.
	 */
	function getMemberForLostPasswordEmail($data) {
		$SQL_data = Convert::raw2sql($data);
		$SQL_email = $SQL_data['Email'];
		$member = DataObject::get_one('Member', "\"Email\" = '{$SQL_email}'");

		return $member;
	}



	////////////////////////////////////////////////////////////////////////////////////////
	// Registration plumbing

	public static function register($authenticator) {
		self::register_authenticator($authenticator);	
	}

	/**
	 * Register a new authenticator
	 *
	 * The new authenticator has to exist and to be derived from the
	 * {@link Authenticator}.
	 * Every authenticator can be registered only once.
	 *
	 * @param string $authenticator Name of the authenticator class to
	 *                              register
	 * @return bool Returns TRUE on success, FALSE otherwise.
	 */
	public static function register_authenticator($authenticator) {
		$authenticator = trim($authenticator);

		if(class_exists($authenticator) == false)
			return false;

		if(is_subclass_of($authenticator, 'Authenticator') == false)
			return false;

		if(in_array($authenticator, self::$authenticators) == false) {
			if(call_user_func(array($authenticator, 'on_register')) === true) {
				array_push(self::$authenticators, $authenticator);
			} else {
				return false;
			}
		}

		return true;
	}
	
	public static function unregister($authenticator) {
		self::unregister_authenticator($authenticator);
	}
	
	/**
	 * Remove a previously registered authenticator
	 *
	 * @param string $authenticator Name of the authenticator class to register
	 * @return bool Returns TRUE on success, FALSE otherwise.
	 */
	public static function unregister_authenticator($authenticator) {
		if(call_user_func(array($authenticator, 'on_unregister')) === true) {
					if(in_array($authenticator, self::$authenticators)) {
						unset(self::$authenticators[array_search($authenticator, self::$authenticators)]);
					}
		};
	}


	/**
	 * Check if a given authenticator is registered
	 *
	 * @param string $authenticator Name of the authenticator class to check
	 * @return bool Returns TRUE if the authenticator is registered, FALSE
	 *              otherwise.
	 */
	public static function is_registered($authenticator) {
		return in_array($authenticator, self::$authenticators);
	}


	/**
	 * Get all registered authenticators
	 *
	 * @return array Returns an array with the class names of all registered
	 *               authenticators.
	 */
	public static function get_authenticators() {
		// put default authenticator first (mainly for tab-order on loginform)
		if($key = array_search(self::$default_authenticator,self::$authenticators)) {
			unset(self::$authenticators[$key]);
			array_unshift(self::$authenticators, self::$default_authenticator);
		}

		return self::$authenticators;
	}
	
	/**
	 * Set a default authenticator (shows first in tabs)
	 *
	 * @param string
	 */
	public static function set_default_authenticator($authenticator) {
		self::$default_authenticator = $authenticator;
		
		
	}
	
	/**
	 * @return string
	 */
	public static function get_default_authenticator() {
		return self::$default_authenticator;
	}


	/**
	 * Callback function that is called when the authenticator is registered
	 *
	 * Use this method for initialization of a newly registered authenticator.
	 * Just overload this method and it will be called when the authenticator
	 * is registered.
	 * <b>If the method returns FALSE, the authenticator won't be
	 * registered!</b>
	 *
	 * @return bool Returns TRUE on success, FALSE otherwise.
	 */
	protected static function on_register() {
		return true;
	}
	
	/**
	 * Callback function that is called when an authenticator is removed.
	 *
	 * @return bool
	 */
	protected static function on_unregister() {
		return true;
	}
}
