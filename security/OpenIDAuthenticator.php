<?php

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
	 * @return bool|Member Returns FALSE if authentication fails, otherwise
	 *                     the member object
	 */
	public function authenticate(array $RAW_data) {
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
			displayError("Authentication error; not a valid OpenID.");
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
					 "</body></html>");

				print implode("\n", $page_contents);
			}
		}
		exit();
	}


	/**
	 * Method that creates the login form for this authentication method
	 *
	 * @return Form Returns the login form to use with this authentication
	 *              method
	 */
	public function getLoginForm() {
		return Object::create("OpenIDLoginForm", $this, "LoginForm");
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

		if(isset($_GET['debug_profile'])) Profiler::mark("OpenIDAuthenticator_Controller");

		die("Not implemented yet!");

		if(isset($_GET['debug_profile'])) Profiler::unmark("OpenIDAuthenticator_Controller");
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
class SessionWrapper {

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