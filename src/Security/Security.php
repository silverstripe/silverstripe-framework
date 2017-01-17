<?php

namespace SilverStripe\Security;

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;
use SilverStripe\View\TemplateGlobalProvider;
use Exception;
use Subsite;

/**
 * Implements a basic security model
 */
class Security extends Controller implements TemplateGlobalProvider
{

    private static $allowed_actions = array(
        'index',
        'login',
        'logout',
        'basicauthlogin',
        'lostpassword',
        'passwordsent',
        'changepassword',
        'ping',
        'LoginForm',
        'ChangePasswordForm',
        'LostPasswordForm',
    );

    /**
     * Default user name. Only used in dev-mode by {@link setDefaultAdmin()}
     *
     * @var string
     * @see setDefaultAdmin()
     */
    protected static $default_username;

    /**
     * Default password. Only used in dev-mode by {@link setDefaultAdmin()}
     *
     * @var string
     * @see setDefaultAdmin()
     */
    protected static $default_password;

    /**
     * If set to TRUE to prevent sharing of the session across several sites
     * in the domain.
     *
     * @config
     * @var bool
     */
    protected static $strict_path_checking = false;

    /**
     * The password encryption algorithm to use by default.
     * This is an arbitrary code registered through {@link PasswordEncryptor}.
     *
     * @config
     * @var string
     */
    private static $password_encryption_algorithm = 'blowfish';

    /**
     * Showing "Remember me"-checkbox
     * on loginform, and saving encrypted credentials to a cookie.
     *
     * @config
     * @var bool
     */
    private static $autologin_enabled = true;

    /**
     * Determine if login username may be remembered between login sessions
     * If set to false this will disable autocomplete and prevent username persisting in the session
     *
     * @config
     * @var bool
     */
    private static $remember_username = true;

    /**
     * Location of word list to use for generating passwords
     *
     * @config
     * @var string
     */
    private static $word_list = './wordlist.txt';

    /**
     * @config
     * @var string
     */
    private static $template = 'BlankPage';

    /**
     * Template thats used to render the pages.
     *
     * @var string
     * @config
     */
    private static $template_main = 'Page';

    /**
     * Class to use for page rendering
     *
     * @var string
     * @config
     */
    private static $page_class = 'Page';

    /**
     * Default message set used in permission failures.
     *
     * @config
     * @var array|string
     */
    private static $default_message_set;

    /**
     * Random secure token, can be used as a crypto key internally.
     * Generate one through 'sake dev/generatesecuretoken'.
     *
     * @config
     * @var String
     */
    private static $token;

    /**
     * The default login URL
     *
     * @config
     *
     * @var string
     */
    private static $login_url = "Security/login";

    /**
     * The default logout URL
     *
     * @config
     *
     * @var string
     */
    private static $logout_url = "Security/logout";

    /**
     * The default lost password URL
     *
     * @config
     *
     * @var string
     */
    private static $lost_password_url = "Security/lostpassword";

    /**
     * Value of X-Frame-Options header
     *
     * @config
     * @var string
     */
    private static $frame_options = 'SAMEORIGIN';

    /**
     * Value of the X-Robots-Tag header (for the Security section)
     *
     * @config
     * @var string
     */
    private static $robots_tag = 'noindex, nofollow';


    /**
     * Get location of word list file
     *
     * @deprecated 4.0 Use the "Security.word_list" config setting instead
     */
    public static function get_word_list()
    {
        Deprecation::notice('4.0', 'Use the "Security.word_list" config setting instead');
        return self::config()->word_list;
    }

    /**
     * Enable or disable recording of login attempts
     * through the {@link LoginRecord} object.
     *
     * @config
     * @var boolean $login_recording
     */
    private static $login_recording = false;

    /**
     * @var boolean If set to TRUE or FALSE, {@link database_is_ready()}
     * will always return FALSE. Used for unit testing.
     */
    static $force_database_is_ready = null;

    /**
     * When the database has once been verified as ready, it will not do the
     * checks again.
     *
     * @var bool
     */
    static $database_is_ready = false;

