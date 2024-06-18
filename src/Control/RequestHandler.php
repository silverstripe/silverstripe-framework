<?php

namespace SilverStripe\Control;

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use ReflectionClass;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\Debug;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionFailureException;
use SilverStripe\Security\Security;
use SilverStripe\View\ViewableData;

/**
 * This class is the base class of any SilverStripe object that can be used to handle HTTP requests.
 *
 * Any RequestHandler object can be made responsible for handling its own segment of the URL namespace.
 * The {@link Director} begins the URL parsing process; it will parse the beginning of the URL to identify which
 * controller is being used.  It will then call {@link handleRequest()} on that Controller, passing it the parameters
 * that it parsed from the URL, and the {@link HTTPRequest} that contains the remainder of the URL to be parsed.
 *
 * You can use ?debug_request=1 to view information about the different components and rule matches for a specific URL.
 *
 * In SilverStripe, URL parsing is distributed throughout the object graph.  For example, suppose that we have a
 * search form that contains a {@link TreeMultiSelectField} named "Groups".  We want to use ajax to load segments of
 * this tree as they are needed rather than downloading the tree right at the beginning.  We could use this URL to get
 * the tree segment that appears underneath
 *
 * Group #36: "admin/crm/SearchForm/field/Groups/treesegment/36"
 *  - Director will determine that admin/crm is controlled by a new ModelAdmin object, and pass control to that.
 *    Matching Director Rule: "admin/crm" => "ModelAdmin" (defined in mysite/_config.php)
 *  - ModelAdmin will determine that SearchForm is controlled by a Form object returned by $this->SearchForm(), and
 *    pass control to that.
 *    Matching $url_handlers: "$Action" => "$Action" (defined in RequestHandler class)
 *  - Form will determine that field/Groups is controlled by the Groups field, a TreeMultiselectField, and pass
 *    control to that.
 *    Matching $url_handlers: 'field/$FieldName!' => 'handleField' (defined in Form class)
 *  - TreeMultiselectField will determine that treesegment/36 is handled by its treesegment() method.  This method
 *    will return an HTML fragment that is output to the screen.
 *    Matching $url_handlers: "$Action/$ID" => "handleItem" (defined in TreeMultiSelectField class)
 *
 * {@link RequestHandler::handleRequest()} is where this behaviour is implemented.
 */
class RequestHandler extends ViewableData
{

    /**
     * Optional url_segment for this request handler
     *
     * @config
     * @var string|null
     */
    private static $url_segment = null;

    /**
     * @var HTTPRequest $request The request object that the controller was called with.
     * Set in {@link handleRequest()}. Useful to generate the {}
     */
    protected HTTPRequest $request;

    /**
     * The DataModel for this request
     */
    protected $model = null;

    /**
     * This variable records whether RequestHandler::__construct()
     * was called or not. Useful for checking if subclasses have
     * called parent::__construct()
     *
     * @var boolean
     */
    protected $brokenOnConstruct = true;

    /**
     * The default URL handling rules.  This specifies that the next component of the URL corresponds to a method to
     * be called on this RequestHandlingData object.
     *
     * The keys of this array are parse rules.  See {@link HTTPRequest::match()} for a description of the rules
     * available.
     *
     * The values of the array are the method to be called if the rule matches.  If this value starts with a '$', then
     * the named parameter of the parsed URL will be used to determine the method name.
     * @config
     */
    private static $url_handlers = [
        '$Action' => '$Action',
    ];


    /**
     * Define a list of action handling methods that are allowed to be called directly by URLs.
     * The variable should be an array of action names. This sample shows the different values that it can contain:
     *
     * <code>
     * [
     *      // someaction can be accessed by anyone, any time
     *      'someaction',
     *      // So can otheraction
     *      'otheraction' => true,
     *      // restrictedaction can only be people with ADMIN privilege
     *      'restrictedaction' => 'ADMIN',
     *      // complexaction can only be accessed if $this->canComplexAction() returns true
     *      'complexaction' '->canComplexAction',
     * ];
     * </code>
     *
     * Form getters count as URL actions as well, and should be included in allowed_actions.
     * Form actions on the other handed (first argument to {@link FormAction()} should NOT be included,
     * these are handled separately through {@link Form->httpSubmission}. You can control access on form actions
     * either by conditionally removing {@link FormAction} in the form construction,
     * or by defining $allowed_actions in your {@link Form} class.
     * @config
     */
    private static $allowed_actions = null;

