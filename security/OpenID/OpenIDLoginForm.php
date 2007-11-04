<?php

/**
 * OpenID log-in form
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */



/**
 * OpenID log-in form
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */
class OpenIDLoginForm extends LoginForm {

	/**
	 * Constructor
	 *
	 * @param Controller $controller The parent controller, necessary to
	 *                               create the appropriate form action tag.
	 * @param string $name The method on the controller that will return this
	 *                     form object.
	 * @param FieldSet|FormField $fields All of the fields in the form - a
	 *                                   {@link FieldSet} of {@link FormField}
	 *                                   objects.
	 * @param FieldSet|FormAction $actions All of the action buttons in the
	 *                                     form - a {@link FieldSet} of
	 *                                     {@link FormAction} objects
	 * @param bool $checkCurrentUser If set to TRUE, it will be checked if a
	 *                               the user is currently logged in, and if
	 *                               so, only a logout button will be rendered
	 */
  function __construct($controller, $name, $fields = null, $actions = null,
                       $checkCurrentUser = true) {

		$this->authenticator_class = 'OpenIDAuthenticator';

		Requirements::themedCSS('openid_login');

		if(isset($_REQUEST['BackURL'])) {
			$backURL = $_REQUEST['BackURL'];
		} else {
			$backURL = Session::get('BackURL');
		}

    if($checkCurrentUser && Member::currentUserID()) {
      $fields = new FieldSet();
      $actions = new FieldSet(new FormAction("logout", _t('Member.BUTTONLOGINOTHER')));
    } else {
      if(!$fields) {
        $fields = new FieldSet(
					new LiteralField("OpenIDDescription", 
						_t('OpenIDLoginForm.DESC', 
							'<div id="OpenIDDescription"><p>OpenID is an Internet-wide identity system
		  					that allows you to sign in to many websites with a single account.
							For more information visit <a href="http://openid.net">openid.net</a>.</p></div>
						')
					),
          new HiddenField("AuthenticationMethod", null,
													$this->authenticator_class, $this),
          new TextField("OpenIDURL", _t('OpenIDLoginForm.URL', "OpenID URL"),
						Session::get('SessionForms.OpenIDLoginForm.OpenIDURL'), null, $this),
          new CheckboxField("Remember", _t('Member.REMEMBERME'),
						Session::get('SessionForms.OpenIDLoginForm.Remember'), $this)
        );
      }
      if(!$actions) {
        $actions = new FieldSet(
          new FormAction("dologin", _t('Member.BUTTONLOGIN'))
        );
      }
    }

		if(isset($backURL)) {
			$fields->push(new HiddenField('BackURL', 'BackURL', $backURL));
		}

		parent::__construct($controller, $name, $fields, $actions);
  }


	/**
	 * Get message from session
	 */
  protected function getMessageFromSession() {
    parent::getMessageFromSession();
    if(($member = Member::currentUser()) &&
         !Session::get('OpenIDLoginForm.force_message')) {
      $this->message = sprintf(_t('Member.LOGGEDINAS'), $member->FirstName);
    }
    Session::set('OpenIDLoginForm.force_message', false);
  }


	/**
	 * Login form handler method
	 *
	 * This method is called when the user clicks on "Log in"
	 *
	 * @param array $data Submitted data
	 */
  public function dologin($data) {
		Session::set('SessionForms.OpenIDLoginForm.Remember',
								 isset($data['Remember']));

		OpenIDAuthenticator::authenticate($data, $this);

		// If the OpenID authenticator returns, an error occured!
		Session::set('SessionForms.OpenIDLoginForm.OpenIDURL', $data['OpenIDURL']);
		
		if(isset($_REQUEST['BackURL']) && $backURL = $_REQUEST['BackURL']) {
			Session::set('BackURL', $backURL);
		}

		if($badLoginURL = Session::get("BadLoginURL")){
			Director::redirect($badLoginURL);
		} else {
			// Show the right tab on failed login
			Director::redirect(Director::absoluteURL(Security::Link("login")) . '#' . $this->FormName() .'_tab');
		}
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

}


?>
