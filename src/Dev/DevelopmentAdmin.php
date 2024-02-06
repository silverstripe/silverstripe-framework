<?php

namespace SilverStripe\Dev;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\DatabaseAdmin;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;

/**
 * Base class for development tools.
 *
 * Configured in framework/_config/dev.yml, with the config key registeredControllers being
 * used to generate the list of links for /dev.
 */
class DevelopmentAdmin extends Controller implements PermissionProvider
{

    private static $url_handlers = [
        '' => 'index',
        'build/defaults' => 'buildDefaults',
        'generatesecuretoken' => 'generatesecuretoken',
        '$Action' => 'runRegisteredController',
    ];

    private static $allowed_actions = [
        'index',
        'buildDefaults',
        'runRegisteredController',
        'generatesecuretoken',
    ];

    /**
     * Controllers for dev admin views
     *
     * e.g [
     *     'urlsegment' => [
     *         'controller' => 'SilverStripe\Dev\DevelopmentAdmin',
     *         'links' => [
     *             'urlsegment' => 'description',
     *             ...
     *         ]
     *     ]
     * ]
     *
     * @var array
     */
    private static $registered_controllers = [];

    /**
     * Assume that CLI equals admin permissions
     * If set to false, normal permission model will apply even in CLI mode
     * Applies to all development admin tasks (E.g. TaskRunner, DatabaseAdmin)
     *
     * @config
     * @var bool
     */
    private static $allow_all_cli = true;

    /**
     * Deny all non-cli requests (browser based ones) to dev admin
     *
     * @config
     * @var bool
     */
    private static $deny_non_cli = false;

    protected function init()
    {
        parent::init();

        if (static::config()->get('deny_non_cli') && !Director::is_cli()) {
            return $this->httpError(404);
        }
        
        if (!$this->canViewAll() && empty($this->getLinks())) {
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
        $links = $this->getLinks();
        // Web mode
        if (!Director::is_cli()) {
            $renderer = DebugView::create();
            echo $renderer->renderHeader();
            echo $renderer->renderInfo("SilverStripe Development Tools", Director::absoluteBaseURL());
            $base = Director::baseURL();

            echo '<div class="options"><ul>';
            $evenOdd = "odd";
            foreach ($links as $action => $description) {
                echo "<li class=\"$evenOdd\"><a href=\"{$base}dev/$action\"><b>/dev/$action:</b>"
                    . " $description</a></li>\n";
                $evenOdd = ($evenOdd == "odd") ? "even" : "odd";
            }

            echo $renderer->renderFooter();

        // CLI mode
        } else {
            echo "SILVERSTRIPE DEVELOPMENT TOOLS\n--------------------------\n\n";
            echo "You can execute any of the following commands:\n\n";
            foreach ($links as $action => $description) {
                echo "  sake dev/$action: $description\n";
            }
            echo "\n\n";
        }
    }

    public function runRegisteredController(HTTPRequest $request)
    {
        $controllerClass = null;

        $baseUrlPart = $request->param('Action');
        $reg = Config::inst()->get(static::class, 'registered_controllers');
        if (isset($reg[$baseUrlPart])) {
            $controllerClass = $reg[$baseUrlPart]['controller'];
        }

        if ($controllerClass && class_exists($controllerClass ?? '')) {
            return $controllerClass::create();
        }

        $msg = 'Error: no controller registered in ' . static::class . ' for: ' . $request->param('Action');
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
     * @deprecated 5.2.0 use getLinks() instead to include permission checks
     * @return array of url => description
     */
    protected static function get_links()
    {
        Deprecation::notice('5.2.0', 'Use getLinks() instead to include permission checks');
        $links = [];

        $reg = Config::inst()->get(static::class, 'registered_controllers');
        foreach ($reg as $registeredController) {
            if (isset($registeredController['links'])) {
                foreach ($registeredController['links'] as $url => $desc) {
                    $links[$url] = $desc;
                }
            }
        }
        return $links;
    }

    protected function getLinks(): array
    {
        $canViewAll = $this->canViewAll();
        $links = [];
        $reg = Config::inst()->get(static::class, 'registered_controllers');
        foreach ($reg as $registeredController) {
            if (isset($registeredController['links'])) {
                if (!ClassInfo::exists($registeredController['controller'])) {
                    continue;
                }

                if (!$canViewAll) {
                    // Check access to controller
                    $controllerSingleton = Injector::inst()->get($registeredController['controller']);
                    if (!$controllerSingleton->hasMethod('canInit') || !$controllerSingleton->canInit()) {
                        continue;
                    }
                }

                foreach ($registeredController['links'] as $url => $desc) {
                    $links[$url] = $desc;
                }
            }
        }
        return $links;
    }

    protected function getRegisteredController($baseUrlPart)
    {
        $reg = Config::inst()->get(static::class, 'registered_controllers');

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

    public function providePermissions(): array
    {
        return [
            'ALL_DEV_ADMIN' => [
                'name' => _t(__CLASS__ . '.ALL_DEV_ADMIN_DESCRIPTION', 'Can view and execute all /dev endpoints'),
                'help' => _t(__CLASS__ . '.ALL_DEV_ADMIN_HELP', 'Can view and execute all /dev endpoints'),
                'category' => static::permissionsCategory(),
                'sort' => 50
            ],
        ];
    }

    public static function permissionsCategory(): string
    {
        return  _t(__CLASS__ . '.PERMISSIONS_CATEGORY', 'Dev permissions');
    }

    protected function canViewAll(): bool
    {
        // Special case for dev/build: Defer permission checks to DatabaseAdmin->init() (see #4957)
        $requestedDevBuild = (stripos($this->getRequest()->getURL() ?? '', 'dev/build') === 0)
            && (stripos($this->getRequest()->getURL() ?? '', 'dev/build/defaults') === false);

        // We allow access to this controller regardless of live-status or ADMIN permission only
        // if on CLI.  Access to this controller is always allowed in "dev-mode", or of the user is ADMIN.
        $allowAllCLI = static::config()->get('allow_all_cli');
        return (
            $requestedDevBuild
            || Director::isDev()
            || (Director::is_cli() && $allowAllCLI)
            // Its important that we don't run this check if dev/build was requested
            || Permission::check(['ADMIN', 'ALL_DEV_ADMIN'])
        );
    }
}