    public function __construct()
    {
        $this->brokenOnConstruct = false;

        $this->setRequest(new NullHTTPRequest());

        parent::__construct();
    }

    /**
     * Handles URL requests.
     *
     *  - ViewableData::handleRequest() iterates through each rule in {@link RequestHandler::$url_handlers}.
     *  - If the rule matches, the named method will be called.
     *  - If there is still more URL to be processed, then handleRequest()
     *    is called on the object that that method returns.
     *
     * Once all of the URL has been processed, the final result is returned.
     * However, if the final result is an array, this
     * array is interpreted as being additional template data to customise the
     * 2nd to last result with, rather than an object
     * in its own right.  This is most frequently used when a Controller's
     * action will return an array of data with which to
     * customise the controller.
     *
     * @param HTTPRequest $request The object that is responsible for distributing URL parsing
     * @return HTTPResponse|RequestHandler|string|array
     */
    public function handleRequest(HTTPRequest $request)
    {
        // $handlerClass is used to step up the class hierarchy to implement url_handlers inheritance
        if ($this->brokenOnConstruct) {
            $handlerClass = static::class;
            throw new BadMethodCallException(
                "parent::__construct() needs to be called on {$handlerClass}::__construct()"
            );
        }

        $this->setRequest($request);

        $match = $this->findAction($request);

        // If nothing matches, return this object
        if (!$match) {
            return $this;
        }

        // Start to find what action to call. Start by using what findAction returned
        $action = $match['action'];

        // We used to put "handleAction" as the action on controllers, but (a) this could only be called when
        // you had $Action in your rule, and (b) RequestHandler didn't have one. $Action is better
        if ($action == 'handleAction') {
            $action = '$Action';
        }

        // Actions can reference URL parameters, eg, '$Action/$ID/$OtherID' => '$Action',
        if ($action[0] == '$') {
            $action = str_replace("-", "_", $request->latestParam(substr($action ?? '', 1)) ?? '');
        }

        if (!$action) {
            if (isset($_REQUEST['debug_request'])) {
                Debug::message("Action not set; using default action method name 'index'");
            }
            $action = "index";
        } elseif (!is_string($action)) {
            throw new InvalidArgumentException("Non-string method name: " . var_export($action, true));
        }

        $classMessage = Director::isLive() ? 'on this handler' : 'on class ' . static::class;

        try {
            if (!$this->hasAction($action)) {
                return $this->httpError(404, "Action '$action' isn't available $classMessage.");
            }
            if (!$this->checkAccessAction($action) || in_array(strtolower($action ?? ''), ['run', 'doinit'])) {
                return $this->httpError(403, "Action '$action' isn't allowed $classMessage.");
            }
            $result = $this->handleAction($request, $action);
        } catch (HTTPResponse_Exception $e) {
            return $e->getResponse();
        } catch (PermissionFailureException $e) {
            $result = Security::permissionFailure(null, $e->getMessage());
        }

        if ($result instanceof HTTPResponse && $result->isError()) {
            if (isset($_REQUEST['debug_request'])) {
                Debug::message("Rule resulted in HTTP error; breaking");
            }
            return $result;
        }

        // If we return a RequestHandler, call handleRequest() on that, even if there is no more URL to
        // parse. It might have its own handler. However, we only do this if we haven't just parsed an
        // empty rule ourselves, to prevent infinite loops. Also prevent further handling of controller
        // actions which return themselves to avoid infinite loops.
        $matchedRuleWasEmpty = $request->isEmptyPattern($match['rule']);
        if ($this !== $result && !$matchedRuleWasEmpty && ($result instanceof RequestHandler || $result instanceof HasRequestHandler)) {
            // Expose delegated request handler
            if ($result instanceof HasRequestHandler) {
                $result = $result->getRequestHandler();
            }
            $returnValue = $result->handleRequest($request);

            // Array results can be used to handle
            if (is_array($returnValue)) {
                $returnValue = $this->customise($returnValue);
            }

            return $returnValue;

        // If we return some other data, and all the URL is parsed, then return that
        } elseif ($request->allParsed()) {
            return $result;

        // But if we have more content on the URL and we don't know what to do with it, return an error.
        } else {
            return $this->httpError(404, "I can't handle sub-URLs $classMessage.");
        }
    }