    /**
     * Set location of word list file
     *
     * @deprecated 4.0 Use the "Security.word_list" config setting instead
     * @param string $wordListFile Location of word list file
     */
    public static function set_word_list($wordListFile)
    {
        Deprecation::notice('4.0', 'Use the "Security.word_list" config setting instead');
        self::config()->word_list = $wordListFile;
    }

    /**
     * Set the default message set used in permissions failures.
     *
     * @deprecated 4.0 Use the "Security.default_message_set" config setting instead
     * @param string|array $messageSet
     */
    public static function set_default_message_set($messageSet)
    {
        Deprecation::notice('4.0', 'Use the "Security.default_message_set" config setting instead');
        self::config()->default_message_set = $messageSet;
    }


    /**
     * Register that we've had a permission failure trying to view the given page
     *
     * This will redirect to a login page.
     * If you don't provide a messageSet, a default will be used.
     *
     * @param Controller $controller The controller that you were on to cause the permission
     *                               failure.
     * @param string|array $messageSet The message to show to the user. This
     *                                 can be a string, or a map of different
     *                                 messages for different contexts.
     *                                 If you pass an array, you can use the
     *                                 following keys:
     *                                   - default: The default message
     *                                   - alreadyLoggedIn: The message to
     *                                                      show if the user
     *                                                      is already logged
     *                                                      in and lacks the
     *                                                      permission to
     *                                                      access the item.
     *
     * The alreadyLoggedIn value can contain a '%s' placeholder that will be replaced with a link
     * to log in.
     * @return HTTPResponse
     */
    public static function permissionFailure($controller = null, $messageSet = null)
    {
        self::set_ignore_disallowed_actions(true);

        if (!$controller) {
            $controller = Controller::curr();
        }

        if (Director::is_ajax()) {
            $response = ($controller) ? $controller->getResponse() : new HTTPResponse();
            $response->setStatusCode(403);
            if (!Member::currentUser()) {
                $response->setBody(_t('ContentController.NOTLOGGEDIN', 'Not logged in'));
                $response->setStatusDescription(_t('ContentController.NOTLOGGEDIN', 'Not logged in'));
                // Tell the CMS to allow re-aunthentication
                if (CMSSecurity::enabled()) {
                    $response->addHeader('X-Reauthenticate', '1');
                }
            }
            return $response;
        }

        // Prepare the messageSet provided
        if (!$messageSet) {
            if ($configMessageSet = static::config()->get('default_message_set')) {
                $messageSet = $configMessageSet;
            } else {
                $messageSet = array(
                    'default' => _t(
                        'Security.NOTEPAGESECURED',
                        "That page is secured. Enter your credentials below and we will send "
                            . "you right along."
                    ),
                    'alreadyLoggedIn' => _t(
                        'Security.ALREADYLOGGEDIN',
                        "You don't have access to this page.  If you have another account that "
                            . "can access that page, you can log in again below.",
                        "%s will be replaced with a link to log in."
                    )
                );
            }
        }

        if (!is_array($messageSet)) {
            $messageSet = array('default' => $messageSet);
        }

        $member = Member::currentUser();

        // Work out the right message to show
        if ($member && $member->exists()) {
            $response = ($controller) ? $controller->getResponse() : new HTTPResponse();
            $response->setStatusCode(403);

            //If 'alreadyLoggedIn' is not specified in the array, then use the default
            //which should have been specified in the lines above
            if (isset($messageSet['alreadyLoggedIn'])) {
                $message = $messageSet['alreadyLoggedIn'];
            } else {
                $message = $messageSet['default'];
            }

            // Somewhat hackish way to render a login form with an error message.
            $me = new Security();
            $form = $me->LoginForm();
            $form->sessionMessage($message, ValidationResult::TYPE_WARNING);
            Session::set('MemberLoginForm.force_message', 1);
            $loginResponse = $me->login();
            if ($loginResponse instanceof HTTPResponse) {
                return $loginResponse;
            }

            $response->setBody((string)$loginResponse);

            $controller->extend('permissionDenied', $member);

            return $response;
        } else {
            $message = $messageSet['default'];
        }

        static::setLoginMessage($message, ValidationResult::TYPE_WARNING);

        Session::set("BackURL", $_SERVER['REQUEST_URI']);

        // TODO AccessLogEntry needs an extension to handle permission denied errors
        // Audit logging hook
        $controller->extend('permissionDenied', $member);

        return $controller->redirect(Controller::join_links(
            static::config()->get('login_url'),
            "?BackURL=" . urlencode($_SERVER['REQUEST_URI'])
        ));
    }

