<?php

namespace SilverStripe\Control;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\View\SSViewer;
use SilverStripe\View\TemplateGlobalProvider;

/**
 * Controllers are the cornerstone of all site functionality in SilverStripe. The {@link Director}
 * selects a controller to pass control to, and then calls {@link handleRequest()}. This method will execute
 * the appropriate action - either by calling the action method, or displaying the action's template.
 *
 * See {@link getTemplate()} for information on how the template is chosen.
 */
class Controller extends RequestHandler implements TemplateGlobalProvider
{

    /**
     * An array of arguments extracted from the URL.
     *
     * @var array
     */
    protected $urlParams;

    /**
     * Contains all GET and POST parameters passed to the current {@link HTTPRequest}.
     *
     * @var array
     */
    protected $requestParams;

    /**
     * The URL part matched on the current controller as determined by the "$Action" part of the
     * {@link $url_handlers} definition. Should correlate to a public method on this controller.
     *
     * Used in {@link render()} and {@link getViewer()} to determine action-specific templates.
     *
     * @var string
     */
    protected $action;

    /**
     * Stack of current controllers. Controller::$controller_stack[0] is the current controller.
     *
     * @var array
     */
    protected static $controller_stack = [];

    /**
     * Assign templates for this controller.
     * Map of action => template name
     *
     * @var array
     */
    protected $templates = [];

    /**
     * The response object that the controller returns.
     *
     * Set in {@link handleRequest()}.
     */
    protected HTTPResponse $response;

    /**
     * If true, a trailing slash is added to the end of URLs, e.g. from {@link Controller::join_links()}
     */
    private static bool $add_trailing_slash = false;

    /**
     * Default URL handlers.
     *
     * @var array
     */
    private static $url_handlers = [
        '$Action//$ID/$OtherID' => 'handleAction',
    ];

    /**
     * @var array
     */
    private static $allowed_actions = [
        'handleAction',
        'handleIndex',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->setResponse(HTTPResponse::create());
    }

    /**
     * Initialisation function that is run before any action on the controller is called.
     *
     * @uses BasicAuth::requireLogin()
     */
    protected function init()
    {
        // This is used to test that subordinate controllers are actually calling parent::init() - a common bug
        $this->baseInitCalled = true;
    }

    /**
     * A stand in function to protect the init function from failing to be called as well as providing before and
     * after hooks for the init function itself
     *
     * This should be called on all controllers before handling requests
     */
    public function doInit()
    {
        //extension hook
        $this->extend('onBeforeInit');

        // Safety call
        $this->baseInitCalled = false;
        $this->init();
        if (!$this->baseInitCalled) {
            $class = static::class;
            user_error(
                "init() method on class '{$class}' doesn't call Controller::init()."
                . "Make sure that you have parent::init() included.",
                E_USER_WARNING
            );
        }

        $this->extend('onAfterInit');
    }

    /**
     * {@inheritdoc}
     *
     * Also set the URLParams
     */
    public function setRequest(HTTPRequest $request): static
    {
        parent::setRequest($request);
        $this->setURLParams($this->getRequest()->allParams());
        return $this;
    }

    /**
     * A bootstrap for the handleRequest method
     *
     * @param HTTPRequest $request
     */
    protected function beforeHandleRequest(HTTPRequest $request)
    {
        //Set up the internal dependencies (request, response)
        $this->setRequest($request);
        //Push the current controller to protect against weird session issues
        $this->pushCurrent();
        $this->setResponse(new HTTPResponse());
        //kick off the init functionality
        $this->doInit();
    }

    /**
     * Cleanup for the handleRequest method
     */
    protected function afterHandleRequest()
    {
        //Pop the current controller from the stack
        $this->popCurrent();
    }

    /**
     * Executes this controller, and return an {@link HTTPResponse} object with the result.
     *
     * This method defers to {@link RequestHandler->handleRequest()} to determine which action
     *    should be executed
     *
     * Note: You should rarely need to overload handleRequest() -
     * this kind of change is only really appropriate for things like nested
     * controllers - {@link ModelAsController} and {@link RootURLController}
     * are two examples here.  If you want to make more
     * orthodox functionality, it's better to overload {@link init()} or {@link index()}.
     *
     * Important: If you are going to overload handleRequest,
     * make sure that you start the method with $this->beforeHandleRequest()
     * and end the method with $this->afterHandleRequest()
     */
    public function handleRequest(HTTPRequest $request): HTTPResponse
    {
        if (!$request) {
            throw new \RuntimeException('Controller::handleRequest() not passed a request!');
        }

        //set up the controller for the incoming request
        $this->beforeHandleRequest($request);

        //if the before handler manipulated the response in a way that we shouldn't proceed, then skip our request
        // handling
        if (!$this->getResponse()->isFinished()) {
            //retrieve the response for the request
            $response = parent::handleRequest($request);

            //prepare the response (we can receive an assortment of response types (strings/objects/HTTPResponses)
            $this->prepareResponse($response);
        }

        //after request work
        $this->afterHandleRequest();

        //return the response
        return $this->getResponse();
    }

