<?php

namespace SilverStripe\Security;

use LogicException;
use Page;
use ReflectionClass;
use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use SilverStripe\View\TemplateGlobalProvider;

/**
 * Implements a basic security model
 */
class Security extends Controller implements TemplateGlobalProvider
{

    private static $allowed_actions = [
        'basicauthlogin',
        'changepassword',
        'index',
        'login',
        'logout',
        'lostpassword',
        'ping',
    ];

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
     * through the {@link LoginAttempt} object.
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
        return array_filter($this->authenticators ?? []);
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
        $authenticators = $this->getAuthenticators();

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
     * @param string $authenticator The configured identifier of the authenticator
     * @return bool Returns TRUE if the authenticator is registered, FALSE
     *              otherwise.
     */
    public function hasAuthenticator($authenticator)
    {
        $authenticators = $this->getAuthenticators();

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
     */
    public static function permissionFailure($controller = null, $messageSet = null): HTTPResponse
    {
        Security::set_ignore_disallowed_actions(true);

        // Parse raw message / escape type
        $parseMessage = function ($message) {
            if ($message instanceof DBField) {
                return [
                    $message->getValue(),
                    $message->config()->get('escape_type') === 'raw'
                        ? ValidationResult::CAST_TEXT
                        : ValidationResult::CAST_HTML,
                ];
            }

            // Default to escaped value
            return [
                $message,
                ValidationResult::CAST_TEXT,
            ];
        };

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
                $messageSet = [
                    'default' => _t(
                        __CLASS__ . '.NOTEPAGESECURED',
                        "That page is secured. Enter your credentials below and we will send "
                            . "you right along."
                    ),
                    'alreadyLoggedIn' => _t(
                        __CLASS__ . '.ALREADYLOGGEDIN',
                        "You don't have access to this page.  If you have another account that "
                            . "can access that page, you can log in again below."
                    )
                ];
            }
        }

        if (!is_array($messageSet)) {
            $messageSet = ['default' => $messageSet];
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

            list($messageText, $messageCast) = $parseMessage($message);
            static::singleton()->setSessionMessage($messageText, ValidationResult::TYPE_WARNING, $messageCast);
            $request = new HTTPRequest('GET', '/');
            if ($controller) {
                $request->setSession($controller->getRequest()->getSession());
            }
            $loginResponse = static::singleton()->login($request);
            if ($loginResponse instanceof HTTPResponse) {
                return $loginResponse;
            }

            $response->setBody((string)$loginResponse);

            $controller->extend('permissionDenied', $member);

            return $response;
        }
        $message = $messageSet['default'];

        $request = $controller->getRequest();
        if ($request->hasSession()) {
            list($messageText, $messageCast) = $parseMessage($message);
            static::singleton()->setSessionMessage($messageText, ValidationResult::TYPE_WARNING, $messageCast);

            $request->getSession()->set("BackURL", $_SERVER['REQUEST_URI']);
        }

        // Audit logging hook
        $controller->extend('permissionDenied', $member);