    protected function init()
    {
        parent::init();

        // Prevent clickjacking, see https://developer.mozilla.org/en-US/docs/HTTP/X-Frame-Options
        if ($this->config()->frame_options) {
            $this->getResponse()->addHeader('X-Frame-Options', $this->config()->frame_options);
        }

        // Prevent search engines from indexing the login page
        if ($this->config()->robots_tag) {
            $this->getResponse()->addHeader('X-Robots-Tag', $this->config()->robots_tag);
        }
    }

    public function index()
    {
        return $this->httpError(404); // no-op
    }

    /**
     * Get the selected authenticator for this request
     *
     * @return string Class name of Authenticator
     */
    protected function getAuthenticator()
    {
        $authenticator = $this->getRequest()->requestVar('AuthenticationMethod');
        if ($authenticator) {
            $authenticators = Authenticator::get_authenticators();
            if (in_array($authenticator, $authenticators)) {
                return $authenticator;
            }
        }
        return Authenticator::get_default_authenticator();
    }

    /**
     * Get the login form to process according to the submitted data
     *
     * @return Form
     * @throws Exception
     */
    public function LoginForm()
    {
        $authenticator = $this->getAuthenticator();
        if ($authenticator) {
            return $authenticator::get_login_form($this);
        }
        throw new Exception('Passed invalid authentication method');
    }

    /**
     * Get the login forms for all available authentication methods
     *
     * @return array Returns an array of available login forms (array of Form
     *               objects).
     *
     * @todo Check how to activate/deactivate authentication methods
     */
    public function GetLoginForms()
    {
        $forms = array();

        $authenticators = Authenticator::get_authenticators();
        foreach ($authenticators as $authenticator) {
            $forms[] = $authenticator::get_login_form($this);
        }

        return $forms;
    }


    /**
     * Get a link to a security action
     *
     * @param string $action Name of the action
     * @return string Returns the link to the given action
     */
    public function Link($action = null)
    {
        /** @skipUpgrade */
        return Controller::join_links(Director::baseURL(), "Security", $action);
    }

    /**
     * This action is available as a keep alive, so user
     * sessions don't timeout. A common use is in the admin.
     */
    public function ping()
    {
        return 1;
    }

    /**
     * Log the currently logged in user out
     *
     * @param bool $redirect Redirect the user back to where they came.
     *                       - If it's false, the code calling logout() is
     *                         responsible for sending the user where-ever
     *                         they should go.
     */
    public function logout($redirect = true)
    {
        $member = Member::currentUser();
        if ($member) {
            $member->logOut();
        }

        if ($redirect && (!$this->getResponse()->isFinished())) {
            $this->redirectBack();
        }
    }

    /**
     * Perform pre-login checking and prepare a response if available prior to login
     *
     * @return HTTPResponse Substitute response object if the login process should be curcumvented.
     * Returns null if should proceed as normal.
     */
    protected function preLogin()
    {
        // Event handler for pre-login, with an option to let it break you out of the login form
        $eventResults = $this->extend('onBeforeSecurityLogin');
        // If there was a redirection, return
        if ($this->redirectedTo()) {
            return $this->getResponse();
        }
        // If there was an HTTPResponse object returned, then return that
        if ($eventResults) {
            foreach ($eventResults as $result) {
                if ($result instanceof HTTPResponse) {
                    return $result;
                }
            }
        }

        // If arriving on the login page already logged in, with no security error, and a ReturnURL then redirect
        // back. The login message check is neccesary to prevent infinite loops where BackURL links to
        // an action that triggers Security::permissionFailure.
        // This step is necessary in cases such as automatic redirection where a user is authenticated
        // upon landing on an SSL secured site and is automatically logged in, or some other case
        // where the user has permissions to continue but is not given the option.
        if ($this->getRequest()->requestVar('BackURL')
            && !$this->getLoginMessage()
            && ($member = Member::currentUser())
            && $member->exists()
        ) {
            return $this->redirectBack();
        }

        return null;
    }

