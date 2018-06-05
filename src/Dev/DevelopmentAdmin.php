<?php

namespace SilverStripe\Dev;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Controller;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\DatabaseAdmin;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Exception;

/**
 * Base class for development tools.
 *
 * Configured in framework/_config/dev.yml, with the config key registeredControllers being
 * used to generate the list of links for /dev.
 *
 * @todo documentation for how to add new unit tests and tasks
 * @todo do we need buildDefaults and generatesecuretoken? if so, register in the list
 * @todo cleanup errors() it's not even an allowed action, so can go
 * @todo cleanup index() html building
 */
class DevelopmentAdmin extends Controller
{

    private static $url_handlers = array(
        '' => 'index',
        'build/defaults' => 'buildDefaults',
        'generatesecuretoken' => 'generatesecuretoken',
        '$Action' => 'runRegisteredController',
    );

    private static $allowed_actions = array(
        'index',
        'buildDefaults',
        'runRegisteredController',
        'generatesecuretoken',
    );

    /**
     * Assume that CLI equals admin permissions
     * If set to false, normal permission model will apply even in CLI mode
     * Applies to all development admin tasks (E.g. TaskRunner, DatabaseAdmin)
     *
     * @config
     * @var bool
     */
    private static $allow_all_cli = true;

    protected function init()
    {
        parent::init();

        // Special case for dev/build: Defer permission checks to DatabaseAdmin->init() (see #4957)
        $requestedDevBuild = (stripos($this->getRequest()->getURL(), 'dev/build') === 0)
            && (stripos($this->getRequest()->getURL(), 'dev/build/defaults') === false);

        // We allow access to this controller regardless of live-status or ADMIN permission only
        // if on CLI.  Access to this controller is always allowed in "dev-mode", or of the user is ADMIN.
        $allowAllCLI = static::config()->get('allow_all_cli');
        $canAccess = (
            $requestedDevBuild
            || Director::isDev()
            || (Director::is_cli() && $allowAllCLI)
            // Its important that we don't run this check if dev/build was requested
            || Permission::check("ADMIN")
        );
        if (!$canAccess) {
            Security::permissionFailure($this);
            return;
        }

        // Backwards compat: Default to "draft" stage, which is important
        // for tasks like dev/build which call DataObject->requireDefaultRecords(),
        // but also for other administrative tasks which have assumptions about the default stage.
        if (class_exists(Versioned::class)) {
            Versioned::set_stage(Versioned::DRAFT);
        }
    }

    public function index()
    {
        // Web mode
        if (!Director::is_cli()) {
            $renderer = DebugView::create();
            echo $renderer->renderHeader();
            echo $renderer->renderInfo("SilverStripe Development Tools", Director::absoluteBaseURL());
            $base = Director::baseURL();

            echo '<div class="options"><ul>';
            $evenOdd = "odd";
            foreach (self::get_links() as $action => $description) {
                echo "<li class=\"$evenOdd\"><a href=\"{$base}dev/$action\"><b>/dev/$action:</b>"
                    . " $description</a></li>\n";
                $evenOdd = ($evenOdd == "odd") ? "even" : "odd";
            }

            echo $renderer->renderFooter();

        // CLI mode
        } else {
            echo "SILVERSTRIPE DEVELOPMENT TOOLS\n--------------------------\n\n";
            echo "You can execute any of the following commands:\n\n";
            foreach (self::get_links() as $action => $description) {
                echo "  sake dev/$action: $description\n";
            }
            echo "\n\n";
        }
    }

    public function runRegisteredController(HTTPRequest $request)
    {
        $controllerClass = null;

        $baseUrlPart = $request->param('Action');
        $reg = Config::inst()->get(__CLASS__, 'registered_controllers');
        if (isset($reg[$baseUrlPart])) {
            $controllerClass = $reg[$baseUrlPart]['controller'];
        }

        if ($controllerClass && class_exists($controllerClass)) {
            return $controllerClass::create();
        }

        $msg = 'Error: no controller registered in ' . __CLASS__ . ' for: ' . $request->param('Action');
        if (Director::is_cli()) {
            // in CLI we cant use httpError because of a bug with stuff being in the output already, see DevAdminControllerTest
            throw new Exception($msg);
        } else {
            $this->httpError(404, $msg);
        }
    }




    /*
     * Internal methods
     */

    /**
     * @return array of url => description
     */
    protected static function get_links()
    {
        $links = [];

        $reg = Config::inst()->get(__CLASS__, 'registered_controllers');
        foreach ($reg as $registeredController) {
            if (isset($registeredController['links'])) {
                foreach ($registeredController['links'] as $url => $desc) {
                    $links[$url] = $desc;
                }
            }
        }
        return $links;
    }

    protected function getRegisteredController($baseUrlPart)
    {
        $reg = Config::inst()->get(__CLASS__, 'registered_controllers');

        if (isset($reg[$baseUrlPart])) {
            $controllerClass = $reg[$baseUrlPart]['controller'];
            return $controllerClass;
        }

        return null;
    }




    /*
     * Unregistered (hidden) actions
     */

    /**
     * Build the default data, calling requireDefaultRecords on all
     * DataObject classes
     * Should match the $url_handlers rule:
     *      'build/defaults' => 'buildDefaults',
     */
    public function buildDefaults()
    {
        $da = DatabaseAdmin::create();

        $renderer = null;
        if (!Director::is_cli()) {
            $renderer = DebugView::create();
            echo $renderer->renderHeader();
            echo $renderer->renderInfo("Defaults Builder", Director::absoluteBaseURL());
            echo "<div class=\"build\">";
        }

        $da->buildDefaults();

        if (!Director::is_cli()) {
            echo "</div>";
            echo $renderer->renderFooter();
        }
    }

    /**
     * Generate a secure token which can be used as a crypto key.
     * Returns the token and suggests PHP configuration to set it.
     */
    public function generatesecuretoken()
    {
        $generator = Injector::inst()->create('SilverStripe\\Security\\RandomGenerator');
        $token = $generator->randomToken('sha1');
        $body = <<<TXT
Generated new token. Please add the following code to your YAML configuration:

Security:
  token: $token

TXT;
        $response = new HTTPResponse($body);
        return $response->addHeader('Content-Type', 'text/plain');
    }

    public function errors()
    {
        $this->redirect("Debug_");
    }
}