    /**
     * Prepare the response (we can receive an assortment of response types (strings/objects/HTTPResponses) and
     * changes the controller response object appropriately
     *
     * @param HTTPResponse|Object $response
     */
    protected function prepareResponse($response)
    {
        if (!is_object($response)) {
            $this->getResponse()->setBody($response);
        } elseif ($response instanceof HTTPResponse) {
            if (isset($_REQUEST['debug_request'])) {
                $class = static::class;
                Debug::message(
                    "Request handler returned HTTPResponse object to {$class} controller;"
                    . "returning it without modification."
                );
            }
            $this->setResponse($response);
        } else {
            // Could be Controller, or ViewableData_Customised controller wrapper
            if (ClassInfo::hasMethod($response, 'getViewer')) {
                if (isset($_REQUEST['debug_request'])) {
                    $class = static::class;
                    $responseClass = get_class($response);
                    Debug::message(
                        "Request handler {$responseClass} object to {$class} controller;"
                        . "rendering with template returned by {$responseClass}::getViewer()"
                    );
                }
                $response = $response->getViewer($this->getAction())->process($response);
            }

            $this->getResponse()->setBody($response);
        }

        //deal with content if appropriate
        ContentNegotiator::process($this->getResponse());
    }

    /**
     * Controller's default action handler.  It will call the method named in "$Action", if that method
     * exists. If "$Action" isn't given, it will use "index" as a default.
     *
     * @param HTTPRequest $request
     * @param string $action
     *
     * @return DBHTMLText|HTTPResponse
     */
    protected function handleAction($request, $action)
    {
        foreach ($request->latestParams() as $k => $v) {
            if ($v || !isset($this->urlParams[$k])) {
                $this->urlParams[$k] = $v;
            }
        }

        $this->action = $action;
        $this->requestParams = $request->requestVars();

        if ($this->hasMethod($action)) {
            $result = parent::handleAction($request, $action);

            // If the action returns an array, customise with it before rendering the template.
            if (is_array($result)) {
                return $this->getViewer($action)->process($this->customise($result));
            } else {
                return $result;
            }
        }

        // Fall back to index action with before/after handlers
        $beforeResult = $this->extend('beforeCallActionHandler', $request, $action);
        if ($beforeResult) {
            return reset($beforeResult);
        }

        $result = $this->getViewer($action)->process($this);

        $afterResult = $this->extend('afterCallActionHandler', $request, $action, $result);
        if ($afterResult) {
            return reset($afterResult);
        }

        return $result;
    }

    /**
     * @param array $urlParams
     * @return $this
     */
    public function setURLParams($urlParams)
    {
        $this->urlParams = $urlParams;
        return $this;
    }

    /**
     * Returns the parameters extracted from the URL by the {@link Director}.
     *
     * @return array
     */
    public function getURLParams()
    {
        return $this->urlParams;
    }

    /**
     * Returns the HTTPResponse object that this controller is building up. Can be used to set the
     * status code and headers.
     */
    public function getResponse(): HTTPResponse
    {
        return $this->response;
    }