    /**
     * Prepare the controller for handling the response to this request
     *
     * @param string $title Title to use
     * @return Controller
     */
    protected function getResponseController($title)
    {
        if (!class_exists('SilverStripe\\CMS\\Model\\SiteTree')) {
            return $this;
        }

        // Use the default setting for which Page to use to render the security page
        $pageClass = $this->stat('page_class');
        $tmpPage = new $pageClass;
        $tmpPage->Title = $title;
        /** @skipUpgrade */
        $tmpPage->URLSegment = 'Security';
        // Disable ID-based caching  of the log-in page by making it a random number
        $tmpPage->ID = -1 * rand(1, 10000000);

        $controllerClass = $tmpPage->getControllerName();
        $controller = $controllerClass::create($tmpPage);
        $controller->setDataModel($this->model);
        $controller->doInit();
        return $controller;
    }

    /**
     * Combine the given forms into a formset with a tabbed interface
     *
     * @param array $forms List of LoginForm instances
     * @return string
     */
    protected function generateLoginFormSet($forms)
    {
        $viewData = new ArrayData(array(
            'Forms' => new ArrayList($forms),
        ));
        return $viewData->renderWith(
            $this->getTemplatesFor('MultiAuthenticatorLogin')
        );
    }

    /**
     * Get the HTML Content for the $Content area during login
     *
     * @param string &$messageType Type of message, if available, passed back to caller
     * @return string Message in HTML format
     */
    protected function getLoginMessage(&$messageType = null)
    {
        $message = Session::get('Security.Message.message');
        $messageType = null;
        if (empty($message)) {
            return null;
        }

        $messageType = Session::get('Security.Message.type');
        $messageCast = Session::get('Security.Message.cast');
        if ($messageCast !== ValidationResult::CAST_HTML) {
            $message = Convert::raw2xml($message);
        }
        return sprintf('<p class="message %s">%s</p>', Convert::raw2att($messageType), $message);
    }

    /**
     * Set the next message to display for the security login page. Defaults to warning
     *
     * @param string $message Message
     * @param string $messageType Message type. One of ValidationResult::TYPE_*
     * @param string $messageCast Message cast. One of ValidationResult::CAST_*
     */
    public static function setLoginMessage(
        $message,
        $messageType = ValidationResult::TYPE_WARNING,
        $messageCast = ValidationResult::CAST_TEXT
    ) {
        Session::set("Security.Message.message", $message);
        Session::set("Security.Message.type", $messageType);
        Session::set("Security.Message.cast", $messageCast);
    }

    /**
     * Clear login message
     */
    public static function clearLoginMessage()
    {
        Session::clear("Security.Message");
    }


    /**
     * Show the "login" page
     *
     * For multiple authenticators, Security_MultiAuthenticatorLogin is used.
     * See getTemplatesFor and getIncludeTemplate for how to override template logic
     *
     * @return string|HTTPResponse Returns the "login" page as HTML code.
     */
    public function login()
    {
        // Check pre-login process
        if ($response = $this->preLogin()) {
            return $response;
        }

        // Get response handler
        $controller = $this->getResponseController(_t('Security.LOGIN', 'Log in'));

        // if the controller calls Director::redirect(), this will break early
        if (($response = $controller->getResponse()) && $response->isFinished()) {
            return $response;
        }

        $forms = $this->GetLoginForms();
        if (!count($forms)) {
            user_error(
                'No login-forms found, please use Authenticator::register_authenticator() to add one',
                E_USER_ERROR
            );
        }

        // Handle any form messages from validation, etc.
        $messageType = '';
        $message = $this->getLoginMessage($messageType);

        // We've displayed the message in the form output, so reset it for the next run.
        static::clearLoginMessage();

        // only display tabs when more than one authenticator is provided
        // to save bandwidth and reduce the amount of custom styling needed
        if (count($forms) > 1) {
            $content = $this->generateLoginFormSet($forms);
        } else {
            $content = $forms[0]->forTemplate();
        }

        // Finally, customise the controller to add any form messages and the form.
        $customisedController = $controller->customise(array(
            "Content" => DBField::create_field('HTMLFragment', $message),
            "Message" => DBField::create_field('HTMLFragment', $message),
            "MessageType" => $messageType,
            "Form" => $content,
        ));

        // Return the customised controller
        return $customisedController->renderWith(
            $this->getTemplatesFor('login')
        );
    }

