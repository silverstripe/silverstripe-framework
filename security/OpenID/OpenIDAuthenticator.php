<?php

/**
 * OpenID authenticator and controller
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */



/**
 * Add the security folder to the include path so that the
 * {@link http://www.openidenabled.com/ PHP OpenID library} finds its files
 */
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR .
				realpath(dirname(__FILE__)));



/**
 * Require the OpenID consumer code.
 */
require_once "Auth/OpenID/Consumer.php";

/**
 * Require the "file store" module, which we'll need to store
 * OpenID information.
 */
require_once "Auth/OpenID/FileStore.php";

/**
 * Require the Simple Registration extension API.
 */
require_once "Auth/OpenID/SReg.php";




/**
 * OpenID authenticator
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */
class OpenIDAuthenticator extends Authenticator {

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
		Object::add_extension('Member', 'OpenIDAuthenticatedRole');
		Object::add_extension('Member_Validator', 	'OpenIDAuthenticatedRole_Validator');
		return parent::on_register();
	}


	/**
	 * Method to authenticate an user
	 *
	 * @param array $RAW_data Raw data to authenticate the user
	 * @param Form $form Optional: If passed, better error messages can be
	 *                             produced by using
	 *                             {@link Form::sessionMessage()}
	 * @return bool Returns FALSE if authentication fails, otherwise the
	 *              method will not return at all because the browser will be
	 *              redirected to some other server.
	 *
	 * @todo Check if we can send the POST request for OpenID 2 directly
	 *       (without rendering a form and using javascript)
	 */
	public static function authenticate(array $RAW_data, Form $form = null) {
		$openid = trim($RAW_data['OpenIDURL']);

    if(strlen($openid) == 0) {
			if(!is_null($form)) {
				$form->sessionMessage(
					_t('OpenIDAuthenticator.ERRORCRED', "Please enter your OpenID URL or your i-name."),
					"bad"
				);
			}
			return false;
		}


		$trust_root = Director::absoluteBaseURL();
		$return_to_url = $trust_root . 'OpenIDAuthenticator_Controller';

		$consumer = new Auth_OpenID_Consumer(new OpenIDStorage(),
																				 new SessionWrapper());


		// No auth request means we can't begin OpenID
		$auth_request = $consumer->begin($openid);
		if(!$auth_request) {
			if(!is_null($form)) {
				$form->sessionMessage(
					_t('OpenIDAuthenticator.ERRORNOVALID',
						"That doesn't seem to be a valid OpenID " .
						"or i-name identifier. Please try again."
					),
					"bad"
				);
			}
			return false;
		}

		$SQL_identity = Convert::raw2sql($auth_request->endpoint->claimed_id);
		if(!($member = DataObject::get_one("Member",
				   "Member.IdentityURL = '$SQL_identity'"))) {
			if(!is_null($form)) {
				$form->sessionMessage(
					_t('OpenIDAuthenticator.ERRORNOTENABLED', 
						"Either your account is not enabled for " .
						"OpenID/i-name authentication " .
						"or the entered identifier is wrong. " .
						"Please try again."),
					"bad"
				);
			}
			return false;
		}


		if($auth_request->shouldSendRedirect()) {
			// For OpenID 1, send a redirect.
			$redirect_url = $auth_request->redirectURL($trust_root,
																								 $return_to_url);

			if(Auth_OpenID::isFailure($redirect_url)) {
				displayError("Could not redirect to server: " .
										 $redirect_url->message);
			} else {
				Director::redirect($redirect_url);
				return false;
			}

		} else {
			// For OpenID 2, use a javascript form to send a POST request to the
			// server.
			$form_id = 'openid_message';
			$form_html = $auth_request->formMarkup($trust_root, $return_to_url,
																						 false,
																						 array('id' => $form_id));

			if(Auth_OpenID::isFailure($form_html)) {
				displayError("Could not redirect to server: " .
										 $form_html->message);
			} else {
				$page_contents = array(
					 "<html><head><title>",
					 _t('OpenIDAuthenticator.TRANSACTIONINPROGRESS', "OpenID transaction in progress"),
					 "</title></head>",
					 "<body onload='document.getElementById(\"". $form_id .
					   "\").submit()'>",
					 $form_html,
					 _t('OpenIDAuthenticator.TRANSACTIONNOTE', 
						"<p>Click &quot;Continue&quot; to login. You are only seeing " .
					 	"this because you appear to have JavaScript disabled.</p>"
					 ),
					 "</body></html>");

				print implode("\n", $page_contents);
			}
		}

		// Stop the script execution! This method should return only on error
		exit();
	}


	/**
	 * Method that creates the login form for this authentication method
	 *
	 * @param Controller The parent controller, necessary to create the
	 *                   appropriate form action tag
	 * @return Form Returns the login form to use with this authentication
	 *              method
	 */
	public static function get_login_form(Controller $controller) {
		return Object::create("OpenIDLoginForm", $controller, "LoginForm");
	}


	/**
	 * Get the name of the authentication method
	 *
	 * @return string Returns the name of the authentication method.
	 */
	public static function get_name() {
		return "OpenID/i-name";
	}
}