    /**
     * Sets the HTTPResponse object that this controller is building up.
     *
     * @param HTTPResponse $response
     *
     * @return $this
     */
    public function setResponse(HTTPResponse $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @var bool
     */
    protected $baseInitCalled = false;

    /**
     * This is the default action handler used if a method doesn't exist. It will process the
     * controller object with the template returned by {@link getViewer()}.
     *
     * @param string $action
     * @return DBHTMLText
     */
    public function defaultAction($action)
    {
        return $this->getViewer($action)->process($this);
    }

    /**
     * Returns the action that is being executed on this controller.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Return the viewer identified being the default handler for this Controller/Action combination.
     *
     * @param string $action
     *
     * @return SSViewer
     */
    public function getViewer($action)
    {
        // Hard-coded templates
        if (isset($this->templates[$action]) && $this->templates[$action]) {
            $templates = $this->templates[$action];
        } elseif (isset($this->templates['index']) && $this->templates['index']) {
            $templates = $this->templates['index'];
        } elseif ($this->template) {
            $templates = $this->template;
        } else {
            // Build templates based on class hierarchy
            $actionTemplates = [];
            $classTemplates = [];
            $parentClass = static::class;
            while ($parentClass !== parent::class) {
                // _action templates have higher priority
                if ($action && $action != 'index') {
                    $actionTemplates[] = strtok($parentClass ?? '', '_') . '_' . $action;
                }
                // class templates have lower priority
                $classTemplates[] = strtok($parentClass ?? '', '_');
                $parentClass = get_parent_class($parentClass ?? '');
            }

            // Add controller templates for inheritance chain
            $templates = array_unique(array_merge($actionTemplates, $classTemplates));
        }

        return SSViewer::create($templates);
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    public function hasAction($action)
    {
        return parent::hasAction($action) || $this->hasActionTemplate($action);
    }

    /**
     * Removes all the "action" part of the current URL and returns the result. If no action parameter
     * is present, returns the full URL.
     *
     * @param string $fullURL
     * @param null|string $action
     *
     * @return string
     */
    public function removeAction($fullURL, $action = null)
    {
        if (!$action) {
            $action = $this->getAction();    //default to current action
        }
        $returnURL = $fullURL;

        if (($pos = strpos($fullURL ?? '', $action ?? '')) !== false) {
            $returnURL = substr($fullURL ?? '', 0, $pos);
        }

        return $returnURL;
    }

    /**
     * Return the class that defines the given action, so that we know where to check allowed_actions.
     * Overrides RequestHandler to also look at defined templates.
     *
     * @param string $action
     *
     * @return string
     */
    protected function definingClassForAction($action)
    {
        $definingClass = parent::definingClassForAction($action);
        if ($definingClass) {
            return $definingClass;
        }

        $class = static::class;
        while ($class != 'SilverStripe\\Control\\RequestHandler') {
            $templateName = strtok($class ?? '', '_') . '_' . $action;
            if (SSViewer::hasTemplate($templateName)) {
                return $class;
            }

            $class = get_parent_class($class ?? '');
        }

        return null;
    }

    /**
     * Returns TRUE if this controller has a template that is specifically designed to handle a
     * specific action.
     *
     * @param string $action
     *
     * @return bool
     */
    public function hasActionTemplate($action)
    {
        if (isset($this->templates[$action])) {
            return true;
        }

        $parentClass = static::class;
        $templates   = [];

        while ($parentClass != __CLASS__) {
            $templates[] = strtok($parentClass ?? '', '_') . '_' . $action;
            $parentClass = get_parent_class($parentClass ?? '');
        }

        return SSViewer::hasTemplate($templates);
    }

    /**
     * Render the current controller with the templates determined by {@link getViewer()}.
     *
     * @param array $params
     *
     * @return string
     */
    public function render($params = null)
    {
        $template = $this->getViewer($this->getAction());

        // if the object is already customised (e.g. through Controller->run()), use it
        $obj = $this->getCustomisedObj() ?: $this;

        if ($params) {
            $obj = $this->customise($params);
        }

        return $template->process($obj);
    }

    /**
     * Returns the current controller.
     *
     * @return Controller
     */
    public static function curr()
    {
        if (Controller::$controller_stack) {
            return Controller::$controller_stack[0];
        }
        user_error("No current controller available", E_USER_WARNING);
        return null;
    }

    /**
     * Tests whether we have a currently active controller or not. True if there is at least 1
     * controller in the stack.
     *
     * @return bool
     */
    public static function has_curr()
    {
        return Controller::$controller_stack ? true : false;
    }

    /**
     * Returns true if the member is allowed to do the given action. Defaults to the currently logged
     * in user.
     *
     * @param string $perm
     * @param null|member $member
     *
     * @return bool
     */
    public function can($perm, $member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        if (is_array($perm)) {
            $perm = array_map([$this, 'can'], $perm ?? [], array_fill(0, count($perm ?? []), $member));
            return min($perm);
        }
        if ($this->hasMethod($methodName = 'can' . $perm)) {
            return $this->$methodName($member);
        } else {
            return true;
        }
    }

    /**
     * Pushes this controller onto the stack of current controllers. This means that any redirection,
     * session setting, or other things that rely on Controller::curr() will now write to this
     * controller object.
     *
     * Note: Ensure this controller is assigned a request with a valid session before pushing
     * it to the stack.
     */
    public function pushCurrent()
    {
        // Ensure this controller has a valid session
        $this->getRequest()->getSession();
        array_unshift(Controller::$controller_stack, $this);
    }

    /**
     * Pop this controller off the top of the stack.
     */
    public function popCurrent()
    {
        if ($this === Controller::$controller_stack[0]) {
            array_shift(Controller::$controller_stack);
        } else {
            $class = static::class;
            user_error(
                "popCurrent called on {$class} controller, but it wasn't at the top of the stack",
                E_USER_WARNING
            );
        }
    }

    /**
     * Redirect to the given URL.
     */
    public function redirect(string $url, int $code = 302): HTTPResponse
    {
        $response = $this->getResponse();
        if ($response->getHeader('Location') && $response->getHeader('Location') != $url) {
            user_error("Already directed to " . $this->getResponse()->getHeader('Location')
                . "; now trying to direct to $url", E_USER_WARNING);
            return $response;
        }
        $response = parent::redirect($url, $code);
        $this->setResponse($response);
        return $response;
    }

    /**
     * Tests whether a redirection has been requested. If redirect() has been called, it will return
     * the URL redirected to. Otherwise, it will return null.
     *
     * @return null|string
     */
    public function redirectedTo()
    {
        return $this->getResponse() && $this->getResponse()->getHeader('Location');
    }

    /**
     * Joins two or more link segments together, putting a slash between them if necessary. Use this
     * for building the results of {@link Link()} methods. If either of the links have query strings,
     * then they will be combined and put at the end of the resulting url.
     *
     * Caution: All parameters are expected to be URI-encoded already.
     *
     * @param string|array $arg One or more link segments, or list of link segments as an array
     * @return string
     */
    public static function join_links($arg = null)
    {
        if (func_num_args() === 1 && is_array($arg)) {
            $args = $arg;
        } else {
            $args = func_get_args();
        }
        $result = "";
        $queryargs = [];
        $fragmentIdentifier = null;

        foreach ($args as $arg) {
            // Find fragment identifier - keep the last one
            if (strpos($arg ?? '', '#') !== false) {
                list($arg, $fragmentIdentifier) = explode('#', $arg ?? '', 2);
            }
            // Find querystrings
            if (strpos($arg ?? '', '?') !== false) {
                list($arg, $suffix) = explode('?', $arg ?? '', 2);
                parse_str($suffix ?? '', $localargs);
                $queryargs = array_merge($queryargs, $localargs);
            }
            // Join paths together
            if ((is_string($arg) && $arg) || is_numeric($arg)) {
                $arg = (string) $arg;
                if ($result && substr($result ?? '', -1) != '/' && $arg[0] != '/') {
                    $result .= "/$arg";
                } else {
                    $result .= (substr($result ?? '', -1) == '/' && $arg[0] == '/') ? ltrim($arg, '/') : $arg;
                }
            }
        }

        $result = static::normaliseTrailingSlash($result);

        if ($queryargs) {
            $result .= '?' . http_build_query($queryargs ?? []);
        }

        if ($fragmentIdentifier) {
            $result .= "#$fragmentIdentifier";
        }

        return $result;
    }

    /**
     * Normalises a URL according to the configuration for add_trailing_slash
     */
    public static function normaliseTrailingSlash(string $url): string
    {
        $querystring = null;
        $fragmentIdentifier = null;

        // Find fragment identifier
        if (strpos($url, '#') !== false) {
            list($url, $fragmentIdentifier) = explode('#', $url, 2);
        }
        // Find querystrings
        if (strpos($url, '?') !== false) {
            list($url, $querystring) = explode('?', $url, 2);
        }

        // Normlise trailing slash
        $shouldHaveTrailingSlash = Controller::config()->uninherited('add_trailing_slash');
        if ($shouldHaveTrailingSlash
            && !str_ends_with($url, '/')
            && !preg_match('/^(.*)\.([^\/]*)$/', Director::makeRelative($url))
        ) {
            // Add trailing slash if enabled and url does not end with a file extension
            $url .= '/';
        } elseif (!$shouldHaveTrailingSlash) {
            // Remove trailing slash if it shouldn't be there
            $url = rtrim($url, '/');
        }

        // Ensure relative root URLs are represented with a slash
        if ($url === '') {
            $url = '/';
        }

        // Add back fragment identifier and querystrings
        if ($querystring) {
            $url .= '?' . $querystring;
        }
        if ($fragmentIdentifier) {
            $url .= "#$fragmentIdentifier";
        }

        return $url;
    }

    /**
     * @return array
     */
    public static function get_template_global_variables()
    {
        return [
            'CurrentPage' => 'curr',
        ];
    }
}