    /**
     * @param HTTPRequest $request
     * @return array
     */
    protected function findAction($request)
    {
        $handlerClass = static::class;

        // We stop after RequestHandler; in other words, at ViewableData
        while ($handlerClass && $handlerClass != ViewableData::class) {
            $urlHandlers = Config::inst()->get($handlerClass, 'url_handlers', Config::UNINHERITED);

            if ($urlHandlers) {
                foreach ($urlHandlers as $rule => $action) {
                    if (isset($_REQUEST['debug_request'])) {
                        $class = static::class;
                        $remaining = $request->remaining();
                        Debug::message("Testing '{$rule}' with '{$remaining}' on {$class}");
                    }

                    if ($request->match($rule, true)) {
                        if (isset($_REQUEST['debug_request'])) {
                            $class = static::class;
                            $latestParams = var_export($request->latestParams(), true);
                            Debug::message(
                                "Rule '{$rule}' matched to action '{$action}' on {$class}. " . "Latest request params: {$latestParams}"
                            );
                        }

                        return [
                            'rule' => $rule,
                            'action' => $action,
                        ];
                    }
                }
            }

            $handlerClass = get_parent_class($handlerClass ?? '');
        }
        return null;
    }

    /**
     * @param string $link
     * @return string
     */
    protected function addBackURLParam($link)
    {
        $backURL = $this->getBackURL();
        if ($backURL) {
            return Controller::join_links($link, '?BackURL=' . urlencode($backURL ?? ''));
        }

        return $link;
    }

    /**
     * Given a request, and an action name, call that action name on this RequestHandler
     *
     * Must not raise HTTPResponse_Exceptions - instead it should return
     *
     * @param $request
     * @param $action
     * @return HTTPResponse
     */
    protected function handleAction($request, $action)
    {
        $classMessage = Director::isLive() ? 'on this handler' : 'on class ' . static::class;

        if (!$this->hasMethod($action)) {
            return new HTTPResponse("Action '$action' isn't available $classMessage.", 404);
        }

        $res = $this->extend('beforeCallActionHandler', $request, $action);
        if ($res) {
            return reset($res);
        }

        $actionRes = $this->$action($request);

        $res = $this->extend('afterCallActionHandler', $request, $action, $actionRes);
        if ($res) {
            return reset($res);
        }

        return $actionRes;
    }

    /**
     * Get a array of allowed actions defined on this controller,
     * any parent classes or extensions.
     *
     * Caution: Since 3.1, allowed_actions definitions only apply
     * to methods on the controller they're defined on,
     * so it is recommended to use the $class argument
     * when invoking this method.
     *
     * @param string $limitToClass
     * @return array|null
     */
    public function allowedActions($limitToClass = null)
    {
        if ($limitToClass) {
            $actions = Config::forClass($limitToClass)->get('allowed_actions', true);
        } else {
            $actions = $this->config()->get('allowed_actions');
        }

        if (is_array($actions)) {
            if (array_key_exists('*', $actions ?? [])) {
                throw new InvalidArgumentException("Invalid allowed_action '*'");
            }

            // convert all keys and values to lowercase to
            // allow for easier comparison, unless it is a permission code
            $actions = array_change_key_case($actions ?? [], CASE_LOWER);

            foreach ($actions as $key => $value) {
                if (is_numeric($key)) {
                    $actions[$key] = strtolower($value ?? '');
                }
            }

            return $actions;
        } else {
            return null;
        }
    }