    public function basicauthlogin()
    {
        $member = BasicAuth::requireLogin("SilverStripe login", 'ADMIN');
        $member->logIn();
    }

    /**
     * Show the "lost password" page
     *
     * @return string Returns the "lost password" page as HTML code.
     */
    public function lostpassword()
    {
        $controller = $this->getResponseController(_t('Security.LOSTPASSWORDHEADER', 'Lost Password'));

        // if the controller calls Director::redirect(), this will break early
        if (($response = $controller->getResponse()) && $response->isFinished()) {
            return $response;
        }

        $customisedController = $controller->customise(array(
            'Content' =>
                '<p>' .
                _t(
                    'Security.NOTERESETPASSWORD',
                    'Enter your e-mail address and we will send you a link with which you can reset your password'
                ) .
                '</p>',
            'Form' => $this->LostPasswordForm(),
        ));

        //Controller::$currentController = $controller;
        return $customisedController->renderWith($this->getTemplatesFor('lostpassword'));
    }


    /**
     * Factory method for the lost password form
     *
     * @skipUpgrade
     * @return Form Returns the lost password form
     */
    public function LostPasswordForm()
    {
        return MemberLoginForm::create(
            $this,
            'LostPasswordForm',
            new FieldList(
                new EmailField('Email', _t('Member.EMAIL', 'Email'))
            ),
            new FieldList(
                new FormAction(
                    'forgotPassword',
                    _t('Security.BUTTONSEND', 'Send me the password reset link')
                )
            ),
            false
        );
    }


    /**
     * Show the "password sent" page, after a user has requested
     * to reset their password.
     *
     * @param HTTPRequest $request The HTTPRequest for this action.
     * @return string Returns the "password sent" page as HTML code.
     */
    public function passwordsent($request)
    {
        $controller = $this->getResponseController(_t('Security.LOSTPASSWORDHEADER', 'Lost Password'));

        // if the controller calls Director::redirect(), this will break early
        if (($response = $controller->getResponse()) && $response->isFinished()) {
            return $response;
        }

        $email = Convert::raw2xml(rawurldecode($request->param('ID')) . '.' . $request->getExtension());

        $customisedController = $controller->customise(array(
            'Title' => _t(
                'Security.PASSWORDSENTHEADER',
                "Password reset link sent to '{email}'",
                array('email' => $email)
            ),
            'Content' =>
                "<p>"
                . _t(
                    'Security.PASSWORDSENTTEXT',
                    "Thank you! A reset link has been sent to '{email}', provided an account exists for this email"
                    . " address.",
                    array('email' => $email)
                )
                . "</p>",
            'Email' => $email
        ));

        //Controller::$currentController = $controller;
        return $customisedController->renderWith($this->getTemplatesFor('passwordsent'));
    }


    /**
     * Create a link to the password reset form.
     *
     * GET parameters used:
     * - m: member ID
     * - t: plaintext token
     *
     * @param Member $member Member object associated with this link.
     * @param string $autologinToken The auto login token.
     * @return string
     */
    public static function getPasswordResetLink($member, $autologinToken)
    {
        $autologinToken = urldecode($autologinToken);
        $selfControllerClass = __CLASS__;
        /** @var static $selfController */
        $selfController = new $selfControllerClass();
        return $selfController->Link('changepassword') . "?m={$member->ID}&t=$autologinToken";
    }