        return $controller->redirect(Controller::join_links(
            Security::config()->uninherited('login_url'),
            "?BackURL=" . urlencode($_SERVER['REQUEST_URI'] ?? '')
        ));
    }

    /**
     * The intended uses of this function is to temporarily change the current user for things such as
     * canView() checks or unit tests.  It is stateless and will not persist between requests.  Importantly
     * it also will not call any logic that may be present in the current IdentityStore logIn() or logout() methods
     *
     * If you are unit testing and calling FunctionalTest::get() or FunctionalTest::post() and you need to change
     * the current user, you should instead use SapphireTest::logInAs() / logOut() which itself will call
     * Injector::inst()->get(IdentityStore::class)->logIn($member) / logout()
     *
     * @param null|Member $currentUser
     */
    public static function setCurrentUser($currentUser = null)
    {
        Security::$currentUser = $currentUser;
    }

    /**
     * @return null|Member
     */
    public static function getCurrentUser()
    {
        return Security::$currentUser;
    }

    /**
     * Get a link to a security action
     *
     * @param string $action Name of the action
     * @return string Returns the link to the given action
     */
    public function Link($action = null)
    {
        $link = Controller::join_links(Director::baseURL(), "Security", $action);
        $this->extend('updateLink', $link, $action);
        return $link;
    }

    /**
     * This action is available as a keep alive, so user
     * sessions don't timeout. A common use is in the admin.
     */
    public function ping()
    {
        HTTPCacheControlMiddleware::singleton()->disableCache();
        Requirements::clear();
        return 1;
    }

    /**
     * Perform pre-login checking and prepare a response if available prior to login
     *
     * @return HTTPResponse Substitute response object if the login process should be circumvented.
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
        // back. The login message check is necessary to prevent infinite loops where BackURL links to
        // an action that triggers Security::permissionFailure.
        // This step is necessary in cases such as automatic redirection where a user is authenticated
        // upon landing on an SSL secured site and is automatically logged in, or some other case
        // where the user has permissions to continue but is not given the option.
        if (!$this->getSessionMessage()
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
        $pageClass = $this->config()->get('page_class');
        if (!$pageClass || !class_exists($pageClass ?? '')) {
            return $this;
        }

        // Create new instance of page holder
        /** @var Page $holderPage */
        $holderPage = Injector::inst()->create($pageClass);
        $holderPage->Title = $title;
        $holderPage->URLSegment = 'Security';
        // Disable ID-based caching  of the log-in page by making it a random number
        $holderPage->ID = -1 * random_int(1, 10000000);

        $controller = ModelAsController::controller_for($holderPage);
        $controller->setRequest($this->getRequest());
        $controller->doInit();

        return $controller;
    }

    /**
     * Combine the given forms into a formset with a tabbed interface
     *
     * @param array|Form[] $forms
     * @return string
     */
    protected function generateTabbedFormSet($forms)
    {
        if (count($forms ?? []) === 1) {
            return $forms;
        }

        $viewData = new ArrayData([
            'Forms' => new ArrayList($forms),
        ]);

        return $viewData->renderWith(
            $this->getTemplatesFor('MultiAuthenticatorTabbedForms')
        );
    }

    /**
     * Get the HTML Content for the $Content area during login
     *
     * @param string $messageType Type of message, if available, passed back to caller (by reference)
     * @return string Message in HTML format
     */
    protected function getSessionMessage(&$messageType = null)
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
    public function setSessionMessage(
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
    public static function clearSessionMessage()
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
        } elseif ($this->getRequest()) {
            $request = $this->getRequest();
        } else {
            throw new HTTPResponse_Exception("No request available", 500);
        }

        // Check pre-login process
        if ($response = $this->preLogin()) {
            return $response;
        }
        $authName = null;

        $handlers = $this->getServiceAuthenticatorsFromRequest($service, $request);

        $link = $this->Link('login');
        array_walk(
            $handlers,
            function (Authenticator &$auth, $name) use ($link) {
                $auth = $auth->getLoginHandler(Controller::join_links($link, $name));
            }
        );

        return $this->delegateToMultipleHandlers(
            $handlers,
            _t(__CLASS__ . '.LOGIN', 'Log in'),
            $this->getTemplatesFor('login'),
            [$this, 'aggregateTabbedForms']
        );
    }

    /**
     * Log the currently logged in user out
     *
     * Logging out without ID-parameter in the URL, will log the user out of all applicable Authenticators.
     *
     * Adding an ID will only log the user out of that Authentication method.
     *
     * @param null|HTTPRequest $request
     * @param int $service
     * @return HTTPResponse|string
     */
    public function logout($request = null, $service = Authenticator::LOGOUT)
    {
        $authName = null;

        if (!$request) {
            $request = $this->getRequest();
        }

        $handlers = $this->getServiceAuthenticatorsFromRequest($service, $request);

        $link = $this->Link('logout');
        array_walk(
            $handlers,
            function (Authenticator &$auth, $name) use ($link) {
                $auth = $auth->getLogoutHandler(Controller::join_links($link, $name));
            }
        );

        return $this->delegateToMultipleHandlers(
            $handlers,
            _t(__CLASS__ . '.LOGOUT', 'Log out'),
            $this->getTemplatesFor('logout'),
            [$this, 'aggregateAuthenticatorResponses']
        );
    }

    /**
     * Get authenticators for the given service, optionally filtered by the ID parameter
     * of the current request
     *
     * @param int $service
     * @param HTTPRequest $request
     * @return array|Authenticator[]
     * @throws HTTPResponse_Exception
     */
    protected function getServiceAuthenticatorsFromRequest($service, HTTPRequest $request)
    {
        $authName = null;

        if ($request->param('ID')) {
            $authName = $request->param('ID');
        }

        // Delegate to a single named handler - e.g. Security/login/<authname>/
        if ($authName && $this->hasAuthenticator($authName)) {
            if ($request) {
                $request->shift();
            }

            $authenticator = $this->getAuthenticator($authName);

            if (!$authenticator->supportedServices() & $service) {
                // Try to be helpful and show the service constant name, e.g. Authenticator::LOGIN
                $constants = array_flip((new ReflectionClass(Authenticator::class))->getConstants() ?? []);

                $message = 'Invalid Authenticator "' . $authName . '" for ';
                if (array_key_exists($service, $constants ?? [])) {
                    $message .= 'service: Authenticator::' . $constants[$service];
                } else {
                    $message .= 'unknown authenticator service';
                }

                throw new HTTPResponse_Exception($message, 400);
            }

            $handlers = [$authName => $authenticator];
        } else {
            // Delegate to all of them, building a tabbed view - e.g. Security/login/
            $handlers = $this->getApplicableAuthenticators($service);
        }

        return $handlers;
    }

    /**
     * Aggregate tabbed forms from each handler to fragments ready to be rendered.
     *
     * @param array $results
     * @return array
     */
    protected function aggregateTabbedForms(array $results)
    {
        $forms = [];
        foreach ($results as $authName => $singleResult) {
            // The result *must* be an array with a Form key
            if (!is_array($singleResult) || !isset($singleResult['Form'])) {
                user_error('Authenticator "' . $authName . '" doesn\'t support tabbed forms', E_USER_WARNING);
                continue;
            }

            $forms[] = $singleResult['Form'];
        }

        if (!$forms) {
            throw new \LogicException('No authenticators found compatible with tabbed forms');
        }

        return [
            'Forms' => ArrayList::create($forms),
            'Form' => $this->generateTabbedFormSet($forms)
        ];
    }

    /**
     * We have three possible scenarios.
     * We get back Content (e.g. Password Reset)
     * We get back a Form (no token set for logout)
     * We get back a HTTPResponse, telling us to redirect.
     * Return the first one, which is the default response, as that covers all required scenarios
     *
     * @param array $results
     * @return array|HTTPResponse
     */
    protected function aggregateAuthenticatorResponses($results)
    {
        $error = false;
        $result = null;
        foreach ($results as $authName => $singleResult) {
            if (($singleResult instanceof HTTPResponse) ||
                (is_array($singleResult) &&
                    (isset($singleResult['Content']) || isset($singleResult['Form'])))
            ) {
                // return the first successful response
                return $singleResult;
            } else {
                // Not a valid response
                $error = true;
            }
        }

        if ($error) {
            throw new \LogicException('No authenticators found compatible with logout operation');
        }

        return $result;
    }

    /**
     * Delegate to a number of handlers and aggregate the results. This is used, for example, to
     * build the log-in page where there are multiple authenticators active.
     *
     * If a single handler is passed, delegateToHandler() will be called instead
     *
     * @param array|RequestHandler[] $handlers
     * @param string $title The title of the form
     * @param array $templates
     * @param callable $aggregator
     * @return array|HTTPResponse|RequestHandler|DBHTMLText|string
     */
    protected function delegateToMultipleHandlers(array $handlers, $title, array $templates, callable $aggregator)
    {

        // Simpler case for a single authenticator
        if (count($handlers ?? []) === 1) {
            return $this->delegateToHandler(array_values($handlers)[0], $title, $templates);
        }

        // Process each of the handlers
        $results = array_map(
            function (RequestHandler $handler) {
                return $handler->handleRequest($this->getRequest());
            },
            $handlers ?? []
        );

        $response = call_user_func_array($aggregator, [$results]);
        // The return could be a HTTPResponse, in which we don't want to call the render
        if (is_array($response)) {
            return $this->renderWrappedController($title, $response, $templates);
        }

        return $response;
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

        // Return the customised controller - may be used to render a Form (e.g. login form)
        if (is_array($result)) {
            $result = $this->renderWrappedController($title, $result, $templates);
        }

        return $result;
    }

    /**
     * Render the given fragments into a security page controller with the given title.
     *
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
        $message = $this->getSessionMessage($messageType);

        // We've displayed the message in the form output, so reset it for the next run.
        static::clearSessionMessage();

        // Ensure title is present - in case getResponseController() didn't return a page controller
        $fragments = array_merge(['Title' => $title], $fragments);
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
        foreach ($authenticators as $authenticator) {
            $handlers[] = $authenticator->getLostPasswordHandler(
                Controller::join_links($this->Link(), 'lostpassword')
            );
        }

        return $this->delegateToMultipleHandlers(
            $handlers,
            _t('SilverStripe\\Security\\Security.LOSTPASSWORDHEADER', 'Lost Password'),
            $this->getTemplatesFor('lostpassword'),
            [$this, 'aggregateAuthenticatorResponses']
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
        $authenticators = $this->getApplicableAuthenticators(Authenticator::CHANGE_PASSWORD);
        $handlers = [];
        foreach ($authenticators as $authenticator) {
            $handlers[] = $authenticator->getChangePasswordHandler($this->Link('changepassword'));
        }

        return $this->delegateToMultipleHandlers(
            $handlers,
            _t('SilverStripe\\Security\\Security.CHANGEPASSWORDHEADER', 'Change your password'),
            $this->getTemplatesFor('changepassword'),
            [$this, 'aggregateAuthenticatorResponses']
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
        $autologinToken = urldecode($autologinToken ?? '');

        return static::singleton()->Link('changepassword') . "?m={$member->ID}&t=$autologinToken";
    }

    /**
     * Determine the list of templates to use for rendering the given action.
     *
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
                $this->config()->get("template_main"),
                "BlankPage"
            ]
        );
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
     * @throws PasswordEncryptor_NotFoundException
     * @see encrypt_passwords()
     */
    public static function encrypt_password($password, $salt = null, $algorithm = null, $member = null)
    {
        // Fall back to the default encryption algorithm
        if (!$algorithm) {
            $algorithm = static::config()->get('password_encryption_algorithm');
        }

        $encryptor = PasswordEncryptor::create_for_algorithm($algorithm);

        // New salts will only need to be generated if the password is hashed for the first time
        $salt = ($salt) ? $salt : $encryptor->salt($password);

        return [
            'password'  => $encryptor->encrypt($password, $salt, $member),
            'salt' => $salt,
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
        if (Security::$force_database_is_ready !== null) {
            return Security::$force_database_is_ready;
        }

        if (Security::$database_is_ready) {
            return Security::$database_is_ready;
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
            $missingFields = array_diff_key($objFields ?? [], $dbFields);

            if ($missingFields) {
                return false;
            }
        }
        Security::$database_is_ready = true;

        return true;
    }

    /**
     * Resets the database_is_ready cache
     */
    public static function clear_database_is_ready()
    {
        Security::$database_is_ready = null;
        Security::$force_database_is_ready = null;
    }

    /**
     * For the database_is_ready call to return a certain value - used for testing
     *
     * @param bool $isReady
     */
    public static function force_database_is_ready($isReady)
    {
        Security::$force_database_is_ready = $isReady;
    }

    /**
     * @config
     * @var string Set the default login dest
     * This is the URL that users will be redirected to after they log in,
     * if they haven't logged in en route to access a secured page.
     * By default, this is set to the homepage.
     */
    private static $default_login_dest = "";

    /**
     * @config
     * @var string Set the default reset password destination
     * This is the URL that users will be redirected to after they change their password,
     * By default, it's redirecting to {@link $login}.
     */
    private static $default_reset_password_dest;

    protected static $ignore_disallowed_actions = false;

    /**
     * Set to true to ignore access to disallowed actions, rather than returning permission failure
     * Note that this is just a flag that other code needs to check with Security::ignore_disallowed_actions()
     * @param bool $flag True or false
     */
    public static function set_ignore_disallowed_actions($flag)
    {
        Security::$ignore_disallowed_actions = $flag;
    }

    public static function ignore_disallowed_actions()
    {
        return Security::$ignore_disallowed_actions;
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
        return Controller::join_links(Director::baseURL(), static::config()->get('login_url'));
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
        $logoutUrl = Controller::join_links(Director::baseURL(), static::config()->get('logout_url'));
        return SecurityToken::inst()->addToUrl($logoutUrl);
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
        return Controller::join_links(Director::baseURL(), static::config()->get('lost_password_url'));
    }

    /**
     * Defines global accessible templates variables.
     *
     * @return array
     */
    public static function get_template_global_variables()
    {
        return [
            "LoginURL" => "login_url",
            "LogoutURL" => "logout_url",
            "LostPasswordURL" => "lost_password_url",
            "CurrentMember"   => "getCurrentUser",
            "currentUser"     => "getCurrentUser"
        ];
    }
}