    /**
     * Checks if this request handler has a specific action,
     * even if the current user cannot access it.
     * Includes class ancestry and extensions in the checks.
     *
     * @param string $action
     * @return bool
     */
    public function hasAction($action)
    {
        if ($action == 'index') {
            return true;
        }

        // Don't allow access to any non-public methods (inspect instance plus all extensions)
        $insts = array_merge([$this], (array) $this->getExtensionInstances());
        foreach ($insts as $inst) {
            if (!method_exists($inst, $action ?? '')) {
                continue;
            }
            $r = new ReflectionClass(get_class($inst));
            $m = $r->getMethod($action);
            if (!$m || !$m->isPublic()) {
                return false;
            }
        }

        $action  = strtolower($action ?? '');
        $actions = $this->allowedActions();

        // Check if the action is defined in the allowed actions of any ancestry class
        // as either a key or value. Note that if the action is numeric, then keys are not
        // searched for actions to prevent actual array keys being recognised as actions.
        if (is_array($actions)) {
            $isKey   = !is_numeric($action) && array_key_exists($action, $actions ?? []);
            $isValue = in_array($action, $actions ?? [], true);
            if ($isKey || $isValue) {
                return true;
            }
        }

        $actionsWithoutExtra = $this->config()->get('allowed_actions', true);
        if (!is_array($actions) || !$actionsWithoutExtra) {
            if (!in_array(strtolower($action ?? ''), ['run', 'doinit']) && method_exists($this, $action ?? '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the class that defines the given action, so that we know where to check allowed_actions.
     *
     * @param string $actionOrigCasing
     * @return string
     */
    protected function definingClassForAction($actionOrigCasing)
    {
        $action = strtolower($actionOrigCasing ?? '');

        $definingClass = null;
        $insts = array_merge([$this], (array) $this->getExtensionInstances());
        foreach ($insts as $inst) {
            if (!method_exists($inst, $action ?? '')) {
                continue;
            }
            $r = new ReflectionClass(get_class($inst));
            $m = $r->getMethod($actionOrigCasing);
            return $m->getDeclaringClass()->getName();
        }
        return null;
    }

    /**
     * Check that the given action is allowed to be called from a URL.
     * It will interrogate {@link RequestHandler::$allowed_actions} to determine this.
     *
     * @param string $action
     * @return bool
     * @throws Exception
     */
    public function checkAccessAction($action)
    {
        $actionOrigCasing = $action;
        $action = strtolower($action ?? '');

        $isAllowed = false;
        $isDefined = false;

        // Get actions for this specific class (without inheritance)
        $definingClass = $this->definingClassForAction($actionOrigCasing);
        $allowedActions = $this->allowedActions($definingClass);

        // check if specific action is set
        if (isset($allowedActions[$action])) {
            $isDefined = true;
            $test = $allowedActions[$action];
            if ($test === true || $test === 1 || $test === '1') {
                // TRUE should always allow access
                $isAllowed = true;
            } elseif (substr($test ?? '', 0, 2) == '->') {
                // Determined by custom method with "->" prefix
                list($method, $arguments) = ClassInfo::parse_class_spec(substr($test ?? '', 2));
                $isAllowed = call_user_func_array([$this, $method], $arguments ?? []);
            } else {
                // Value is a permission code to check the current member against
                $isAllowed = Permission::check($test);
            }
        } elseif (is_array($allowedActions)
            && (($key = array_search($action, $allowedActions ?? [], true)) !== false)
            && is_numeric($key)
        ) {
            // Allow numeric array notation (search for array value as action instead of key)
            $isDefined = true;
            $isAllowed = true;
        } elseif (is_array($allowedActions) && !count($allowedActions ?? [])) {
            // If defined as empty array, deny action
            $isAllowed = false;
        } elseif ($allowedActions === null) {
            // If undefined, allow action based on configuration
            $isAllowed = false;
        }

        // If we don't have a match in allowed_actions,
        // whitelist the 'index' action as well as undefined actions based on configuration.
        if (!$isDefined && ($action == 'index' || empty($action))) {
            $isAllowed = true;
        }

        return $isAllowed;
    }

    /**
     * Throws a HTTP error response encased in a {@link HTTPResponse_Exception}, which is later caught in
     * {@link RequestHandler::handleAction()} and returned to the user.
     *
     * @param int $errorCode
     * @param string $errorMessage Plaintext error message
     * @uses HTTPResponse_Exception
     * @throws HTTPResponse_Exception
     */
    public function httpError($errorCode, $errorMessage = null)
    {
        $request = $this->getRequest();

        // Call a handler method such as onBeforeHTTPError404
        $this->extend("onBeforeHTTPError{$errorCode}", $request, $errorMessage);

        // Call a handler method such as onBeforeHTTPError, passing 404 as the first arg
        $this->extend('onBeforeHTTPError', $errorCode, $request, $errorMessage);

        // Throw a new exception
        throw new HTTPResponse_Exception($errorMessage, $errorCode);
    }

    /**
     * Returns the HTTPRequest object that this controller is using.
     * Returns a placeholder {@link NullHTTPRequest} object unless
     * {@link handleAction()} or {@link handleRequest()} have been called,
     * which adds a reference to an actual {@link HTTPRequest} object.
     *
     * @return HTTPRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Typically the request is set through {@link handleAction()}
     * or {@link handleRequest()}, but in some based we want to set it manually.
     */
    public function setRequest(HTTPRequest $request): static
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Returns a link to this controller.
     * Returns null if no link could be generated.
     *
     * Overload with your own Link rules if they exist.
     *
     * @param string $action Optional action
     * @return ?string
     */
    public function Link($action = null)
    {
        // Check configured url_segment
        $url = $this->config()->get('url_segment');
        if ($url) {
            $link = Controller::join_links($url, $action);

            // Give extensions the chance to modify by reference
            $this->extend('updateLink', $link, $action);
            return $link;
        }

        // no link defined by default
        trigger_error(
            'Request handler ' . static::class . ' does not have a url_segment defined. ' . 'Relying on this link may be an application error',
            E_USER_WARNING
        );
        return null;
    }

    /**
     * Get the absolute URL for this controller, including protocol and host.
     * Returns null if no link could be generated.
     *
     * @param string $action See {@link Link()}
     * @return ?string
     */
    public function AbsoluteLink($action = '')
    {
        $link = $this->Link($action);
        if ($link === null) {
            return null;
        }
        return Director::absoluteURL((string) $link);
    }

    /**
     * Redirect to the given URL.
     */
    public function redirect(string $url, int $code = 302): HTTPResponse
    {
        $url = Director::absoluteURL($url);
        $response = new HTTPResponse();
        return $response->redirect($url, $code);
    }

    /**
     * Safely get the value of the BackURL param, if provided via querystring / posted var
     *
     * @return string
     */
    public function getBackURL()
    {
        $request = $this->getRequest();
        if (!$request) {
            return null;
        }
        $backURL = $request->requestVar('BackURL');
        // Fall back to X-Backurl header
        if (!$backURL && $request->isAjax() && $request->getHeader('X-Backurl')) {
            $backURL = $request->getHeader('X-Backurl');
        }
        if (!$backURL) {
            return null;
        }
        if (Director::is_site_url($backURL)) {
            return $backURL;
        }
        return null;
    }

    /**
     * Returns the referer, if it is safely validated as an internal URL
     * and can be redirected to.
     *
     * @internal called from {@see Form::getValidationErrorResponse}
     * @return string|null
     */
    public function getReturnReferer()
    {
        $referer = $this->getReferer();
        if ($referer && Director::is_site_url($referer)) {
            return $referer;
        }
        return null;
    }

    /**
     * Get referer
     *
     * @return string
     */
    public function getReferer()
    {
        $request = $this->getRequest();
        if (!$request) {
            return null;
        }
        return $request->getHeader('Referer');
    }

    /**
     * Redirect back. Uses either the HTTP-Referer or a manually set request-variable called "BackURL".
     * This variable is needed in scenarios where HTTP-Referer is not sent (e.g when calling a page by
     * location.href in IE). If none of the two variables is available, it will redirect to the base
     * URL (see {@link Director::baseURL()}).
     *
     * @uses redirect()
     */
    public function redirectBack(): HTTPResponse
    {
        // Prefer to redirect to ?BackURL, but fall back to Referer header
        // As a last resort redirect to base url
        $url = $this->getBackURL()
            ?: $this->getReturnReferer()
            ?: Director::baseURL();

        // Only direct to absolute urls
        $url = Director::absoluteURL((string) $url);
        return $this->redirect($url);
    }
}