    /**
     * Show the "change password" page.
     * This page can either be called directly by logged-in users
     * (in which case they need to provide their old password),
     * or through a link emailed through {@link lostpassword()}.
     * In this case no old password is required, authentication is ensured
     * through the Member.AutoLoginHash property.
     *
     * @see ChangePasswordForm
     *
     * @return string|HTTPRequest Returns the "change password" page as HTML code, or a redirect response
     */
    public function changepassword()
    {
        $controller = $this->getResponseController(_t('Security.CHANGEPASSWORDHEADER', 'Change your password'));

        // if the controller calls Director::redirect(), this will break early
        if (($response = $controller->getResponse()) && $response->isFinished()) {
            return $response;
        }

        // Extract the member from the URL.
        /** @var Member $member */
        $member = null;
        if (isset($_REQUEST['m'])) {
            $member = Member::get()->filter('ID', (int)$_REQUEST['m'])->first();
        }

        // Check whether we are merely changin password, or resetting.
        if (isset($_REQUEST['t']) && $member && $member->validateAutoLoginToken($_REQUEST['t'])) {
            // On first valid password reset request redirect to the same URL without hash to avoid referrer leakage.

            // if there is a current member, they should be logged out
            if ($curMember = Member::currentUser()) {
                $curMember->logOut();
            }

            // Store the hash for the change password form. Will be unset after reload within the ChangePasswordForm.
            Session::set('AutoLoginHash', $member->encryptWithUserSettings($_REQUEST['t']));

            return $this->redirect($this->Link('changepassword'));
        } elseif (Session::get('AutoLoginHash')) {
            // Subsequent request after the "first load with hash" (see previous if clause).
            $customisedController = $controller->customise(array(
                'Content' =>
                    '<p>' .
                    _t('Security.ENTERNEWPASSWORD', 'Please enter a new password.') .
                    '</p>',
                'Form' => $this->ChangePasswordForm(),
            ));
        } elseif (Member::currentUser()) {
            // Logged in user requested a password change form.
            $customisedController = $controller->customise(array(
                'Content' => '<p>'
                    . _t('Security.CHANGEPASSWORDBELOW', 'You can change your password below.') . '</p>',
                'Form' => $this->ChangePasswordForm()));
        } else {
            // Show friendly message if it seems like the user arrived here via password reset feature.
            if (isset($_REQUEST['m']) || isset($_REQUEST['t'])) {
                $customisedController = $controller->customise(
                    array('Content' =>
                        _t(
                            'Security.NOTERESETLINKINVALID',
                            '<p>The password reset link is invalid or expired.</p>'
                            . '<p>You can request a new one <a href="{link1}">here</a> or change your password after'
                            . ' you <a href="{link2}">logged in</a>.</p>',
                            array('link1' => $this->Link('lostpassword'), 'link2' => $this->Link('login'))
                        )
                    )
                );
            } else {
                return self::permissionFailure(
                    $this,
                    _t('Security.ERRORPASSWORDPERMISSION', 'You must be logged in in order to change your password!')
                );
            }
        }

        return $customisedController->renderWith($this->getTemplatesFor('changepassword'));
    }

    /**
     * Factory method for the lost password form
     *
     * @return ChangePasswordForm Returns the lost password form
     */
    public function ChangePasswordForm()
    {
        /** @skipUpgrade */
        $formName = 'ChangePasswordForm';
        return Injector::inst()->createWithArgs(
            'SilverStripe\\Security\\ChangePasswordForm',
            [ $this,  $formName]
        );
    }

    /**
     * Determine the list of templates to use for rendering the given action.
     *
     * @skipUpgrade
     * @param string $action
     * @return array Template list
     */
    public function getTemplatesFor($action)
    {
        $templates = SSViewer::get_templates_by_class(get_class($this), "_{$action}", __CLASS__);
        return array_merge(
            $templates,
            [
                "Security_{$action}",
                "Security",
                $this->stat("template_main"),
                "BlankPage"
            ]
        );
    }

