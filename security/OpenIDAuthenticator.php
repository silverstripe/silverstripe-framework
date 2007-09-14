<?php

/**
 * OpenID authenticator and controller
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */



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
	 * Method to authenticate an user
	 *
	 * @param array $RAW_data Raw data to authenticate the user
   * @param Form $form Optional: If passed, better error messages can be
   *                             produced by using
   *                             {@link Form::sessionMessage()}
	 * @return bool|Member Returns FALSE if authentication fails, otherwise
	 *                     the member object
	 */
	public function authenticate(array $RAW_data, Form $form = null) {
		$openid = $RAW_data['OpenIDURL'];

		$trust_root = Director::absoluteBaseURL();
		$return_to_url = $trust_root . 'OpenIDAuthenticator_Controller';

		/**
		 * @todo Change the store to use the database!
		 */
		// FIXXXME
		$store_path = TEMP_FOLDER;

		if(!file_exists($store_path) && !mkdir($store_path)) {
			print "Could not create the FileStore directory '$store_path'. ".
					" Please check the effective permissions.";
			exit(0);
		}
		$store = new Auth_OpenID_FileStore($store_path);
		// END FIXXXME
		$consumer = new Auth_OpenID_Consumer($store, new SessionWrapper());


		// Begin the OpenID authentication process.
		$auth_request = $consumer->begin($openid);

		// No auth request means we can't begin OpenID.
		if(!$auth_request) {
			if(!is_null($form)) {
				$form->sessionMessage("That doesn't seem to be a valid OpenID " .
																"or i-name identifier. Please try again.",
															"bad");
			}
			return false;
		}

		$SQL_user = Convert::raw2sql($auth_request->endpoint->claimed_id);
		if(!($member = DataObject::get_one("Member", "Email = '$SQL_user'"))) {
			if(!is_null($form)) {
				$form->sessionMessage("Either your account is not enabled for " .
																"OpenID/i-name authentication " .
																"or the entered identifier is wrong. " .
																"Please try again.",
															"bad");
			}
			return false;
		}



		/**
		 * @todo Check if the POST request should be send directly (without rendering a form)
		 */
		// For OpenID 1, send a redirect.  For OpenID 2, use a Javascript
		// form to send a POST request to the server.
		if($auth_request->shouldSendRedirect()) {
			$redirect_url = $auth_request->redirectURL($trust_root, $return_to_url);

			// If the redirect URL can't be built, display an error
			// message.
			if(Auth_OpenID::isFailure($redirect_url)) {
				displayError("Could not redirect to server: " . $redirect_url->message);
			} else {
				Director::redirect($redirect_url);
//				header("Location: ".$redirect_url);

			}
		} else {
			// Generate form markup and render it.
			$form_id = 'openid_message';
			$form_html = $auth_request->formMarkup($trust_root, $return_to_url,
																						 false, array('id' => $form_id));

			// Display an error if the form markup couldn't be generated;
			// otherwise, render the HTML.
			if(Auth_OpenID::isFailure($form_html)) {
				displayError("Could not redirect to server: " . $form_html->message);
			} else {
				$page_contents = array(
					 "<html><head><title>",
					 "OpenID transaction in progress",
					 "</title></head>",
					 "<body onload='document.getElementById(\"".$form_id."\").submit()'>",
					 $form_html,
					 "<p>Click &quot;Continue&quot; to login. You are only seeing this because you appear to have JavaScript disabled.</p>",
					 "</body></html>");

				print implode("\n", $page_contents);
			}
		}
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
	public static function getLoginForm(Controller $controller) {
		return Object::create("OpenIDLoginForm", $controller, "LoginForm");
	}


  /**
   * Get the name of the authentication method
   *
   * @return string Returns the name of the authentication method.
   */
  public static function getName() {
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
	 */
	function run($requestParams) {
		parent::init();

		if(isset($_GET['debug_profile']))
			Profiler::mark("OpenIDAuthenticator_Controller");


		/**
		 * This is where the example will store its OpenID information.
		 * You should change this path if you want the example store to be
		 * created elsewhere.  After you're done playing with the example
		 * script, you'll have to remove this directory manually.
		 */
		$store_path = TEMP_FOLDER;

		if(!file_exists($store_path) && !mkdir($store_path)) {
			print "Could not create the FileStore directory '$store_path'. ".
					" Please check the effective permissions.";
			exit(0);
		}
		$store = new Auth_OpenID_FileStore($store_path);

		$consumer = new Auth_OpenID_Consumer($store, new SessionWrapper());



		// Complete the authentication process using the server's response.
		$response = $consumer->complete();

		if($response->status == Auth_OpenID_CANCEL) {
			Session::set("Security.Message.message",
									 "The verification was cancelled. Please try again.");
			Session::set("Security.Message.type", "bad");

			if(isset($_GET['debug_profile']))
				Profiler::unmark("OpenIDAuthenticator_Controller");

			Director::redirect("Security/login");

		} else if($response->status == Auth_OpenID_FAILURE) {
			Session::set("Security.Message.message", // use $response->message ??
									 "The OpenID/i-name authentication failed.");
			Session::set("Security.Message.type", "bad");

			if(isset($_GET['debug_profile']))
				Profiler::unmark("OpenIDAuthenticator_Controller");

			Director::redirect("Security/login");

		} else if($response->status == Auth_OpenID_SUCCESS) {
			$openid = $response->identity_url;
			$user = $openid;

			if($response->endpoint->canonicalID) {
				$user = $response->endpoint->canonicalID;
			}


			if(isset($_GET['debug_profile']))
				Profiler::unmark("OpenIDAuthenticator_Controller");


			$SQL_user = Convert::raw2sql($user);
			if($member = DataObject::get_one("Member", "Email = '$SQL_user'")) {
				$firstname = Convert::raw2xml($member->FirstName);
				Session::set("Security.Message.message", "Welcome Back, {$firstname}");
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
										 "Login failed. Please try again.");
				Session::set("Security.Message.type", "bad");

				if($badLoginURL = Session::get("BadLoginURL")) {
					Director::redirect($badLoginURL);
				} else {
					Director::redirectBack();
				}
			}
		}
	}


	/**
	 * Helper function to set a session message for the OpenID login form
	 *
	 * @param string $message Message to store
	 * @param string $type Message type (e.g. "good" or "bad")
	 */
	function sessionMessage($message, $type) {
		Session::set("FormInfo.OpenIDLoginForm_LoginForm.formError.message", $message);
		Session::set("'FormInfo.OpenIDLoginForm_LoginForm.formError.type", $type);
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