/**
 * OpenIDAuthenticator Controller
 *
 * This class handles the response of the OpenID server to authenticate
 * the user
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */
class OpenIDAuthenticator_Controller extends Controller {

	/**
	 * Run the controller
	 *
	 * @param array $requestParams Passed request parameters
	 */
	function run($requestParams) {
		$this->pushCurrent();
		$this->response = new HTTPResponse();

		parent::init();

		if(isset($_GET['debug_profile']))
			Profiler::mark("OpenIDAuthenticator_Controller");


		$consumer = new Auth_OpenID_Consumer(new OpenIDStorage(),
																				 new SessionWrapper());


		// Complete the authentication process using the server's response.
		$response = $consumer->complete();

		if($response->status == Auth_OpenID_CANCEL) {
			Session::set("Security.Message.message",
				_t('OpenIDAuthenticator.VERIFICATIONCANCELLED', "The verification was cancelled. Please try again."));
			Session::set("Security.Message.type", "bad");

			if(isset($_GET['debug_profile']))
				Profiler::unmark("OpenIDAuthenticator_Controller");

			Director::redirect("Security/login");

		} else if($response->status == Auth_OpenID_FAILURE) {
			Session::set("Security.Message.message", // use $response->message ??
				_t('OpenIDAuthenticator.AUTHFAILED', "The OpenID/i-name authentication failed.")
			);
			Session::set("Security.Message.type", "bad");

			if(isset($_GET['debug_profile']))
				Profiler::unmark("OpenIDAuthenticator_Controller");

			Director::redirect("Security/login");

		} else if($response->status == Auth_OpenID_SUCCESS) {
			$openid = $response->identity_url;

			if($response->endpoint->canonicalID) {
				$openid = $response->endpoint->canonicalID;
			}


			if(isset($_GET['debug_profile']))
				Profiler::unmark("OpenIDAuthenticator_Controller");


			$SQL_identity = Convert::raw2sql($openid);
			if($member = DataObject::get_one("Member",
				   "Member.IdentityURL = '$SQL_identity'")) {
				$firstname = Convert::raw2xml($member->FirstName);
				Session::set("Security.Message.message",
					sprintf(_t('Member.WELCOMEBACK'), $firstname)
				);
				Session::set("Security.Message.type", "good");

				$member->LogIn(
					Session::get('SessionForms.OpenIDLoginForm.Remember'));

				Session::clear('SessionForms.OpenIDLoginForm.OpenIDURL');
				Session::clear('SessionForms.OpenIDLoginForm.Remember');

				if($backURL = Session::get("BackURL")) {
					Session::clear("BackURL");
					Director::redirect($backURL);
				}	else {
					Director::redirectBack();
				}

			}	else {
				Session::set("Security.Message.message",
					_t('OpenIDAuthenticator.LOGINFAILED', "Login failed. Please try again.")
				);
				Session::set("Security.Message.type", "bad");

				if($badLoginURL = Session::get("BadLoginURL")) {
					Director::redirect($badLoginURL);
				} else {
					Director::redirectBack();
				}
			}
		}

		return $this->response;
	}


	/**
	 * Helper function to set a session message for the OpenID login form
	 *
	 * @param string $message Message to store
	 * @param string $type Message type (e.g. "good" or "bad")
	 */
	function sessionMessage($message, $type) {
		Session::set("FormInfo.OpenIDLoginForm_LoginForm.formError.message",
								 $message);
		Session::set("'FormInfo.OpenIDLoginForm_LoginForm.formError.type",
								 $type);
	}

}



/**
 * Session wrapper class for the OpenID library
 *
 * This class is a wrapper for the {@link Session} which implements the
 * interface of the {@link Auth_Yadis_PHPSession} class.
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */
class SessionWrapper extends Auth_Yadis_PHPSession {

	/**
	 * Set a session key/value pair.
	 *
	 * @param string $name The name of the session key to add.
	 * @param string $value The value to add to the session.
	 */
	public function set($name, $value) {
		Session::set($name, $value);
	}


	/**
	 * Get a key's value from the session.
	 *
	 * @param string $name The name of the key to retrieve.
	 * @param string $default The optional value to return if the key
	 * is not found in the session.
	 * @return string $result The key's value in the session or
	 * $default if it isn't found.
	 */
	public function get($name, $default=null) {
		$value = Session::get($name);
		if(is_null($value))
			 $value = $default;

		return $value;
	}


	/**
	 * Remove a key/value pair from the session.
	 *
	 * @param string $name The name of the key to remove.
	 */
	public function del($name) {
		Session::clear($name);
	}


	/**
	 * Return the contents of the session in array form.
	 */
	public function contents() {
		return Session::getAll();
	}
}


?>