    /**
     * Return an existing member with administrator privileges, or create one of necessary.
     *
     * Will create a default 'Administrators' group if no group is found
     * with an ADMIN permission. Will create a new 'Admin' member with administrative permissions
     * if no existing Member with these permissions is found.
     *
     * Important: Any newly created administrator accounts will NOT have valid
     * login credentials (Email/Password properties), which means they can't be used for login
     * purposes outside of any default credentials set through {@link Security::setDefaultAdmin()}.
     *
     * @return Member
     */
    public static function findAnAdministrator()
    {
        // coupling to subsites module
        $origSubsite = null;
        if (is_callable('Subsite::changeSubsite')) {
            $origSubsite = Subsite::currentSubsiteID();
            Subsite::changeSubsite(0);
        }

        /** @var Member $member */
        $member = null;

        // find a group with ADMIN permission
        $adminGroup = Permission::get_groups_by_permission('ADMIN')->first();

        if (is_callable('Subsite::changeSubsite')) {
            Subsite::changeSubsite($origSubsite);
        }

        if ($adminGroup) {
            $member = $adminGroup->Members()->First();
        }

        if (!$adminGroup) {
            Group::singleton()->requireDefaultRecords();
            $adminGroup = Permission::get_groups_by_permission('ADMIN')->first();
        }

        if (!$member) {
            Member::singleton()->requireDefaultRecords();
            $member = Permission::get_members_by_permission('ADMIN')->first();
        }

        if (!$member) {
            $member = Member::default_admin();
        }

        if (!$member) {
            // Failover to a blank admin
            $member = Member::create();
            $member->FirstName = _t('Member.DefaultAdminFirstname', 'Default Admin');
            $member->write();
            // Add member to group instead of adding group to member
            // This bypasses the privilege escallation code in Member_GroupSet
            $adminGroup
                ->DirectMembers()
                ->add($member);
        }

        return $member;
    }

    /**
     * Flush the default admin credentials
     */
    public static function clear_default_admin()
    {
        self::$default_username = null;
        self::$default_password = null;
    }


    /**
     * Set a default admin in dev-mode
     *
     * This will set a static default-admin which is not existing
     * as a database-record. By this workaround we can test pages in dev-mode
     * with a unified login. Submitted login-credentials are first checked
     * against this static information in {@link Security::authenticate()}.
     *
     * @param string $username The user name
     * @param string $password The password (in cleartext)
     * @return bool True if successfully set
     */
    public static function setDefaultAdmin($username, $password)
    {
        // don't overwrite if already set
        if (self::$default_username || self::$default_password) {
            return false;
        }

        self::$default_username = $username;
        self::$default_password = $password;
        return true;
    }

    /**
     * Checks if the passed credentials are matching the default-admin.
     * Compares cleartext-password set through Security::setDefaultAdmin().
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public static function check_default_admin($username, $password)
    {
        return (
            self::$default_username === $username
            && self::$default_password === $password
            && self::has_default_admin()
        );
    }

    /**
     * Check that the default admin account has been set.
     */
    public static function has_default_admin()
    {
        return !empty(self::$default_username) && !empty(self::$default_password);
    }

    /**
     * Get default admin username
     *
     * @return string
     */
    public static function default_admin_username()
    {
        return self::$default_username;
    }

    /**
     * Get default admin password
     *
     * @return string
     */
    public static function default_admin_password()
    {
        return self::$default_password;
    }

    /**
     * Encrypt a password according to the current password encryption settings.
     * If the settings are so that passwords shouldn't be encrypted, the
     * result is simple the clear text password with an empty salt except when
     * a custom algorithm ($algorithm parameter) was passed.
     *
     * @param string $password The password to encrypt
     * @param string $salt Optional: The salt to use. If it is not passed, but
     *  needed, the method will automatically create a
     *  random salt that will then be returned as return value.
     * @param string $algorithm Optional: Use another algorithm to encrypt the
     *  password (so that the encryption algorithm can be changed over the time).
     * @param Member $member Optional
     * @return mixed Returns an associative array containing the encrypted
     *  password and the used salt in the form:
     * <code>
     *  array(
     *  'password' => string,
     *  'salt' => string,
     *  'algorithm' => string,
     *  'encryptor' => PasswordEncryptor instance
     *  )
     * </code>
     * If the passed algorithm is invalid, FALSE will be returned.
     *
     * @see encrypt_passwords()
     */
    public static function encrypt_password($password, $salt = null, $algorithm = null, $member = null)
    {
        // Fall back to the default encryption algorithm
        if (!$algorithm) {
            $algorithm = self::config()->password_encryption_algorithm;
        }

        $e = PasswordEncryptor::create_for_algorithm($algorithm);

        // New salts will only need to be generated if the password is hashed for the first time
        $salt = ($salt) ? $salt : $e->salt($password);

        return array(
            'password' => $e->encrypt($password, $salt, $member),
            'salt' => $salt,
            'algorithm' => $algorithm,
            'encryptor' => $e
        );
    }

