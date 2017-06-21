<?php

namespace SilverStripe\Security;

use LogicException;
use Page;
use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Control\Session;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;
use SilverStripe\View\TemplateGlobalProvider;

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
    );

    /**
     * If set to TRUE to prevent sharing of the session across several sites
     * in the domain.
     *
     * @config
     * @var bool
     */
    private static $strict_path_checking = false;

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
     * If set to false this will disable auto-complete and prevent username persisting in the session
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
     * Template that is used to render the pages.
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
    private static $page_class = Page::class;

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
    private static $login_url = 'Security/login';

    /**
     * The default logout URL
     *
     * @config
     *
     * @var string
     */
    private static $logout_url = 'Security/logout';

    /**
     * The default lost password URL
     *
     * @config
     *
     * @var string
     */
    private static $lost_password_url = 'Security/lostpassword';

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
    protected static $force_database_is_ready;

    /**
     * When the database has once been verified as ready, it will not do the
     * checks again.
     *
     * @var bool
     */
    protected static $database_is_ready = false;

    /**
     * @var Authenticator[] available authenticators
     */
    private $authenticators = [];

    /**
     * @var Member Currently logged in user (if available)
     */
    protected static $currentUser;

    /**
     * @return Authenticator[]
     */
    public function getAuthenticators()
    {
        return $this->authenticators;
    }

    /**
     * @param Authenticator[] $authenticators
     */
    public function setAuthenticators(array $authenticators)
    {
        $this->authenticators = $authenticators;
    }

    protected function init()
    {
        parent::init();

        // Prevent clickjacking, see https://developer.mozilla.org/en-US/docs/HTTP/X-Frame-Options
        $frameOptions = static::config()->get('frame_options');
        if ($frameOptions) {
            $this->getResponse()->addHeader('X-Frame-Options', $frameOptions);
        }

        // Prevent search engines from indexing the login page
        $robotsTag = static::config()->get('robots_tag');
        if ($robotsTag) {
            $this->getResponse()->addHeader('X-Robots-Tag', $robotsTag);
        }
    }

    public function index()
    {
        $this->httpError(404); // no-op
    }

    /**
     * Get the selected authenticator for this request
     *
     * @param string $name The identifier of the authenticator in your config
     * @return Authenticator Class name of Authenticator
     * @throws LogicException
     */
    protected function getAuthenticator($name = 'default')
    {
        $authenticators = $this->authenticators;

        if (isset($authenticators[$name])) {
            return $authenticators[$name];
        }

        throw new LogicException('No valid authenticator found');
    }

    /**
     * Get all registered authenticators
     *
     * @param int $service The type of service that is requested
     * @return Authenticator[] Return an array of Authenticator objects
     */
    public function getApplicableAuthenticators($service = Authenticator::LOGIN)
    {
        $authenticators = $this->getAuthenticators();

        /** @var Authenticator $authenticator */
        foreach ($authenticators as $name => $authenticator) {
            if (!($authenticator->supportedServices() & $service)) {
                unset($authenticators[$name]);
            }
        }

        if (empty($authenticators)) {
            throw new LogicException('No applicable authenticators found');
        }

        return $authenticators;
    }

    /**
     * Check if a given authenticator is registered
     *
     * @param string $authenticator The configured identifier of the authenicator
     * @return bool Returns TRUE if the authenticator is registered, FALSE
     *              otherwise.
     */
    public function hasAuthenticator($authenticator)
    {
        $authenticators = $this->authenticators;

        return !empty($authenticators[$authenticator]);
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

        if (!$controller && Controller::has_curr()) {
            $controller = Controller::curr();
        }

        if (Director::is_ajax()) {
            $response = ($controller) ? $controller->getResponse() : new HTTPResponse();
            $response->setStatusCode(403);
            if (!static::getCurrentUser()) {
                $response->setBody(
                    _t('SilverStripe\\CMS\\Controllers\\ContentController.NOTLOGGEDIN', 'Not logged in')
                );
                $response->setStatusDescription(
                    _t('SilverStripe\\CMS\\Controllers\\ContentController.NOTLOGGEDIN', 'Not logged in')
                );
                // Tell the CMS to allow re-authentication
                if (CMSSecurity::singleton()->enabled()) {
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
                    'default'         => _t(
                        'SilverStripe\\Security\\Security.NOTEPAGESECURED',
                        "That page is secured. Enter your credentials below and we will send "
                        . "you right along."
                    ),
                    'alreadyLoggedIn' => _t(
                        'SilverStripe\\Security\\Security.ALREADYLOGGEDIN',
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

        $member = static::getCurrentUser();

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

            static::singleton()->setLoginMessage($message, ValidationResult::TYPE_WARNING);
            $loginResponse = static::singleton()->login($controller ? $controller->getRequest() : $controller);
            if ($loginResponse instanceof HTTPResponse) {
                return $loginResponse;
            }

            $response->setBody((string)$loginResponse);

            $controller->extend('permissionDenied', $member);

            return $response;
        } else {
            $message = $messageSet['default'];
        }

        static::singleton()->setLoginMessage($message, ValidationResult::TYPE_WARNING);

        $controller->getRequest()->getSession()->set("BackURL", $_SERVER['REQUEST_URI']);

        // TODO AccessLogEntry needs an extension to handle permission denied errors
        // Audit logging hook
        $controller->extend('permissionDenied', $member);

        return $controller->redirect(Controller::join_links(
            Security::config()->uninherited('login_url'),
            "?BackURL=" . urlencode($_SERVER['REQUEST_URI'])
        ));
    }

    /**
     * @param null|Member $currentUser
     */
    public static function setCurrentUser($currentUser = null)
    {
        self::$currentUser = $currentUser;
    }

    /**
     * @return null|Member
     */
    public static function getCurrentUser()
    {
        return self::$currentUser;
    }

    /**
     * Get the login forms for all available authentication methods
     *
     * @deprecated 5.0.0 Now handled by {@link static::delegateToMultipleHandlers}
     *
     * @return array Returns an array of available login forms (array of Form
     *               objects).
     *
     */
    public function getLoginForms()
    {
        Deprecation::notice('5.0.0', 'Now handled by delegateToMultipleHandlers');

        return array_map(
            function (Authenticator $authenticator) {
                return [
                    $authenticator->getLoginHandler($this->Link())->loginForm()
                ];
            },
            $this->getApplicableAuthenticators()
        );
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
     * Logging out without ID-parameter in the URL, will log the user out of all applicable Authenticators.
     *
     * Adding an ID will only log the user out of that Authentication method.
     *
     * Logging out of Default will <i>always</i> completely log out the user.
     *
     * @param bool $redirect Redirect the user back to where they came.
     *                       - If it's false, the code calling logout() is
     *                         responsible for sending the user where-ever
     *                         they should go.
     * @return HTTPResponse|null
     */
    public function logout($redirect = true)
    {
        $this->extend('beforeMemberLoggedOut');
        $member = static::getCurrentUser();

        if ($member) { // If we don't have a member, there's not much to log out.
            /** @var array|Authenticator[] $authenticators */
            $authenticators = $this->getApplicableAuthenticators(Authenticator::LOGOUT);

            /** @var Authenticator[] $authenticator */
            foreach ($authenticators as $name => $authenticator) {
                $handler = $authenticator->getLogOutHandler(Controller::join_links($this->Link(), 'logout'));
                $this->delegateToHandler($handler, $name);
            }
            // In the rare case, but plausible with e.g. an external IdentityStore, the user is not logged out.
            if (static::getCurrentUser() !== null) {
                $this->extend('failureMemberLoggedOut', $authenticator);

                return $this->redirectBack();
            }
            $this->extend('successMemberLoggedOut', $authenticator);
            // Member is successfully logged out. Write possible changes to the database.
            $member->write();
        }
        $this->extend('afterMemberLoggedOut');

        if ($redirect && (!$this->getResponse()->isFinished())) {
            return $this->redirectBack();
        }

        return null;
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
        if (!$this->getLoginMessage()
            && ($member = static::getCurrentUser())
            && $member->exists()
            && $this->getRequest()->requestVar('BackURL')
        ) {
            return $this->redirectBack();
        }

        return null;
    }

    public function getRequest()
    {
        // Support Security::singleton() where a request isn't always injected
        $request = parent::getRequest();
        if ($request) {
            return $request;
        }

        if (Controller::has_curr() && Controller::curr() !== $this) {
            return Controller::curr()->getRequest();
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
        // Use the default setting for which Page to use to render the security page
        $pageClass = $this->stat('page_class');
        if (!$pageClass || !class_exists($pageClass)) {
            return $this;
        }

        // Create new instance of page holder
        /** @var Page $holderPage */
        $holderPage = Injector::inst()->create($pageClass);
        $holderPage->Title = $title;
        /** @skipUpgrade */
        $holderPage->URLSegment = 'Security';
        // Disable ID-based caching  of the log-in page by making it a random number
        $holderPage->ID = -1 * random_int(1, 10000000);

        $controller = ModelAsController::controller_for($holderPage);
        $controller->doInit();

        return $controller;
    }

    /**
     * Combine the given forms into a formset with a tabbed interface
     *
     * @param array|Form[] $forms
     * @return string
     */
    protected function generateLoginFormSet($forms)
    {
        if (count($forms) === 1) {
            return $forms;
        }

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
        $session = $this->getRequest()->getSession();
        $message = $session->get('Security.Message.message');
        $messageType = null;
        if (empty($message)) {
            return null;
        }

        $messageType = $session->get('Security.Message.type');
        $messageCast = $session->get('Security.Message.cast');
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
    public function setLoginMessage(
        $message,
        $messageType = ValidationResult::TYPE_WARNING,
        $messageCast = ValidationResult::CAST_TEXT
    ) {
        Controller::curr()
            ->getRequest()
            ->getSession()
            ->set("Security.Message.message", $message)
            ->set("Security.Message.type", $messageType)
            ->set("Security.Message.cast", $messageCast);
    }

    /**
     * Clear login message
     */
    public static function clearLoginMessage()
    {
        Controller::curr()
            ->getRequest()
            ->getSession()
            ->clear("Security.Message");
    }


    /**
     * Show the "login" page
     *
     * For multiple authenticators, Security_MultiAuthenticatorLogin is used.
     * See getTemplatesFor and getIncludeTemplate for how to override template logic
     *
     * @param null|HTTPRequest $request
     * @param int $service
     * @return HTTPResponse|string Returns the "login" page as HTML code.
     * @throws HTTPResponse_Exception
     */
    public function login($request = null, $service = Authenticator::LOGIN)
    {
        if ($request) {
            $this->setRequest($request);
        } elseif ($request) {
            $request = $this->getRequest();
        } else {
            throw new HTTPResponse_Exception("No request available", 500);
        }

        // Check pre-login process
        if ($response = $this->preLogin()) {
            return $response;
        }
        $authName = null;

        if ($request && $request->param('ID')) {
            $authName = $request->param('ID');
        }

        $link = $this->Link('login');

        // Delegate to a single handler - Security/login/<authname>/...
        if ($authName && $this->hasAuthenticator($authName)) {
            if ($request) {
                $request->shift();
            }

            $authenticator = $this->getAuthenticator($authName);

            if (!$authenticator->supportedServices() & $service) {
                throw new HTTPResponse_Exception('Invalid Authenticator "' . $authName . '" for login action', 418);
            }

            $handlers = [$authName => $authenticator];
        } else {
            // Delegate to all of them, building a tabbed view - Security/login
            $handlers = $this->getApplicableAuthenticators($service);
        }

        array_walk(
            $handlers,
            function (Authenticator &$auth, $name) use ($link) {
                $auth = $auth->getLoginHandler(Controller::join_links($link, $name));
            }
        );

        return $this->delegateToMultipleHandlers(
            $handlers,
            _t('Security.LOGIN', 'Log in'),
            $this->getTemplatesFor('login')
        );
    }

    /**
     * Delegate to an number of handlers, extracting their forms and rendering a tabbed form-set.
     * This is used to built the log-in page where there are multiple authenticators active.
     *
     * If a single handler is passed, delegateToHandler() will be called instead
     *
     * @param array|RequestHandler[] $handlers
     * @param string $title The title of the form
     * @param array $templates
     * @return array|HTTPResponse|RequestHandler|DBHTMLText|string
     */
    protected function delegateToMultipleHandlers(array $handlers, $title, array $templates)
    {

        // Simpler case for a single authenticator
        if (count($handlers) === 1) {
            return $this->delegateToHandler(array_values($handlers)[0], $title, $templates);
        }

        // Process each of the handlers
        $results = array_map(
            function (RequestHandler $handler) {
                return $handler->handleRequest($this->getRequest());
            },
            $handlers
        );

        // Aggregate all their forms, assuming they all return
        $forms = [];
        foreach ($results as $authName => $singleResult) {
            // The result *must* be an array with a Form key
            if (!is_array($singleResult) || !isset($singleResult['Form'])) {
                user_error('Authenticator "' . $authName . '" doesn\'t support a tabbed login', E_USER_WARNING);
                continue;
            }

            $forms[] = $singleResult['Form'];
        }

        if (!$forms) {
            throw new \LogicException('No authenticators found compatible with a tabbed login');
        }

        return $this->renderWrappedController(
            $title,
            [
                'Forms' => ArrayList::create($forms),
                'Form' => $this->generateLoginFormSet($forms),
            ],
            $templates
        );
    }

    /**
     * Delegate to another RequestHandler, rendering any fragment arrays into an appropriate.
     * controller.
     *
     * @param RequestHandler $handler
     * @param string $title The title of the form
     * @param array $templates
     * @return array|HTTPResponse|RequestHandler|DBHTMLText|string
     */
    protected function delegateToHandler(RequestHandler $handler, $title, array $templates = [])
    {
        $result = $handler->handleRequest($this->getRequest());

        // Return the customised controller - used to render in a Form
        // Post requests are expected to be login posts, so they'll be handled downstairs
        if (is_array($result)) {
            $result = $this->renderWrappedController($title, $result, $templates);
        }

        return $result;
    }

    /**
     * Render the given fragments into a security page controller with the given title.
     * @param string $title string The title to give the security page
     * @param array $fragments A map of objects to render into the page, e.g. "Form"
     * @param array $templates An array of templates to use for the render
     * @return HTTPResponse|DBHTMLText
     */
    protected function renderWrappedController($title, array $fragments, array $templates)
    {
        $controller = $this->getResponseController($title);

        // if the controller calls Director::redirect(), this will break early
        if (($response = $controller->getResponse()) && $response->isFinished()) {
            return $response;
        }

        // Handle any form messages from validation, etc.
        $messageType = '';
        $message = $this->getLoginMessage($messageType);

        // We've displayed the message in the form output, so reset it for the next run.
        static::clearLoginMessage();

        if ($message) {
            $messageResult = [
                'Content'     => DBField::create_field('HTMLFragment', $message),
                'Message'     => DBField::create_field('HTMLFragment', $message),
                'MessageType' => $messageType
            ];
            $fragments = array_merge($fragments, $messageResult);
        }

        return $controller->customise($fragments)->renderWith($templates);
    }

    public function basicauthlogin()
    {
        $member = BasicAuth::requireLogin($this->getRequest(), 'SilverStripe login', 'ADMIN');
        static::setCurrentUser($member);
    }

    /**
     * Show the "lost password" page
     *
     * @return string Returns the "lost password" page as HTML code.
     */
    public function lostpassword()
    {
        $handlers = [];
        $authenticators = $this->getApplicableAuthenticators(Authenticator::RESET_PASSWORD);
        /** @var Authenticator $authenticator */
        foreach ($authenticators as $authenticator) {
            $handlers[] = $authenticator->getLostPasswordHandler(
                Controller::join_links($this->Link(), 'lostpassword')
            );
        }

        return $this->delegateToMultipleHandlers(
            $handlers,
            _t('SilverStripe\\Security\\Security.LOSTPASSWORDHEADER', 'Lost Password'),
            $this->getTemplatesFor('lostpassword')
        );
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
        /** @var array|Authenticator[] $authenticators */
        $authenticators = $this->getApplicableAuthenticators(Authenticator::CHANGE_PASSWORD);
        $handlers = [];
        foreach ($authenticators as $authenticator) {
            $handlers[] = $authenticator->getChangePasswordHandler($this->Link('changepassword'));
        }

        return $this->delegateToMultipleHandlers(
            $handlers,
            _t('SilverStripe\\Security\\Security.CHANGEPASSWORDHEADER', 'Change your password'),
            $this->getTemplatesFor('changepassword')
        );
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

        return static::singleton()->Link('changepassword') . "?m={$member->ID}&t=$autologinToken";
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
        $templates = SSViewer::get_templates_by_class(static::class, "_{$action}", __CLASS__);

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
     *
     * @deprecated 4.0.0..5.0.0 Please use DefaultAdminService::findOrCreateDefaultAdmin()
     */
    public static function findAnAdministrator()
    {
        Deprecation::notice('5.0.0', 'Please use DefaultAdminService::findOrCreateDefaultAdmin()');

        $service = DefaultAdminService::singleton();
        return $service->findOrCreateDefaultAdmin();
        }

    /**
     * Flush the default admin credentials
     *
     * @deprecated 4.0.0..5.0.0 Please use DefaultAdminService::clearDefaultAdmin()
     */
    public static function clear_default_admin()
    {
        Deprecation::notice('5.0.0', 'Please use DefaultAdminService::clearDefaultAdmin()');

        DefaultAdminService::clearDefaultAdmin();
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
     *
     * @deprecated 4.0.0..5.0.0 Please use DefaultAdminService::setDefaultAdmin($username, $password)
     */
    public static function setDefaultAdmin($username, $password)
    {
        Deprecation::notice('5.0.0', 'Please use DefaultAdminService::setDefaultAdmin($username, $password)');

        DefaultAdminService::setDefaultAdmin($username, $password);
        return true;
    }

    /**
     * Checks if the passed credentials are matching the default-admin.
     * Compares cleartext-password set through Security::setDefaultAdmin().
     *
     * @param string $username
     * @param string $password
     * @return bool
     *
     * @deprecated 4.0.0..5.0.0 Use DefaultAdminService::isDefaultAdminCredentials() instead
     */
    public static function check_default_admin($username, $password)
    {
        Deprecation::notice('5.0.0', 'Please use DefaultAdminService::isDefaultAdminCredentials($username, $password)');

        /** @var DefaultAdminService $service */
        return DefaultAdminService::isDefaultAdminCredentials($username, $password);
    }

    /**
     * Check that the default admin account has been set.
     *
     * @deprecated 4.0.0..5.0.0 Use DefaultAdminService::hasDefaultAdmin() instead
     */
    public static function has_default_admin()
    {
        Deprecation::notice('5.0.0', 'Please use DefaultAdminService::hasDefaultAdmin()');

        return DefaultAdminService::hasDefaultAdmin();
    }

    /**
     * Get default admin username
     *
     * @deprecated 4.0.0..5.0.0 Use DefaultAdminService::getDefaultAdminUsername()
     * @return string
     */
    public static function default_admin_username()
    {
        Deprecation::notice('5.0.0', 'Please use DefaultAdminService::getDefaultAdminUsername()');

        return DefaultAdminService::getDefaultAdminUsername();
    }

    /**
     * Get default admin password
     *
     * @deprecated 4.0.0..5.0.0 Use DefaultAdminService::getDefaultAdminPassword()
     * @return string
     */
    public static function default_admin_password()
    {
        Deprecation::notice('5.0.0', 'Please use DefaultAdminService::getDefaultAdminPassword()');

        return DefaultAdminService::getDefaultAdminPassword();
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
            $algorithm = self::config()->get('password_encryption_algorithm');
        }

        $encryptor = PasswordEncryptor::create_for_algorithm($algorithm);

        // New salts will only need to be generated if the password is hashed for the first time
        $salt = ($salt) ? $salt : $encryptor->salt($password);

        return [
            'password'  => $encryptor->encrypt($password, $salt, $member),
            'salt'      => $salt,
            'algorithm' => $algorithm,
            'encryptor' => $encryptor
        ];
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
     * Resets the database_is_ready cache
     */
    public static function clear_database_is_ready()
    {
        self::$database_is_ready = null;
        self::$force_database_is_ready = null;
    }

    /**
     * For the database_is_ready call to return a certain value - used for testing
     *
     * @param bool $isReady
     */
    public static function force_database_is_ready($isReady)
    {
        self::$force_database_is_ready = $isReady;
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
     * @param bool $flag True or false
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
        return Controller::join_links(Director::baseURL(), self::config()->get('login_url'));
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
        return Controller::join_links(Director::baseURL(), self::config()->get('logout_url'));
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
        return Controller::join_links(Director::baseURL(), self::config()->get('lost_password_url'));
    }

    /**
     * Defines global accessible templates variables.
     *
     * @return array
     */
    public static function get_template_global_variables()
    {
        return [
            "LoginURL"        => "login_url",
            "LogoutURL"       => "logout_url",
            "LostPasswordURL" => "lost_password_url",
            "CurrentMember"   => "getCurrentUser",
            "currentUser"     => "getCurrentUser"
        ];
    }
}
