<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DB;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Supports debugging and core error handling.
 *
 * Attaches custom methods to the default error handling hooks
 * in PHP. Currently, two levels of error are supported:
 *
 * - Notice
 * - Warning
 * - Error
 *
 * Uncaught exceptions are currently passed to the debug
 * reporter as standard PHP errors.
 *
 * Errors handled by this class are passed along to {@link SS_Log}.
 * For configuration information, see the {@link SS_Log}
 * class documentation.
 */
class Debug
{

    /**
     * Show the contents of val in a debug-friendly way.
     * Debug::show() is intended to be equivalent to dprintr()
     * Does not work on live mode.
     *
     * @param mixed $val
     * @param bool $showHeader
     * @param HTTPRequest|null $request
     */
    public static function show($val, $showHeader = true, HTTPRequest $request = null)
    {
        // Don't show on live
        if (Director::isLive()) {
            return;
        }

        echo static::create_debug_view($request)
            ->debugVariable($val, static::caller(), $showHeader);
    }

    /**
     * Returns the caller for a specific method
     *
     * @return array
     */
    public static function caller()
    {
        $bt = debug_backtrace();
        $caller = isset($bt[2]) ? $bt[2] : [];
        $caller['line'] = $bt[1]['line'];
        $caller['file'] = $bt[1]['file'];
        if (!isset($caller['class'])) {
            $caller['class'] = '';
        }
        if (!isset($caller['type'])) {
            $caller['type'] = '';
        }
        if (!isset($caller['function'])) {
            $caller['function'] = '';
        }
        return $caller;
    }

    /**
     * Close out the show dumper.
     * Does not work on live mode
     *
     * @param mixed $val
     * @param bool $showHeader
     * @param HTTPRequest $request
     */
    public static function endshow($val, $showHeader = true, HTTPRequest $request = null)
    {
        // Don't show on live
        if (Director::isLive()) {
            return;
        }

        echo static::create_debug_view($request)
            ->debugVariable($val, static::caller(), $showHeader);

        die();
    }

    /**
     * Quick dump of a variable.
     * Note: This method will output in live!
     *
     * @param mixed $val
     * @param HTTPRequest $request Current request to influence output format
     */
    public static function dump($val, HTTPRequest $request = null)
    {
        echo Debug::create_debug_view($request)
            ->renderVariable($val, Debug::caller());
    }

    /**
     * Get debug text for this object
     *
     * @param mixed $val
     * @param HTTPRequest $request
     * @return string
     */
    public static function text($val, HTTPRequest $request = null)
    {
        return static::create_debug_view($request)
            ->debugVariableText($val);
    }

    /**
     * Show a debugging message.
     * Does not work on live mode
     *
     * @param string $message
     * @param bool $showHeader
     * @param HTTPRequest|null $request
     */
    public static function message($message, $showHeader = true, HTTPRequest $request = null)
    {
        // Don't show on live
        if (Director::isLive()) {
            return;
        }

        echo static::create_debug_view($request)
            ->renderMessage($message, static::caller(), $showHeader);
    }

    /**
     * Create an instance of an appropriate DebugView object.
     *
     * @param HTTPRequest $request Optional request to target this view for
     * @return DebugView
     */
    public static function create_debug_view(HTTPRequest $request = null)
    {
        $service = static::supportsHTML($request)
            ? DebugView::class
            : CliDebugView::class;
        return Injector::inst()->get($service);
    }

    /**
     * Determine if the given request supports html output
     *
     * @param HTTPRequest $request
     * @return bool
     */
    protected static function supportsHTML(HTTPRequest $request = null)
    {
        // No HTML output in CLI
        if (Director::is_cli()) {
            return false;
        }

        // Get current request if registered
        if (!$request && Injector::inst()->has(HTTPRequest::class)) {
            $request = Injector::inst()->get(HTTPRequest::class);
        }
        if (!$request) {
            return false;
        }
        // Request must include text/html
        $accepted = $request->getAcceptMimetypes(false);

        // Explicit opt in
        if (in_array('text/html', $accepted ?? [])) {
            return true;
        };

        // Implicit opt-out
        if (in_array('application/json', $accepted ?? [])) {
            return false;
        }

        // Fallback to wildcard comparison
        if (in_array('*/*', $accepted ?? [])) {
            return true;
        }
        return false;
    }

    /**
     * Check if the user has permissions to run URL debug tools,
     * else redirect them to log in.
     */
    public static function require_developer_login()
    {
        // Don't require login for dev mode
        if (Director::isDev()) {
            return;
        }

        if (isset($_SESSION['loggedInAs'])) {
            // We have to do some raw SQL here, because this method is called in Object::defineMethods().
            // This means we have to be careful about what objects we create, as we don't want Object::defineMethods()
            // being called again.
            // This basically calls Permission::checkMember($_SESSION['loggedInAs'], 'ADMIN');

            $memberID = $_SESSION['loggedInAs'];
            $permission = DB::prepared_query(
                '
				SELECT "ID" FROM "Permission"
				INNER JOIN "Group_Members" ON "Permission"."GroupID" = "Group_Members"."GroupID"
				WHERE "Permission"."Code" = ?
				AND "Permission"."Type" = ?
				AND "Group_Members"."MemberID" = ?',
                [
                    'ADMIN', // Code
                    Permission::GRANT_PERMISSION, // Type
                    $memberID // MemberID
                ]
            )->value();

            if ($permission) {
                return;
            }
        }

        // This basically does the same as
        // Security::permissionFailure(null, "You need to login with developer access to make use of debugging tools.")
        // We have to do this because of how early this method is called in execution.
        $_SESSION['SilverStripe\\Security\\Security']['Message']['message']
            = "You need to login with developer access to make use of debugging tools.";
        $_SESSION['SilverStripe\\Security\\Security']['Message']['type'] =  'warning';
        $_SESSION['BackURL'] = $_SERVER['REQUEST_URI'];
        header($_SERVER['SERVER_PROTOCOL'] . " 302 Found");
        header("Location: " . Director::baseURL() . Security::login_url());
        die();
    }
}