    /**
     * Checks the database is in a state to perform security checks.
     * See {@link DatabaseAdmin->init()} for more information.
     *
     * @return bool
     */
    public static function database_is_ready()
    {
        // Used for unit tests
        if (self::$force_database_is_ready !== null) {
            return self::$force_database_is_ready;
        }

        if (self::$database_is_ready) {
            return self::$database_is_ready;
        }

        $requiredClasses = ClassInfo::dataClassesFor(Member::class);
        $requiredClasses[] = Group::class;
        $requiredClasses[] = Permission::class;
        $schema = DataObject::getSchema();
        foreach ($requiredClasses as $class) {
            // Skip test classes, as not all test classes are scaffolded at once
            if (is_a($class, TestOnly::class, true)) {
                continue;
            }

            // if any of the tables aren't created in the database
            $table = $schema->tableName($class);
            if (!ClassInfo::hasTable($table)) {
                return false;
            }

            // HACK: DataExtensions aren't applied until a class is instantiated for
            // the first time, so create an instance here.
            singleton($class);

            // if any of the tables don't have all fields mapped as table columns
            $dbFields = DB::field_list($table);
            if (!$dbFields) {
                return false;
            }

            $objFields = $schema->databaseFields($class, false);
            $missingFields = array_diff_key($objFields, $dbFields);

            if ($missingFields) {
                return false;
            }
        }
        self::$database_is_ready = true;

        return true;
    }

    /**
     * Enable or disable recording of login attempts
     * through the {@link LoginRecord} object.
     *
     * @deprecated 4.0 Use the "Security.login_recording" config setting instead
     * @param boolean $bool
     */
    public static function set_login_recording($bool)
    {
        Deprecation::notice('4.0', 'Use the "Security.login_recording" config setting instead');
        self::$login_recording = (bool)$bool;
    }

    /**
     * @deprecated 4.0 Use the "Security.login_recording" config setting instead
     * @return boolean
     */
    public static function login_recording()
    {
        Deprecation::notice('4.0', 'Use the "Security.login_recording" config setting instead');
        return self::$login_recording;
    }

    /**
     * @config
     * @var string Set the default login dest
     * This is the URL that users will be redirected to after they log in,
     * if they haven't logged in en route to access a secured page.
     * By default, this is set to the homepage.
     */
    private static $default_login_dest = "";

    protected static $ignore_disallowed_actions = false;

    /**
     * Set to true to ignore access to disallowed actions, rather than returning permission failure
     * Note that this is just a flag that other code needs to check with Security::ignore_disallowed_actions()
     * @param $flag True or false
     */
    public static function set_ignore_disallowed_actions($flag)
    {
        self::$ignore_disallowed_actions = $flag;
    }

    public static function ignore_disallowed_actions()
    {
        return self::$ignore_disallowed_actions;
    }

    /**
     * Get the URL of the log-in page.
     *
     * To update the login url use the "Security.login_url" config setting.
     *
     * @return string
     */
    public static function login_url()
    {
        return Controller::join_links(Director::baseURL(), self::config()->login_url);
    }


    /**
     * Get the URL of the logout page.
     *
     * To update the logout url use the "Security.logout_url" config setting.
     *
     * @return string
     */
    public static function logout_url()
    {
        return Controller::join_links(Director::baseURL(), self::config()->logout_url);
    }

    /**
     * Get the URL of the logout page.
     *
     * To update the logout url use the "Security.logout_url" config setting.
     *
     * @return string
     */
    public static function lost_password_url()
    {
        return Controller::join_links(Director::baseURL(), self::config()->lost_password_url);
    }

    /**
     * Defines global accessible templates variables.
     *
     * @return array
     */
    public static function get_template_global_variables()
    {
        return array(
            "LoginURL" => "login_url",
            "LogoutURL" => "logout_url",
            "LostPasswordURL" => "lost_password_url",
        );
    }
}
